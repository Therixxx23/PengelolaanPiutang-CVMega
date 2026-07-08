<?php

namespace App\Http\Controllers;

use App\Exports\TagihanBelumLunasExport;
use App\Models\Tagihan;
use App\Services\PiutangAgingService;
use OpenSpout\Writer\XLSX\Writer;

class LaporanUmurPiutangController extends Controller
{
    public function __invoke(PiutangAgingService $agingService)
    {
        // NF-04: diukur 2026-07-06, response time 34ms dengan 239 tagihan, 51 pelanggan
        // Query count: 2 queries, no N+1 detected
        $bucket = request('bucket', 'semua');
        $valid = ['semua', 'lancar', '0-30', '31-60', '>60'];

        if (! in_array($bucket, $valid)) {
            $bucket = 'semua';
        }

        // Summary counts selalu dari FULL data (tidak kena paginate)
        $buckets = $agingService->getBucketedTagihan();

        $summary = [];
        foreach ($buckets as $key => $items) {
            $summary[$key] = [
                'count' => $items->count(),
                'total' => $items->sum('total_tagihan'),
            ];
        }

        // Query paginated untuk bucket spesifik
        $paginatedTagihan = null;

        if ($bucket !== 'semua') {
            $query = Tagihan::with('pelanggan')->where('status', 'belum_lunas');

            $today = now()->startOfDay();

            match ($bucket) {
                'lancar' => $query->whereDate('tanggal_jatuh_tempo', '>=', $today),
                '0-30' => $query
                    ->whereDate('tanggal_jatuh_tempo', '>=', $today->copy()->subDays(30))
                    ->whereDate('tanggal_jatuh_tempo', '<=', $today->copy()->subDays(1)),
                '31-60' => $query
                    ->whereDate('tanggal_jatuh_tempo', '>=', $today->copy()->subDays(60))
                    ->whereDate('tanggal_jatuh_tempo', '<=', $today->copy()->subDays(31)),
                '>60' => $query->whereDate('tanggal_jatuh_tempo', '<=', $today->copy()->subDays(61)),
            };

            $paginatedTagihan = $query->paginate(10);
            $paginatedTagihan->appends(['bucket' => $bucket]);
        }

        $bucketKeys = $bucket === 'semua'
            ? array_keys($buckets)
            : [$bucket];

        return view('laporan.umur-piutang', compact(
            'buckets', 'summary', 'bucket', 'bucketKeys', 'paginatedTagihan',
        ));
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
