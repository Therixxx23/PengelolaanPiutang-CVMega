<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TagihanItem extends Model
{
    protected $table = 'tagihan_items';

    protected $fillable = [
        'id_tagihan',
        'kode_barang',
        'nama_barang',
        'kelas',
        'spesifikasi',
        'satuan',
        'jenis_barang',
        'kategori',
        'sub_kategori',
        'kode_supplier',
        'nama_supplier',
        'harga_jual',
        'qty_bruto',
        'nilai_bruto',
        'persen_diskon',
        'nilai_diskon',
        'nilai_netto',
        'qty_retur',
        'nilai_retur',
        'qty_netto',
        'netto_penj',
    ];

    protected function casts(): array
    {
        return [
            'harga_jual' => 'decimal:2',
            'nilai_bruto' => 'decimal:2',
            'nilai_diskon' => 'decimal:2',
            'nilai_netto' => 'decimal:2',
            'nilai_retur' => 'decimal:2',
            'netto_penj' => 'decimal:2',
        ];
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class, 'id_tagihan');
    }
}
