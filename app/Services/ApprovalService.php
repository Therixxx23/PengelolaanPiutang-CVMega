<?php

namespace App\Services;

use App\Models\LogAktivitas;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Support\Carbon;

class ApprovalService
{
    public function tentukanStatus(Tagihan $tagihan): string
    {
        if ($tagihan->approval_status !== 'aktif') {
            return $tagihan->approval_status;
        }

        if ($tagihan->status === 'lunas') {
            return 'aktif';
        }

        return $tagihan->butuh_approval ? 'menunggu_persetujuan' : 'aktif';
    }

    public function setujui(Tagihan $tagihan, User $user, ?string $catatan = null): Tagihan
    {
        if ($tagihan->approval_status !== 'menunggu_persetujuan') {
            throw new \LogicException('Hanya tagihan berstatus "menunggu persetujuan" yang dapat disetujui.');
        }

        $sebelum = ['approval_status' => 'menunggu_persetujuan'];

        $tagihan->approval_status = 'aktif';
        $tagihan->approved_by = $user->id;
        $tagihan->approved_at = now();
        $tagihan->approval_note = $catatan;
        $tagihan->save();

        $this->log('setujui_tagihan', $tagihan, $sebelum, [
            'approval_status' => 'aktif',
            'approved_by' => $user->id,
        ]);

        return $tagihan;
    }

    public function tolak(Tagihan $tagihan, User $user, ?string $catatan = null): Tagihan
    {
        if ($tagihan->approval_status !== 'menunggu_persetujuan') {
            throw new \LogicException('Hanya tagihan berstatus "menunggu persetujuan" yang dapat ditolak.');
        }

        if ($catatan === null || trim($catatan) === '') {
            throw new \InvalidArgumentException('Alasan penolakan wajib diisi.');
        }

        $sebelum = ['approval_status' => 'menunggu_persetujuan'];

        $tagihan->approval_status = 'ditolak';
        $tagihan->approved_by = $user->id;
        $tagihan->approved_at = now();
        $tagihan->approval_note = $catatan;
        $tagihan->save();

        $this->log('tolak_tagihan', $tagihan, $sebelum, [
            'approval_status' => 'ditolak',
            'approval_note' => $catatan,
        ]);

        return $tagihan;
    }

    public function ringkasan(): array
    {
        $hariIni = Carbon::today();

        $total = [
            'tagihan_aktif' => Tagihan::aktif()->count(),
            'menunggu' => Tagihan::menungguApproval()->count(),
            'ditolak' => Tagihan::where('approval_status', 'ditolak')->count(),
            'disetujui_hari_ini' => Tagihan::aktif()
                ->whereDate('approved_at', $hariIni)
                ->count(),
        ];

        $menunggu = Tagihan::menungguApproval()
            ->with('pelanggan')
            ->orderBy('tanggal_tagihan')
            ->get()
            ->map(fn (Tagihan $tagihan) => [
                'id_tagihan' => $tagihan->id_tagihan,
                'no_invoice' => $tagihan->no_invoice,
                'pelanggan' => $tagihan->pelanggan?->nama_pelanggan,
                'total_tagihan' => (float) $tagihan->total_tagihan,
                'tanggal_jatuh_tempo' => $tagihan->tanggal_jatuh_tempo,
            ]);

        return [
            'total' => $total,
            'menunggu' => $menunggu,
        ];
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
