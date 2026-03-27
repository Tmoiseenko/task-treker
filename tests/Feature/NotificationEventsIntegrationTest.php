<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Events\CommentAdded;
use App\Events\DeadlineApproaching;
use App\Events\TaskAssigned;
use App\Events\TaskStatusChanged;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationEventsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\StageSeeder::class);
    }

    public function test_task_assigned_event_listener_creates_notification(): void
    {
        $assignee = User::factory()->create();
        $author = User::factory()->create();
        $project = Project::factory()->create();
        
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'author_id' => $author->id,
            'assignee_id' => null, // Start without assignee
        ]);

        // Clear any notifications created during task creation
        Notification::query()->delete();
        $this->assertDatabaseCount('notifications', 0);

        // Manually call the notification service (simulating what the listener does)
        $notificationService = app(\App\Services\NotificationService::class);
        $task->assignee_id = $assignee->id;
        $notificationService->notifyTaskAssigned($task);

        // Verify notification was created
        $this->assertDatabaseCount('notifications', 1);
        
        $notification = Notification::first();
        $this->assertEquals($assignee->id, $notification->user_id);
        $this->assertEquals('task_assigned', $notification->type);
        $this->assertEquals($task->id, $notification->data['task_id']);
    }

    public function test_task_status_changed_event_listener_creates_notifications(): void
    {
        $assignee = User::factory()->create();
        $author = User::factory()->create();
        $project = Project::factory()->create();
        
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'author_id' => $author->id,
            'assignee_id' => $assignee->id,
            'status' => TaskStatus::TODO,
        ]);

        // Clear any notifications created during task creation
        Notification::query()->delete();
        $this->assertDatabaseCount('notifications', 0);

        $oldStatus = TaskStatus::TODO;
        $newStatus = TaskStatus::IN_PROGRESS;
        
        // Update task status
        $task->status = $newStatus;

        // Manually call the notification service (simulating what the listener does)
        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->notifyStatusChanged($task, $oldStatus);

        // Verify notifications were created for both assignee and author
        $this->assertDatabaseCount('notifications', 2);
        
        $notifications = Notification::all();
        $this->assertTrue($notifications->pluck('user_id')->contains($assignee->id));
        $this->assertTrue($notifications->pluck('user_id')->contains($author->id));
        
        foreach ($notifications as $notification) {
            $this->assertEquals('status_changed', $notification->type);
            $this->assertEquals($task->id, $notification->data['task_id']);
        }
    }

    public function test_comment_added_event_listener_creates_notifications(): void
    {
        $assignee = User::factory()->create();
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $project = Project::factory()->create();
        
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'author_id' => $author->id,
            'assignee_id' => $assignee->id,
        ]);

        // Clear any notifications created during task creation
        Notification::query()->delete();

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $commenter->id,
            'content' => 'Test comment',
        ]);

        $this->assertDatabaseCount('notifications', 0);

        // Manually call the notification service (simulating what the listener does)
        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->notifyCommentAdded($comment);

        // Verify notifications were created for assignee and author (not commenter)
        $this->assertDatabaseCount('notifications', 2);
        
        $notifications = Notification::all();
        $this->assertTrue($notifications->pluck('user_id')->contains($assignee->id));
        $this->assertTrue($notifications->pluck('user_id')->contains($author->id));
        $this->assertFalse($notifications->pluck('user_id')->contains($commenter->id));
        
        foreach ($notifications as $notification) {
            $this->assertEquals('comment_added', $notification->type);
            $this->assertEquals($task->id, $notification->data['task_id']);
            $this->assertEquals($comment->id, $notification->data['comment_id']);
        }
    }

    public function test_deadline_approaching_event_listener_creates_notifications(): void
    {
        // Create project manager role and user
        $pmRole = Role::where('name', 'project_manager')->first();
        $projectManager = User::factory()->create();
        $projectManager->roles()->attach($pmRole->id);
        
        $assignee = User::factory()->create();
        $project = Project::factory()->create();
        
        // Add both users to project
        $project->members()->attach([$projectManager->id, $assignee->id]);
        
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'assignee_id' => $assignee->id,
            'due_date' => now()->addHours(23),
        ]);

        // Clear any notifications created during task creation
        Notification::query()->delete();
        $this->assertDatabaseCount('notifications', 0);

        // Manually call the notification service (simulating what the listener does)
        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->notifyDeadlineApproaching($task);

        // Verify notifications were created for assignee and project manager
        $notifications = Notification::all();
        
        // Should have 2 notifications
        $this->assertEquals(2, $notifications->count(), 'Should have 2 notifications');
        
        // Should have notification for assignee
        $this->assertTrue($notifications->pluck('user_id')->contains($assignee->id), 'Assignee should receive notification');
        
        // Should have notification for project manager
        $this->assertTrue($notifications->pluck('user_id')->contains($projectManager->id), 'Project manager should receive notification');
        
        foreach ($notifications as $notification) {
            $this->assertEquals('deadline_approaching', $notification->type);
            $this->assertEquals($task->id, $notification->data['task_id']);
        }
    }

    public function test_comment_added_does_not_notify_commenter(): void
    {
        $author = User::factory()->create();
        $project = Project::factory()->create();
        
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'author_id' => $author->id,
        ]);

        // Clear any notifications created during task creation
        Notification::query()->delete();

        // Author comments on their own task
        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $author->id,
            'content' => 'Self comment',
        ]);

        $this->assertDatabaseCount('notifications', 0);

        // Manually call the notification service (simulating what the listener does)
        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->notifyCommentAdded($comment);

        // No notifications should be created (commenter is the only relevant user)
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_status_changed_does_not_duplicate_notification_when_author_is_assignee(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'author_id' => $user->id,
            'assignee_id' => $user->id,
            'status' => TaskStatus::TODO,
        ]);

        // Clear any notifications created during task creation
        Notification::query()->delete();
        $this->assertDatabaseCount('notifications', 0);

        // Manually call the notification service (simulating what the listener does)
        $notificationService = app(\App\Services\NotificationService::class);
        $task->status = TaskStatus::IN_PROGRESS;
        $notificationService->notifyStatusChanged($task, TaskStatus::TODO);

        // Only one notification should be created (not duplicated)
        $this->assertDatabaseCount('notifications', 1);
        
        $notification = Notification::first();
        $this->assertEquals($user->id, $notification->user_id);
    }
}
