<?php

namespace App\Http\Controllers;

use App\Services\PiutangAgingService;

class LaporanUmurPiutangController extends Controller
{
    public function __invoke(PiutangAgingService $agingService)
    {
        $buckets = $agingService->getBucketedTagihan();
        $summary = $agingService->getBucketSummary();

        return view('laporan.umur-piutang', compact('buckets', 'summary'));
    }
}
