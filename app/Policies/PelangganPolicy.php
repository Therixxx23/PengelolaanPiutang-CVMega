<?php

namespace App\Policies;

use App\Models\Pelanggan;
use App\Models\User;

class PelangganPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Pelanggan $pelanggan): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Pelanggan $pelanggan): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, Pelanggan $pelanggan): bool
    {
        return $user->role === 'admin';
    }
}
