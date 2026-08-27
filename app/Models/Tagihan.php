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
        'no_invoice',
        'no_sj',
        'tanggal_tagihan',
        'tanggal_jatuh_tempo',
        'total_tagihan',
        'status',
        'approval_status',
        'approval_note',
        'kode_sales',
        'nama_sales',
        'sumber_dana',
        'status_penagihan',
        'catatan_penagihan_terakhir',
        'assigned_sales_id',
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

    public function items(): HasMany
    {
        return $this->hasMany(TagihanItem::class, 'id_tagihan');
    }

    public function assignedSales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_sales_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getButuhApprovalAttribute(): bool
    {
        $totalBaru = (string) $this->total_tagihan;

        $melebihiThreshold = bccomp(
            $totalBaru,
            (string) config('piutang.approval_threshold'),
            2
        ) >= 0;

        $totalPiutangAktif = (string) $this->pelanggan->tagihan()
            ->where('status', 'belum_lunas')
            ->where('id_tagihan', '!=', $this->id_tagihan)
            ->sum('total_tagihan');

        $batasKredit = (string) $this->pelanggan->batas_kredit;
        $totalBaruTotal = bcadd($totalPiutangAktif, $totalBaru, 2);

        $melebihiBatasKredit = bccomp($batasKredit, '0', 2) > 0
            && bccomp($totalBaruTotal, $batasKredit, 2) > 0;

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
