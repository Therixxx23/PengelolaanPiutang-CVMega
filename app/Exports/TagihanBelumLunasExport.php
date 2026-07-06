<?php

namespace App\Exports;

use App\Models\Tagihan;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AbstractWriter;

class TagihanBelumLunasExport
{
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

        $tagihan = Tagihan::with('pelanggan', 'pembayaran')
            ->where('status', 'belum_lunas')
            ->get()
            ->sortByDesc(function ($t) {
                $order = ['>60' => 3, '31-60' => 2, '0-30' => 1, 'lancar' => 0];

                return $order[$t->aging_bucket] ?? -1;
            });

        foreach ($tagihan as $t) {
            $totalDibayar = $t->pembayaran->sum('jumlah_bayar');
            $sisa = $t->total_tagihan - $totalDibayar;

            $bucketLabels = [
                'lancar' => 'Lancar',
                '0-30' => '0-30 Hari',
                '31-60' => '31-60 Hari',
                '>60' => '>60 Hari',
            ];

            $writer->addRow(Row::fromValues([
                $t->no_invoice,
                $t->pelanggan->nama_pelanggan,
                $t->pelanggan->wilayah ?: '',
                $t->tanggal_tagihan->format('Y-m-d'),
                $t->tanggal_jatuh_tempo->format('Y-m-d'),
                (float) $t->total_tagihan,
                (float) $totalDibayar,
                (float) max(0, $sisa),
                $t->days_overdue,
                $bucketLabels[$t->aging_bucket] ?? $t->aging_bucket,
                $t->is_overdue ? 'Jatuh Tempo' : 'Belum Lunas',
            ]));
        }
    }
}
