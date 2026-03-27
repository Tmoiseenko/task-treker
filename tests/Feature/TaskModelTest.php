<?php

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('task model can be created with required fields', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    
    $task = Task::create([
        'title' => 'Test Task',
        'description' => 'Test Description',
        'project_id' => $project->id,
        'author_id' => $user->id,
        'priority' => TaskPriority::HIGH,
        'status' => TaskStatus::TODO,
    ]);
    
    expect($task)->toBeInstanceOf(Task::class)
        ->and($task->title)->toBe('Test Task')
        ->and($task->priority)->toBe(TaskPriority::HIGH)
        ->and($task->status)->toBe(TaskStatus::TODO);
});

test('task has correct relationships', function () {
    $task = new Task();
    
    expect($task->project())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class)
        ->and($task->author())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class)
        ->and($task->assignee())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class)
        ->and($task->tags())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class)
        ->and($task->taskStages())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class)
        ->and($task->comments())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class)
        ->and($task->attachments())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class)
        ->and($task->auditLogs())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class)
        ->and($task->checklistItems())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class)
        ->and($task->bugReports())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class)
        ->and($task->parentTask())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class)
        ->and($task->documents())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
});

test('task casts work correctly', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    
    $task = Task::create([
        'title' => 'Test Task',
        'project_id' => $project->id,
        'author_id' => $user->id,
        'priority' => 'high',
        'status' => 'todo',
        'due_date' => '2024-12-31 23:59:59',
    ]);
    
    expect($task->priority)->toBeInstanceOf(TaskPriority::class)
        ->and($task->status)->toBeInstanceOf(TaskStatus::class)
        ->and($task->due_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});
