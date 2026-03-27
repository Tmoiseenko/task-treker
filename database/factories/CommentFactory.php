<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Task;
use App\Models\MoonshineUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'moonshine_user_id' => MoonshineUser::factory(),
            'content' => fake()->paragraph(),
            'deleted_at' => null,
        ];
    }

    /**
     * Indicate that the comment is deleted.
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }
}
