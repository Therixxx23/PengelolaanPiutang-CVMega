<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    protected $table = 'import_log';

    protected $fillable = [
        'user_id',
        'nama_file',
        'total_baris',
        'total_faktur',
        'faktur_baru',
        'faktur_skip',
        'pelanggan_baru',
        'status',
        'pesan_error',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
