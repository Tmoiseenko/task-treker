<?php

use App\Models\Project;
use App\Models\Stage;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new ReportService();
});

describe('getTaskTimeReport', function () {
    test('returns correct report structure for task with time entries', function () {
        $user1 = User::factory()->create(['name' => 'John Doe', 'hourly_rate' => 50.00]);
        $user2 = User::factory()->create(['name' => 'Jane Smith', 'hourly_rate' => 60.00]);
        
        $project = Project::factory()->create();
        $stage1 = Stage::factory()->create(['name' => 'Backend']);
        $stage2 = Stage::factory()->create(['name' => 'Frontend']);
        $project->stages()->attach([$stage1->id, $stage2->id]);
        
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Test Task',
        ]);
        
        $taskStages = $task->taskStages;
        
        // User 1 работает над Backend - 3 часа
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStages[0]->id,
            'user_id' => $user1->id,
            'hours' => 3.0,
            'cost' => 150.00,
        ]);
        
        // User 2 работает над Frontend - 2.5 часа
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStages[1]->id,
            'user_id' => $user2->id,
            'hours' => 2.5,
            'cost' => 150.00,
        ]);
        
        $report = $this->service->getTaskTimeReport($task);
        
        expect($report)->toHaveKeys(['task_id', 'task_title', 'total_hours', 'total_cost', 'by_user']);
        expect($report['task_id'])->toBe($task->id);
        expect($report['task_title'])->toBe('Test Task');
        expect($report['total_hours'])->toBe(5.5);
        expect($report['total_cost'])->toBe(300.00);
        expect($report['by_user'])->toHaveCount(2);
        
        // Проверяем данные по пользователям
        $user1Data = collect($report['by_user'])->firstWhere('user_id', $user1->id);
        expect($user1Data['user_name'])->toBe('John Doe');
        expect($user1Data['total_hours'])->toBe(3.0);
        expect($user1Data['total_cost'])->toBe(150.00);
        expect($user1Data['stages'])->toHaveCount(1);
        expect($user1Data['stages'][0]['stage_name'])->toBe('Backend');
    });
    
    test('returns empty report for task without time entries', function () {
        $project = Project::factory()->create();
        $stage = Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        
        $report = $this->service->getTaskTimeReport($task);
        
        expect($report['total_hours'])->toBe(0.0);
        expect($report['total_cost'])->toBe(0.0);
        expect($report['by_user'])->toBeEmpty();
    });
    
    test('aggregates multiple entries from same user on same stage', function () {
        $user = User::factory()->create(['hourly_rate' => 50.00]);
        
        $project = Project::factory()->create();
        $stage = Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        $taskStage = $task->taskStages()->first();
        
        // Пользователь добавляет несколько записей времени
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $user->id,
            'hours' => 2.0,
            'cost' => 100.00,
        ]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $user->id,
            'hours' => 1.5,
            'cost' => 75.00,
        ]);
        
        $report = $this->service->getTaskTimeReport($task);
        
        expect($report['by_user'])->toHaveCount(1);
        expect($report['by_user'][0]['total_hours'])->toBe(3.5);
        expect($report['by_user'][0]['total_cost'])->toBe(175.00);
    });
});

