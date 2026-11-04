<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Cancelled => 'Dibatalkan',
            self::Completed => 'Selesai',
        };
    }

    /** Nama tone untuk komponen badge. */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Cancelled => 'slate',
            self::Completed => 'indigo',
        };
    }

    public function isCancellable(): bool
    {
        return in_array($this, [self::Pending, self::Approved], true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
