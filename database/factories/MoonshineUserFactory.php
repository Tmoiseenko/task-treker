<?php

namespace Database\Factories;

use App\Models\MoonshineUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MoonshineUser>
 */
class MoonshineUserFactory extends Factory
{
    protected $model = MoonshineUser::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'hourly_rate' => fake()->randomFloat(2, 20, 150),
            'moonshine_user_role_id' => 1, // Default Admin role
        ];
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'moonshine_user_role_id' => 1,
        ]);
    }

    /**
     * Set a specific hourly rate.
     */
    public function withHourlyRate(float $rate): static
    {
        return $this->state(fn (array $attributes) => [
            'hourly_rate' => $rate,
        ]);
    }
}
