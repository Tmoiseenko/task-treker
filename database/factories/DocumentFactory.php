<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\MoonshineUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'category' => fake()->randomElement(['api_documentation', 'architecture', 'integration_guide', 'general_notes']),
            'project_id' => Project::factory(),
            'moonshine_author_id' => MoonshineUser::factory(),
            'version' => 1,
        ];
    }
}
