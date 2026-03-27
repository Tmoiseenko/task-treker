<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Project;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем базовые этапы из сидера
        Stage::factory()->create(['name' => 'Дизайн', 'order' => 1]);
        Stage::factory()->create(['name' => 'Бэкенд', 'order' => 2]);
        Stage::factory()->create(['name' => 'Админка', 'order' => 3]);
        Stage::factory()->create(['name' => 'Фронтенд', 'order' => 4]);
        Stage::factory()->create(['name' => 'Мобилка', 'order' => 5]);
        Stage::factory()->create(['name' => 'Тестирование', 'order' => 6]);
    }

    public function test_project_can_be_created_with_stages_and_members(): void
    {
        $users = User::factory()->count(3)->create();
        $stages = Stage::whereIn('name', ['Дизайн', 'Бэкенд', 'Тестирование'])->get();

        $project = Project::factory()->create([
            'name' => 'Telegram Bot Project',
            'type' => ProjectType::TELEGRAM_BOT,
            'status' => ProjectStatus::ACTIVE,
        ]);

        // Прикрепляем этапы
        $project->stages()->attach($stages->pluck('id'));
        
        // Прикрепляем участников
        $project->members()->attach($users->pluck('id'));

        // Проверяем, что проект создан
        $this->assertDatabaseHas('projects', [
            'name' => 'Telegram Bot Project',
            'type' => ProjectType::TELEGRAM_BOT->value,
        ]);

        // Проверяем связи
        $this->assertCount(3, $project->fresh()->stages);
        $this->assertCount(3, $project->fresh()->members);
    }


    public function test_project_stages_match_project_type_defaults(): void
    {
        // Тест для мобильного приложения
        $mobileProject = Project::factory()->create(['type' => ProjectType::MOBILE_APP]);
        $mobileStages = Stage::whereIn('name', ProjectType::MOBILE_APP->getDefaultStages())->get();
        $mobileProject->stages()->attach($mobileStages->pluck('id'));

        $this->assertCount(4, $mobileProject->fresh()->stages);
        $this->assertTrue($mobileProject->stages->pluck('name')->contains('Дизайн'));
        $this->assertTrue($mobileProject->stages->pluck('name')->contains('Мобилка'));

        // Тест для веб-сайта
        $websiteProject = Project::factory()->create(['type' => ProjectType::WEBSITE]);
        $websiteStages = Stage::whereIn('name', ProjectType::WEBSITE->getDefaultStages())->get();
        $websiteProject->stages()->attach($websiteStages->pluck('id'));

        $this->assertCount(5, $websiteProject->fresh()->stages);
        $this->assertTrue($websiteProject->stages->pluck('name')->contains('Фронтенд'));
        $this->assertTrue($websiteProject->stages->pluck('name')->contains('Админка'));
    }

    public function test_project_can_have_multiple_members_with_different_roles(): void
    {
        $project = Project::factory()->create();
        
        $manager = User::factory()->create(['name' => 'Project Manager']);
        $developer = User::factory()->create(['name' => 'Developer']);
        $designer = User::factory()->create(['name' => 'Designer']);

        $project->members()->attach([$manager->id, $developer->id, $designer->id]);

        $this->assertCount(3, $project->members);
        $this->assertTrue($project->members->pluck('name')->contains('Project Manager'));
        $this->assertTrue($project->members->pluck('name')->contains('Developer'));
        $this->assertTrue($project->members->pluck('name')->contains('Designer'));
    }

    public function test_project_stages_can_be_updated(): void
    {
        $project = Project::factory()->create(['type' => ProjectType::TELEGRAM_BOT]);
        
        // Изначально добавляем 3 этапа
        $initialStages = Stage::whereIn('name', ['Дизайн', 'Бэкенд', 'Тестирование'])->get();
        $project->stages()->attach($initialStages->pluck('id'));
        
        $this->assertCount(3, $project->fresh()->stages);

        // Добавляем еще один этап
        $additionalStage = Stage::where('name', 'Фронтенд')->first();
        $project->stages()->attach($additionalStage->id);

        $this->assertCount(4, $project->fresh()->stages);
    }

    public function test_project_members_can_be_updated(): void
    {
        $project = Project::factory()->create();
        $initialUsers = User::factory()->count(2)->create();
        
        $project->members()->attach($initialUsers->pluck('id'));
        $this->assertCount(2, $project->fresh()->members);

        // Добавляем нового участника
        $newUser = User::factory()->create();
        $project->members()->attach($newUser->id);

        $this->assertCount(3, $project->fresh()->members);
    }

    public function test_deleting_project_does_not_delete_stages_or_members(): void
    {
        $project = Project::factory()->create();
        $stages = Stage::take(2)->get();
        $users = User::factory()->count(2)->create();

        $project->stages()->attach($stages->pluck('id'));
        $project->members()->attach($users->pluck('id'));

        $stageIds = $stages->pluck('id')->toArray();
        $userIds = $users->pluck('id')->toArray();

        // Удаляем проект
        $project->delete();

        // Проверяем, что этапы и пользователи остались
        foreach ($stageIds as $stageId) {
            $this->assertDatabaseHas('stages', ['id' => $stageId]);
        }

        foreach ($userIds as $userId) {
            $this->assertDatabaseHas('users', ['id' => $userId]);
        }
    }
}
