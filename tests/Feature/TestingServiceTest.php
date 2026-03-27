<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\ChecklistItem;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\TestingService;

beforeEach(function () {
    $this->testingService = new TestingService();
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();
});

describe('createChecklist', function () {
    test('creates checklist items for a task', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        $items = [
            'Check login functionality',
            'Verify error messages',
            'Test edge cases',
        ];
        
        $checklistItems = $this->testingService->createChecklist($task, $items);
        
        expect($checklistItems)->toHaveCount(3);
        expect($task->checklistItems)->toHaveCount(3);
        
        $firstItem = $checklistItems->first();
        expect($firstItem->title)->toBe('Check login functionality');
        expect($firstItem->is_completed)->toBeFalse();
        expect($firstItem->order)->toBe(1);
    });
    
    test('creates checklist items with array format', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        $items = [
            ['title' => 'First check'],
            ['title' => 'Second check'],
        ];
        
        $checklistItems = $this->testingService->createChecklist($task, $items);
        
        expect($checklistItems)->toHaveCount(2);
        expect($checklistItems->first()->title)->toBe('First check');
    });
    
    test('sets correct order for checklist items', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        $items = ['Item 1', 'Item 2', 'Item 3'];
        
        $checklistItems = $this->testingService->createChecklist($task, $items);
        
        expect($checklistItems->pluck('order')->toArray())->toBe([1, 2, 3]);
    });
    
    test('creates empty checklist when no items provided', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        $checklistItems = $this->testingService->createChecklist($task, []);
        
        expect($checklistItems)->toHaveCount(0);
    });
});

describe('toggleChecklistItem', function () {
    test('toggles checklist item from incomplete to complete', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        $item = ChecklistItem::factory()->create([
            'task_id' => $task->id,
            'is_completed' => false,
        ]);
        
        $this->testingService->toggleChecklistItem($item);
        
        expect($item->fresh()->is_completed)->toBeTrue();
    });
    
    test('toggles checklist item from complete to incomplete', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        $item = ChecklistItem::factory()->create([
            'task_id' => $task->id,
            'is_completed' => true,
        ]);
        
        $this->testingService->toggleChecklistItem($item);
        
        expect($item->fresh()->is_completed)->toBeFalse();
    });
    
    test('can toggle item multiple times', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        $item = ChecklistItem::factory()->create([
            'task_id' => $task->id,
            'is_completed' => false,
        ]);
        
        $this->testingService->toggleChecklistItem($item);
        expect($item->fresh()->is_completed)->toBeTrue();
        
        $this->testingService->toggleChecklistItem($item->fresh());
        expect($item->fresh()->is_completed)->toBeFalse();
        
        $this->testingService->toggleChecklistItem($item->fresh());
        expect($item->fresh()->is_completed)->toBeTrue();
    });
});

describe('getChecklistProgress', function () {
    test('returns 0 when no checklist items exist', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        $progress = $this->testingService->getChecklistProgress($task);
        
        expect($progress)->toBe(0.0);
    });
    
    test('returns 0 when no items are completed', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        ChecklistItem::factory()->count(3)->create([
            'task_id' => $task->id,
            'is_completed' => false,
        ]);
        
        $progress = $this->testingService->getChecklistProgress($task);
        
        expect($progress)->toBe(0.0);
    });
    
    test('returns 100 when all items are completed', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        ChecklistItem::factory()->count(3)->create([
            'task_id' => $task->id,
            'is_completed' => true,
        ]);
        
        $progress = $this->testingService->getChecklistProgress($task);
        
        expect($progress)->toBe(100.0);
    });
    
    test('calculates correct percentage for partial completion', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        ChecklistItem::factory()->count(2)->create([
            'task_id' => $task->id,
            'is_completed' => true,
        ]);
        
        ChecklistItem::factory()->count(2)->create([
            'task_id' => $task->id,
            'is_completed' => false,
        ]);
        
        $progress = $this->testingService->getChecklistProgress($task);
        
        expect($progress)->toBe(50.0);
    });
    
    test('rounds progress to 2 decimal places', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        // 1 completed out of 3 = 33.333...%
        ChecklistItem::factory()->create([
            'task_id' => $task->id,
            'is_completed' => true,
        ]);
        
        ChecklistItem::factory()->count(2)->create([
            'task_id' => $task->id,
            'is_completed' => false,
        ]);
        
        $progress = $this->testingService->getChecklistProgress($task);
        
        expect($progress)->toBe(33.33);
    });
});