describe('getProjectTimeReport', function () {
    test('returns correct report structure for project with multiple tasks', function () {
        $user1 = User::factory()->create(['name' => 'Developer 1', 'hourly_rate' => 50.00]);
        $user2 = User::factory()->create(['name' => 'Developer 2', 'hourly_rate' => 60.00]);
        
        $project = Project::factory()->create(['name' => 'Test Project']);
        $stage = Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task1 = Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Task 1',
        ]);
        
        $task2 = Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Task 2',
        ]);
        
        // User 1 работает над Task 1 - 4 часа
        TimeEntry::factory()->create([
            'task_stage_id' => $task1->taskStages()->first()->id,
            'user_id' => $user1->id,
            'hours' => 4.0,
            'cost' => 200.00,
        ]);
        
        // User 2 работает над Task 2 - 3 часа
        TimeEntry::factory()->create([
            'task_stage_id' => $task2->taskStages()->first()->id,
            'user_id' => $user2->id,
            'hours' => 3.0,
            'cost' => 180.00,
        ]);
        
        $report = $this->service->getProjectTimeReport($project);
        
        expect($report)->toHaveKeys(['project_id', 'project_name', 'total_hours', 'total_cost', 'by_task', 'by_user']);
        expect($report['project_id'])->toBe($project->id);
        expect($report['project_name'])->toBe('Test Project');
        expect($report['total_hours'])->toBe(7.0);
        expect($report['total_cost'])->toBe(380.00);
        expect($report['by_task'])->toHaveCount(2);
        expect($report['by_user'])->toHaveCount(2);
    });
    
    test('does not include time entries from other projects', function () {
        $user = User::factory()->create(['hourly_rate' => 50.00]);
        
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();
        $stage = Stage::factory()->create();
        $project1->stages()->attach($stage);
        $project2->stages()->attach($stage);
        
        $task1 = Task::factory()->create(['project_id' => $project1->id]);
        $task2 = Task::factory()->create(['project_id' => $project2->id]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $task1->taskStages()->first()->id,
            'user_id' => $user->id,
            'hours' => 5.0,
            'cost' => 250.00,
        ]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $task2->taskStages()->first()->id,
            'user_id' => $user->id,
            'hours' => 3.0,
            'cost' => 150.00,
        ]);
        
        $report = $this->service->getProjectTimeReport($project1);
        
        expect($report['total_hours'])->toBe(5.0);
        expect($report['total_cost'])->toBe(250.00);
        expect($report['by_task'])->toHaveCount(1);
    });
});

describe('getUserPaymentReport', function () {
    test('returns correct payment report for user within period', function () {
        $user = User::factory()->create([
            'name' => 'John Developer',
            'hourly_rate' => 50.00,
        ]);
        
        $project1 = Project::factory()->create(['name' => 'Project A']);
        $project2 = Project::factory()->create(['name' => 'Project B']);
        $stage = Stage::factory()->create();
        $project1->stages()->attach($stage);
        $project2->stages()->attach($stage);
        
        $task1 = Task::factory()->create(['project_id' => $project1->id]);
        $task2 = Task::factory()->create(['project_id' => $project2->id]);
        
        // Записи в пределах периода
        TimeEntry::factory()->create([
            'task_stage_id' => $task1->taskStages()->first()->id,
            'user_id' => $user->id,
            'hours' => 4.0,
            'cost' => 200.00,
            'date' => '2024-01-15',
        ]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $task2->taskStages()->first()->id,
            'user_id' => $user->id,
            'hours' => 3.0,
            'cost' => 150.00,
            'date' => '2024-01-20',
        ]);
        
        // Запись вне периода (не должна учитываться)
        TimeEntry::factory()->create([
            'task_stage_id' => $task1->taskStages()->first()->id,
            'user_id' => $user->id,
            'hours' => 2.0,
            'cost' => 100.00,
            'date' => '2024-02-05',
        ]);
        
        $from = Carbon::parse('2024-01-01');
        $to = Carbon::parse('2024-01-31');
        
        $report = $this->service->getUserPaymentReport($user, $from, $to);
        
        expect($report)->toHaveKeys([
            'user_id', 'user_name', 'hourly_rate',
            'period_from', 'period_to',
            'total_hours', 'total_payment',
            'by_project', 'by_date'
        ]);
        
        expect($report['user_id'])->toBe($user->id);
        expect($report['user_name'])->toBe('John Developer');
        expect($report['total_hours'])->toBe(7.0);
        expect($report['total_payment'])->toBe(350.00);
        expect($report['by_project'])->toHaveCount(2);
        expect($report['by_date'])->toHaveCount(2);
    });
    
    test('returns empty report when user has no entries in period', function () {
        $user = User::factory()->create(['hourly_rate' => 50.00]);
        
        $from = Carbon::parse('2024-01-01');
        $to = Carbon::parse('2024-01-31');
        
        $report = $this->service->getUserPaymentReport($user, $from, $to);
        
        expect($report['total_hours'])->toBe(0.0);
        expect($report['total_payment'])->toBe(0.0);
        expect($report['by_project'])->toBeEmpty();
        expect($report['by_date'])->toBeEmpty();
    });
    
    test('groups entries by project correctly', function () {
        $user = User::factory()->create(['hourly_rate' => 50.00]);
        
        $project1 = Project::factory()->create(['name' => 'Project A']);
        $project2 = Project::factory()->create(['name' => 'Project B']);
        $stage = Stage::factory()->create();
        $project1->stages()->attach($stage);
        $project2->stages()->attach($stage);
        
        $task1 = Task::factory()->create(['project_id' => $project1->id]);
        $task2 = Task::factory()->create(['project_id' => $project2->id]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $task1->taskStages()->first()->id,
            'user_id' => $user->id,
            'hours' => 5.0,
            'cost' => 250.00,
            'date' => '2024-01-15',
        ]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $task2->taskStages()->first()->id,
            'user_id' => $user->id,
            'hours' => 3.0,
            'cost' => 150.00,
            'date' => '2024-01-16',
        ]);
        
        $from = Carbon::parse('2024-01-01');
        $to = Carbon::parse('2024-01-31');
        
        $report = $this->service->getUserPaymentReport($user, $from, $to);
        
        $projectA = collect($report['by_project'])->firstWhere('project_name', 'Project A');
        $projectB = collect($report['by_project'])->firstWhere('project_name', 'Project B');
        
        expect($projectA['total_hours'])->toBe(5.0);
        expect($projectA['total_cost'])->toBe(250.00);
        expect($projectB['total_hours'])->toBe(3.0);
        expect($projectB['total_cost'])->toBe(150.00);
    });
});

