<?php

namespace App\Policies;

use App\Models\Building;
use App\Models\User;

class BuildingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Building $building): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Building $building): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Building $building): bool
    {
        return $user->isAdmin();
    }
}
