<?php

namespace Tests\Feature;

use App\Models\Stage;
use App\MoonShine\Resources\Stage\StageResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StageResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_stage_resource_is_registered(): void
    {
        $this->assertTrue(class_exists(StageResource::class));
    }

    public function test_stage_model_exists(): void
    {
        $this->assertTrue(class_exists(Stage::class));
    }

    public function test_stage_can_be_created_with_required_fields(): void
    {
        $stage = Stage::create([
            'name' => 'Дизайн',
            'description' => 'Этап разработки дизайна',
            'order' => 1,
        ]);

        $this->assertDatabaseHas('stages', [
            'name' => 'Дизайн',
            'description' => 'Этап разработки дизайна',
            'order' => 1,
        ]);
    }

    public function test_stage_name_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Stage::create([
            'description' => 'Описание без названия',
            'order' => 1,
        ]);
    }

    public function test_stage_order_has_default_value(): void
    {
        $stage = Stage::create([
            'name' => 'Тестирование',
        ]);

        $this->assertEquals(0, $stage->order);
    }
}
