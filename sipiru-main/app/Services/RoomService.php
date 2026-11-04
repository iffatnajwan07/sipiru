<?php

namespace App\Services;

use App\Models\Building;
use App\Models\Room;
use App\Support\NameNormalizer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoomService
{
    public function __construct(private ActivityLogger $logger) {}

    public function create(array $data): Room
    {
        return $this->guardUnique(fn () => DB::transaction(function () use ($data) {
            // Kunci baris gedung agar pembuatan ruangan paralel di gedung yang sama berurutan
            Building::whereKey($data['building_id'])->lockForUpdate()->firstOrFail();
            $room = Room::create($data);
            $this->logger->log('CREATE_ROOM', $room, "Membuat ruangan {$room->name}", ['after' => $room->auditAttributes()]);

            return $room;
        }));
    }

    public function update(Room $room, array $data): Room
    {
        return $this->guardUnique(fn () => DB::transaction(function () use ($room, $data) {
            $room = Room::whereKey($room->getKey())->lockForUpdate()->firstOrFail();
            $before = $room->auditAttributes();
            $room->update($data);
            $this->logger->logChanges('UPDATE_ROOM', $room, $before, $room->auditAttributes(), "Mengubah ruangan {$room->name}");

            return $room;
        }));
    }

    public function setActive(Room $room, bool $active): Room
    {
        return DB::transaction(function () use ($room, $active) {
            $room->update(['is_active' => $active]);
            $this->logger->log($active ? 'ACTIVATE_ROOM' : 'DEACTIVATE_ROOM', $room,
                ($active ? 'Mengaktifkan' : 'Menonaktifkan')." ruangan {$room->name}");

            return $room;
        });
    }

    /** Ruangan dengan riwayat peminjaman tidak boleh dihapus (data historis dijaga). */
    public function delete(Room $room): void
    {
        DB::transaction(function () use ($room) {
            $room = Room::whereKey($room->getKey())->lockForUpdate()->firstOrFail();
            $count = $room->bookings()->withTrashed()->count();
            if ($count > 0) {
                throw ValidationException::withMessages([
                    'room' => "Ruangan tidak dapat dihapus karena memiliki {$count} riwayat peminjaman. Nonaktifkan ruangan jika sudah tidak digunakan.",
                ]);
            }
            $room->delete();
            $this->logger->log('DELETE_ROOM', $room, "Menghapus ruangan {$room->name}");
        });
    }

    public function checkDuplicate(?int $buildingId, ?string $name, ?string $code, ?int $ignoreId = null): array
    {
        $nameNorm = NameNormalizer::normalize($name);
        $codeNorm = NameNormalizer::normalize($code);
        $base = fn () => Room::query()->with('building:id,name,code')->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId));

        $sameName = ($buildingId && $nameNorm !== '') ? $base()->where('building_id', $buildingId)->where('name_normalized', $nameNorm)->first() : null;
        $sameCode = ($buildingId && $codeNorm !== '') ? $base()->where('building_id', $buildingId)->where('code_normalized', $codeNorm)->first() : null;

        // Info saja: nama yang sama di gedung lain (diperbolehkan)
        $otherBuildings = $nameNorm !== ''
            ? $base()->where('name_normalized', $nameNorm)->when($buildingId, fn ($q) => $q->where('building_id', '!=', $buildingId))
                ->get()->pluck('building.name')->unique()->values()->all()
            : [];

        return [
            'name_exists' => (bool) $sameName,
            'code_exists' => (bool) $sameCode,
            'building_name' => $buildingId ? Building::withTrashed()->find($buildingId)?->name : null,
            'name_match' => $sameName ? ['name' => $sameName->name, 'code' => $sameName->code, 'status' => $sameName->is_active ? 'Aktif' : 'Nonaktif'] : null,
            'code_match' => $sameCode ? ['name' => $sameCode->name, 'code' => $sameCode->code] : null,
            'other_buildings' => $otherBuildings,
        ];
    }

    private function guardUnique(callable $callback): Room
    {
        try {
            return $callback();
        } catch (UniqueConstraintViolationException $e) {
            preg_match("/for key '([^']+)'/", $e->getMessage(), $m);
            $field = str_contains($m[1] ?? '', 'code') ? 'code' : 'name';
            throw ValidationException::withMessages([
                $field => $field === 'code' ? 'Kode ruang sudah digunakan di gedung ini.' : 'Ruang dengan nama tersebut sudah ada di gedung ini.',
            ]);
        }
    }
}
