<?php

use App\Enums\TaskStatus;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->notificationService = new NotificationService();
});

test('notifyTaskAssigned creates notification for assignee', function () {
    $assignee = User::factory()->create();
    $task = Task::factory()->create(['assignee_id' => $assignee->id]);

    $this->notificationService->notifyTaskAssigned($task);

    expect(Notification::where('user_id', $assignee->id)->count())->toBe(1);
    
    $notification = Notification::where('user_id', $assignee->id)->first();
    expect($notification->type)->toBe('task_assigned')
        ->and($notification->title)->toBe('Вам назначена задача')
        ->and($notification->data['task_id'])->toBe($task->id);
});

test('notifyTaskAssigned does nothing when task has no assignee', function () {
    $task = Task::factory()->create(['assignee_id' => null]);

    $this->notificationService->notifyTaskAssigned($task);

    expect(Notification::count())->toBe(0);
});

test('notifyStatusChanged creates notifications for assignee and author', function () {
    $author = User::factory()->create();
    $assignee = User::factory()->create();
    $task = Task::factory()->create([
        'author_id' => $author->id,
        'assignee_id' => $assignee->id,
        'status' => TaskStatus::IN_PROGRESS,
    ]);

    $this->notificationService->notifyStatusChanged($task, TaskStatus::TODO);

    expect(Notification::count())->toBe(2);
    expect(Notification::where('user_id', $assignee->id)->count())->toBe(1);
    expect(Notification::where('user_id', $author->id)->count())->toBe(1);
});

test('notifyStatusChanged creates only one notification when author is assignee', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'author_id' => $user->id,
        'assignee_id' => $user->id,
        'status' => TaskStatus::IN_PROGRESS,
    ]);

    $this->notificationService->notifyStatusChanged($task, TaskStatus::TODO);

    expect(Notification::count())->toBe(1);
    expect(Notification::where('user_id', $user->id)->count())->toBe(1);
});

test('notifyCommentAdded creates notifications for assignee and author', function () {
    $author = User::factory()->create();
    $assignee = User::factory()->create();
    $commenter = User::factory()->create();
    
    $task = Task::factory()->create([
        'author_id' => $author->id,
        'assignee_id' => $assignee->id,
    ]);
    
    $comment = Comment::factory()->create([
        'task_id' => $task->id,
        'user_id' => $commenter->id,
    ]);

    $this->notificationService->notifyCommentAdded($comment);

    expect(Notification::count())->toBe(2);
    expect(Notification::where('user_id', $assignee->id)->count())->toBe(1);
    expect(Notification::where('user_id', $author->id)->count())->toBe(1);
});

test('notifyCommentAdded does not notify commenter', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'author_id' => $user->id,
        'assignee_id' => $user->id,
    ]);
    
    $comment = Comment::factory()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
    ]);

    $this->notificationService->notifyCommentAdded($comment);

    expect(Notification::count())->toBe(0);
});

test('notifyDeadlineApproaching creates notifications for assignee and project managers', function () {
    $projectManager = User::factory()->create();
    $pmRole = Role::factory()->create(['name' => 'project-manager']);
    $projectManager->roles()->attach($pmRole);
    
    $assignee = User::factory()->create();
    
    $project = Project::factory()->create();
    $project->members()->attach([$projectManager->id, $assignee->id]);
    
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assignee_id' => $assignee->id,
        'due_date' => now()->addHours(24),
    ]);

    $this->notificationService->notifyDeadlineApproaching($task);

    expect(Notification::count())->toBe(2);
    expect(Notification::where('user_id', $assignee->id)->count())->toBe(1);
    expect(Notification::where('user_id', $projectManager->id)->count())->toBe(1);
    
    $notification = Notification::where('user_id', $assignee->id)->first();
    expect($notification->type)->toBe('deadline_approaching');
});

