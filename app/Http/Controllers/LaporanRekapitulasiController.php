<?php

namespace App\Http\Controllers;

use App\Services\PiutangAgingService;

class LaporanRekapitulasiController extends Controller
{
    public function __invoke(PiutangAgingService $agingService)
    {
        $ringkasan = $agingService->getAllPelangganSummary();
        $totalPiutang = $agingService->getTotalPiutang();
        $totalTertagih = $agingService->getTotalTertagih();

        $chartLabels = $ringkasan->pluck('pelanggan.nama_pelanggan')->map(fn ($n) => explode(' ', $n)[0])->toArray();
        $chartData = $ringkasan->pluck('sisa_piutang')->toArray();

        return view('laporan.rekapitulasi', compact(
            'ringkasan', 'totalPiutang', 'totalTertagih',
            'chartLabels', 'chartData'
        ));
    }
}
