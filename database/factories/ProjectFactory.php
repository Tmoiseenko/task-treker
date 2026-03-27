<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(['mobile_app', 'telegram_bot', 'website', 'crm_system']),
            'status' => fake()->randomElement(['active', 'completed', 'archived', 'on_hold']),
        ];
    }
}
