<?php

namespace App\Policies;

use App\Models\Period;
use App\Models\User;

class PeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Period $period): bool
    {
        return $user->isAdmin();
    }
}
