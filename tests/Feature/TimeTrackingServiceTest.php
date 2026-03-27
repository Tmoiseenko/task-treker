<?php

use App\Models\Project;
use App\Models\Stage;
use App\Models\Task;
use App\Models\TaskStage;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\TimeTrackingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new TimeTrackingService();
});

describe('startTimer', function () {
    test('starts timer and stores data in cache', function () {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $stage = Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        $taskStage = $task->taskStages()->first();
        
        $this->service->startTimer($taskStage, $user);
        
        expect($this->service->isTimerRunning($taskStage, $user))->toBeTrue();
        
        $timerData = $this->service->getTimerData($taskStage, $user);
        expect($timerData)->toHaveKeys(['started_at', 'task_stage_id', 'user_id']);
        expect($timerData['task_stage_id'])->toBe($taskStage->id);
        expect($timerData['user_id'])->toBe($user->id);
    });
});

describe('stopTimer', function () {
    test('stops timer and creates time entry', function () {
        $user = User::factory()->create(['hourly_rate' => 50.00]);
        $project = Project::factory()->create();
        $stage = Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        $taskStage = $task->taskStages()->first();
        
        // Запускаем таймер в прошлом (симулируем работу в течение 2 часов)
        $cacheKey = "timer:task_stage_{$taskStage->id}:user_{$user->id}";
        Cache::put($cacheKey, [
            'started_at' => now()->subHours(2)->toIso8601String(),
            'task_stage_id' => $taskStage->id,
            'user_id' => $user->id,
        ], now()->addDays(7));
        
        // Останавливаем таймер
        $timeEntry = $this->service->stopTimer($taskStage, $user);
        
        expect($timeEntry)->toBeInstanceOf(TimeEntry::class);
        expect($timeEntry->user_id)->toBe($user->id);
        expect($timeEntry->task_stage_id)->toBe($taskStage->id);
        expect((float) $timeEntry->hours)->toBeGreaterThan(1.9); // Примерно 2 часа
        expect((float) $timeEntry->hours)->toBeLessThan(2.1);
        expect((float) $timeEntry->cost)->toBeGreaterThan(0);
        
        // Таймер должен быть удален из кэша
        expect($this->service->isTimerRunning($taskStage, $user))->toBeFalse();
    });
    
    test('throws exception when timer not found', function () {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $stage = Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        $taskStage = $task->taskStages()->first();
        
        // Пытаемся остановить таймер без запуска
        $this->service->stopTimer($taskStage, $user);
    })->throws(RuntimeException::class, 'Timer not found or already stopped');
});

describe('addManualEntry', function () {
    test('creates time entry with calculated cost', function () {
        $user = User::factory()->create(['hourly_rate' => 50.00]);
        $project = Project::factory()->create();
        $stage = Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        $taskStage = $task->taskStages()->first();
        
        $timeEntry = $this->service->addManualEntry([
            'task_stage_id' => $taskStage->id,
            'user_id' => $user->id,
            'hours' => 5.5,
            'date' => '2024-01-15',
            'description' => 'Manual work entry',
        ]);
        
        expect((float) $timeEntry->hours)->toBe(5.5);
        expect((float) $timeEntry->cost)->toBe(275.00); // 5.5 * 50
        expect($timeEntry->date->format('Y-m-d'))->toBe('2024-01-15');
        expect($timeEntry->description)->toBe('Manual work entry');
    });
    
    test('uses current date when date not provided', function () {
        $user = User::factory()->create(['hourly_rate' => 50.00]);
        $project = Project::factory()->create();
        $stage = Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        $taskStage = $task->taskStages()->first();
        
        $timeEntry = $this->service->addManualEntry([
            'task_stage_id' => $taskStage->id,
            'user_id' => $user->id,
            'hours' => 3.0,
        ]);
        
        expect($timeEntry->date->format('Y-m-d'))->toBe(now()->format('Y-m-d'));
    });
});

