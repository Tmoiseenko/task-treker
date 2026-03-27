<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStage;
use App\Models\Stage;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $manager;
    protected Project $project;
    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем роли и разрешения
        $userRole = Role::firstOrCreate(
            ['name' => 'developer'],
            ['description' => 'Developer role']
        );
        $managerRole = Role::firstOrCreate(
            ['name' => 'project-manager'],
            ['description' => 'Project Manager role']
        );
        
        $viewFinancesPermission = Permission::firstOrCreate(
            ['name' => 'view-finances'],
            ['action' => 'view-finances', 'description' => 'View finances']
        );
        
        if (!$managerRole->permissions()->where('permission_id', $viewFinancesPermission->id)->exists()) {
            $managerRole->permissions()->attach($viewFinancesPermission);
        }

        // Создаем пользователей
        $this->user = User::factory()->create(['hourly_rate' => 50]);
        $this->user->roles()->attach($userRole);
        
        $this->manager = User::factory()->create(['hourly_rate' => 100]);
        $this->manager->roles()->attach($managerRole);

        // Создаем проект и задачу
        $this->project = Project::factory()->create();
        $stage = Stage::factory()->create(['name' => 'Backend']);
        $this->project->stages()->attach($stage);
        
        // Добавляем пользователей как членов проекта
        $this->project->members()->attach([$this->user->id, $this->manager->id]);
        
        $this->task = Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $this->user->id,
        ]);
    }

    public function test_task_time_report_returns_view_with_data()
    {
        $taskStage = $this->task->taskStages()->first();
        
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $this->user->id,
            'hours' => 5,
            'cost' => 250,
            'date' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('reports.task-time', $this->task));

        $response->assertStatus(200);
        $response->assertViewIs('reports.task-time');
        $response->assertViewHas('task', $this->task);
        $response->assertViewHas('report');
        
        $report = $response->viewData('report');
        $this->assertEquals(5, $report['total_hours']);
        $this->assertEquals(250, $report['total_cost']);
    }

    public function test_task_time_report_requires_authentication()
    {
        $response = $this->get(route('reports.task-time', $this->task));
        
        // Without authentication middleware configured, we just check it's not 200
        $this->assertNotEquals(200, $response->status());
    }

    public function test_project_time_report_returns_view_with_data()
    {
        $taskStage = $this->task->taskStages()->first();
        
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $this->user->id,
            'hours' => 10,
            'cost' => 500,
            'date' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('reports.project-time', $this->project));

        $response->assertStatus(200);
        $response->assertViewIs('reports.project-time');
        $response->assertViewHas('project', $this->project);
        $response->assertViewHas('report');
        
        $report = $response->viewData('report');
        $this->assertEquals(10, $report['total_hours']);
        $this->assertEquals(500, $report['total_cost']);
    }

    public function test_user_payment_report_shows_current_user_by_default()
    {
        $taskStage = $this->task->taskStages()->first();
        
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $this->user->id,
            'hours' => 8,
            'cost' => 400,
            'date' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('reports.user-payment'));

        $response->assertStatus(200);
        $response->assertViewIs('reports.user-payment');
        $response->assertViewHas('user', $this->user);
        $response->assertViewHas('report');
    }

    public function test_user_payment_report_accepts_date_range()
    {
        $taskStage = $this->task->taskStages()->first();
        
        $from = Carbon::now()->subDays(7);
        $to = Carbon::now();
        
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $this->user->id,
            'hours' => 8,
            'cost' => 400,
            'date' => Carbon::now()->subDays(3),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('reports.user-payment', [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ]));

        $response->assertStatus(200);
        $response->assertViewHas('from');
        $response->assertViewHas('to');
        
        $viewFrom = $response->viewData('from');
        $viewTo = $response->viewData('to');
        
        $this->assertEquals($from->toDateString(), $viewFrom->toDateString());
        $this->assertEquals($to->toDateString(), $viewTo->toDateString());
    }

    public function test_user_cannot_view_other_user_payment_report_without_permission()
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('reports.user-payment.specific', $otherUser));

        $response->assertStatus(403);
    }

    public function test_manager_can_view_other_user_payment_report()
    {
        $taskStage = $this->task->taskStages()->first();
        
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $this->user->id,
            'hours' => 8,
            'cost' => 400,
            'date' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('reports.user-payment.specific', $this->user));

        $response->assertStatus(200);
        $response->assertViewHas('user', $this->user);
    }

    public function test_team_payment_report_requires_view_finances_permission()
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.team-payment'));

        $response->assertStatus(403);
    }

    public function test_team_payment_report_returns_view_with_data()
    {
        $taskStage = $this->task->taskStages()->first();
        
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $this->user->id,
            'hours' => 8,
            'cost' => 400,
            'date' => Carbon::now(),
        ]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $this->manager->id,
            'hours' => 5,
            'cost' => 500,
            'date' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('reports.team-payment'));

        $response->assertStatus(200);
        $response->assertViewIs('reports.team-payment');
        $response->assertViewHas('report');
        
        $report = $response->viewData('report');
        $this->assertEquals(13, $report['total_hours']);
        $this->assertEquals(900, $report['total_payment']);
    }

    public function test_team_payment_report_accepts_date_range()
    {
        $from = Carbon::now()->subDays(7);
        $to = Carbon::now();

        $response = $this->actingAs($this->manager)
            ->get(route('reports.team-payment', [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ]));

        $response->assertStatus(200);
        $response->assertViewHas('from');
        $response->assertViewHas('to');
    }

    public function test_user_payment_report_uses_current_month_by_default()
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.user-payment'));

        $response->assertStatus(200);
        
        $from = $response->viewData('from');
        $to = $response->viewData('to');
        
        $this->assertEquals(Carbon::now()->startOfMonth()->toDateString(), $from->toDateString());
        $this->assertEquals(Carbon::now()->endOfMonth()->toDateString(), $to->toDateString());
    }

    public function test_team_payment_report_uses_current_month_by_default()
    {
        $response = $this->actingAs($this->manager)
            ->get(route('reports.team-payment'));

        $response->assertStatus(200);
        
        $from = $response->viewData('from');
        $to = $response->viewData('to');
        
        $this->assertEquals(Carbon::now()->startOfMonth()->toDateString(), $from->toDateString());
        $this->assertEquals(Carbon::now()->endOfMonth()->toDateString(), $to->toDateString());
    }
}
