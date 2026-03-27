<?php

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('task observer uses audit service to log status changes', function () {
    // Requirements 7.4: WHEN pользователь изменяет статус задачи, THE Система SHALL записать изменение статуса в аудит-лог
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $this->actingAs($user);

    $task = Task::create([
        'title' => 'Test Task',
        'project_id' => $project->id,
        'author_id' => $user->id,
        'priority' => TaskPriority::MEDIUM,
        'status' => TaskStatus::TODO,
    ]);

    // Change status
    $task->update(['status' => TaskStatus::IN_PROGRESS]);

    // Verify audit log was created via AuditService
    $auditLog = AuditLog::where('task_id', $task->id)
        ->where('field', 'status')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->old_value)->toBe('todo')
        ->and($auditLog->new_value)->toBe('in_progress')
        ->and($auditLog->user_id)->toBe($user->id);
});

test('task observer uses audit service to log assignee changes', function () {
    // Requirements 7.5: WHEN пользователь изменяет исполнителя задачи, THE Система SHALL записать изменение исполнителя в аудит-лог
    $user = User::factory()->create();
    $assignee1 = User::factory()->create();
    $assignee2 = User::factory()->create();
    $project = Project::factory()->create();

    $this->actingAs($user);

    $task = Task::create([
        'title' => 'Test Task',
        'project_id' => $project->id,
        'author_id' => $user->id,
        'assignee_id' => $assignee1->id,
        'priority' => TaskPriority::MEDIUM,
        'status' => TaskStatus::TODO,
    ]);

    // Change assignee
    $task->update(['assignee_id' => $assignee2->id]);

    // Verify audit log was created via AuditService
    $auditLog = AuditLog::where('task_id', $task->id)
        ->where('field', 'assignee_id')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->old_value)->toBe((string) $assignee1->id)
        ->and($auditLog->new_value)->toBe((string) $assignee2->id)
        ->and($auditLog->user_id)->toBe($user->id);
});

test('task observer uses audit service to log priority changes', function () {
    // Requirements 7.6: WHEN пользователь изменяет приоритет задачи, THE Система SHALL записать изменение приоритета в аудит-лог
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $this->actingAs($user);

    $task = Task::create([
        'title' => 'Test Task',
        'project_id' => $project->id,
        'author_id' => $user->id,
        'priority' => TaskPriority::LOW,
        'status' => TaskStatus::TODO,
    ]);

    // Change priority
    $task->update(['priority' => TaskPriority::HIGH]);

    // Verify audit log was created via AuditService
    $auditLog = AuditLog::where('task_id', $task->id)
        ->where('field', 'priority')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->old_value)->toBe('low')
        ->and($auditLog->new_value)->toBe('high')
        ->and($auditLog->user_id)->toBe($user->id);
});

test('task observer logs all three fields when changed together', function () {
    // Integration test: verify all three fields (status, assignee, priority) are logged
    $user = User::factory()->create();
    $assignee = User::factory()->create();
    $project = Project::factory()->create();

    $this->actingAs($user);

    $task = Task::create([
        'title' => 'Test Task',
        'project_id' => $project->id,
        'author_id' => $user->id,
        'priority' => TaskPriority::LOW,
        'status' => TaskStatus::TODO,
    ]);

    // Change all three fields at once
    $task->update([
        'status' => TaskStatus::IN_PROGRESS,
        'assignee_id' => $assignee->id,
        'priority' => TaskPriority::HIGH,
    ]);

    // Verify all three audit logs were created
    $auditLogs = AuditLog::where('task_id', $task->id)->get();

    expect($auditLogs)->toHaveCount(3);

    // Verify status change
    $statusLog = $auditLogs->firstWhere('field', 'status');
    expect($statusLog)->not->toBeNull()
        ->and($statusLog->old_value)->toBe('todo')
        ->and($statusLog->new_value)->toBe('in_progress');

    // Verify assignee change
    $assigneeLog = $auditLogs->firstWhere('field', 'assignee_id');
    expect($assigneeLog)->not->toBeNull()
        ->and($assigneeLog->new_value)->toBe((string) $assignee->id);

    // Verify priority change
    $priorityLog = $auditLogs->firstWhere('field', 'priority');
    expect($priorityLog)->not->toBeNull()
        ->and($priorityLog->old_value)->toBe('low')
        ->and($priorityLog->new_value)->toBe('high');
});

test('audit service properly converts enum values in task observer', function () {
    // Verify that enum conversion is handled by AuditService
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $this->actingAs($user);

    $task = Task::create([
        'title' => 'Test Task',
        'project_id' => $project->id,
        'author_id' => $user->id,
        'priority' => TaskPriority::MEDIUM,
        'status' => TaskStatus::TODO,
    ]);

    // Update with enum values
    $task->update([
        'priority' => TaskPriority::FROZEN,
        'status' => TaskStatus::DONE,
    ]);

    // Verify enums are stored as strings
    $priorityLog = AuditLog::where('task_id', $task->id)
        ->where('field', 'priority')
        ->first();

    $statusLog = AuditLog::where('task_id', $task->id)
        ->where('field', 'status')
        ->first();

    expect($priorityLog->old_value)->toBe('medium')
        ->and($priorityLog->new_value)->toBe('frozen')
        ->and($statusLog->old_value)->toBe('todo')
        ->and($statusLog->new_value)->toBe('done');
});
