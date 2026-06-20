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

    public function totalPiutangAktif(): float
    {
        return (float) $this->tagihan()
            ->where('status', 'belum_lunas')
            ->sum('total_tagihan');
    }

    public function cekBatasKredit(float $tagihanBaru): array
    {
        $totalAktif = $this->totalPiutangAktif();
        $totalBaru = $totalAktif + $tagihanBaru;
        $batas = (float) $this->batas_kredit;
        $sisaLimit = max(0, $batas - $totalAktif);

        return [
            'exceeded' => $batas > 0 && $totalBaru > $batas,
            'total_piutang_aktif' => $totalAktif,
            'total_baru' => $totalBaru,
            'batas_kredit' => $batas,
            'sisa_limit' => $sisaLimit,
            'kelebihan' => max(0, $totalBaru - $batas),
        ];
    }
}
