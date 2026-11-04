<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->is($model);
    }

    /** File KTP hanya untuk admin atau pemiliknya. */
    public function viewKtp(User $user, User $model): bool
    {
        return $this->view($user, $model) && filled($model->ktp_path);
    }

    public function update(User $user, User $model): bool
    {
        return $user->is($model);
    }

    /** Admin mengaktifkan/menonaktifkan akun lain (tidak untuk dirinya sendiri). */
    public function toggleActive(User $user, User $model): bool
    {
        return $user->isAdmin() && ! $user->is($model);
    }
}
