<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class TagihanFilterService
{
    public static function applyBucketFilter(Builder $query, string $bucket): Builder
    {
        $today = now()->startOfDay();

        return match ($bucket) {
            'lancar' => $query->whereDate('tanggal_jatuh_tempo', '>=', $today),
            '0-30' => $query
                ->whereDate('tanggal_jatuh_tempo', '>=', $today->copy()->subDays(30))
                ->whereDate('tanggal_jatuh_tempo', '<=', $today->copy()->subDays(1)),
            '31-60' => $query
                ->whereDate('tanggal_jatuh_tempo', '>=', $today->copy()->subDays(60))
                ->whereDate('tanggal_jatuh_tempo', '<=', $today->copy()->subDays(31)),
            '>60' => $query->whereDate('tanggal_jatuh_tempo', '<=', $today->copy()->subDays(61)),
            default => $query,
        };
    }

    public static function applyPeriodeFilter(Builder $query, string $periode): Builder
    {
        $start = match ($periode) {
            'minggu-ini' => now()->startOfWeek(),
            'bulan-ini' => now()->startOfMonth(),
            'tahun-ini' => now()->startOfYear(),
            default => null,
        };

        return $start ? $query->whereDate('tanggal_tagihan', '>=', $start) : $query;
    }
}
