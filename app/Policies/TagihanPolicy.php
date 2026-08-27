<?php

namespace App\Policies;

use App\Models\Tagihan;
use App\Models\User;

class TagihanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrasi();
    }

    public function view(User $user, Tagihan $tagihan): bool
    {
        return $user->isAdministrasi();
    }

    public function create(User $user): bool
    {
        return $user->isAdministrasi();
    }

    public function import(User $user): bool
    {
        return $user->isAdministrasi() || $user->isPimpinan();
    }

    public function update(User $user, Tagihan $tagihan): bool
    {
        return $user->isAdministrasi();
    }

    public function delete(User $user, Tagihan $tagihan): bool
    {
        return $user->isAdministrasi();
    }

    public function viewApproval(User $user): bool
    {
        return $user->isPimpinan();
    }

    public function approve(User $user, Tagihan $tagihan): bool
    {
        return $user->isPimpinan()
            && $tagihan->approval_status === 'menunggu_persetujuan';
    }

    public function viewSales(User $user, Tagihan $tagihan): bool
    {
        if ($user->role === 'sales') {
            return $tagihan->assigned_sales_id === $user->id;
        }

        return in_array($user->role, [
            'pimpinan',
            'bagian_administrasi',
            'bagian_keuangan',
        ]);
    }

    public function updatePenagihan(User $user, Tagihan $tagihan): bool
    {
        if ($user->role === 'sales') {
            return $tagihan->assigned_sales_id === $user->id
                && $tagihan->approval_status === 'aktif';
        }

        return in_array($user->role, [
            'pimpinan',
            'bagian_administrasi',
        ]);
    }
}