describe('calculateCost', function () {
    test('calculates cost correctly', function () {
        $user = User::factory()->create(['hourly_rate' => 75.50]);
        $project = Project::factory()->create();
        $stage = Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        $taskStage = $task->taskStages()->first();
        
        $timeEntry = TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $user->id,
            'hours' => 4.25,
            'cost' => 0, // Will be recalculated
        ]);
        
        $cost = $this->service->calculateCost($timeEntry);
        
        expect($cost)->toBe(320.88); // 4.25 * 75.50 = 320.875, rounded to 320.88
    });
});

describe('getTaskTotalHours', function () {
    test('returns total hours for task across all stages', function () {
        $user1 = User::factory()->create(['hourly_rate' => 50.00]);
        $user2 = User::factory()->create(['hourly_rate' => 60.00]);
        
        $project = Project::factory()->create();
        $stage1 = Stage::factory()->create();
        $stage2 = Stage::factory()->create();
        $project->stages()->attach([$stage1->id, $stage2->id]);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        $taskStages = $task->taskStages;
        
        // Добавляем записи времени для разных этапов
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStages[0]->id,
            'user_id' => $user1->id,
            'hours' => 3.5,
        ]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStages[1]->id,
            'user_id' => $user2->id,
            'hours' => 2.25,
        ]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStages[0]->id,
            'user_id' => $user1->id,
            'hours' => 1.75,
        ]);
        
        $totalHours = $this->service->getTaskTotalHours($task);
        
        expect($totalHours)->toBe(7.5); // 3.5 + 2.25 + 1.75
    });
    
    test('returns zero when task has no time entries', function () {
        $project = Project::factory()->create();
        $stage = Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        
        $totalHours = $this->service->getTaskTotalHours($task);
        
        expect($totalHours)->toBe(0.0);
    });
});

describe('getProjectTotalHours', function () {
    test('returns total hours for project across all tasks', function () {
        $user = User::factory()->create(['hourly_rate' => 50.00]);
        
        $project = Project::factory()->create();
        $stage = Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task1 = Task::factory()->create(['project_id' => $project->id]);
        $task2 = Task::factory()->create(['project_id' => $project->id]);
        
        // Добавляем записи времени для разных задач
        TimeEntry::factory()->create([
            'task_stage_id' => $task1->taskStages()->first()->id,
            'user_id' => $user->id,
            'hours' => 5.0,
        ]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $task2->taskStages()->first()->id,
            'user_id' => $user->id,
            'hours' => 3.5,
        ]);
        
        $totalHours = $this->service->getProjectTotalHours($project);
        
        expect($totalHours)->toBe(8.5);
    });
    
    test('does not include hours from other projects', function () {
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
        ]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $task2->taskStages()->first()->id,
            'user_id' => $user->id,
            'hours' => 3.0,
        ]);
        
        $totalHours = $this->service->getProjectTotalHours($project1);
        
        expect($totalHours)->toBe(5.0);
    });
});

