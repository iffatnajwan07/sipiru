<?php

namespace Database\Factories;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Password123'),
            'phone' => '0812'.fake()->unique()->numerify('########'),
            'user_type' => UserType::Pegawai,
            'nip' => fake()->unique()->numerify('19##########0####'),
            'is_active' => true,
            'policy_accepted_at' => now(),
            'policy_version' => config('booking.policy_version'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['user_type' => UserType::Admin]);
    }

    public function pegawai(): static
    {
        return $this->state(fn () => ['user_type' => UserType::Pegawai, 'position' => 'Dosen']);
    }

    public function eksternal(): static
    {
        return $this->state(fn () => [
            'user_type' => UserType::Eksternal,
            'nip' => null,
            'nik' => fake()->unique()->numerify('11############'.'##'),
            'ktp_path' => 'ktp/dummy.pdf',
        ]);
    }

    public function withoutPolicy(): static
    {
        return $this->state(fn () => ['policy_accepted_at' => null, 'policy_version' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
