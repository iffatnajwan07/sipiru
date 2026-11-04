<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    public function definition(): array
    {
        $n = fake()->unique()->numberBetween(1, 99999);

        return [
            'building_id' => Building::factory(),
            'code' => 'R-'.$n,
            'name' => 'Ruang '.$n,
            'floor' => (string) fake()->numberBetween(1, 4),
            'capacity' => fake()->numberBetween(10, 100),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
