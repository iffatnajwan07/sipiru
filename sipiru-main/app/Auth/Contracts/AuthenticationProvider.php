<?php

namespace App\Auth\Contracts;

use App\Auth\Exceptions\AuthProviderException;
use App\Auth\InternalIdentity;

/**
 * Kontrak autentikasi pegawai internal USK.
 *
 * Modul peminjaman hanya bergantung pada interface ini, sehingga
 * LocalDevelopmentAuthProvider dapat diganti SimpegAuthProvider
 * cukup lewat AUTH_INTERNAL_PROVIDER tanpa mengubah modul lain.
 */
interface AuthenticationProvider
{
    /**
     * Verifikasi kredensial. Kembalikan identitas jika valid, null jika salah.
     *
     * @throws AuthProviderException jika provider tidak tersedia / belum dikonfigurasi
     */
    public function authenticate(string $identifier, string $password): ?InternalIdentity;

    /** Nama provider untuk ditampilkan/log. */
    public function name(): string;

    /** Dipanggil saat logout (mis. untuk mengakhiri sesi SSO). */
    public function logout(): void;
}
