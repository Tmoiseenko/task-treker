<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\AuditLog;
use App\Models\Task;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->auditService = new AuditService();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('logChange', function () {
    test('creates audit log entry with all required fields', function () {
        $task = Task::factory()->create();
        
        $auditLog = $this->auditService->logChange(
            $task,
            'title',
            'Old Title',
            'New Title'
        );
        
        expect($auditLog)->toBeInstanceOf(AuditLog::class)
            ->and($auditLog->task_id)->toBe($task->id)
            ->and($auditLog->user_id)->toBe($this->user->id)
            ->and($auditLog->field)->toBe('title')
            ->and($auditLog->old_value)->toBe('Old Title')
            ->and($auditLog->new_value)->toBe('New Title');
    });

    test('logs change with null old value', function () {
        $task = Task::factory()->create();
        
        $auditLog = $this->auditService->logChange(
            $task,
            'assignee_id',
            null,
            5
        );
        
        expect($auditLog->old_value)->toBeNull()
            ->and($auditLog->new_value)->toBe(5);
    });

    test('logs change with null new value', function () {
        $task = Task::factory()->create();
        
        $auditLog = $this->auditService->logChange(
            $task,
            'assignee_id',
            5,
            null
        );
        
        expect($auditLog->old_value)->toBe(5)
            ->and($auditLog->new_value)->toBeNull();
    });

    test('converts enum old value to string', function () {
        $task = Task::factory()->create();
        
        $auditLog = $this->auditService->logChange(
            $task,
            'status',
            TaskStatus::TODO,
            TaskStatus::IN_PROGRESS
        );
        
        expect($auditLog->old_value)->toBe('todo')
            ->and($auditLog->new_value)->toBe('in_progress');
    });

    test('converts enum new value to string', function () {
        $task = Task::factory()->create();
        
        $auditLog = $this->auditService->logChange(
            $task,
            'priority',
            TaskPriority::LOW,
            TaskPriority::HIGH
        );
        
        expect($auditLog->old_value)->toBe('low')
            ->and($auditLog->new_value)->toBe('high');
    });

    test('handles mixed enum and string values', function () {
        $task = Task::factory()->create();
        
        $auditLog = $this->auditService->logChange(
            $task,
            'status',
            'todo',
            TaskStatus::IN_PROGRESS
        );
        
        expect($auditLog->old_value)->toBe('todo')
            ->and($auditLog->new_value)->toBe('in_progress');
    });

    test('records timestamp automatically', function () {
        $task = Task::factory()->create();
        
        $auditLog = $this->auditService->logChange(
            $task,
            'title',
            'Old',
            'New'
        );
        
        expect($auditLog->created_at)->not->toBeNull()
            ->and($auditLog->created_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    });

    test('logs multiple changes to same task', function () {
        $task = Task::factory()->create();
        
        $this->auditService->logChange($task, 'title', 'Old Title', 'New Title');
        $this->auditService->logChange($task, 'status', TaskStatus::TODO, TaskStatus::IN_PROGRESS);
        $this->auditService->logChange($task, 'priority', TaskPriority::LOW, TaskPriority::HIGH);
        
        expect($task->auditLogs()->count())->toBe(3);
    });

    test('logs changes by different users', function () {
        $task = Task::factory()->create();
        $user2 = User::factory()->create();
        
        $auditLog1 = $this->auditService->logChange($task, 'title', 'Old', 'New');
        
        $this->actingAs($user2);
        $auditLog2 = $this->auditService->logChange($task, 'description', 'Old Desc', 'New Desc');
        
        expect($auditLog1->user_id)->toBe($this->user->id)
            ->and($auditLog2->user_id)->toBe($user2->id);
    });

    test('handles numeric values', function () {
        $task = Task::factory()->create();
        
        $auditLog = $this->auditService->logChange(
            $task,
            'assignee_id',
            1,
            2
        );
        
        expect($auditLog->old_value)->toBe(1)
            ->and($auditLog->new_value)->toBe(2);
    });

    test('handles boolean values', function () {
        $task = Task::factory()->create();
        
        $auditLog = $this->auditService->logChange(
            $task,
            'is_archived',
            false,
            true
        );
        
        expect($auditLog->old_value)->toBe(false)
            ->and($auditLog->new_value)->toBe(true);
    });
});

describe('getTaskHistory', function () {
    test('returns empty collection for task with no history', function () {
        $task = Task::factory()->create();
        
        $history = $this->auditService->getTaskHistory($task);
        
        expect($history)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
            ->and($history)->toHaveCount(0);
    });

    test('returns all audit logs for a task', function () {
        $task = Task::factory()->create();
        
        $this->auditService->logChange($task, 'title', 'Old', 'New');
        $this->auditService->logChange($task, 'status', TaskStatus::TODO, TaskStatus::IN_PROGRESS);
        $this->auditService->logChange($task, 'priority', TaskPriority::LOW, TaskPriority::HIGH);
        
        $history = $this->auditService->getTaskHistory($task);
        
        expect($history)->toHaveCount(3);
    });

    test('returns history ordered by created_at descending', function () {
        $task = Task::factory()->create();
        
        // Create logs with slight delays to ensure different timestamps
        $log1 = $this->auditService->logChange($task, 'title', 'Old', 'New');
        sleep(1);
        $log2 = $this->auditService->logChange($task, 'status', TaskStatus::TODO, TaskStatus::IN_PROGRESS);
        sleep(1);
        $log3 = $this->auditService->logChange($task, 'priority', TaskPriority::LOW, TaskPriority::HIGH);
        
        $history = $this->auditService->getTaskHistory($task);
        
        expect($history->first()->id)->toBe($log3->id)
            ->and($history->last()->id)->toBe($log1->id);
    });

    test('eager loads user relationship', function () {
        $task = Task::factory()->create();
        $this->auditService->logChange($task, 'title', 'Old', 'New');
        
        $history = $this->auditService->getTaskHistory($task);
        
        expect($history->first()->relationLoaded('user'))->toBeTrue()
            ->and($history->first()->user)->toBeInstanceOf(User::class)
            ->and($history->first()->user->id)->toBe($this->user->id);
    });

    test('returns only logs for specified task', function () {
        $task1 = Task::factory()->create();
        $task2 = Task::factory()->create();
        
        $this->auditService->logChange($task1, 'title', 'Old1', 'New1');
        $this->auditService->logChange($task1, 'status', TaskStatus::TODO, TaskStatus::IN_PROGRESS);
        $this->auditService->logChange($task2, 'title', 'Old2', 'New2');
        
        $history = $this->auditService->getTaskHistory($task1);
        
        expect($history)->toHaveCount(2)
            ->and($history->every(fn($log) => $log->task_id === $task1->id))->toBeTrue();
    });

    test('includes all audit log fields', function () {
        $task = Task::factory()->create();
        $this->auditService->logChange($task, 'title', 'Old Title', 'New Title');
        
        $history = $this->auditService->getTaskHistory($task);
        $log = $history->first();
        
        expect($log->task_id)->toBe($task->id)
            ->and($log->user_id)->toBe($this->user->id)
            ->and($log->field)->toBe('title')
            ->and($log->old_value)->toBe('Old Title')
            ->and($log->new_value)->toBe('New Title')
            ->and($log->created_at)->not->toBeNull();
    });

    test('handles task with many audit logs', function () {
        $task = Task::factory()->create();
        
        // Create 50 audit logs
        for ($i = 0; $i < 50; $i++) {
            $this->auditService->logChange($task, 'title', "Old $i", "New $i");
        }
        
        $history = $this->auditService->getTaskHistory($task);
        
        expect($history)->toHaveCount(50);
    });

    test('returns fresh data from database', function () {
        $task = Task::factory()->create();
        
        $this->auditService->logChange($task, 'title', 'Old', 'New');
        $history1 = $this->auditService->getTaskHistory($task);
        
        $this->auditService->logChange($task, 'status', TaskStatus::TODO, TaskStatus::IN_PROGRESS);
        $history2 = $this->auditService->getTaskHistory($task);
        
        expect($history1)->toHaveCount(1)
            ->and($history2)->toHaveCount(2);
    });
});

describe('integration with Task model', function () {
    test('audit logs are accessible through task relationship', function () {
        $task = Task::factory()->create();
        $this->auditService->logChange($task, 'title', 'Old', 'New');
        
        expect($task->auditLogs)->toHaveCount(1)
            ->and($task->auditLogs->first()->field)->toBe('title');
    });

    test('audit logs are deleted when task is deleted', function () {
        $task = Task::factory()->create();
        $this->auditService->logChange($task, 'title', 'Old', 'New');
        $this->auditService->logChange($task, 'status', TaskStatus::TODO, TaskStatus::IN_PROGRESS);
        
        $taskId = $task->id;
        $task->delete();
        
        expect(AuditLog::where('task_id', $taskId)->count())->toBe(0);
    });
});

