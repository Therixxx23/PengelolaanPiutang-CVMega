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

    public function update(User $user, Tagihan $tagihan): bool
    {
        return $user->isAdministrasi();
    }

    public function delete(User $user, Tagihan $tagihan): bool
    {
        return $user->isAdministrasi();
    }
}
