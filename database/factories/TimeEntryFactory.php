<?php

namespace Database\Factories;

use App\Models\TaskStage;
use App\Models\MoonshineUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = MoonshineUser::factory()->create(['hourly_rate' => fake()->randomFloat(2, 20, 100)]);
        $hours = fake()->randomFloat(2, 0.5, 8);
        
        return [
            'task_stage_id' => TaskStage::factory(),
            'moonshine_user_id' => $user->id,
            'hours' => $hours,
            'date' => fake()->date(),
            'description' => fake()->optional()->sentence(),
            'cost' => round($hours * $user->hourly_rate, 2),
        ];
    }
}
