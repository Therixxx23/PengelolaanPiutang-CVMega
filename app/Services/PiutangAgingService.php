<?php

namespace App\Services;

use App\Models\Tagihan;
use Illuminate\Support\Collection;

class PiutangAgingService
{
    public function getBucketedTagihan(): array
    {
        $tagihan = Tagihan::with('pelanggan')
            ->where('status', 'belum_lunas')
            ->get();

        $buckets = [
            'lancar' => collect(),
            '0-30' => collect(),
            '31-60' => collect(),
            '>60' => collect(),
        ];

        foreach ($tagihan as $t) {
            $bucket = $this->getBucketForTagihan($t);
            $buckets[$bucket]->push($t);
        }

        return $buckets;
    }

    public function getBucketForTagihan(Tagihan $tagihan): string
    {
        $days = $this->getDaysOverdue($tagihan);

        if ($days <= 0) {
            return 'lancar';
        }

        if ($days <= 30) {
            return '0-30';
        }

        if ($days <= 60) {
            return '31-60';
        }

        return '>60';
    }

    public function getDaysOverdue(Tagihan $tagihan): int
    {
        if ($tagihan->status === 'lunas' || $tagihan->tanggal_jatuh_tempo === null) {
            return 0;
        }

        $days = now()->startOfDay()->diffInDays(
            $tagihan->tanggal_jatuh_tempo->startOfDay(),
            false
        );

        return max(0, $days);
    }

    public function getBucketSummary(): array
    {
        $buckets = $this->getBucketedTagihan();
        $summary = [];

        foreach ($buckets as $key => $items) {
            $summary[$key] = [
                'count' => $items->count(),
                'total' => $items->sum('total_tagihan'),
            ];
        }

        return $summary;
    }

    public function getTotalPiutang(): float
    {
        return Tagihan::where('status', 'belum_lunas')->sum('total_tagihan');
    }

    public function getTotalTertagih(): float
    {
        return Tagihan::where('status', 'lunas')->sum('total_tagihan');
    }

    public function getAllPelangganSummary(): Collection
    {
        return Tagihan::with('pelanggan', 'pembayaran')
            ->get()
            ->groupBy('id_pelanggan')
            ->map(function (Collection $tagihan, $idPelanggan) {
                $pelanggan = $tagihan->first()->pelanggan;
                $totalTagihan = $tagihan->sum('total_tagihan');
                $totalTerbayar = $tagihan->flatMap->pembayaran->sum('jumlah_bayar');
                $sisa = $totalTagihan - $totalTerbayar;

                $aktif = $tagihan->where('status', 'belum_lunas');
                $bucketTerburuk = null;

                foreach ($aktif as $t) {
                    $b = $this->getBucketForTagihan($t);
                    $order = ['lancar' => 0, '0-30' => 1, '31-60' => 2, '>60' => 3];
                    if ($bucketTerburuk === null || ($order[$b] ?? 0) > ($order[$bucketTerburuk] ?? 0)) {
                        $bucketTerburuk = $b;
                    }
                }

                return (object) [
                    'pelanggan' => $pelanggan,
                    'total_tagihan' => $totalTagihan,
                    'total_terbayar' => $totalTerbayar,
                    'sisa_piutang' => $sisa,
                    'bucket_terburuk' => $bucketTerburuk,
                    'jumlah_tagihan' => $tagihan->count(),
                    'jumlah_tagihan_aktif' => $aktif->count(),
                ];
            })
            ->sortByDesc('sisa_piutang')
            ->values();
    }
}
