<?php

namespace App\Enums;

enum UserType: string
{
    case Admin = 'admin';
    case Pegawai = 'pegawai';
    case Eksternal = 'eksternal';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Pegawai => 'Pegawai USK',
            self::Eksternal => 'Eksternal',
        };
    }
}
