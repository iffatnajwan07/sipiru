<?php

namespace App\Auth;

/**
 * Identitas pegawai hasil autentikasi dari provider internal (lokal/SIMPEG).
 * Tidak pernah membawa password.
 */
final readonly class InternalIdentity
{
    public function __construct(
        public string $nip,
        public string $name,
        public string $email,
        public ?string $position = null,
        public ?string $phone = null,
        /** Data mentah tambahan dari provider (tidak disimpan). */
        public array $attributes = [],
    ) {}
}
