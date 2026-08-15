<?php

namespace App\Services;

use App\Models\LogAktivitas;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use Illuminate\Validation\ValidationException;

class PembayaranService
{
    public function catatPembayaran(Tagihan $tagihan, array $data): Pembayaran
    {
        if (! $tagihan->bisa_dibayar) {
            if ($tagihan->approval_status === 'menunggu_persetujuan') {
                throw new \LogicException('Tagihan ini sedang menunggu persetujuan Pimpinan dan belum bisa menerima pembayaran.');
            }

            if ($tagihan->approval_status === 'ditolak') {
                throw new \LogicException('Tagihan ini telah ditolak dan tidak bisa menerima pembayaran.');
            }

            throw new \LogicException('Tagihan sudah lunas dan tidak bisa menerima pembayaran lagi.');
        }

        $totalDibayar = (string) $tagihan->pembayaran()->sum('jumlah_bayar');
        $sisaTagihan = bcsub((string) $tagihan->total_tagihan, $totalDibayar, 2);

        if (bccomp((string) $data['jumlah_bayar'], $sisaTagihan, 2) > 0) {
            throw ValidationException::withMessages([
                'jumlah_bayar' => 'Jumlah bayar melebihi sisa tagihan (Rp '.number_format((float) $sisaTagihan, 2).').',
            ]);
        }

        $pembayaran = $tagihan->pembayaran()->create([
            'tanggal_bayar' => $data['tanggal_bayar'],
            'jumlah_bayar' => $data['jumlah_bayar'],
            'metode_bayar' => $data['metode_bayar'],
            'keterangan' => $data['keterangan'] ?? null,
        ]);

        $this->sinkronkanStatus($tagihan);

        $this->log('catat_pembayaran', $pembayaran, [], [
            'jumlah_bayar' => $pembayaran->jumlah_bayar,
            'id_tagihan' => $pembayaran->id_tagihan,
        ]);

        return $pembayaran;
    }

    public function sinkronkanStatus(Tagihan $tagihan): void
    {
        $totalDibayar = (string) $tagihan->pembayaran()->sum('jumlah_bayar');

        $tagihan->status = bccomp($totalDibayar, (string) $tagihan->total_tagihan, 2) >= 0
            ? 'lunas'
            : 'belum_lunas';

        $tagihan->save();
    }

    private function log(string $aksi, $model, array $sebelum = [], array $sesudah = []): void
    {
        $userId = auth()->id();

        if ($userId === null) {
            return;
        }

        LogAktivitas::create([
            'user_id' => $userId,
            'aksi' => $aksi,
            'model_type' => class_basename($model),
            'model_id' => $model->getKey(),
            'data_sebelum' => $sebelum ?: null,
            'data_sesudah' => $sesudah ?: null,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
