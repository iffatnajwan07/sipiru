<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\UserType;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'nip', 'nik', 'phone', 'position'])]
#[Hidden(['password', 'remember_token', 'ktp_path', 'signature_path'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'policy_accepted_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'user_type' => UserType::class,
            'is_active' => 'boolean',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    public function isAdmin(): bool
    {
        return $this->user_type === UserType::Admin;
    }

    public function isPegawai(): bool
    {
        return $this->user_type === UserType::Pegawai;
    }

    public function isEksternal(): bool
    {
        return $this->user_type === UserType::Eksternal;
    }

    /** Label identitas: NIP untuk pegawai/admin, NIK untuk eksternal. */
    public function identifierLabel(): string
    {
        return $this->isEksternal() ? 'NIK' : 'NIP';
    }

    public function identifier(): ?string
    {
        return $this->isEksternal() ? $this->nik : $this->nip;
    }

    public function hasAcceptedCurrentPolicy(): bool
    {
        return $this->policy_accepted_at !== null
            && $this->policy_version === config('booking.policy_version');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeAdmins(Builder $query): void
    {
        $query->where('user_type', UserType::Admin->value);
    }

    public function upcomingBookings(): HasMany
    {
        return $this->bookings()
            ->whereIn('status', [BookingStatus::Approved->value, BookingStatus::Pending->value])
            ->where('end_at', '>=', now())
            ->orderBy('start_at');
    }
}
