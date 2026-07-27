<?php

namespace App\Http\Controllers;

use App\Exports\TagihanBelumLunasExport;
use App\Models\Tagihan;
use App\Services\PiutangAgingService;
use App\Services\TagihanFilterService;
use OpenSpout\Writer\XLSX\Writer;

class LaporanUmurPiutangController extends Controller
{
    private const VALID_BUCKETS = ['semua', 'lancar', '0-30', '31-60', '>60'];

    private const VALID_PERIODES = ['semua', 'minggu-ini', 'bulan-ini', 'tahun-ini'];

    private const BUCKET_LABELS = [
        'lancar' => 'Lancar',
        '0-30' => '0–30 Hari',
        '31-60' => '31–60 Hari',
        '>60' => '>60 Hari',
    ];

    private const PERIODE_LABELS = [
        'minggu-ini' => 'minggu ini',
        'bulan-ini' => 'bulan ini',
        'tahun-ini' => 'tahun ini',
    ];

    public function __invoke(PiutangAgingService $agingService)
    {
        $bucket = request('bucket', 'semua');
        $periode = request('periode', 'semua');

        if (! in_array($bucket, self::VALID_BUCKETS)) {
            $bucket = 'semua';
        }
        if (! in_array($periode, self::VALID_PERIODES)) {
            $periode = 'semua';
        }

        // Query berbasis periode untuk summary bucketing
        $query = Tagihan::with('pelanggan')->where('status', 'belum_lunas');
        TagihanFilterService::applyPeriodeFilter($query, $periode);
        $allTagihan = $query->get();

        $buckets = [
            'lancar' => collect(),
            '0-30' => collect(),
            '31-60' => collect(),
            '>60' => collect(),
        ];
        foreach ($allTagihan as $t) {
            $buckets[$agingService->getBucketForTagihan($t)]->push($t);
        }

        $summary = [];
        foreach ($buckets as $key => $items) {
            $summary[$key] = [
                'count' => $items->count(),
                'total' => $items->sum('total_tagihan'),
            ];
        }

        // Paginated untuk bucket spesifik
        $paginatedTagihan = null;

        if ($bucket !== 'semua') {
            $viewQuery = Tagihan::with('pelanggan')->where('status', 'belum_lunas');
            TagihanFilterService::applyPeriodeFilter($viewQuery, $periode);
            TagihanFilterService::applyBucketFilter($viewQuery, $bucket);

            $paginatedTagihan = $viewQuery->paginate(10);
            $paginatedTagihan->appends(['bucket' => $bucket, 'periode' => $periode]);
        }

        // Deskripsi export
        $totalFiltered = $allTagihan->count();
        $exportCount = $bucket !== 'semua' ? ($summary[$bucket]['count'] ?? 0) : $totalFiltered;
        $exportDescription = $this->buildExportDescription($bucket, $periode, $exportCount, $totalFiltered);

        $bucketKeys = $bucket === 'semua' ? array_keys($buckets) : [$bucket];

        return view('laporan.umur-piutang', compact(
            'buckets', 'summary', 'bucket', 'bucketKeys', 'paginatedTagihan',
            'periode', 'exportDescription',
        ));
    }

    public function exportExcel()
    {
        $bucket = request('bucket', 'semua');
        $periode = request('periode', 'semua');

        if (! in_array($bucket, self::VALID_BUCKETS)) {
            $bucket = 'semua';
        }
        if (! in_array($periode, self::VALID_PERIODES)) {
            $periode = 'semua';
        }

        $filename = $this->buildExportFilename($bucket, $periode);

        $path = tempnam(sys_get_temp_dir(), 'piutang').'.xlsx';

        $writer = new Writer;
        $writer->openToFile($path);

        $export = new TagihanBelumLunasExport($bucket, $periode);
        $export->write($writer);

        $writer->close();

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    private function buildExportDescription(string $bucket, string $periode, int $exportCount, int $totalFiltered): string
    {
        if ($totalFiltered === 0) {
            return 'Tidak ada tagihan untuk filter ini';
        }

        $bucketPart = self::BUCKET_LABELS[$bucket] ?? null;
        $periodePart = self::PERIODE_LABELS[$periode] ?? null;

        if (! $bucketPart && ! $periodePart) {
            return 'Mengexport semua '.$totalFiltered.' tagihan belum lunas';
        }

        $desc = 'Mengexport';

        if ($exportCount > 0 && $bucketPart) {
            $desc .= ' '.$exportCount.' tagihan '.$bucketPart;
        } else {
            $desc .= ' tagihan';
        }

        if ($periodePart) {
            $desc .= ' '.$periodePart;
        }

        return $desc;
    }

    private function buildExportFilename(string $bucket, string $periode): string
    {
        $parts = [];

        if ($bucket !== 'semua') {
            $parts[] = str_replace('/', '-', $bucket);
        }
        if ($periode !== 'semua') {
            $parts[] = ['minggu-ini' => 'MingguIni', 'bulan-ini' => 'BulanIni', 'tahun-ini' => 'TahunIni'][$periode] ?? $periode;
        }

        $parts[] = now()->format('Y-m-d');

        return 'Rekap-Piutang-'.implode('-', $parts).'.xlsx';
    }
}
