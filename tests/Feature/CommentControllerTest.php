<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $projectManager;
    protected User $developer;
    protected User $outsider;
    protected Project $project;
    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed permissions and roles
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);

        // Get roles
        $pmRole = \App\Models\Role::where('name', 'project_manager')->first();
        $devRole = \App\Models\Role::where('name', 'developer')->first();

        // Create users
        $this->projectManager = User::factory()->create();
        $this->projectManager->roles()->attach($pmRole);

        $this->developer = User::factory()->create();
        $this->developer->roles()->attach($devRole);

        $this->outsider = User::factory()->create();
        $this->outsider->roles()->attach($devRole);

        // Create project and task
        $this->project = Project::factory()->create();
        $this->project->members()->attach([$this->projectManager->id, $this->developer->id]);

        $this->task = Task::factory()->create([
            'project_id' => $this->project->id,
            'author_id' => $this->projectManager->id,
        ]);
    }

    public function test_project_member_can_add_comment(): void
    {
        $response = $this->actingAs($this->developer)
            ->post(route('comments.store', $this->task), [
                'content' => 'This is a test comment',
            ]);

        $response->assertRedirect(route('tasks.show', $this->task));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('comments', [
            'task_id' => $this->task->id,
            'user_id' => $this->developer->id,
            'content' => 'This is a test comment',
        ]);
    }

    public function test_comment_requires_content(): void
    {
        $response = $this->actingAs($this->developer)
            ->post(route('comments.store', $this->task), [
                'content' => '',
            ]);

        $response->assertSessionHasErrors('content');
    }

    public function test_comment_content_cannot_exceed_max_length(): void
    {
        $longContent = str_repeat('a', 10001);

        $response = $this->actingAs($this->developer)
            ->post(route('comments.store', $this->task), [
                'content' => $longContent,
            ]);

        $response->assertSessionHasErrors('content');
    }

    public function test_non_project_member_cannot_add_comment(): void
    {
        $response = $this->actingAs($this->outsider)
            ->post(route('comments.store', $this->task), [
                'content' => 'This should not work',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('comments', [
            'task_id' => $this->task->id,
            'user_id' => $this->outsider->id,
        ]);
    }

    public function test_user_can_delete_own_comment(): void
    {
        $comment = Comment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->developer->id,
            'content' => 'My comment',
        ]);

        $response = $this->actingAs($this->developer)
            ->delete(route('comments.destroy', [$this->task, $comment]));

        $response->assertRedirect(route('tasks.show', $this->task));
        $response->assertSessionHas('success');

        $comment->refresh();
        $this->assertNotNull($comment->deleted_at);
    }

    public function test_user_cannot_delete_others_comment(): void
    {
        $comment = Comment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->projectManager->id,
            'content' => 'PM comment',
        ]);

        $response = $this->actingAs($this->developer)
            ->delete(route('comments.destroy', [$this->task, $comment]));

        $response->assertForbidden();

        $comment->refresh();
        $this->assertNull($comment->deleted_at);
    }

    public function test_cannot_delete_comment_from_different_task(): void
    {
        $otherTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'author_id' => $this->projectManager->id,
        ]);

        $comment = Comment::factory()->create([
            'task_id' => $otherTask->id,
            'user_id' => $this->developer->id,
            'content' => 'Comment on other task',
        ]);

        $response = $this->actingAs($this->developer)
            ->delete(route('comments.destroy', [$this->task, $comment]));

        $response->assertNotFound();
    }

    public function test_multiple_users_can_comment_on_same_task(): void
    {
        $this->actingAs($this->developer)
            ->post(route('comments.store', $this->task), [
                'content' => 'Developer comment',
            ]);

        $this->actingAs($this->projectManager)
            ->post(route('comments.store', $this->task), [
                'content' => 'PM comment',
            ]);

        $this->assertEquals(2, $this->task->comments()->count());
    }
}
