<?php

namespace App\Http\Controllers;

use App\Models\Tagihan;
use App\Services\PiutangAgingService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke(PiutangAgingService $agingService)
    {
        $user = Auth::user();

        if ($user->isAdministrasi()) {
            $tagihanBelumLunas = Tagihan::where('status', 'belum_lunas')->count();
            $tagihanJatuhTempoMingguIni = Tagihan::where('status', 'belum_lunas')
                ->whereBetween('tanggal_jatuh_tempo', [now()->startOfWeek(), now()->endOfWeek()])
                ->count();
            $totalPiutang = $agingService->getTotalPiutang();
            $tagihanTerbaru = Tagihan::with('pelanggan')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            return view('dashboard', compact(
                'tagihanBelumLunas', 'tagihanJatuhTempoMingguIni',
                'totalPiutang', 'tagihanTerbaru'
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