describe('getTeamPaymentReport', function () {
    test('returns correct team payment report for period', function () {
        $user1 = User::factory()->create(['name' => 'Developer 1', 'hourly_rate' => 50.00]);
        $user2 = User::factory()->create(['name' => 'Developer 2', 'hourly_rate' => 60.00]);
        
        $project = Project::factory()->create(['name' => 'Test Project']);
        $stage = Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $task->taskStages()->first()->id,
            'user_id' => $user1->id,
            'hours' => 5.0,
            'cost' => 250.00,
            'date' => '2024-01-15',
        ]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $task->taskStages()->first()->id,
            'user_id' => $user2->id,
            'hours' => 4.0,
            'cost' => 240.00,
            'date' => '2024-01-16',
        ]);
        
        $from = Carbon::parse('2024-01-01');
        $to = Carbon::parse('2024-01-31');
        
        $report = $this->service->getTeamPaymentReport($from, $to);
        
        expect($report)->toHaveKeys([
            'period_from', 'period_to',
            'total_hours', 'total_payment',
            'by_user', 'by_project'
        ]);
        
        expect($report['total_hours'])->toBe(9.0);
        expect($report['total_payment'])->toBe(490.00);
        expect($report['by_user'])->toHaveCount(2);
        expect($report['by_project'])->toHaveCount(1);
    });
    
    test('includes project breakdown for each user', function () {
        $user = User::factory()->create(['name' => 'Developer', 'hourly_rate' => 50.00]);
        
        $project1 = Project::factory()->create(['name' => 'Project A']);
        $project2 = Project::factory()->create(['name' => 'Project B']);
        $stage = Stage::factory()->create();
        $project1->stages()->attach($stage);
        $project2->stages()->attach($stage);
        
        $task1 = Task::factory()->create(['project_id' => $project1->id]);
        $task2 = Task::factory()->create(['project_id' => $project2->id]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $task1->taskStages()->first()->id,
            'user_id' => $user->id,
            'hours' => 5.0,
            'cost' => 250.00,
            'date' => '2024-01-15',
        ]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $task2->taskStages()->first()->id,
            'user_id' => $user->id,
            'hours' => 3.0,
            'cost' => 150.00,
            'date' => '2024-01-16',
        ]);
        
        $from = Carbon::parse('2024-01-01');
        $to = Carbon::parse('2024-01-31');
        
        $report = $this->service->getTeamPaymentReport($from, $to);
        
        $userData = $report['by_user'][0];
        expect($userData['by_project'])->toHaveCount(2);
        
        $projectA = collect($userData['by_project'])->firstWhere('project_name', 'Project A');
        $projectB = collect($userData['by_project'])->firstWhere('project_name', 'Project B');
        
        expect($projectA['hours'])->toBe(5.0);
        expect($projectB['hours'])->toBe(3.0);
    });
    
    test('filters entries by date range correctly', function () {
        $user = User::factory()->create(['hourly_rate' => 50.00]);
        
        $project = Project::factory()->create();
        $stage = Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        
        // Внутри периода
        TimeEntry::factory()->create([
            'task_stage_id' => $task->taskStages()->first()->id,
            'user_id' => $user->id,
            'hours' => 5.0,
            'cost' => 250.00,
            'date' => '2024-01-15',
        ]);
        
        // Вне периода
        TimeEntry::factory()->create([
            'task_stage_id' => $task->taskStages()->first()->id,
            'user_id' => $user->id,
            'hours' => 3.0,
            'cost' => 150.00,
            'date' => '2024-02-15',
        ]);
        
        $from = Carbon::parse('2024-01-01');
        $to = Carbon::parse('2024-01-31');
        
        $report = $this->service->getTeamPaymentReport($from, $to);
        
        expect($report['total_hours'])->toBe(5.0);
        expect($report['total_payment'])->toBe(250.00);
    });
    
    test('returns empty report when no entries in period', function () {
        $from = Carbon::parse('2024-01-01');
        $to = Carbon::parse('2024-01-31');
        
        $report = $this->service->getTeamPaymentReport($from, $to);
        
        expect($report['total_hours'])->toBe(0.0);
        expect($report['total_payment'])->toBe(0.0);
        expect($report['by_user'])->toBeEmpty();
        expect($report['by_project'])->toBeEmpty();
    });
});

