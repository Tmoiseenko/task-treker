<?php

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Estimate;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStage;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Estimate Model Tests
test('estimate model can be created with required fields', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $task = Task::factory()->create(['project_id' => $project->id]);
    $taskStage = TaskStage::factory()->create(['task_id' => $task->id]);
    
    $estimate = Estimate::create([
        'task_stage_id' => $taskStage->id,
        'user_id' => $user->id,
        'hours' => 5.5,
    ]);
    
    expect($estimate)->toBeInstanceOf(Estimate::class)
        ->and($estimate->hours)->toBe('5.50')
        ->and($estimate->task_stage_id)->toBe($taskStage->id)
        ->and($estimate->user_id)->toBe($user->id);
});

test('estimate has correct relationships', function () {
    $estimate = new Estimate();
    
    expect($estimate->taskStage())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class)
        ->and($estimate->user())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
});

// TimeEntry Model Tests
test('time entry model can be created with required fields', function () {
    $user = User::factory()->create(['hourly_rate' => 50.00]);
    $project = Project::factory()->create();
    $task = Task::factory()->create(['project_id' => $project->id]);
    $taskStage = TaskStage::factory()->create(['task_id' => $task->id]);
    
    $timeEntry = TimeEntry::create([
        'task_stage_id' => $taskStage->id,
        'user_id' => $user->id,
        'hours' => 3.5,
        'date' => '2024-03-15',
        'description' => 'Working on feature',
        'cost' => 175.00,
    ]);
    
    expect($timeEntry)->toBeInstanceOf(TimeEntry::class)
        ->and($timeEntry->hours)->toBe('3.50')
        ->and($timeEntry->cost)->toBe('175.00')
        ->and($timeEntry->date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('time entry has correct relationships', function () {
    $timeEntry = new TimeEntry();
    
    expect($timeEntry->taskStage())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class)
        ->and($timeEntry->user())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
});

test('time entry calculateCost method works correctly', function () {
    $user = User::factory()->create(['hourly_rate' => 50.00]);
    $project = Project::factory()->create();
    $task = Task::factory()->create(['project_id' => $project->id]);
    $taskStage = TaskStage::factory()->create(['task_id' => $task->id]);
    
    $timeEntry = TimeEntry::create([
        'task_stage_id' => $taskStage->id,
        'user_id' => $user->id,
        'hours' => 4.0,
        'date' => '2024-03-15',
        'cost' => 0, // Will be calculated
    ]);
    
    $calculatedCost = $timeEntry->calculateCost();
    
    expect($calculatedCost)->toBe(200.0); // 4 hours * $50/hour
});

// Document Model Tests
test('document model can be created with required fields', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    
    $document = Document::create([
        'title' => 'API Documentation',
        'content' => '# API Endpoints',
        'category' => 'api_documentation',
        'project_id' => $project->id,
        'author_id' => $user->id,
        'version' => 1,
    ]);
    
    expect($document)->toBeInstanceOf(Document::class)
        ->and($document->title)->toBe('API Documentation')
        ->and($document->version)->toBe(1)
        ->and($document->category->value)->toBe('api_documentation');
});

test('document has correct relationships', function () {
    $document = new Document();
    
    expect($document->project())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class)
        ->and($document->author())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class)
        ->and($document->tasks())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class)
        ->and($document->versions())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

// DocumentVersion Model Tests
test('document version model can be created with required fields', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $document = Document::factory()->create([
        'project_id' => $project->id,
        'author_id' => $user->id,
    ]);
    
    $version = DocumentVersion::create([
        'document_id' => $document->id,
        'content' => '# Updated API Endpoints',
        'version' => 2,
        'user_id' => $user->id,
        'created_at' => now(),
    ]);
    
    expect($version)->toBeInstanceOf(DocumentVersion::class)
        ->and($version->version)->toBe(2)
        ->and($version->created_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('document version has correct relationships', function () {
    $version = new DocumentVersion();
    
    expect($version->document())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class)
        ->and($version->user())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
});

// Notification Model Tests
test('notification model can be created with required fields', function () {
    $user = User::factory()->create();
    
    $notification = Notification::create([
        'user_id' => $user->id,
        'type' => 'task_assigned',
        'title' => 'New Task Assigned',
        'message' => 'You have been assigned to a new task',
        'data' => ['task_id' => 1],
    ]);
    
    expect($notification)->toBeInstanceOf(Notification::class)
        ->and($notification->type)->toBe('task_assigned')
        ->and($notification->data)->toBeArray()
        ->and($notification->data['task_id'])->toBe(1)
        ->and($notification->read_at)->toBeNull();
});

test('notification has correct relationships', function () {
    $notification = new Notification();
    
    expect($notification->user())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
});

test('notification casts work correctly', function () {
    $user = User::factory()->create();
    
    $notification = Notification::create([
        'user_id' => $user->id,
        'type' => 'deadline_approaching',
        'title' => 'Deadline Soon',
        'message' => 'Task deadline is in 24 hours',
        'data' => ['task_id' => 5, 'hours_remaining' => 24],
        'read_at' => '2024-03-15 10:30:00',
    ]);
    
    expect($notification->data)->toBeArray()
        ->and($notification->read_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});
