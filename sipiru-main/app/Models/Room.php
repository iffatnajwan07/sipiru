<?php

namespace App\Models;

use App\Support\NameNormalizer;
use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['building_id', 'code', 'name', 'floor', 'capacity', 'price', 'description', 'is_active'])]
#[Hidden(['name_unique_key', 'code_unique_key'])]
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'capacity' => 'integer',
            'price' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Room $room) {
            $room->public_token ??= Str::random(40);
        });

        static::saving(function (Room $room) {
            $room->name = NameNormalizer::clean($room->name);
            $room->code = mb_strtoupper(NameNormalizer::clean($room->code));
            $room->floor = $room->floor !== null ? NameNormalizer::clean((string) $room->floor) : null;
            $room->name_normalized = NameNormalizer::normalize($room->name);
            $room->code_normalized = NameNormalizer::normalize($room->code);
        });
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class)->withTrashed();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** Ruangan dapat dipinjam: ruangan aktif DAN gedungnya aktif. */
    public function isBookable(): bool
    {
        return $this->is_active
            && ! $this->trashed()
            && $this->building
            && $this->building->is_active
            && ! $this->building->trashed();
    }

    public function scopeBookable(Builder $query): void
    {
        $query->where('is_active', true)
            ->whereHas('building', fn ($q) => $q->where('is_active', true));
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        if (filled($term)) {
            $needle = '%'.NameNormalizer::normalize($term).'%';
            $query->where(fn ($q) => $q->where('rooms.name_normalized', 'like', $needle)
                ->orWhere('rooms.code_normalized', 'like', $needle));
        }
    }

    public function fullName(): string
    {
        return $this->name.' — '.($this->building?->name ?? '-');
    }

    public function auditAttributes(): array
    {
        return $this->only(['building_id', 'code', 'name', 'floor', 'capacity', 'price', 'description', 'is_active']);
    }
}