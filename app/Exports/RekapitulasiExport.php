<?php

namespace App\Exports;

use App\Models\Pelanggan;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AbstractWriter;

class RekapitulasiExport
{
    private string $search;

    private string $wilayah;

    public function __construct(string $search = '', string $wilayah = 'semua')
    {
        $this->search = $search;
        $this->wilayah = $wilayah;
    }

    public function write(AbstractWriter $writer): void
    {
        $headerStyle = new Style(fontBold: true);

        $headers = [
            'No', 'Nama Pelanggan', 'Wilayah',
            'Total Tagihan', 'Total Terbayar', 'Sisa Piutang',
            'Status Terburuk', 'Tagihan Aktif',
        ];

        $writer->addRow(Row::fromValuesWithStyle($headers, $headerStyle));

        $pelanggan = Pelanggan::with(['tagihan.pembayaran'])
            ->when($this->search, fn ($q) => $q->where('nama_pelanggan', 'like', "%{$this->search}%"))
            ->when($this->wilayah !== 'semua', fn ($q) => $q->where('wilayah', $this->wilayah))
            ->orderBy('nama_pelanggan')
            ->get();

        $ringkasan = $pelanggan->map(function ($p) {
            $totalTagihan = $p->tagihan->sum('total_tagihan');
            $totalTerbayar = $p->tagihan->flatMap->pembayaran->sum('jumlah_bayar');
            $sisa = $totalTagihan - $totalTerbayar;

            $aktif = $p->tagihan->where('status', 'belum_lunas');
            $bucketTerburuk = null;

            foreach ($aktif as $t) {
                $b = $t->aging_bucket;
                $order = ['lancar' => 0, '0-30' => 1, '31-60' => 2, '>60' => 3];
                if ($bucketTerburuk === null || ($order[$b] ?? 0) > ($order[$bucketTerburuk] ?? 0)) {
                    $bucketTerburuk = $b;
                }
            }

            return [
                'pelanggan' => $p,
                'total_tagihan' => $totalTagihan,
                'total_terbayar' => $totalTerbayar,
                'sisa_piutang' => $sisa,
                'bucket_terburuk' => $bucketTerburuk,
                'jumlah_tagihan_aktif' => $aktif->count(),
            ];
        })->sortByDesc('sisa_piutang');

        $bucketLabels = [
            'lancar' => 'Lancar',
            '0-30' => '0-30 Hari',
            '31-60' => '31-60 Hari',
            '>60' => '>60 Hari',
        ];

        $no = 0;
        foreach ($ringkasan as $r) {
            $no++;
            $writer->addRow(Row::fromValues([
                $no,
                $r['pelanggan']->nama_pelanggan,
                $r['pelanggan']->wilayah ?: '',
                (float) $r['total_tagihan'],
                (float) $r['total_terbayar'],
                (float) max(0, $r['sisa_piutang']),
                $r['bucket_terburuk'] ? ($bucketLabels[$r['bucket_terburuk']] ?? $r['bucket_terburuk']) : 'Lunas',
                $r['jumlah_tagihan_aktif'],
            ]));
        }

        $totalStyle = new Style(fontBold: true);
        $writer->addRow(Row::fromValuesWithStyle([
            '',
            'TOTAL',
            '',
            (float) $ringkasan->sum('total_tagihan'),
            (float) $ringkasan->sum('total_terbayar'),
            (float) $ringkasan->sum('sisa_piutang'),
            '',
            $ringkasan->sum('jumlah_tagihan_aktif'),
        ], $totalStyle));
    }
}
