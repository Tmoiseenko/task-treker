<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Project;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем базовые этапы
        Stage::factory()->create(['name' => 'Дизайн']);
        Stage::factory()->create(['name' => 'Бэкенд']);
        Stage::factory()->create(['name' => 'Админка']);
        Stage::factory()->create(['name' => 'Фронтенд']);
        Stage::factory()->create(['name' => 'Мобилка']);
        Stage::factory()->create(['name' => 'Тестирование']);
    }

    public function test_project_can_be_created_with_basic_fields(): void
    {
        $project = Project::factory()->create([
            'name' => 'Test Project',
            'description' => 'Test Description',
            'type' => ProjectType::WEBSITE,
            'status' => ProjectStatus::ACTIVE,
        ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'description' => 'Test Description',
            'type' => ProjectType::WEBSITE->value,
            'status' => ProjectStatus::ACTIVE->value,
        ]);
    }

    public function test_project_can_have_stages_attached(): void
    {
        $project = Project::factory()->create();
        $stages = Stage::take(3)->get();

        $project->stages()->attach($stages->pluck('id'));

        $this->assertCount(3, $project->stages);
    }


    public function test_project_can_have_members_attached(): void
    {
        $project = Project::factory()->create();
        $users = User::factory()->count(3)->create();

        $project->members()->attach($users->pluck('id'));

        $this->assertCount(3, $project->members);
    }

    public function test_project_type_returns_correct_default_stages(): void
    {
        $mobileAppStages = ProjectType::MOBILE_APP->getDefaultStages();
        $this->assertEquals(['Дизайн', 'Бэкенд', 'Мобилка', 'Тестирование'], $mobileAppStages);

        $websiteStages = ProjectType::WEBSITE->getDefaultStages();
        $this->assertEquals(['Дизайн', 'Бэкенд', 'Админка', 'Фронтенд', 'Тестирование'], $websiteStages);

        $telegramBotStages = ProjectType::TELEGRAM_BOT->getDefaultStages();
        $this->assertEquals(['Дизайн', 'Бэкенд', 'Тестирование'], $telegramBotStages);

        $crmStages = ProjectType::CRM_SYSTEM->getDefaultStages();
        $this->assertEquals(['Дизайн', 'Бэкенд', 'Админка', 'Фронтенд', 'Тестирование'], $crmStages);
    }

    public function test_project_stages_can_be_retrieved_by_default_stage_names(): void
    {
        $project = Project::factory()->create(['type' => ProjectType::MOBILE_APP]);
        
        $defaultStageNames = ProjectType::MOBILE_APP->getDefaultStages();
        $stages = Stage::whereIn('name', $defaultStageNames)->get();

        $this->assertCount(4, $stages);
        $this->assertTrue($stages->pluck('name')->contains('Дизайн'));
        $this->assertTrue($stages->pluck('name')->contains('Бэкенд'));
        $this->assertTrue($stages->pluck('name')->contains('Мобилка'));
        $this->assertTrue($stages->pluck('name')->contains('Тестирование'));
    }

    public function test_project_relationships_are_defined(): void
    {
        $project = Project::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $project->tasks());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $project->stages());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $project->members());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $project->documents());
    }
}
