<?php

namespace App\Policies;

use App\Models\Pembayaran;
use App\Models\User;

class PembayaranPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Pembayaran $pembayaran): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'bagian_administrasi';
    }

    public function update(User $user, Pembayaran $pembayaran): bool
    {
        return $user->role === 'bagian_administrasi';
    }

    public function delete(User $user, Pembayaran $pembayaran): bool
    {
        return $user->role === 'bagian_administrasi';
    }
}
