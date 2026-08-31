<?php

namespace App\Models;

use Database\Factories\PembayaranBuktiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranBukti extends Model
{
    /** @use HasFactory<PembayaranBuktiFactory> */
    use HasFactory;

    protected $table = 'pembayaran_bukti';

    protected $fillable = [
        'tagihan_id',
        'sales_id',
        'file_path',
        'nominal_dibayar',
        'tanggal_bayar',
        'status',
        'catatan_reject',
        'validated_by',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'nominal_dibayar' => 'decimal:2',
            'tanggal_bayar' => 'date',
            'status' => 'string',
            'validated_at' => 'datetime',
        ];
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_id', 'id_tagihan');
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
