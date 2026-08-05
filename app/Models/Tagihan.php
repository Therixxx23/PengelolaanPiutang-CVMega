<?php

namespace App\Models;

use Database\Factories\TagihanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tagihan extends Model
{
    /** @use HasFactory<TagihanFactory> */
    use HasFactory;

    protected $table = 'tagihan';

    protected $primaryKey = 'id_tagihan';

    protected $fillable = [
        'id_pelanggan',
        'tanggal_tagihan',
        'tanggal_jatuh_tempo',
        'total_tagihan',
        'approval_status',
        'approval_note',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_tagihan' => 'date',
            'tanggal_jatuh_tempo' => 'date',
            'total_tagihan' => 'decimal:2',
            'status' => 'string',
            'approval_status' => 'string',
            'approved_at' => 'datetime',
        ];
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'id_tagihan', 'id_tagihan');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getButuhApprovalAttribute(): bool
    {
        $totalBaru = (float) $this->total_tagihan;

        $melebihiThreshold = $totalBaru >= (float) config('piutang.approval_threshold');

        $totalPiutangAktif = (float) $this->pelanggan->tagihan()
            ->where('status', 'belum_lunas')
            ->where('id_tagihan', '!=', $this->id_tagihan)
            ->sum('total_tagihan');

        $batasKredit = (float) $this->pelanggan->batas_kredit;
        $melebihiBatasKredit = $batasKredit > 0
            && ($totalPiutangAktif + $totalBaru) > $batasKredit;

        return $melebihiThreshold || $melebihiBatasKredit;
    }

    public function getBisaDibayarAttribute(): bool
    {
        return $this->status === 'belum_lunas'
            && $this->approval_status === 'aktif';
    }

    public function scopeMenungguApproval(Builder $query): void
    {
        $query->where('approval_status', 'menunggu_persetujuan');
    }

    public function scopeAktif(Builder $query): void
    {
        $query->where('approval_status', 'aktif');
    }

    public function scopeBisaDibayar(Builder $query): void
    {
        $query->where('status', 'belum_lunas')
            ->where('approval_status', 'aktif');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'belum_lunas'
            && $this->tanggal_jatuh_tempo !== null
            && $this->tanggal_jatuh_tempo->isPast();
    }

    public function getDaysOverdueAttribute(): int
    {
        if ($this->status === 'lunas' || $this->tanggal_jatuh_tempo === null) {
            return 0;
        }

        $due = $this->tanggal_jatuh_tempo->startOfDay();

        if ($due->gte(now()->startOfDay())) {
            return 0;
        }

        return (int) $due->diffInDays(now()->startOfDay());
    }

    public function getAgingBucketAttribute(): string
    {
        if ($this->status === 'lunas') {
            return 'lunas';
        }

        $days = $this->days_overdue;

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

    public function scopeOverdue(Builder $query): void
    {
        $query->where('status', 'belum_lunas')
            ->where('tanggal_jatuh_tempo', '<', now()->startOfDay());
    }

    public function scopeAgingBucket(Builder $query, string $bucket): void
    {
        $today = now()->startOfDay();

        $query->where('status', 'belum_lunas');

        match ($bucket) {
            'lancar' => $query->whereDate('tanggal_jatuh_tempo', '>=', $today),
            '0-30' => $query->whereDate('tanggal_jatuh_tempo', '>=', $today->copy()->subDays(30))
                ->whereDate('tanggal_jatuh_tempo', '<', $today),
            '31-60' => $query->whereDate('tanggal_jatuh_tempo', '>=', $today->copy()->subDays(60))
                ->whereDate('tanggal_jatuh_tempo', '<', $today->copy()->subDays(30)),
            '>60' => $query->whereDate('tanggal_jatuh_tempo', '<', $today->copy()->subDays(60)),
            default => throw new \InvalidArgumentException("Bucket '$bucket' tidak dikenal"),
        };
    }
}
