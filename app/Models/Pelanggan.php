<?php

namespace App\Models;

use Database\Factories\PelangganFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pelanggan extends Model
{
    /** @use HasFactory<PelangganFactory> */
    use HasFactory;

    protected $table = 'pelanggan';

    protected $primaryKey = 'id_pelanggan';

    protected $fillable = [
        'nama_pelanggan',
        'alamat',
        'wilayah',
        'no_telepon',
        'batas_kredit',
    ];

    protected function casts(): array
    {
        return [
            'batas_kredit' => 'decimal:2',
        ];
    }

    public function tagihan(): HasMany
    {
        return $this->hasMany(Tagihan::class, 'id_pelanggan', 'id_pelanggan');
    }
}
