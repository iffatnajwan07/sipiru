<?php

namespace App\Policies;

use App\Models\User;

class EmailSettingPolicy
{
    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }
}
