<?php

namespace App\Http\Controllers;

use App\Exports\TagihanBelumLunasExport;
use App\Services\PiutangAgingService;
use OpenSpout\Writer\XLSX\Writer;

class LaporanUmurPiutangController extends Controller
{
    public function __invoke(PiutangAgingService $agingService)
    {
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
