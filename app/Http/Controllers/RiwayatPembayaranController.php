<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use Illuminate\Http\Request;

class RiwayatPembayaranController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->authorize('viewLaporan');

        $query = Pembayaran::with(['tagihan.pelanggan', 'tagihan.pembayaran'])
            ->orderBy('tanggal_bayar', 'desc');

        $sumber_dana = $request->get('sumber_dana', 'semua');

        if ($request->filled('id_pelanggan')) {
            $query->whereHas('tagihan', function ($q) use ($request) {
                $q->where('id_pelanggan', $request->id_pelanggan);
            });
        }

        if ($sumber_dana !== 'semua') {
            $query->whereHas('tagihan', function ($q) use ($sumber_dana) {
                $q->where('sumber_dana', $sumber_dana);
            });
        }

        if ($request->filled('dari')) {
            $query->whereDate('tanggal_bayar', '>=', $request->dari);
        }

        if ($request->filled('sampai')) {
            $query->whereDate('tanggal_bayar', '<=', $request->sampai);
        }

        $summaryQuery = clone $query;
        $summary = [
            'total' => $summaryQuery->sum('jumlah_bayar'),
            'rata_rata' => $summaryQuery->avg('jumlah_bayar'),
            'jumlah' => $summaryQuery->count(),
        ];

        $pembayaran = $query->paginate(15);
        $pembayaran->appends(request()->only(['id_pelanggan', 'dari', 'sampai', 'sumber_dana']));

        $pelanggan = Pelanggan::orderBy('nama_pelanggan')->get();
        $daftarSumber = Tagihan::whereNotNull('sumber_dana')->distinct()->orderBy('sumber_dana')->pluck('sumber_dana');

        return view('laporan.riwayat-pembayaran', compact(
            'pembayaran', 'pelanggan', 'summary', 'sumber_dana', 'daftarSumber'
        ));
    }
}
