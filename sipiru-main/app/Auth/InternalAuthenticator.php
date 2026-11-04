<?php

namespace App\Auth;

use App\Auth\Contracts\AuthenticationProvider;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Menghubungkan identitas dari provider internal ke akun lokal (provisioning otomatis).
 */
class InternalAuthenticator
{
    public function __construct(private AuthenticationProvider $provider) {}

    public function attempt(string $nip, string $password): ?User
    {
        $identity = $this->provider->authenticate($nip, $password);

        return $identity ? $this->syncUser($identity) : null;
    }

    public function provider(): AuthenticationProvider
    {
        return $this->provider;
    }

    private function syncUser(InternalIdentity $identity): User
    {
        return DB::transaction(function () use ($identity) {
            $user = User::withTrashed()->where('nip', $identity->nip)->lockForUpdate()->first();

            if (! $user) {
                $user = new User;
                $user->user_type = UserType::Pegawai;
                $user->nip = $identity->nip;
                $user->is_active = true;
            }

            // Data master pegawai mengikuti SIMPEG; password SIMPEG tidak disimpan.
            $user->name = $identity->name;
            $user->email = $identity->email;
            $user->position = $identity->position ?? $user->position;
            $user->phone = $user->phone ?: $identity->phone;
            $user->save();

            return $user;
        });
    }
}
