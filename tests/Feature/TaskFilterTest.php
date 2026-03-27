<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Stage;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $projectManager;
    protected User $developer1;
    protected User $developer2;
    protected Project $project1;
    protected Project $project2;

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

        $this->developer1 = User::factory()->create();
        $this->developer1->roles()->attach($devRole);

        $this->developer2 = User::factory()->create();
        $this->developer2->roles()->attach($devRole);

        // Create projects with stages
        $this->project1 = Project::factory()->create(['name' => 'Project 1']);
        $this->project2 = Project::factory()->create(['name' => 'Project 2']);
        
        $stages = Stage::factory()->count(3)->create();
        $this->project1->stages()->attach($stages);
        $this->project2->stages()->attach($stages);

        // Add members to projects
        $this->project1->members()->attach([
            $this->projectManager->id,
            $this->developer1->id,
            $this->developer2->id
        ]);
        $this->project2->members()->attach([
            $this->projectManager->id,
            $this->developer1->id
        ]);
    }

    public function test_filter_by_project(): void
    {
        // Create tasks in different projects
        $task1 = Task::factory()->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->projectManager->id,
        ]);

        $task2 = Task::factory()->create([
            'project_id' => $this->project2->id,
            'author_id' => $this->projectManager->id,
        ]);

        $response = $this->actingAs($this->projectManager)
            ->get(route('tasks.index', ['project_id' => $this->project1->id]));

        $response->assertOk();
        $response->assertViewHas('tasks', function ($tasks) use ($task1, $task2) {
            return $tasks->contains($task1) && !$tasks->contains($task2);
        });
    }

    public function test_filter_by_status(): void
    {
        $taskTodo = Task::factory()->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->projectManager->id,
            'status' => TaskStatus::TODO,
        ]);

        $taskInProgress = Task::factory()->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->projectManager->id,
            'status' => TaskStatus::IN_PROGRESS,
        ]);

        $response = $this->actingAs($this->projectManager)
            ->get(route('tasks.index', ['status' => TaskStatus::TODO->value]));

        $response->assertOk();
        $response->assertViewHas('tasks', function ($tasks) use ($taskTodo, $taskInProgress) {
            return $tasks->contains($taskTodo) && !$tasks->contains($taskInProgress);
        });
    }

    public function test_filter_by_priority(): void
    {
        $taskHigh = Task::factory()->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->projectManager->id,
            'priority' => TaskPriority::HIGH,
        ]);

        $taskLow = Task::factory()->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->projectManager->id,
            'priority' => TaskPriority::LOW,
        ]);

        $response = $this->actingAs($this->projectManager)
            ->get(route('tasks.index', ['priority' => TaskPriority::HIGH->value]));

        $response->assertOk();
        $response->assertViewHas('tasks', function ($tasks) use ($taskHigh, $taskLow) {
            return $tasks->contains($taskHigh) && !$tasks->contains($taskLow);
        });
    }

    public function test_filter_by_assignee(): void
    {
        $task1 = Task::factory()->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->projectManager->id,
            'assignee_id' => $this->developer1->id,
        ]);

        $task2 = Task::factory()->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->projectManager->id,
            'assignee_id' => $this->developer2->id,
        ]);

        $response = $this->actingAs($this->projectManager)
            ->get(route('tasks.index', ['assignee_id' => $this->developer1->id]));

        $response->assertOk();
        $response->assertViewHas('tasks', function ($tasks) use ($task1, $task2) {
            return $tasks->contains($task1) && !$tasks->contains($task2);
        });
    }

    public function test_filter_by_author(): void
    {
        $task1 = Task::factory()->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->projectManager->id,
        ]);

        $task2 = Task::factory()->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->developer1->id,
        ]);

        $response = $this->actingAs($this->projectManager)
            ->get(route('tasks.index', ['author_id' => $this->projectManager->id]));

        $response->assertOk();
        $response->assertViewHas('tasks', function ($tasks) use ($task1, $task2) {
            return $tasks->contains($task1) && !$tasks->contains($task2);
        });
    }

    public function test_filter_by_single_tag(): void
    {
        $tag1 = Tag::factory()->create(['name' => 'Bug']);
        $tag2 = Tag::factory()->create(['name' => 'Feature']);

        $task1 = Task::factory()->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->projectManager->id,
        ]);
        $task1->tags()->attach($tag1);

        $task2 = Task::factory()->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->projectManager->id,
        ]);
        $task2->tags()->attach($tag2);

        $response = $this->actingAs($this->projectManager)
            ->get(route('tasks.index', ['tags' => $tag1->id]));

        $response->assertOk();
        $response->assertViewHas('tasks', function ($tasks) use ($task1, $task2) {
            return $tasks->contains($task1) && !$tasks->contains($task2);
        });
    }

    public function test_filter_by_multiple_tags(): void
    {
        $tag1 = Tag::factory()->create(['name' => 'Bug']);
        $tag2 = Tag::factory()->create(['name' => 'Feature']);
        $tag3 = Tag::factory()->create(['name' => 'Enhancement']);

        $task1 = Task::factory()->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->projectManager->id,
        ]);
        $task1->tags()->attach([$tag1->id, $tag2->id]);

        $task2 = Task::factory()->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->projectManager->id,
        ]);
        $task2->tags()->attach($tag3);

        $response = $this->actingAs($this->projectManager)
            ->get(route('tasks.index', ['tags' => [$tag1->id, $tag2->id]]));

        $response->assertOk();
        $response->assertViewHas('tasks', function ($tasks) use ($task1, $task2) {
            // Task1 should be included (has tag1 or tag2)
            // Task2 should not be included (has only tag3)
            return $tasks->contains($task1) && !$tasks->contains($task2);
        });
    }

    public function test_search_by_title(): void
    {
        $task1 = Task::factory()->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->projectManager->id,
            'title' => 'Fix authentication bug',
        ]);

        $task2 = Task::factory()->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->projectManager->id,
            'title' => 'Add new feature',
        ]);

        $response = $this->actingAs($this->projectManager)
            ->get(route('tasks.index', ['search' => 'authentication']));

        $response->assertOk();
        $response->assertViewHas('tasks', function ($tasks) use ($task1, $task2) {
            return $tasks->contains($task1) && !$tasks->contains($task2);
        });
    }

    public function test_search_by_description(): void
    {
        $task1 = Task::factory()->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->projectManager->id,
            'title' => 'Task 1',
            'description' => 'This task involves database optimization',
        ]);

        $task2 = Task::factory()->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->projectManager->id,
            'title' => 'Task 2',
            'description' => 'This task involves UI improvements',
        ]);

        $response = $this->actingAs($this->projectManager)
            ->get(route('tasks.index', ['search' => 'database']));

        $response->assertOk();
        $response->assertViewHas('tasks', function ($tasks) use ($task1, $task2) {
            return $tasks->contains($task1) && !$tasks->contains($task2);
        });
    }

    public function test_multiple_filters_applied_together(): void
    {
        // Create tasks with different combinations
        $matchingTask = Task::factory()->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->projectManager->id,
            'assignee_id' => $this->developer1->id,
            'status' => TaskStatus::IN_PROGRESS,
            'priority' => TaskPriority::HIGH,
            'title' => 'Important authentication fix',
        ]);

        $wrongProject = Task::factory()->create([
            'project_id' => $this->project2->id,
            'author_id' => $this->projectManager->id,
            'assignee_id' => $this->developer1->id,
            'status' => TaskStatus::IN_PROGRESS,
            'priority' => TaskPriority::HIGH,
        ]);

        $wrongStatus = Task::factory()->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->projectManager->id,
            'assignee_id' => $this->developer1->id,
            'status' => TaskStatus::TODO,
            'priority' => TaskPriority::HIGH,
        ]);

        $wrongAssignee = Task::factory()->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->projectManager->id,
            'assignee_id' => $this->developer2->id,
            'status' => TaskStatus::IN_PROGRESS,
            'priority' => TaskPriority::HIGH,
        ]);

        // Apply multiple filters
        $response = $this->actingAs($this->projectManager)
            ->get(route('tasks.index', [
                'project_id' => $this->project1->id,
                'status' => TaskStatus::IN_PROGRESS->value,
                'priority' => TaskPriority::HIGH->value,
                'assignee_id' => $this->developer1->id,
                'search' => 'authentication',
            ]));

        $response->assertOk();
        $response->assertViewHas('tasks', function ($tasks) use ($matchingTask, $wrongProject, $wrongStatus, $wrongAssignee) {
            // Only the matching task should be in results
            return $tasks->contains($matchingTask) 
                && !$tasks->contains($wrongProject)
                && !$tasks->contains($wrongStatus)
                && !$tasks->contains($wrongAssignee);
        });
    }

    public function test_filters_with_no_results(): void
    {
        Task::factory()->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->projectManager->id,
            'status' => TaskStatus::TODO,
        ]);

        $response = $this->actingAs($this->projectManager)
            ->get(route('tasks.index', ['status' => TaskStatus::DONE->value]));

        $response->assertOk();
        $response->assertViewHas('tasks', function ($tasks) {
            return $tasks->count() === 0;
        });
    }

    public function test_filters_are_case_insensitive_for_search(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->projectManager->id,
            'title' => 'Fix Authentication Bug',
        ]);

        $response = $this->actingAs($this->projectManager)
            ->get(route('tasks.index', ['search' => 'AUTHENTICATION']));

        $response->assertOk();
        $response->assertViewHas('tasks', function ($tasks) use ($task) {
            return $tasks->contains($task);
        });
    }

    public function test_pagination_works_with_filters(): void
    {
        // Create 25 tasks with same status
        Task::factory()->count(25)->create([
            'project_id' => $this->project1->id,
            'author_id' => $this->projectManager->id,
            'status' => TaskStatus::TODO,
        ]);

        $response = $this->actingAs($this->projectManager)
            ->get(route('tasks.index', ['status' => TaskStatus::TODO->value]));

        $response->assertOk();
        $response->assertViewHas('tasks', function ($tasks) {
            // Default pagination is 20 per page
            return $tasks->count() === 20 && $tasks->hasMorePages();
        });
    }
}
