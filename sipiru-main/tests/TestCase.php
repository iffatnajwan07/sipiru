<?php

namespace Tests;

use App\Enums\PeriodStatus;
use App\Models\Building;
use App\Models\Period;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Waktu tetap agar hasil test tidak bergantung pada tanggal saat dijalankan
        $this->travelTo(Carbon::parse('2026-03-10 08:00:00'));
    }

    protected function admin(array $attributes = []): User
    {
        return User::factory()->admin()->create($attributes);
    }

    protected function pegawai(array $attributes = []): User
    {
        return User::factory()->pegawai()->create($attributes);
    }

    protected function eksternal(array $attributes = []): User
    {
        return User::factory()->eksternal()->create($attributes);
    }

    protected function activePeriod(?int $year = null): Period
    {
        return Period::create(['year' => $year ?? now()->year, 'status' => PeriodStatus::Active]);
    }

    protected function building(array $attributes = []): Building
    {
        return Building::factory()->create($attributes);
    }

    protected function room(array $attributes = []): Room
    {
        return Room::factory()->create($attributes);
    }

    /** Payload form pengajuan booking. */
    protected function bookingPayload(Room $room, string $date = '2026-03-12', string $start = '09:00', string $end = '11:00', array $extra = []): array
    {
        return ['room_id' => $room->id, 'date' => $date, 'start_time' => $start, 'end_time' => $end, 'notes' => 'Rapat'] + $extra;
    }
}