describe('getUserTotalHours', function () {
    test('returns total hours for user without date filter', function () {
        $user = User::factory()->create(['hourly_rate' => 50.00]);
        $project = Project::factory()->create();
        $stage = Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        $taskStage = $task->taskStages()->first();
        
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $user->id,
            'hours' => 4.0,
            'date' => '2024-01-10',
        ]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $user->id,
            'hours' => 3.5,
            'date' => '2024-01-15',
        ]);
        
        $totalHours = $this->service->getUserTotalHours($user);
        
        expect($totalHours)->toBe(7.5);
    });
    
    test('returns total hours for user within date range', function () {
        $user = User::factory()->create(['hourly_rate' => 50.00]);
        $project = Project::factory()->create();
        $stage = Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        $taskStage = $task->taskStages()->first();
        
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $user->id,
            'hours' => 4.0,
            'date' => '2024-01-05',
        ]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $user->id,
            'hours' => 3.5,
            'date' => '2024-01-15',
        ]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $user->id,
            'hours' => 2.0,
            'date' => '2024-01-25',
        ]);
        
        $from = Carbon::parse('2024-01-10');
        $to = Carbon::parse('2024-01-20');
        
        $totalHours = $this->service->getUserTotalHours($user, $from, $to);
        
        expect($totalHours)->toBe(3.5); // Only the entry on 2024-01-15
    });
    
    test('returns total hours for user from specific date', function () {
        $user = User::factory()->create(['hourly_rate' => 50.00]);
        $project = Project::factory()->create();
        $stage = Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        $taskStage = $task->taskStages()->first();
        
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $user->id,
            'hours' => 4.0,
            'date' => '2024-01-05',
        ]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $user->id,
            'hours' => 3.5,
            'date' => '2024-01-15',
        ]);
        
        $from = Carbon::parse('2024-01-10');
        
        $totalHours = $this->service->getUserTotalHours($user, $from);
        
        expect($totalHours)->toBe(3.5);
    });
    
    test('does not include hours from other users', function () {
        $user1 = User::factory()->create(['hourly_rate' => 50.00]);
        $user2 = User::factory()->create(['hourly_rate' => 60.00]);
        
        $project = Project::factory()->create();
        $stage = Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        $taskStage = $task->taskStages()->first();
        
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $user1->id,
            'hours' => 5.0,
        ]);
        
        TimeEntry::factory()->create([
            'task_stage_id' => $taskStage->id,
            'user_id' => $user2->id,
            'hours' => 3.0,
        ]);
        
        $totalHours = $this->service->getUserTotalHours($user1);
        
        expect($totalHours)->toBe(5.0);
    });
});

describe('isTimerRunning', function () {
    test('returns true when timer is running', function () {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $stage = Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        $taskStage = $task->taskStages()->first();
        
        $this->service->startTimer($taskStage, $user);
        
        expect($this->service->isTimerRunning($taskStage, $user))->toBeTrue();
    });
    
    test('returns false when timer is not running', function () {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $stage = Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        $taskStage = $task->taskStages()->first();
        
        expect($this->service->isTimerRunning($taskStage, $user))->toBeFalse();
    });
});

// Property-based tests
describe('Property Tests', function () {
    test('Property 13: time entry cost equals hours times rate', function () {
        // Feature: project-management-system, Property 13
        // Validates: Requirements 6.2
        
        $user = User::factory()->create(['hourly_rate' => fake()->randomFloat(2, 10, 200)]);
        $project = Project::factory()->create();
        $stage = Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        $taskStage = $task->taskStages()->first();
        
        $hours = fake()->randomFloat(2, 0.5, 10);
        
        $timeEntry = $this->service->addManualEntry([
            'task_stage_id' => $taskStage->id,
            'user_id' => $user->id,
            'hours' => $hours,
            'date' => now()->toDateString(),
        ]);
        
        $expectedCost = round($hours * $user->hourly_rate, 2);
        
        // Convert to float for comparison since DB might return string
        expect((float) $timeEntry->cost)->toBe($expectedCost);
    })->repeat(100);
    
    test('Property 12: task total hours equals sum of all stage hours', function () {
        // Feature: project-management-system, Property 12
        // Validates: Requirements 5.6
        
        $user = User::factory()->create(['hourly_rate' => 50.00]);
        $project = Project::factory()->create();
        
        // Создаем несколько этапов
        $stageCount = fake()->numberBetween(2, 5);
        $stages = Stage::factory()->count($stageCount)->create();
        $project->stages()->attach($stages->pluck('id'));
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        $taskStages = $task->taskStages;
        
        $expectedTotal = 0;
        
        // Добавляем случайные записи времени для каждого этапа
        foreach ($taskStages as $taskStage) {
            $entryCount = fake()->numberBetween(1, 3);
            for ($i = 0; $i < $entryCount; $i++) {
                $hours = fake()->randomFloat(2, 0.5, 8);
                $expectedTotal += $hours;
                
                TimeEntry::factory()->create([
                    'task_stage_id' => $taskStage->id,
                    'user_id' => $user->id,
                    'hours' => $hours,
                ]);
            }
        }
        
        $actualTotal = $this->service->getTaskTotalHours($task);
        
        // Используем небольшую погрешность из-за округления float
        expect(abs($actualTotal - $expectedTotal))->toBeLessThan(0.01);
    })->repeat(50);
});
