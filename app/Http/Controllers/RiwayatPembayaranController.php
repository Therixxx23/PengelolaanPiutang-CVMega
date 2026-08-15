<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\Pembayaran;
use Illuminate\Http\Request;

class RiwayatPembayaranController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->authorize('viewLaporan');

        $query = Pembayaran::with(['tagihan.pelanggan', 'tagihan.pembayaran'])
            ->orderBy('tanggal_bayar', 'desc');

        if ($request->filled('id_pelanggan')) {
            $query->whereHas('tagihan', function ($q) use ($request) {
                $q->where('id_pelanggan', $request->id_pelanggan);
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
        $pembayaran->appends(request()->only(['id_pelanggan', 'dari', 'sampai']));

        $pelanggan = Pelanggan::orderBy('nama_pelanggan')->get();

        return view('laporan.riwayat-pembayaran', compact('pembayaran', 'pelanggan', 'summary'));
    }
}
