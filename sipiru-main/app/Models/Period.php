<?php

namespace App\Models;

use App\Enums\PeriodStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['year', 'status'])]
#[Hidden(['active_flag'])]
class Period extends Model
{
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'status' => PeriodStatus::class,
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public static function current(): ?self
    {
        return static::where('status', PeriodStatus::Active->value)->first();
    }

    public function isActive(): bool
    {
        return $this->status === PeriodStatus::Active;
    }
}
