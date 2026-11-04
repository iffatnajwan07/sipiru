<?php

namespace App\Models;

use App\Support\NameNormalizer;
use Database\Factories\BuildingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'name', 'address', 'description', 'is_active'])]
#[Hidden(['name_unique_key', 'code_unique_key'])]
class Building extends Model
{
    /** @use HasFactory<BuildingFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        // Normalisasi SELALU dihitung di model agar semua jalur penyimpanan konsisten.
        static::saving(function (Building $building) {
            $building->name = NameNormalizer::clean($building->name);
            $building->code = mb_strtoupper(NameNormalizer::clean($building->code));
            $building->name_normalized = NameNormalizer::normalize($building->name);
            $building->code_normalized = NameNormalizer::normalize($building->code);
        });
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        if (filled($term)) {
            $needle = '%'.NameNormalizer::normalize($term).'%';
            $query->where(fn ($q) => $q->where('name_normalized', 'like', $needle)
                ->orWhere('code_normalized', 'like', $needle));
        }
    }

    /** Snapshot atribut yang relevan untuk activity log. */
    public function auditAttributes(): array
    {
        return $this->only(['code', 'name', 'address', 'description', 'is_active']);
    }
}