test('notifyDeadlineExpired creates notifications for assignee and project managers', function () {
    $projectManager = User::factory()->create();
    $pmRole = Role::factory()->create(['name' => 'project-manager']);
    $projectManager->roles()->attach($pmRole);
    
    $assignee = User::factory()->create();
    
    $project = Project::factory()->create();
    $project->members()->attach([$projectManager->id, $assignee->id]);
    
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assignee_id' => $assignee->id,
        'due_date' => now()->subDay(),
    ]);

    $this->notificationService->notifyDeadlineExpired($task);

    expect(Notification::count())->toBe(2);
    
    $notification = Notification::where('user_id', $assignee->id)->first();
    expect($notification->type)->toBe('deadline_expired')
        ->and($notification->title)->toBe('Срок выполнения истек');
});

test('notifyTaskReadyForTesting creates notifications for testers', function () {
    $tester1 = User::factory()->create();
    $tester2 = User::factory()->create();
    $testerRole = Role::factory()->create(['name' => 'tester']);
    $tester1->roles()->attach($testerRole);
    $tester2->roles()->attach($testerRole);
    
    $project = Project::factory()->create();
    $project->members()->attach([$tester1->id, $tester2->id]);
    
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'status' => TaskStatus::IN_TESTING,
    ]);

    $this->notificationService->notifyTaskReadyForTesting($task);

    expect(Notification::count())->toBe(2);
    expect(Notification::where('user_id', $tester1->id)->count())->toBe(1);
    expect(Notification::where('user_id', $tester2->id)->count())->toBe(1);
    
    $notification = Notification::where('user_id', $tester1->id)->first();
    expect($notification->type)->toBe('ready_for_testing');
});

test('notifyBugReportCreated creates notification for original task assignee', function () {
    $assignee = User::factory()->create();
    $originalTask = Task::factory()->create(['assignee_id' => $assignee->id]);
    $bugReport = Task::factory()->create(['parent_task_id' => $originalTask->id]);

    $this->notificationService->notifyBugReportCreated($bugReport, $originalTask);

    expect(Notification::where('user_id', $assignee->id)->count())->toBe(1);
    
    $notification = Notification::where('user_id', $assignee->id)->first();
    expect($notification->type)->toBe('bug_report_created')
        ->and($notification->data['bug_report_id'])->toBe($bugReport->id)
        ->and($notification->data['original_task_id'])->toBe($originalTask->id);
});

test('notifyBugReportCreated does nothing when original task has no assignee', function () {
    $originalTask = Task::factory()->create(['assignee_id' => null]);
    $bugReport = Task::factory()->create(['parent_task_id' => $originalTask->id]);

    $this->notificationService->notifyBugReportCreated($bugReport, $originalTask);

    expect(Notification::count())->toBe(0);
});

test('notifyAllBugsFixed creates notifications for testers', function () {
    $tester = User::factory()->create();
    $testerRole = Role::factory()->create(['name' => 'tester']);
    $tester->roles()->attach($testerRole);
    
    $project = Project::factory()->create();
    $project->members()->attach($tester->id);
    
    $task = Task::factory()->create(['project_id' => $project->id]);

    $this->notificationService->notifyAllBugsFixed($task);

    expect(Notification::where('user_id', $tester->id)->count())->toBe(1);
    
    $notification = Notification::where('user_id', $tester->id)->first();
    expect($notification->type)->toBe('all_bugs_fixed')
        ->and($notification->title)->toBe('Все баги исправлены');
});

test('notification data contains correct task information', function () {
    $assignee = User::factory()->create();
    $task = Task::factory()->create([
        'assignee_id' => $assignee->id,
        'title' => 'Test Task Title',
    ]);

    $this->notificationService->notifyTaskAssigned($task);

    $notification = Notification::where('user_id', $assignee->id)->first();
    expect($notification->data)
        ->toHaveKey('task_id')
        ->toHaveKey('task_title')
        ->toHaveKey('project_id')
        ->and($notification->data['task_title'])->toBe('Test Task Title');
});