// Integration tests
describe('Integration Tests', function () {
    test('all report methods work together consistently', function () {
        $user1 = User::factory()->create(['hourly_rate' => 50.00]);
        $user2 = User::factory()->create(['hourly_rate' => 60.00]);
        
        $project = Project::factory()->create();
        $stage = Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $task->taskStages()->first()->id,
            'user_id' => $user1->id,
            'hours' => 5.0,
            'cost' => 250.00,
            'date' => '2024-01-15',
        ]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $task->taskStages()->first()->id,
            'user_id' => $user2->id,
            'hours' => 3.0,
            'cost' => 180.00,
            'date' => '2024-01-16',
        ]);
        
        $from = Carbon::parse('2024-01-01');
        $to = Carbon::parse('2024-01-31');
        
        // Получаем все отчеты
        $taskReport = $this->service->getTaskTimeReport($task);
        $projectReport = $this->service->getProjectTimeReport($project);
        $user1Report = $this->service->getUserPaymentReport($user1, $from, $to);
        $teamReport = $this->service->getTeamPaymentReport($from, $to);
        
        // Проверяем консистентность данных
        expect($taskReport['total_hours'])->toBe(8.0);
        expect($projectReport['total_hours'])->toBe(8.0);
        expect($teamReport['total_hours'])->toBe(8.0);
        
        expect($taskReport['total_cost'])->toBe(430.00);
        expect($projectReport['total_cost'])->toBe(430.00);
        expect($teamReport['total_payment'])->toBe(430.00);
        
        expect($user1Report['total_hours'])->toBe(5.0);
        expect($user1Report['total_payment'])->toBe(250.00);
    });
});
