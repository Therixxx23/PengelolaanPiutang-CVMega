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

    public function totalPiutangAktif(): string
    {
        return (string) $this->tagihan()
            ->where('status', 'belum_lunas')
            ->sum('total_tagihan');
    }

    public function cekBatasKredit(string $tagihanBaru): array
    {
        $totalAktif = $this->totalPiutangAktif();
        $totalBaru = bcadd($totalAktif, $tagihanBaru, 2);
        $batas = (string) $this->batas_kredit;
        $sisaLimit = bccomp($batas, $totalAktif, 2) > 0
            ? bcsub($batas, $totalAktif, 2)
            : '0';

        return [
            'exceeded' => bccomp($batas, '0', 2) > 0 && bccomp($totalBaru, $batas, 2) > 0,
            'total_piutang_aktif' => $totalAktif,
            'total_baru' => $totalBaru,
            'batas_kredit' => $batas,
            'sisa_limit' => $sisaLimit,
            'kelebihan' => bccomp($totalBaru, $batas, 2) > 0
                ? bcsub($totalBaru, $batas, 2)
                : '0',
        ];
    }
}
