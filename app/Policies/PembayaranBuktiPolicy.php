<?php

namespace App\Policies;

use App\Models\PembayaranBukti;
use App\Models\Tagihan;
use App\Models\User;

class PembayaranBuktiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrasi()
            || $user->isKeuangan()
            || $user->isSales();
    }

    public function view(User $user, PembayaranBukti $bukti): bool
    {
        if ($user->isAdministrasi() || $user->isKeuangan()) {
            return true;
        }

        return $user->isSales() && $bukti->sales_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isSales();
    }

    public function upload(User $user, Tagihan $tagihan): bool
    {
        return $user->isSales()
            && $tagihan->assigned_sales_id === $user->id
            && $tagihan->approval_status === 'aktif';
    }

    public function validate(User $user): bool
    {
        return $user->isKeuangan();
    }

    public function approve(User $user, PembayaranBukti $bukti): bool
    {
        return $user->isKeuangan() && $bukti->isPending();
    }

    public function reject(User $user, PembayaranBukti $bukti): bool
    {
        return $user->isKeuangan() && $bukti->isPending();
    }
}
