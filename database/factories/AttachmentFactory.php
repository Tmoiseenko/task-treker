<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Task;
use App\Models\MoonshineUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attachment>
 */
class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalName = fake()->word() . '.' . fake()->fileExtension();
        $filename = uniqid() . '_' . time() . '.' . fake()->fileExtension();

        return [
            'task_id' => Task::factory(),
            'moonshine_user_id' => MoonshineUser::factory(),
            'filename' => $filename,
            'original_name' => $originalName,
            'mime_type' => fake()->mimeType(),
            'size' => fake()->numberBetween(1000, 1000000),
            'path' => 'attachments/' . $filename,
        ];
    }
}
