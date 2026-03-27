<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->taskService = new TaskService();
});

describe('createTask', function () {
    test('creates task with all required fields', function () {
        $project = Project::factory()->create();
        $author = User::factory()->create();
        
        $taskData = [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'project_id' => $project->id,
            'author_id' => $author->id,
            'status' => TaskStatus::TODO,
        ];
        
        $task = $this->taskService->createTask($taskData);
        
        expect($task)->toBeInstanceOf(Task::class)
            ->and($task->title)->toBe('Test Task')
            ->and($task->description)->toBe('Test Description')
            ->and($task->project_id)->toBe($project->id)
            ->and($task->author_id)->toBe($author->id)
            ->and($task->status)->toBe(TaskStatus::TODO);
    });

    test('automatically creates task stages when task is created', function () {
        $project = Project::factory()->create();
        $stages = Stage::factory()->count(3)->create();
        $project->stages()->attach($stages);
        $author = User::factory()->create();
        
        $taskData = [
            'title' => 'Test Task',
            'project_id' => $project->id,
            'author_id' => $author->id,
            'status' => TaskStatus::TODO,
        ];
        
        $task = $this->taskService->createTask($taskData);
        
        expect($task->taskStages)->toHaveCount(3);
    });

    test('creates task stages in correct order', function () {
        $project = Project::factory()->create();
        $stage1 = Stage::factory()->create(['order' => 1]);
        $stage2 = Stage::factory()->create(['order' => 2]);
        $stage3 = Stage::factory()->create(['order' => 3]);
        $project->stages()->attach([$stage1->id, $stage2->id, $stage3->id]);
        $author = User::factory()->create();
        
        $taskData = [
            'title' => 'Test Task',
            'project_id' => $project->id,
            'author_id' => $author->id,
            'status' => TaskStatus::TODO,
        ];
        
        $task = $this->taskService->createTask($taskData);
        
        $taskStages = $task->taskStages()->orderBy('order')->get();
        expect($taskStages[0]->order)->toBe(1)
            ->and($taskStages[1]->order)->toBe(2)
            ->and($taskStages[2]->order)->toBe(3);
    });
});

describe('updateTask', function () {
    test('updates task fields', function () {
        $task = Task::factory()->create(['title' => 'Old Title']);
        
        $updatedTask = $this->taskService->updateTask($task, [
            'title' => 'New Title',
            'description' => 'New Description',
        ]);
        
        expect($updatedTask->title)->toBe('New Title')
            ->and($updatedTask->description)->toBe('New Description');
    });

    test('returns fresh task instance after update', function () {
        $task = Task::factory()->create();
        
        $updatedTask = $this->taskService->updateTask($task, [
            'title' => 'Updated Title',
        ]);
        
        expect($updatedTask->title)->toBe('Updated Title')
            ->and($updatedTask->wasRecentlyCreated)->toBeFalse();
    });
});

describe('assignTask', function () {
    test('assigns user to task', function () {
        $task = Task::factory()->create(['assignee_id' => null]);
        $user = User::factory()->create();
        
        $this->taskService->assignTask($task, $user);
        
        $task->refresh();
        expect($task->assignee_id)->toBe($user->id);
    });

    test('can reassign task to different user', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $task = Task::factory()->create(['assignee_id' => $user1->id]);
        
        $this->taskService->assignTask($task, $user2);
        
        $task->refresh();
        expect($task->assignee_id)->toBe($user2->id);
    });
});

describe('takeTask', function () {
    test('sets assignee and changes status to in_progress', function () {
        $task = Task::factory()->create([
            'assignee_id' => null,
            'status' => TaskStatus::TODO,
        ]);
        $user = User::factory()->create();
        
        $this->taskService->takeTask($task, $user);
        
        $task->refresh();
        expect($task->assignee_id)->toBe($user->id)
            ->and($task->status)->toBe(TaskStatus::IN_PROGRESS);
    });

    test('can take task that already has assignee', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $task = Task::factory()->create([
            'assignee_id' => $user1->id,
            'status' => TaskStatus::TODO,
        ]);
        
        $this->taskService->takeTask($task, $user2);
        
        $task->refresh();
        expect($task->assignee_id)->toBe($user2->id)
            ->and($task->status)->toBe(TaskStatus::IN_PROGRESS);
    });
});

