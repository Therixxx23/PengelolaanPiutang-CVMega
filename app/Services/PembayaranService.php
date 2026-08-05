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

        $totalDibayar = $tagihan->pembayaran()->sum('jumlah_bayar');
        $sisaTagihan = $tagihan->total_tagihan - $totalDibayar;

        if ($data['jumlah_bayar'] > $sisaTagihan) {
            throw ValidationException::withMessages([
                'jumlah_bayar' => 'Jumlah bayar melebihi sisa tagihan (Rp '.number_format($sisaTagihan, 2).').',
            ]);
        }

        $pembayaran = $tagihan->pembayaran()->create([
            'tanggal_bayar' => $data['tanggal_bayar'],
            'jumlah_bayar' => $data['jumlah_bayar'],
            'metode_bayar' => $data['metode_bayar'],
            'keterangan' => $data['keterangan'] ?? null,
        ]);

        $totalBaru = $totalDibayar + $data['jumlah_bayar'];

        if (abs($totalBaru - $tagihan->total_tagihan) < 0.01) {
            $tagihan->status = 'lunas';
            $tagihan->save();
        }

        $this->log('catat_pembayaran', $pembayaran, [], [
            'jumlah_bayar' => $pembayaran->jumlah_bayar,
            'id_tagihan' => $pembayaran->id_tagihan,
        ]);

        return $pembayaran;
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
