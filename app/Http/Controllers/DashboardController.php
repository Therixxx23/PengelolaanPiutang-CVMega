<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\Tagihan;
use App\Services\PiutangAgingService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke(PiutangAgingService $agingService)
    {
        $user = Auth::user();

        if ($user->isAdministrasi()) {
            $tagihanBelumLunas = Tagihan::aktif()->where('status', 'belum_lunas')->count();
            $tagihanJatuhTempoMingguIni = Tagihan::aktif()->where('status', 'belum_lunas')
                ->whereBetween('tanggal_jatuh_tempo', [now()->startOfWeek(), now()->endOfWeek()])
                ->count();
            $totalPiutang = $agingService->getTotalPiutang();
            $totalPelangganAktif = Pelanggan::whereHas('tagihan', fn ($q) => $q->aktif()->where('status', 'belum_lunas'))->count();
            $agingSummary = $agingService->getBucketSummary();
            $tagihanTerbaru = Tagihan::aktif()->with('pelanggan')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
            $jatuhTempoMingguIni = Tagihan::aktif()->with('pelanggan')
                ->where('status', 'belum_lunas')
                ->whereBetween('tanggal_jatuh_tempo', [now()->startOfWeek(), now()->endOfWeek()])
                ->orderBy('tanggal_jatuh_tempo')
                ->limit(5)
                ->get();

            return view('dashboard', compact(
                'tagihanBelumLunas', 'tagihanJatuhTempoMingguIni',
                'totalPiutang', 'totalPelangganAktif', 'agingSummary',
                'tagihanTerbaru', 'jatuhTempoMingguIni'
            ));
        }

        $summary = $agingService->getBucketSummary();
        $totalPiutang = $agingService->getTotalPiutang();
        $totalTertagih = $agingService->getTotalTertagih();
        $buckets = $agingService->getBucketedTagihan();

        return view('dashboard', compact(
            'summary', 'totalPiutang', 'totalTertagih', 'buckets'
        ));
    }
}
