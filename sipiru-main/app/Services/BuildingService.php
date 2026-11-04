<?php

namespace App\Services;

use App\Models\Building;
use App\Support\NameNormalizer;
use App\Support\SimilarityChecker;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BuildingService
{
    public function __construct(private ActivityLogger $logger) {}

    public function create(array $data): Building
    {
        return $this->guardUnique(fn () => DB::transaction(function () use ($data) {
            $building = Building::create($data);
            $this->logger->log('CREATE_BUILDING', $building, "Membuat gedung {$building->name}", ['after' => $building->auditAttributes()]);

            return $building;
        }));
    }

    public function update(Building $building, array $data): Building
    {
        return $this->guardUnique(fn () => DB::transaction(function () use ($building, $data) {
            $building = Building::whereKey($building->getKey())->lockForUpdate()->firstOrFail();
            $before = $building->auditAttributes();
            $building->update($data);
            $this->logger->logChanges('UPDATE_BUILDING', $building, $before, $building->auditAttributes(), "Mengubah gedung {$building->name}");

            return $building;
        }));
    }

    public function setActive(Building $building, bool $active): Building
    {
        return DB::transaction(function () use ($building, $active) {
            $building->update(['is_active' => $active]);
            $this->logger->log($active ? 'ACTIVATE_BUILDING' : 'DEACTIVATE_BUILDING', $building,
                ($active ? 'Mengaktifkan' : 'Menonaktifkan')." gedung {$building->name}");

            return $building;
        });
    }

    /** Hapus (soft delete) hanya jika gedung tidak memiliki ruangan sama sekali. */
    public function delete(Building $building): void
    {
        DB::transaction(function () use ($building) {
            $building = Building::whereKey($building->getKey())->lockForUpdate()->firstOrFail();
            $count = $building->rooms()->withTrashed()->count();
            if ($count > 0) {
                throw ValidationException::withMessages([
                    'building' => "Gedung tidak dapat dihapus. Gedung ini masih memiliki {$count} ruangan. Nonaktifkan gedung jika sudah tidak digunakan.",
                ]);
            }
            $building->delete();
            $this->logger->log('DELETE_BUILDING', $building, "Menghapus gedung {$building->name}");
        });
    }

    /**
     * Pengecekan duplikasi untuk UI (exact normalized match + kemiripan).
     */
    public function checkDuplicate(?string $name, ?string $code, ?int $ignoreId = null): array
    {
        $nameNorm = NameNormalizer::normalize($name);
        $codeNorm = NameNormalizer::normalize($code);
        $query = fn () => Building::query()->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId));

        $exactName = $nameNorm !== '' ? $query()->where('name_normalized', $nameNorm)->first() : null;
        $exactCode = $codeNorm !== '' ? $query()->where('code_normalized', $codeNorm)->first() : null;

        $similar = collect();
        if ($nameNorm !== '' && ! $exactName) {
            $similar = $query()->get(['id', 'code', 'name', 'is_active'])
                ->filter(fn (Building $b) => SimilarityChecker::isSimilar($b->name, $name))
                ->sortByDesc(fn (Building $b) => SimilarityChecker::score($b->name, $name))
                ->take(3)->values();
        }

        return [
            'name_exists' => (bool) $exactName,
            'code_exists' => (bool) $exactCode,
            'name_match' => $exactName ? $this->present($exactName) : null,
            'code_match' => $exactCode ? $this->present($exactCode) : null,
            'similar' => $similar->map(fn ($b) => $this->present($b))->all(),
        ];
    }

    private function present(Building $b): array
    {
        return ['id' => $b->id, 'name' => $b->name, 'code' => $b->code, 'status' => $b->is_active ? 'Aktif' : 'Nonaktif'];
    }

    /** Pengaman terakhir: unique constraint database → pesan validasi yang ramah. */
    private function guardUnique(callable $callback): Building
    {
        try {
            return $callback();
        } catch (UniqueConstraintViolationException $e) {
            preg_match("/for key '([^']+)'/", $e->getMessage(), $m);
            $field = str_contains($m[1] ?? '', 'code') ? 'code' : 'name';
            throw ValidationException::withMessages([
                $field => $field === 'code' ? 'Kode gedung sudah digunakan.' : 'Gedung dengan nama tersebut sudah terdaftar.',
            ]);
        }
    }
}
