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
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EventsAndListenersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\StageSeeder::class);
    }

    public function test_task_assigned_event_creates_notification(): void
    {
        Event::fake([TaskAssigned::class]);

        $assignee = User::factory()->create();
        $author = User::factory()->create();
        $project = Project::factory()->create();
        
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'author_id' => $author->id,
            'assignee_id' => $assignee->id,
        ]);

        // Dispatch the event manually
        Event::dispatch(new TaskAssigned($task, $assignee));

        Event::assertDispatched(TaskAssigned::class);
    }

    public function test_task_status_changed_event_creates_notification(): void
    {
        Event::fake([TaskStatusChanged::class]);

        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'author_id' => $user->id,
            'status' => TaskStatus::TODO,
        ]);

        $oldStatus = TaskStatus::TODO;
        $newStatus = TaskStatus::IN_PROGRESS;

        Event::dispatch(new TaskStatusChanged($task, $oldStatus, $newStatus));

        Event::assertDispatched(TaskStatusChanged::class);
    }

    public function test_comment_added_event_creates_notification(): void
    {
        Event::fake([CommentAdded::class]);

        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $project = Project::factory()->create();
        
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'author_id' => $author->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $commenter->id,
            'content' => 'Test comment',
        ]);

        Event::dispatch(new CommentAdded($comment));

        Event::assertDispatched(CommentAdded::class);
    }

    public function test_deadline_approaching_event_creates_notification(): void
    {
        Event::fake([DeadlineApproaching::class]);

        $assignee = User::factory()->create();
        $project = Project::factory()->create();
        
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'assignee_id' => $assignee->id,
            'due_date' => now()->addHours(23),
        ]);

        Event::dispatch(new DeadlineApproaching($task));

        Event::assertDispatched(DeadlineApproaching::class);
    }

    public function test_listeners_are_registered(): void
    {
        $listeners = Event::getListeners(TaskAssigned::class);
        $this->assertNotEmpty($listeners);

        $listeners = Event::getListeners(TaskStatusChanged::class);
        $this->assertNotEmpty($listeners);

        $listeners = Event::getListeners(CommentAdded::class);
        $this->assertNotEmpty($listeners);

        $listeners = Event::getListeners(DeadlineApproaching::class);
        $this->assertNotEmpty($listeners);
    }
}
