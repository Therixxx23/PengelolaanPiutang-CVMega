<?php

namespace App\Services;

use App\Models\CatatanPenagihan;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PenagihanService
{
    protected const VALID_STATUS = [
        'belum_ditagih',
        'sedang_ditagih',
        'janji_bayar',
        'sudah_ditagih',
    ];

    public function updateStatus(
        Tagihan $tagihan,
        User $sales,
        string $status,
        ?string $catatan = null
    ): void {
        if (! in_array($status, self::VALID_STATUS, true)) {
            throw new \InvalidArgumentException('Status penagihan tidak valid.');
        }

        DB::transaction(function () use ($tagihan, $sales, $status, $catatan) {
            CatatanPenagihan::create([
                'id_tagihan' => $tagihan->id_tagihan,
                'user_id' => $sales->id,
                'status_penagihan' => $status,
                'catatan' => $catatan,
            ]);

            $tagihan->update([
                'status_penagihan' => $status,
                'catatan_penagihan_terakhir' => $catatan,
            ]);
        });
    }

    public function assignSales(Tagihan $tagihan, ?int $salesId): void
    {
        $tagihan->update(['assigned_sales_id' => $salesId]);
    }
}
