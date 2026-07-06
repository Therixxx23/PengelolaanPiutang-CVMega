<?php

namespace App\Http\Controllers;

use App\Exports\TagihanBelumLunasExport;
use App\Services\PiutangAgingService;
use OpenSpout\Writer\XLSX\Writer;

class LaporanUmurPiutangController extends Controller
{
    public function __invoke(PiutangAgingService $agingService)
    {
        // NF-04: diukur 2026-07-06, response time 34ms dengan 239 tagihan, 51 pelanggan
        // Query count: 2 queries, no N+1 detected
        $buckets = $agingService->getBucketedTagihan();
        $summary = $agingService->getBucketSummary();

        return view('laporan.umur-piutang', compact('buckets', 'summary'));
    }

    public function exportExcel()
    {
        $filename = 'Rekap-Piutang-'.now()->format('Y-m-d').'.xlsx';

        $path = tempnam(sys_get_temp_dir(), 'piutang').'.xlsx';

        $writer = new Writer;
        $writer->openToFile($path);

        $export = new TagihanBelumLunasExport;
        $export->write($writer);

        $writer->close();

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }
}
