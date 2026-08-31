<?php

namespace App\Services;

use App\Models\LogAktivitas;
use App\Models\PembayaranBukti;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PembayaranBuktiService
{
    public function __construct(
        protected PembayaranService $pembayaranService,
    ) {}

    public function simpanBukti(Tagihan $tagihan, User $sales, array $data): PembayaranBukti
    {
        if ($tagihan->status === 'lunas') {
            throw new \LogicException('Tagihan sudah lunas dan tidak bisa menerima bukti pembayaran.');
        }

        if ($tagihan->approval_status !== 'aktif') {
            throw new \LogicException('Tagihan belum aktif dan tidak bisa menerima bukti pembayaran.');
        }

        if ($tagihan->assigned_sales_id !== $sales->id) {
            throw new \LogicException('Tagihan ini bukan tanggung jawab Anda.');
        }

        $sisa = $this->sisaTagihan($tagihan);

        if (bccomp((string) $data['nominal_dibayar'], $sisa, 2) > 0) {
            throw ValidationException::withMessages([
                'nominal_dibayar' => 'Nominal melebihi sisa tagihan (Rp '.number_format((float) $sisa, 2).').',
            ]);
        }

        $file = $data['file'];
        $path = $file->store('bukti-bayar', 'local');

        $bukti = $tagihan->pembayaranBukti()->create([
            'sales_id' => $sales->id,
            'file_path' => $path,
            'nominal_dibayar' => $data['nominal_dibayar'],
            'tanggal_bayar' => $data['tanggal_bayar'],
            'status' => 'pending',
        ]);

        $this->log('upload_bukti_bayar', $bukti, [], [
            'id_tagihan' => $tagihan->id_tagihan,
            'nominal_dibayar' => $bukti->nominal_dibayar,
        ]);

        return $bukti;
    }

    public function setujuiBukti(PembayaranBukti $bukti, User $validator): PembayaranBukti
    {
        if (! $bukti->isPending()) {
            throw new \LogicException('Bukti pembayaran sudah divalidasi sebelumnya.');
        }

        $tagihan = $bukti->tagihan;

        if ($tagihan->status === 'lunas') {
            throw new \LogicException('Tagihan sudah lunas, bukti ini tidak dapat disetujui.');
        }

        $sisa = $this->sisaTagihan($tagihan);

        if (bccomp((string) $bukti->nominal_dibayar, $sisa, 2) > 0) {
            $bukti->status = 'rejected';
            $bukti->catatan_reject = 'Nominal melebihi sisa tagihan saat validasi (Rp '.number_format((float) $sisa, 2).').';
            $bukti->validated_by = $validator->id;
            $bukti->validated_at = now();
            $bukti->save();

            $this->log('tolak_bukti_bayar', $bukti, ['status' => 'pending'], [
                'status' => 'rejected',
                'catatan_reject' => $bukti->catatan_reject,
                'validated_by' => $validator->id,
            ]);

            throw ValidationException::withMessages([
                'nominal' => 'Nominal bukti melebihi sisa tagihan saat validasi. Bukti ditolak otomatis.',
            ]);
        }

        DB::transaction(function () use ($bukti, $validator, $tagihan) {
            $tagihan->pembayaran()->create([
                'tanggal_bayar' => $bukti->tanggal_bayar,
                'jumlah_bayar' => $bukti->nominal_dibayar,
                'metode_bayar' => 'transfer',
                'keterangan' => 'Disetujui dari bukti pembayaran #'.$bukti->id,
            ]);

            $bukti->status = 'approved';
            $bukti->validated_by = $validator->id;
            $bukti->validated_at = now();
            $bukti->save();

            $this->pembayaranService->sinkronkanStatus($tagihan);
        });

        $this->log('setujui_bukti_bayar', $bukti, ['status' => 'pending'], [
            'status' => 'approved',
            'validated_by' => $validator->id,
        ]);

        return $bukti;
    }

    public function tolakBukti(PembayaranBukti $bukti, User $validator, string $catatan): PembayaranBukti
    {
        if (! $bukti->isPending()) {
            throw new \LogicException('Bukti pembayaran sudah divalidasi sebelumnya.');
        }

        if (trim($catatan) === '') {
            throw new \InvalidArgumentException('Catatan penolakan wajib diisi.');
        }

        $bukti->status = 'rejected';
        $bukti->catatan_reject = $catatan;
        $bukti->validated_by = $validator->id;
        $bukti->validated_at = now();
        $bukti->save();

        $this->log('tolak_bukti_bayar', $bukti, ['status' => 'pending'], [
            'status' => 'rejected',
            'catatan_reject' => $catatan,
            'validated_by' => $validator->id,
        ]);

        return $bukti;
    }

    private function sisaTagihan(Tagihan $tagihan): string
    {
        $totalDibayar = (string) $tagihan->pembayaran()->sum('jumlah_bayar');

        $sisa = bcsub((string) $tagihan->total_tagihan, $totalDibayar, 2);

        return bccomp($sisa, '0', 2) < 0 ? '0' : $sisa;
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
