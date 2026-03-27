<?php

namespace Tests\Feature\MoonShine;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run seeders to set up roles and permissions
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
    }

    public function test_task_resource_is_registered(): void
    {
        $this->assertTrue(class_exists(\App\MoonShine\Resources\Task\TaskResource::class));
    }

    public function test_task_can_be_created_with_all_fields(): void
    {
        $project = Project::factory()->create();
        $author = User::factory()->create();
        $assignee = User::factory()->create();

        $task = Task::create([
            'title' => 'Test Task',
            'description' => 'Test Description',
            'project_id' => $project->id,
            'author_id' => $author->id,
            'assignee_id' => $assignee->id,
            'priority' => TaskPriority::HIGH,
            'status' => TaskStatus::TODO,
            'due_date' => now()->addDays(7),
        ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'project_id' => $project->id,
            'author_id' => $author->id,
            'assignee_id' => $assignee->id,
        ]);

        $this->assertEquals(TaskPriority::HIGH, $task->priority);
        $this->assertEquals(TaskStatus::TODO, $task->status);
    }

    public function test_task_relationships_work_correctly(): void
    {
        $project = Project::factory()->create();
        $author = User::factory()->create();
        $assignee = User::factory()->create();

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'author_id' => $author->id,
            'assignee_id' => $assignee->id,
        ]);

        $this->assertEquals($project->id, $task->project->id);
        $this->assertEquals($author->id, $task->author->id);
        $this->assertEquals($assignee->id, $task->assignee->id);
    }

    public function test_task_can_have_tags(): void
    {
        $task = Task::factory()->create();
        $tag = \App\Models\Tag::factory()->create(['name' => 'Bug']);

        $task->tags()->attach($tag);

        $this->assertTrue($task->tags->contains($tag));
        $this->assertEquals('Bug', $task->tags->first()->name);
    }
}
