<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckDeadlinesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::factory()->create(['name' => 'project-manager']);
        Role::factory()->create(['name' => 'developer']);
    }

    public function test_command_sends_notification_for_approaching_deadline(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $manager = User::factory()->create();
        $managerRole = Role::where('name', 'project-manager')->first();
        $manager->roles()->attach($managerRole);
        $project->members()->attach([$user->id, $manager->id]);

        // Create task with deadline in 23 hours
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'assignee_id' => $user->id,
            'due_date' => Carbon::now()->addHours(23),
            'status' => TaskStatus::IN_PROGRESS,
        ]);

        $this->artisan('tasks:check-deadlines')
            ->assertExitCode(0);

        // Check that notifications were created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'deadline_approaching',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $manager->id,
            'type' => 'deadline_approaching',
        ]);
    }

    public function test_command_sends_notification_for_expired_deadline(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $manager = User::factory()->create();
        $managerRole = Role::where('name', 'project-manager')->first();
        $manager->roles()->attach($managerRole);
        $project->members()->attach([$user->id, $manager->id]);

        // Create task with expired deadline
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'assignee_id' => $user->id,
            'due_date' => Carbon::now()->subHours(2),
            'status' => TaskStatus::IN_PROGRESS,
        ]);

        $this->artisan('tasks:check-deadlines')
            ->assertExitCode(0);

        // Check that notifications were created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'deadline_expired',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $manager->id,
            'type' => 'deadline_expired',
        ]);
    }

    public function test_command_does_not_send_duplicate_notifications(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $project->members()->attach($user->id);

        // Create task with deadline in 23 hours
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'assignee_id' => $user->id,
            'due_date' => Carbon::now()->addHours(23),
            'status' => TaskStatus::IN_PROGRESS,
        ]);

        // Run command first time
        $this->artisan('tasks:check-deadlines');

        $notificationCount = Notification::where('user_id', $user->id)
            ->where('type', 'deadline_approaching')
            ->count();

        $this->assertEquals(1, $notificationCount);

        // Run command second time
        $this->artisan('tasks:check-deadlines');

        // Should still be 1 notification (no duplicate)
        $notificationCount = Notification::where('user_id', $user->id)
            ->where('type', 'deadline_approaching')
            ->count();

        $this->assertEquals(1, $notificationCount);
    }

    public function test_command_ignores_completed_tasks(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $project->members()->attach($user->id);

        // Create completed task with expired deadline
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'assignee_id' => $user->id,
            'due_date' => Carbon::now()->subHours(2),
            'status' => TaskStatus::DONE,
        ]);

        $this->artisan('tasks:check-deadlines')
            ->assertExitCode(0);

        // No notifications should be created for completed tasks
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $user->id,
            'type' => 'deadline_expired',
        ]);
    }

    public function test_command_ignores_tasks_without_deadline(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $project->members()->attach($user->id);

        // Create task without deadline
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'assignee_id' => $user->id,
            'due_date' => null,
            'status' => TaskStatus::IN_PROGRESS,
        ]);

        $this->artisan('tasks:check-deadlines')
            ->assertExitCode(0);

        // No notifications should be created
        $notificationCount = Notification::where('user_id', $user->id)->count();
        $this->assertEquals(0, $notificationCount);
    }

    public function test_command_ignores_tasks_with_deadline_beyond_24_hours(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $project->members()->attach($user->id);

        // Create task with deadline in 25 hours
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'assignee_id' => $user->id,
            'due_date' => Carbon::now()->addHours(25),
            'status' => TaskStatus::IN_PROGRESS,
        ]);

        $this->artisan('tasks:check-deadlines')
            ->assertExitCode(0);

        // No notifications should be created
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $user->id,
            'type' => 'deadline_approaching',
        ]);
    }
}
