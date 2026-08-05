<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAktivitas extends Model
{
    public $timestamps = false;

    protected $table = 'log_aktivitas';

    protected $fillable = [
        'user_id',
        'aksi',
        'model_type',
        'model_id',
        'data_sebelum',
        'data_sesudah',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'data_sebelum' => 'array',
            'data_sesudah' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
