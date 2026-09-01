<?php

namespace App\Exports;

use App\Models\Tagihan;
use App\Services\TagihanFilterService;
use App\Support\SpreadsheetSafeString;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AbstractWriter;

class TagihanBelumLunasExport
{
    private string $bucket;

    private string $periode;

    public function __construct(string $bucket = 'semua', string $periode = 'semua')
    {
        $this->bucket = $bucket;
        $this->periode = $periode;
    }

    public function write(AbstractWriter $writer): void
    {
        $headerStyle = new Style(fontBold: true);

        $headers = [
            'No. Invoice', 'Nama Pelanggan', 'Wilayah',
            'Tanggal Tagihan', 'Jatuh Tempo',
            'Total Tagihan', 'Total Terbayar', 'Sisa Piutang',
            'Umur Piutang (hari)', 'Kategori Umur', 'Status',
        ];

        $writer->addRow(Row::fromValuesWithStyle($headers, $headerStyle));

        $query = Tagihan::aktif()->with('pelanggan', 'pembayaran')
            ->where('status', 'belum_lunas');

        TagihanFilterService::applyPeriodeFilter($query, $this->periode);
        TagihanFilterService::applyBucketFilter($query, $this->bucket);

        $tagihan = $query->get()
            ->sortByDesc(function ($t) {
                $order = ['>60' => 3, '31-60' => 2, '0-30' => 1, 'lancar' => 0];

                return $order[$t->aging_bucket] ?? -1;
            });

        $bucketLabels = [
            'lancar' => 'Lancar',
            '0-30' => '0-30 Hari',
            '31-60' => '31-60 Hari',
            '>60' => '>60 Hari',
        ];

        foreach ($tagihan as $t) {
            $totalDibayar = $t->pembayaran->sum('jumlah_bayar');
            $sisa = $t->total_tagihan - $totalDibayar;

            $writer->addRow(Row::fromValues([
                SpreadsheetSafeString::make($t->no_invoice),
                SpreadsheetSafeString::make($t->pelanggan->nama_pelanggan),
                SpreadsheetSafeString::make($t->pelanggan->wilayah ?: ''),
                $t->tanggal_tagihan->format('Y-m-d'),
                $t->tanggal_jatuh_tempo->format('Y-m-d'),
                (float) $t->total_tagihan,
                (float) $totalDibayar,
                (float) max(0, $sisa),
                $t->days_overdue,
                $bucketLabels[$t->aging_bucket] ?? SpreadsheetSafeString::make($t->aging_bucket),
                $t->is_overdue ? 'Jatuh Tempo' : 'Belum Lunas',
            ]));
        }
    }
}
