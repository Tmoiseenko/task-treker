<?php

namespace Tests\Feature;

use App\Models\Stage;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Stage management functionality
 * Requirements: 2.3, 2.4
 */
class StageManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that stages can be created with all required fields
     * Validates: Requirement 2.3 - Creating stages in the directory
     */
    public function test_stage_can_be_created_with_name_description_and_order(): void
    {
        $stage = Stage::create([
            'name' => 'Дизайн',
            'description' => 'Этап разработки дизайна интерфейса',
            'order' => 1,
        ]);

        $this->assertDatabaseHas('stages', [
            'name' => 'Дизайн',
            'description' => 'Этап разработки дизайна интерфейса',
            'order' => 1,
        ]);

        $this->assertEquals('Дизайн', $stage->name);
        $this->assertEquals('Этап разработки дизайна интерфейса', $stage->description);
        $this->assertEquals(1, $stage->order);
    }

    /**
     * Test that stages can be updated
     * Validates: Requirement 2.4 - Editing stages in the directory
     */
    public function test_stage_can_be_updated(): void
    {
        $stage = Stage::create([
            'name' => 'Бэкенд',
            'description' => 'Разработка серверной части',
            'order' => 2,
        ]);

        $stage->update([
            'name' => 'Backend Development',
            'description' => 'Разработка серверной части приложения',
            'order' => 3,
        ]);

        $this->assertDatabaseHas('stages', [
            'id' => $stage->id,
            'name' => 'Backend Development',
            'description' => 'Разработка серверной части приложения',
            'order' => 3,
        ]);
    }

    /**
     * Test that stages can be deleted
     * Validates: Requirement 2.4 - Deleting stages from the directory
     */
    public function test_stage_can_be_deleted(): void
    {
        $stage = Stage::create([
            'name' => 'Тестирование',
            'description' => 'Этап тестирования',
            'order' => 5,
        ]);

        $stageId = $stage->id;
        $stage->delete();

        $this->assertDatabaseMissing('stages', [
            'id' => $stageId,
        ]);
    }

    /**
     * Test that stage name is required
     * Validates: Requirement 2.3 - Stage requires name
     */
    public function test_stage_name_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Stage::create([
            'description' => 'Описание без названия',
            'order' => 1,
        ]);
    }

    /**
     * Test that stage description is optional
     * Validates: Requirement 2.3 - Description is optional
     */
    public function test_stage_description_is_optional(): void
    {
        $stage = Stage::create([
            'name' => 'Фронтенд',
            'order' => 4,
        ]);

        $this->assertDatabaseHas('stages', [
            'name' => 'Фронтенд',
            'description' => null,
            'order' => 4,
        ]);
    }

    /**
     * Test that stage order has default value
     * Validates: Requirement 2.3 - Order field functionality
     */
    public function test_stage_order_defaults_to_zero(): void
    {
        $stage = Stage::create([
            'name' => 'Админка',
        ]);

        $this->assertEquals(0, $stage->order);
    }

    /**
     * Test that stages can be associated with projects
     * Validates: Requirement 2.13 - Connection between project and stages
     */
    public function test_stage_can_be_associated_with_projects(): void
    {
        $stage = Stage::create([
            'name' => 'Дизайн',
            'order' => 1,
        ]);

        $project = Project::create([
            'name' => 'Test Project',
            'description' => 'Test Description',
            'type' => 'website',
        ]);

        $project->stages()->attach($stage);

        $this->assertTrue($project->stages->contains($stage));
        $this->assertTrue($stage->projects->contains($project));
    }

    /**
     * Test that multiple stages can be ordered correctly
     * Validates: Requirement 2.3 - Order field for sorting
     */
    public function test_stages_can_be_ordered(): void
    {
        Stage::create(['name' => 'Тестирование', 'order' => 5]);
        Stage::create(['name' => 'Дизайн', 'order' => 1]);
        Stage::create(['name' => 'Бэкенд', 'order' => 2]);
        Stage::create(['name' => 'Фронтенд', 'order' => 3]);

        $stages = Stage::orderBy('order')->get();

        $this->assertEquals('Дизайн', $stages[0]->name);
        $this->assertEquals('Бэкенд', $stages[1]->name);
        $this->assertEquals('Фронтенд', $stages[2]->name);
        $this->assertEquals('Тестирование', $stages[3]->name);
    }
}
