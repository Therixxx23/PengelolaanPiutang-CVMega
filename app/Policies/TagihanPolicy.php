<?php

namespace App\Policies;

use App\Models\Tagihan;
use App\Models\User;

class TagihanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Tagihan $tagihan): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Tagihan $tagihan): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, Tagihan $tagihan): bool
    {
        return $user->role === 'admin';
    }
}