describe('changeStatus', function () {
    test('changes status when transition is valid', function () {
        $task = Task::factory()->create(['status' => TaskStatus::TODO]);
        
        $this->taskService->changeStatus($task, TaskStatus::IN_PROGRESS);
        
        $task->refresh();
        expect($task->status)->toBe(TaskStatus::IN_PROGRESS);
    });

    test('throws exception when transition is invalid', function () {
        $task = Task::factory()->create(['status' => TaskStatus::TODO]);
        
        expect(fn() => $this->taskService->changeStatus($task, TaskStatus::DONE))
            ->toThrow(InvalidArgumentException::class);
    });

    test('allows valid transition from in_progress to in_testing', function () {
        $task = Task::factory()->create(['status' => TaskStatus::IN_PROGRESS]);
        
        $this->taskService->changeStatus($task, TaskStatus::IN_TESTING);
        
        $task->refresh();
        expect($task->status)->toBe(TaskStatus::IN_TESTING);
    });

    test('allows valid transition from test_failed to in_progress', function () {
        $task = Task::factory()->create(['status' => TaskStatus::TEST_FAILED]);
        
        $this->taskService->changeStatus($task, TaskStatus::IN_PROGRESS);
        
        $task->refresh();
        expect($task->status)->toBe(TaskStatus::IN_PROGRESS);
    });

    test('prevents transition from done status', function () {
        $task = Task::factory()->create(['status' => TaskStatus::DONE]);
        
        expect(fn() => $this->taskService->changeStatus($task, TaskStatus::IN_PROGRESS))
            ->toThrow(InvalidArgumentException::class);
    });

    // Requirement 11.8: Tester can change status to "done" when testing is successful
    test('allows transition from in_testing to done', function () {
        $task = Task::factory()->create(['status' => TaskStatus::IN_TESTING]);
        
        $this->taskService->changeStatus($task, TaskStatus::DONE);
        
        $task->refresh();
        expect($task->status)->toBe(TaskStatus::DONE);
    });

    // Requirement 11.9: Tester can change status to "test_failed" when critical errors are found
    test('allows transition from in_testing to test_failed', function () {
        $task = Task::factory()->create(['status' => TaskStatus::IN_TESTING]);
        
        $this->taskService->changeStatus($task, TaskStatus::TEST_FAILED);
        
        $task->refresh();
        expect($task->status)->toBe(TaskStatus::TEST_FAILED);
    });

    // Requirement 11.10: Developer can return task from "test_failed" to "in_progress"
    test('allows transition from test_failed to in_progress', function () {
        $task = Task::factory()->create(['status' => TaskStatus::TEST_FAILED]);
        
        $this->taskService->changeStatus($task, TaskStatus::IN_PROGRESS);
        
        $task->refresh();
        expect($task->status)->toBe(TaskStatus::IN_PROGRESS);
    });

    test('prevents invalid transition from in_testing to todo', function () {
        $task = Task::factory()->create(['status' => TaskStatus::IN_TESTING]);
        
        expect(fn() => $this->taskService->changeStatus($task, TaskStatus::TODO))
            ->toThrow(InvalidArgumentException::class);
    });

    test('prevents invalid transition from test_failed to done', function () {
        $task = Task::factory()->create(['status' => TaskStatus::TEST_FAILED]);
        
        expect(fn() => $this->taskService->changeStatus($task, TaskStatus::DONE))
            ->toThrow(InvalidArgumentException::class);
    });
});

describe('createTaskStages', function () {
    test('creates task stages for all project stages', function () {
        $project = Project::factory()->create();
        $stages = Stage::factory()->count(4)->create();
        $project->stages()->attach($stages);
        $task = Task::factory()->create(['project_id' => $project->id]);
        
        // Clear automatically created task stages to test the method
        $task->taskStages()->delete();
        
        $this->taskService->createTaskStages($task);
        
        expect($task->taskStages()->count())->toBe(4);
    });

    test('creates task stages with correct stage_id references', function () {
        $project = Project::factory()->create();
        $stage1 = Stage::factory()->create();
        $stage2 = Stage::factory()->create();
        $project->stages()->attach([$stage1->id, $stage2->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);
        
        // Clear automatically created task stages to test the method
        $task->taskStages()->delete();
        
        $this->taskService->createTaskStages($task);
        
        $taskStageIds = $task->taskStages()->pluck('stage_id')->toArray();
        expect($taskStageIds)->toContain($stage1->id)
            ->and($taskStageIds)->toContain($stage2->id);
    });

    test('creates task stages in order based on project stage order', function () {
        $project = Project::factory()->create();
        $stage1 = Stage::factory()->create(['order' => 1]);
        $stage2 = Stage::factory()->create(['order' => 2]);
        $stage3 = Stage::factory()->create(['order' => 3]);
        $project->stages()->attach([$stage1->id, $stage2->id, $stage3->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);
        
        // Clear automatically created task stages to test the method
        $task->taskStages()->delete();
        
        $this->taskService->createTaskStages($task);
        
        $taskStages = $task->taskStages()->orderBy('order')->get();
        expect($taskStages[0]->order)->toBe(1)
            ->and($taskStages[1]->order)->toBe(2)
            ->and($taskStages[2]->order)->toBe(3);
    });

    test('handles project with no stages', function () {
        $project = Project::factory()->create();
        $task = Task::factory()->create(['project_id' => $project->id]);
        
        // Clear automatically created task stages to test the method
        $task->taskStages()->delete();
        
        $this->taskService->createTaskStages($task);
        
        expect($task->taskStages()->count())->toBe(0);
    });
});
