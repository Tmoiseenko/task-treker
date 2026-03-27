<?php

namespace Database\Factories;

use App\Enums\StageStatus;
use App\Models\Stage;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaskStage>
 */
class TaskStageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'stage_id' => Stage::factory(),
            'status' => fake()->randomElement(StageStatus::cases()),
            'order' => fake()->numberBetween(1, 10),
        ];
    }
}
