<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatatanPenagihan extends Model
{
    protected $table = 'catatan_penagihan';

    protected $fillable = [
        'id_tagihan',
        'user_id',
        'status_penagihan',
        'catatan',
    ];

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class, 'id_tagihan');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusPenagihanLabelAttribute(): string
    {
        return match ($this->status_penagihan) {
            'belum_ditagih' => 'Belum Ditagih',
            'sedang_ditagih' => 'Sedang Ditagih',
            'janji_bayar' => 'Janji Bayar',
            'sudah_ditagih' => 'Sudah Ditagih',
            default => '-',
        };
    }
}
