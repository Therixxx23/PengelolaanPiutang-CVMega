<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\Pembayaran;
use Illuminate\Http\Request;

class RiwayatPembayaranController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = Pembayaran::with(['tagihan.pelanggan'])->orderBy('tanggal_bayar', 'desc');

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

        $pembayaran = $query->paginate(20);
        $pelanggan = Pelanggan::orderBy('nama_pelanggan')->get();

        return view('laporan.riwayat-pembayaran', compact('pembayaran', 'pelanggan'));
    }
}
