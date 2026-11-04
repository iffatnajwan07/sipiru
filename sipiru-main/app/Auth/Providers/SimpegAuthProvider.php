<?php

namespace App\Auth\Providers;

use App\Auth\Contracts\AuthenticationProvider;
use App\Auth\Exceptions\AuthProviderException;
use App\Auth\InternalIdentity;

/**
 * Provider PRODUCTION untuk SIMPEG USK.
 *
 * SENGAJA BELUM DIIMPLEMENTASIKAN: endpoint, metode autentikasi (REST/LDAP/SSO/OAuth),
 * kredensial klien, pemetaan field, dan mekanisme logout belum diberikan oleh ICT USK.
 * Detail tersebut TIDAK dikarang di sini. Lihat README bagian "SIMPEG Integration".
 *
 * Yang perlu dilakukan saat detail tersedia:
 *  1. Kirim $identifier/$password ke SIMPEG sesuai metode resmi (jangan simpan password).
 *  2. Petakan respons ke InternalIdentity (nip, name, email, position, phone).
 *  3. Kembalikan null bila kredensial salah; lempar AuthProviderException bila SIMPEG tidak dapat dihubungi.
 */
class SimpegAuthProvider implements AuthenticationProvider
{
    public function __construct(private array $config = [])
    {
        $this->config = $config ?: (array) config('auth_providers.simpeg');
    }

    public function authenticate(string $identifier, string $password): ?InternalIdentity
    {
        if (blank($this->config['base_url'] ?? null)) {
            throw new AuthProviderException('Integrasi SIMPEG belum dikonfigurasi. Hubungi administrator sistem.');
        }

        throw new AuthProviderException('Integrasi SIMPEG belum diimplementasikan. Menunggu spesifikasi resmi dari ICT USK.');
    }

    public function name(): string
    {
        return 'simpeg';
    }

    public function logout(): void
    {
        // Diisi sesuai mekanisme logout/session SIMPEG.
    }
}
