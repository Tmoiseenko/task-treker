<?php

namespace Tests\Feature;

use App\Models\Stage;
use Database\Seeders\StageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StageSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_stage_seeder_creates_all_basic_stages(): void
    {
        // Run the seeder
        $this->seed(StageSeeder::class);

        // Verify all 6 stages were created
        $this->assertDatabaseCount('stages', 6);

        // Verify each stage exists with correct data
        $expectedStages = [
            ['name' => 'Дизайн', 'description' => 'Разработка дизайна и UI/UX', 'order' => 1],
            ['name' => 'Бэкенд', 'description' => 'Разработка серверной части и API', 'order' => 2],
            ['name' => 'Админка', 'description' => 'Разработка административной панели', 'order' => 3],
            ['name' => 'Фронтенд', 'description' => 'Разработка клиентской части', 'order' => 4],
            ['name' => 'Мобилка', 'description' => 'Разработка мобильного приложения', 'order' => 5],
            ['name' => 'Тестирование', 'description' => 'Тестирование и проверка качества', 'order' => 6],
        ];

        foreach ($expectedStages as $expectedStage) {
            $this->assertDatabaseHas('stages', $expectedStage);
        }
    }

    public function test_stages_have_required_fields(): void
    {
        $this->seed(StageSeeder::class);

        $stages = Stage::all();

        foreach ($stages as $stage) {
            $this->assertNotEmpty($stage->name);
            $this->assertNotEmpty($stage->description);
            $this->assertIsInt($stage->order);
            $this->assertGreaterThan(0, $stage->order);
        }
    }

    public function test_stages_are_ordered_correctly(): void
    {
        $this->seed(StageSeeder::class);

        $stages = Stage::orderBy('order')->get();

        $expectedOrder = ['Дизайн', 'Бэкенд', 'Админка', 'Фронтенд', 'Мобилка', 'Тестирование'];

        foreach ($stages as $index => $stage) {
            $this->assertEquals($expectedOrder[$index], $stage->name);
            $this->assertEquals($index + 1, $stage->order);
        }
    }
}
