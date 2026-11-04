<?php

namespace Database\Factories;

use App\Models\Building;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Building>
 */
class BuildingFactory extends Factory
{
    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->lexify('???'));

        return [
            'code' => $code,
            'name' => 'Gedung '.$code.' '.fake()->unique()->numberBetween(1, 99999),
            'address' => fake()->streetAddress(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
