<?php

namespace Database\Factories;

use App\Models\Estimate;
use App\Models\TaskStage;
use App\Models\MoonshineUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Estimate>
 */
class EstimateFactory extends Factory
{
    protected $model = Estimate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_stage_id' => TaskStage::factory(),
            'moonshine_user_id' => MoonshineUser::factory(),
            'hours' => fake()->randomFloat(2, 1, 40),
        ];
    }
}
