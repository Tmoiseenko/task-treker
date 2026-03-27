<?php

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('task observer automatically creates task stages when task is created', function () {
    // Create a project with stages
    $project = Project::factory()->create();
    $stages = Stage::factory()->count(3)->create();
    $project->stages()->attach($stages);

    $user = User::factory()->create();

    // Create a task
    $task = Task::create([
        'title' => 'Test Task',
        'description' => 'Test Description',
        'project_id' => $project->id,
        'author_id' => $user->id,
        'priority' => TaskPriority::HIGH,
        'status' => TaskStatus::TODO,
    ]);

    // Verify that TaskStages were created automatically
    expect($task->taskStages)->toHaveCount(3);

    // Verify each TaskStage has correct attributes
    $taskStages = $task->taskStages()->orderBy('order')->get();
    foreach ($taskStages as $index => $taskStage) {
        expect($taskStage->stage_id)->toBe($stages[$index]->id)
            ->and($taskStage->status->value)->toBe('not_started')
            ->and($taskStage->order)->toBe($index + 1);
    }
});

test('task observer logs changes to audit log when task is updated', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    // Authenticate the user
    $this->actingAs($user);

    // Create a task
    $task = Task::create([
        'title' => 'Original Title',
        'description' => 'Original Description',
        'project_id' => $project->id,
        'author_id' => $user->id,
        'priority' => TaskPriority::LOW,
        'status' => TaskStatus::TODO,
    ]);

    // Update the task
    $task->update([
        'title' => 'Updated Title',
        'priority' => TaskPriority::HIGH,
        'status' => TaskStatus::IN_PROGRESS,
    ]);

    // Verify audit logs were created
    $auditLogs = AuditLog::where('task_id', $task->id)->get();
    
    expect($auditLogs)->toHaveCount(3);

    // Check title change
    $titleLog = $auditLogs->firstWhere('field', 'title');
    expect($titleLog)->not->toBeNull()
        ->and($titleLog->old_value)->toBe('Original Title')
        ->and($titleLog->new_value)->toBe('Updated Title')
        ->and($titleLog->user_id)->toBe($user->id);

    // Check priority change
    $priorityLog = $auditLogs->firstWhere('field', 'priority');
    expect($priorityLog)->not->toBeNull()
        ->and($priorityLog->old_value)->toBe('low')
        ->and($priorityLog->new_value)->toBe('high')
        ->and($priorityLog->user_id)->toBe($user->id);

    // Check status change
    $statusLog = $auditLogs->firstWhere('field', 'status');
    expect($statusLog)->not->toBeNull()
        ->and($statusLog->old_value)->toBe('todo')
        ->and($statusLog->new_value)->toBe('in_progress')
        ->and($statusLog->user_id)->toBe($user->id);
});

test('task observer does not log timestamp changes', function () {
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

    // Touch the task to update timestamps
    $task->touch();

    // Verify no audit logs were created for timestamp updates
    $auditLogs = AuditLog::where('task_id', $task->id)->get();
    
    expect($auditLogs)->toHaveCount(0);
});

test('task observer logs changes even when user is not authenticated', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    // Create task without authentication
    $task = Task::create([
        'title' => 'Test Task',
        'project_id' => $project->id,
        'author_id' => $user->id,
        'priority' => TaskPriority::MEDIUM,
        'status' => TaskStatus::TODO,
    ]);

    // Update task without authentication
    $task->update([
        'title' => 'Updated Title',
    ]);

    // Verify audit log was created with null user_id
    $auditLog = AuditLog::where('task_id', $task->id)
        ->where('field', 'title')
        ->first();
    
    expect($auditLog)->not->toBeNull()
        ->and($auditLog->user_id)->toBeNull()
        ->and($auditLog->old_value)->toBe('Test Task')
        ->and($auditLog->new_value)->toBe('Updated Title');
});

test('task stages are created with correct order', function () {
    $project = Project::factory()->create();
    
    // Create stages with specific order
    $stage1 = Stage::factory()->create(['name' => 'Design', 'order' => 1]);
    $stage2 = Stage::factory()->create(['name' => 'Backend', 'order' => 2]);
    $stage3 = Stage::factory()->create(['name' => 'Frontend', 'order' => 3]);
    
    $project->stages()->attach([$stage1->id, $stage2->id, $stage3->id]);

    $user = User::factory()->create();

    $task = Task::create([
        'title' => 'Test Task',
        'project_id' => $project->id,
        'author_id' => $user->id,
        'priority' => TaskPriority::MEDIUM,
        'status' => TaskStatus::TODO,
    ]);

    // Verify task stages are created in correct order
    $taskStages = $task->taskStages()->orderBy('order')->get();
    
    expect($taskStages[0]->order)->toBe(1)
        ->and($taskStages[1]->order)->toBe(2)
        ->and($taskStages[2]->order)->toBe(3);
});

test('task observer handles multiple field updates correctly', function () {
    $user = User::factory()->create();
    $assignee = User::factory()->create();
    $project = Project::factory()->create();

    $this->actingAs($user);

    $task = Task::create([
        'title' => 'Test Task',
        'description' => 'Original Description',
        'project_id' => $project->id,
        'author_id' => $user->id,
        'priority' => TaskPriority::LOW,
        'status' => TaskStatus::TODO,
    ]);

    // Update multiple fields at once
    $task->update([
        'title' => 'New Title',
        'description' => 'New Description',
        'assignee_id' => $assignee->id,
        'priority' => TaskPriority::HIGH,
        'status' => TaskStatus::IN_PROGRESS,
    ]);

    // Verify all changes were logged
    $auditLogs = AuditLog::where('task_id', $task->id)->get();
    
    expect($auditLogs)->toHaveCount(5);
    
    $fields = $auditLogs->pluck('field')->toArray();
    expect($fields)->toContain('title')
        ->and($fields)->toContain('description')
        ->and($fields)->toContain('assignee_id')
        ->and($fields)->toContain('priority')
        ->and($fields)->toContain('status');
});
