<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskFilterComponentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->user = User::factory()->create();
        $role = \App\Models\Role::where('name', 'project_manager')->first();
        $this->user->roles()->attach($role);

        $this->project = Project::factory()->create();
    }

    public function test_filter_component_renders_with_alpine_js(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('tasks.index'));

        $response->assertStatus(200);
        $response->assertSee('x-data="taskFilters()"', false);
        $response->assertSee('x-init="initFromUrl()"', false);
    }

    public function test_filters_preserve_state_in_url(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::IN_PROGRESS,
            'priority' => TaskPriority::HIGH,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('tasks.index', [
                'status' => TaskStatus::IN_PROGRESS->value,
                'priority' => TaskPriority::HIGH->value,
            ]));

        $response->assertStatus(200);
        $response->assertSee($task->title);
    }

    public function test_search_filter_works(): void
    {
        $task1 = Task::factory()->create([
            'project_id' => $this->project->id,
            'title' => 'Unique Search Term',
        ]);

        $task2 = Task::factory()->create([
            'project_id' => $this->project->id,
            'title' => 'Different Title',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('tasks.index', ['search' => 'Unique']));

        $response->assertStatus(200);
        $response->assertSee($task1->title);
        $response->assertDontSee($task2->title);
    }

    public function test_project_filter_works(): void
    {
        $project2 = Project::factory()->create();

        $task1 = Task::factory()->create(['project_id' => $this->project->id]);
        $task2 = Task::factory()->create(['project_id' => $project2->id]);

        $response = $this->actingAs($this->user)
            ->get(route('tasks.index', ['project_id' => $this->project->id]));

        $response->assertStatus(200);
        $response->assertSee($task1->title);
        $response->assertDontSee($task2->title);
    }

    public function test_status_filter_works(): void
    {
        $task1 = Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::TODO,
        ]);

        $task2 = Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::DONE,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('tasks.index', ['status' => TaskStatus::TODO->value]));

        $response->assertStatus(200);
        $response->assertSee($task1->title);
        $response->assertDontSee($task2->title);
    }

    public function test_priority_filter_works(): void
    {
        $task1 = Task::factory()->create([
            'project_id' => $this->project->id,
            'priority' => TaskPriority::HIGH,
        ]);

        $task2 = Task::factory()->create([
            'project_id' => $this->project->id,
            'priority' => TaskPriority::LOW,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('tasks.index', ['priority' => TaskPriority::HIGH->value]));

        $response->assertStatus(200);
        $response->assertSee($task1->title);
        $response->assertDontSee($task2->title);
    }

    public function test_assignee_filter_works(): void
    {
        $assignee = User::factory()->create();

        $task1 = Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $assignee->id,
        ]);

        $task2 = Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('tasks.index', ['assignee_id' => $assignee->id]));

        $response->assertStatus(200);
        $response->assertSee($task1->title);
        $response->assertDontSee($task2->title);
    }

    public function test_author_filter_works(): void
    {
        $author = User::factory()->create();

        $task1 = Task::factory()->create([
            'project_id' => $this->project->id,
            'author_id' => $author->id,
        ]);

        $task2 = Task::factory()->create([
            'project_id' => $this->project->id,
            'author_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('tasks.index', ['author_id' => $author->id]));

        $response->assertStatus(200);
        $response->assertSee($task1->title);
        $response->assertDontSee($task2->title);
    }

    public function test_tags_filter_works_with_comma_separated_values(): void
    {
        $tag1 = Tag::factory()->create(['name' => 'Backend']);
        $tag2 = Tag::factory()->create(['name' => 'Frontend']);
        $tag3 = Tag::factory()->create(['name' => 'Design']);

        $task1 = Task::factory()->create(['project_id' => $this->project->id]);
        $task1->tags()->attach([$tag1->id, $tag2->id]);

        $task2 = Task::factory()->create(['project_id' => $this->project->id]);
        $task2->tags()->attach([$tag3->id]);

        $task3 = Task::factory()->create(['project_id' => $this->project->id]);
        // No tags

        // Filter by tag1 and tag2 (comma-separated)
        $response = $this->actingAs($this->user)
            ->get(route('tasks.index', ['tags' => "{$tag1->id},{$tag2->id}"]));

        $response->assertStatus(200);
        $response->assertSee($task1->title);
        $response->assertDontSee($task2->title);
        $response->assertDontSee($task3->title);
    }

    public function test_multiple_filters_work_together(): void
    {
        $assignee = User::factory()->create();
        $tag = Tag::factory()->create();

        $task1 = Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::IN_PROGRESS,
            'priority' => TaskPriority::HIGH,
            'assignee_id' => $assignee->id,
        ]);
        $task1->tags()->attach($tag);

        $task2 = Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::TODO,
            'priority' => TaskPriority::HIGH,
            'assignee_id' => $assignee->id,
        ]);

        $task3 = Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::IN_PROGRESS,
            'priority' => TaskPriority::LOW,
            'assignee_id' => $assignee->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('tasks.index', [
                'status' => TaskStatus::IN_PROGRESS->value,
                'priority' => TaskPriority::HIGH->value,
                'assignee_id' => $assignee->id,
                'tags' => $tag->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee($task1->title);
        $response->assertDontSee($task2->title);
        $response->assertDontSee($task3->title);
    }

    public function test_filter_component_shows_active_filters_indicator(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('tasks.index', ['status' => TaskStatus::TODO->value]));

        $response->assertStatus(200);
        $response->assertSee('x-show="hasActiveFilters()"', false);
    }

    public function test_empty_filters_are_not_added_to_url(): void
    {
        Task::factory()->create(['project_id' => $this->project->id]);

        $response = $this->actingAs($this->user)
            ->get(route('tasks.index', [
                'search' => '',
                'project_id' => '',
                'status' => '',
            ]));

        $response->assertStatus(200);
    }
}
