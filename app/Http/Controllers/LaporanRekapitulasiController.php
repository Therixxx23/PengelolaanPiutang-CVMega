<?php

namespace App\Http\Controllers;

use App\Services\PiutangAgingService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class LaporanRekapitulasiController extends Controller
{
    public function __invoke(PiutangAgingService $agingService)
    {
        $allRingkasan = $agingService->getAllPelangganSummary();
        $totalPiutang = $agingService->getTotalPiutang();
        $totalTertagih = $agingService->getTotalTertagih();
        $totalPelanggan = $allRingkasan->count();

        $chartLabels = $allRingkasan->pluck('pelanggan.nama_pelanggan')->map(fn ($n) => explode(' ', $n)[0])->toArray();
        $chartData = $allRingkasan->pluck('sisa_piutang')->toArray();

        $perPage = 10;
        $currentPage = Paginator::resolveCurrentPage();
        $ringkasan = new LengthAwarePaginator(
            $allRingkasan->forPage($currentPage, $perPage)->values(),
            $totalPelanggan,
            $perPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath()],
        );

        return view('laporan.rekapitulasi', compact(
            'ringkasan', 'totalPiutang', 'totalTertagih', 'totalPelanggan',
            'chartLabels', 'chartData'
        ));
    }
}
