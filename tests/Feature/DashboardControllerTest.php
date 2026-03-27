<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::factory()->create(['name' => 'admin']);
        Role::factory()->create(['name' => 'project-manager']);
        Role::factory()->create(['name' => 'developer']);
        Role::factory()->create(['name' => 'designer']);
        Role::factory()->create(['name' => 'tester']);
    }

    public function test_dashboard_displays_active_projects_count(): void
    {
        $user = User::factory()->create();
        
        // Create active and inactive projects
        Project::factory()->count(3)->create(['status' => ProjectStatus::ACTIVE]);
        Project::factory()->count(2)->create(['status' => ProjectStatus::COMPLETED]);
        
        $response = $this->actingAs($user)->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertViewHas('statistics', function ($statistics) {
            return $statistics['active_projects_count'] === 3;
        });
    }

    public function test_dashboard_displays_tasks_by_status(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        // Create tasks with different statuses
        Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::TODO]);
        Task::factory()->count(2)->create(['project_id' => $project->id, 'status' => TaskStatus::IN_PROGRESS]);
        Task::factory()->count(3)->create(['project_id' => $project->id, 'status' => TaskStatus::DONE]);
        
        $response = $this->actingAs($user)->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertViewHas('statistics', function ($statistics) {
            return $statistics['tasks_by_status']['todo'] === 1
                && $statistics['tasks_by_status']['in_progress'] === 2
                && $statistics['tasks_by_status']['done'] === 3;
        });
    }

    public function test_dashboard_displays_tasks_by_priority(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        // Create tasks with different priorities
        Task::factory()->count(2)->create(['project_id' => $project->id, 'priority' => TaskPriority::HIGH]);
        Task::factory()->count(3)->create(['project_id' => $project->id, 'priority' => TaskPriority::MEDIUM]);
        Task::factory()->create(['project_id' => $project->id, 'priority' => TaskPriority::LOW]);
        
        $response = $this->actingAs($user)->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertViewHas('statistics', function ($statistics) {
            return $statistics['tasks_by_priority']['high'] === 2
                && $statistics['tasks_by_priority']['medium'] === 3
                && $statistics['tasks_by_priority']['low'] === 1;
        });
    }

    public function test_dashboard_displays_top_specialists(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        // Create specialists with completed tasks
        $specialist1 = User::factory()->create(['name' => 'John Doe']);
        $specialist2 = User::factory()->create(['name' => 'Jane Smith']);
        
        Task::factory()->count(5)->create([
            'project_id' => $project->id,
            'assignee_id' => $specialist1->id,
            'status' => TaskStatus::DONE
        ]);
        
        Task::factory()->count(3)->create([
            'project_id' => $project->id,
            'assignee_id' => $specialist2->id,
            'status' => TaskStatus::DONE
        ]);
        
        $response = $this->actingAs($user)->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertViewHas('statistics', function ($statistics) use ($specialist1, $specialist2) {
            return count($statistics['top_specialists']) === 2
                && $statistics['top_specialists'][0]['id'] === $specialist1->id
                && $statistics['top_specialists'][0]['completed_tasks'] === 5
                && $statistics['top_specialists'][1]['id'] === $specialist2->id
                && $statistics['top_specialists'][1]['completed_tasks'] === 3;
        });
    }

    public function test_dashboard_displays_current_month_hours(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        // Add stages to project
        $stage = \App\Models\Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        $taskStage = $task->taskStages()->first();
        
        // Create time entries for current month
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $user->id,
            'hours' => 10.5,
            'date' => Carbon::now()->startOfMonth()->addDays(5),
            'cost' => 525.00
        ]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $user->id,
            'hours' => 8.0,
            'date' => Carbon::now()->startOfMonth()->addDays(10),
            'cost' => 400.00
        ]);
        
        // Create time entry for previous month (should not be counted)
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $user->id,
            'hours' => 5.0,
            'date' => Carbon::now()->subMonth(),
            'cost' => 250.00
        ]);
        
        $response = $this->actingAs($user)->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertViewHas('statistics', function ($statistics) {
            return $statistics['current_month_hours'] === 18.5;
        });
    }

    public function test_dashboard_displays_current_month_payments(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        // Add stages to project
        $stage = \App\Models\Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        $taskStage = $task->taskStages()->first();
        
        // Create time entries for current month
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $user->id,
            'hours' => 10.0,
            'date' => Carbon::now()->startOfMonth()->addDays(5),
            'cost' => 500.00
        ]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $user->id,
            'hours' => 8.0,
            'date' => Carbon::now()->startOfMonth()->addDays(10),
            'cost' => 400.00
        ]);
        
        $response = $this->actingAs($user)->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertViewHas('statistics', function ($statistics) {
            return $statistics['current_month_payments'] === 900.00;
        });
    }

    public function test_dashboard_displays_personal_statistics_for_specialists(): void
    {
        $developerRole = Role::where('name', 'developer')->first();
        $specialist = User::factory()->create(['hourly_rate' => 50.00]);
        $specialist->roles()->attach($developerRole);
        
        $project = Project::factory()->create();
        
        // Add stages to project
        $stage = \App\Models\Stage::factory()->create();
        $project->stages()->attach($stage);
        
        // Create tasks for specialist
        Task::factory()->count(2)->create([
            'project_id' => $project->id,
            'assignee_id' => $specialist->id,
            'status' => TaskStatus::DONE
        ]);
        
        Task::factory()->count(3)->create([
            'project_id' => $project->id,
            'assignee_id' => $specialist->id,
            'status' => TaskStatus::IN_PROGRESS
        ]);
        
        Task::factory()->create([
            'project_id' => $project->id,
            'assignee_id' => $specialist->id,
            'status' => TaskStatus::TODO
        ]);
        
        // Create time entries for current month
        $task = Task::where('assignee_id', $specialist->id)->first();
        $taskStage = $task->taskStages()->first();
        
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $specialist->id,
            'hours' => 15.0,
            'date' => Carbon::now()->startOfMonth()->addDays(5),
            'cost' => 750.00
        ]);
        
        $response = $this->actingAs($specialist)->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertViewHas('statistics', function ($statistics) {
            return isset($statistics['personal_stats'])
                && $statistics['personal_stats']['assigned_tasks'] === 6
                && $statistics['personal_stats']['completed_tasks'] === 2
                && $statistics['personal_stats']['in_progress_tasks'] === 3
                && $statistics['personal_stats']['monthly_hours'] === 15.0
                && $statistics['personal_stats']['monthly_payment'] === 750.00;
        });
    }

    public function test_dashboard_does_not_display_personal_statistics_for_non_specialists(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);
        
        $response = $this->actingAs($admin)->get(route('dashboard'));
        
        $response->assertStatus(200);
        $response->assertViewHas('statistics', function ($statistics) {
            return !isset($statistics['personal_stats']);
        });
    }
}
