<?php

namespace App\Auth\Providers;

use App\Auth\Contracts\AuthenticationProvider;
use App\Auth\InternalIdentity;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Provider DEVELOPMENT: memverifikasi NIP + password terhadap akun pegawai lokal (hasil seeder).
 * Tidak untuk production.
 */
class LocalDevelopmentAuthProvider implements AuthenticationProvider
{
    public function authenticate(string $identifier, string $password): ?InternalIdentity
    {
        $user = User::where('user_type', UserType::Pegawai->value)
            ->where('nip', $identifier)
            ->first();

        if (! $user || ! $user->password || ! Hash::check($password, $user->password)) {
            return null;
        }

        return new InternalIdentity(
            nip: $user->nip,
            name: $user->name,
            email: $user->email,
            position: $user->position,
            phone: $user->phone,
        );
    }

    public function name(): string
    {
        return 'local';
    }

    public function logout(): void
    {
        // Tidak ada sesi eksternal.
    }
}
