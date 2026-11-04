<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Period;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDays(2)->setTime(9, 0);

        return [
            'booking_number' => 'PMJ-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'user_id' => User::factory(),
            'room_id' => Room::factory(),
            'period_id' => fn () => Period::current()?->id ?? Period::create(['year' => now()->year, 'status' => 'active'])->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addHours(2),
            'status' => BookingStatus::Pending,
            'notes' => fake()->sentence(),
        ];
    }

    public function at(string $start, string $end): static
    {
        return $this->state(fn () => ['start_at' => $start, 'end_at' => $end]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => BookingStatus::Approved,
            'approved_at' => now(),
            'verification_token' => Str::random(48),
        ]);
    }
}
