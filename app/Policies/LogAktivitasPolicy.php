<?php

namespace App\Policies;

use App\Models\LogAktivitas;
use App\Models\User;

class LogAktivitasPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPimpinan();
    }

    public function view(User $user, LogAktivitas $log): bool
    {
        return $user->isPimpinan();
    }
}
