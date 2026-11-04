<?php

namespace App\Enums;

enum PeriodStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return $this === self::Active ? 'Aktif' : 'Tidak Aktif';
    }
}