describe('createBugReport', function () {
    test('creates bug report linked to original task', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $this->user->id,
        ]);
        
        $bugData = [
            'title' => 'Login button not working',
            'description' => 'Steps to reproduce: 1. Click login 2. Nothing happens',
            'author_id' => $this->user->id,
        ];
        
        $bugReport = $this->testingService->createBugReport($task, $bugData);
        
        expect($bugReport)->toBeInstanceOf(Task::class);
        expect($bugReport->title)->toBe('Login button not working');
        expect($bugReport->description)->toBe('Steps to reproduce: 1. Click login 2. Nothing happens');
        expect($bugReport->parent_task_id)->toBe($task->id);
        expect($bugReport->project_id)->toBe($task->project_id);
        expect($bugReport->status)->toBe(TaskStatus::TODO);
    });
    
    test('creates bug report with default title when not provided', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'title' => 'User Authentication',
        ]);
        
        $bugReport = $this->testingService->createBugReport($task, [
            'description' => 'Bug description',
            'author_id' => $this->user->id,
        ]);
        
        expect($bugReport->title)->toBe('Bug: User Authentication');
    });
    
    test('assigns bug report to original task assignee by default', function () {
        $assignee = User::factory()->create();
        
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $assignee->id,
        ]);
        
        $bugReport = $this->testingService->createBugReport($task, [
            'title' => 'Bug title',
            'author_id' => $this->user->id,
        ]);
        
        expect($bugReport->assignee_id)->toBe($assignee->id);
    });
    
    test('can override assignee in bug report', function () {
        $originalAssignee = User::factory()->create();
        $newAssignee = User::factory()->create();
        
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $originalAssignee->id,
        ]);
        
        $bugReport = $this->testingService->createBugReport($task, [
            'title' => 'Bug title',
            'author_id' => $this->user->id,
            'assignee_id' => $newAssignee->id,
        ]);
        
        expect($bugReport->assignee_id)->toBe($newAssignee->id);
    });
    
    test('inherits priority from original task', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'priority' => TaskPriority::HIGH,
        ]);
        
        $bugReport = $this->testingService->createBugReport($task, [
            'title' => 'Bug title',
            'author_id' => $this->user->id,
        ]);
        
        expect($bugReport->priority)->toBe(TaskPriority::HIGH);
    });
    
    test('bug report is accessible through original task relationship', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        $bugReport = $this->testingService->createBugReport($task, [
            'title' => 'Bug title',
            'author_id' => $this->user->id,
        ]);
        
        expect($task->bugReports)->toHaveCount(1);
        expect($task->bugReports->first()->id)->toBe($bugReport->id);
    });
});

describe('linkBugReport', function () {
    test('links bug report to original task', function () {
        $originalTask = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        $bugReport = Task::factory()->create([
            'project_id' => $this->project->id,
            'parent_task_id' => null,
        ]);
        
        $this->testingService->linkBugReport($bugReport, $originalTask);
        
        expect($bugReport->fresh()->parent_task_id)->toBe($originalTask->id);
    });
    
    test('can relink bug report to different task', function () {
        $task1 = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        $task2 = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        $bugReport = Task::factory()->create([
            'project_id' => $this->project->id,
            'parent_task_id' => $task1->id,
        ]);
        
        $this->testingService->linkBugReport($bugReport, $task2);
        
        expect($bugReport->fresh()->parent_task_id)->toBe($task2->id);
    });
});

describe('checkAllBugsFixed', function () {
    test('returns true when no bug reports exist', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        $allFixed = $this->testingService->checkAllBugsFixed($task);
        
        expect($allFixed)->toBeTrue();
    });
    
    test('returns true when all bug reports are done', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        Task::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'parent_task_id' => $task->id,
            'status' => TaskStatus::DONE,
        ]);
        
        $allFixed = $this->testingService->checkAllBugsFixed($task);
        
        expect($allFixed)->toBeTrue();
    });
    
    test('returns false when some bug reports are not done', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        Task::factory()->create([
            'project_id' => $this->project->id,
            'parent_task_id' => $task->id,
            'status' => TaskStatus::DONE,
        ]);
        
        Task::factory()->create([
            'project_id' => $this->project->id,
            'parent_task_id' => $task->id,
            'status' => TaskStatus::IN_PROGRESS,
        ]);
        
        $allFixed = $this->testingService->checkAllBugsFixed($task);
        
        expect($allFixed)->toBeFalse();
    });
    
    test('returns false when all bug reports are in progress', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        Task::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'parent_task_id' => $task->id,
            'status' => TaskStatus::IN_PROGRESS,
        ]);
        
        $allFixed = $this->testingService->checkAllBugsFixed($task);
        
        expect($allFixed)->toBeFalse();
    });
    
    test('returns false when bug reports are in testing', function () {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);
        
        Task::factory()->create([
            'project_id' => $this->project->id,
            'parent_task_id' => $task->id,
            'status' => TaskStatus::IN_TESTING,
        ]);
        
        $allFixed = $this->testingService->checkAllBugsFixed($task);
        
        expect($allFixed)->toBeFalse();
    });
});
