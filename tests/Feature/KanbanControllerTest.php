<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Role;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KanbanControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $manager;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $developerRole = Role::factory()->create(['name' => 'developer']);
        $managerRole = Role::factory()->create(['name' => 'project-manager']);

        // Create users
        $this->user = User::factory()->create();
        $this->user->roles()->attach($developerRole);

        $this->manager = User::factory()->create();
        $this->manager->roles()->attach($managerRole);

        // Create project
        $this->project = Project::factory()->create();
    }

    public function test_index_displays_kanban_board(): void
    {
        $response = $this->actingAs($this->user)->get(route('kanban.index'));

        $response->assertStatus(200);
        $response->assertViewIs('kanban.index');
        $response->assertViewHas('tasksByStatus');
        $response->assertViewHas('projects');
    }

    public function test_index_groups_tasks_by_status(): void
    {
        // Create tasks with different statuses
        $todoTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::TODO,
        ]);

        $inProgressTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::IN_PROGRESS,
        ]);

        $inTestingTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::IN_TESTING,
        ]);

        $testFailedTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::TEST_FAILED,
        ]);

        $doneTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::DONE,
        ]);

        $response = $this->actingAs($this->user)->get(route('kanban.index'));

        $response->assertStatus(200);
        
        $tasksByStatus = $response->viewData('tasksByStatus');
        
        $this->assertCount(1, $tasksByStatus[TaskStatus::TODO->value]);
        $this->assertCount(1, $tasksByStatus[TaskStatus::IN_PROGRESS->value]);
        $this->assertCount(1, $tasksByStatus[TaskStatus::IN_TESTING->value]);
        $this->assertCount(1, $tasksByStatus[TaskStatus::TEST_FAILED->value]);
        $this->assertCount(1, $tasksByStatus[TaskStatus::DONE->value]);
    }

    public function test_index_filters_by_project(): void
    {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        Task::factory()->create([
            'project_id' => $project1->id,
            'status' => TaskStatus::TODO,
        ]);

        Task::factory()->create([
            'project_id' => $project2->id,
            'status' => TaskStatus::TODO,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('kanban.index', ['project_id' => $project1->id]));

        $response->assertStatus(200);
        
        $tasksByStatus = $response->viewData('tasksByStatus');
        $allTasks = collect($tasksByStatus)->flatten();
        
        $this->assertCount(1, $allTasks);
        $this->assertEquals($project1->id, $allTasks->first()->project_id);
    }

    public function test_index_filters_by_assignee(): void
    {
        $assignee1 = User::factory()->create();
        $assignee2 = User::factory()->create();

        Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $assignee1->id,
            'status' => TaskStatus::TODO,
        ]);

        Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $assignee2->id,
            'status' => TaskStatus::TODO,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('kanban.index', ['assignee_id' => $assignee1->id]));

        $response->assertStatus(200);
        
        $tasksByStatus = $response->viewData('tasksByStatus');
        $allTasks = collect($tasksByStatus)->flatten();
        
        $this->assertCount(1, $allTasks);
        $this->assertEquals($assignee1->id, $allTasks->first()->assignee_id);
    }

    public function test_index_filters_by_priority(): void
    {
        Task::factory()->create([
            'project_id' => $this->project->id,
            'priority' => TaskPriority::HIGH,
            'status' => TaskStatus::TODO,
        ]);

        Task::factory()->create([
            'project_id' => $this->project->id,
            'priority' => TaskPriority::LOW,
            'status' => TaskStatus::TODO,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('kanban.index', ['priority' => TaskPriority::HIGH->value]));

        $response->assertStatus(200);
        
        $tasksByStatus = $response->viewData('tasksByStatus');
        $allTasks = collect($tasksByStatus)->flatten();
        
        $this->assertCount(1, $allTasks);
        $this->assertEquals(TaskPriority::HIGH, $allTasks->first()->priority);
    }

    public function test_index_filters_by_tags(): void
    {
        $tag1 = Tag::factory()->create(['name' => 'frontend']);
        $tag2 = Tag::factory()->create(['name' => 'backend']);

        $task1 = Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::TODO,
        ]);
        $task1->tags()->attach($tag1);

        $task2 = Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::TODO,
        ]);
        $task2->tags()->attach($tag2);

        $response = $this->actingAs($this->user)
            ->get(route('kanban.index', ['tags' => [$tag1->id]]));

        $response->assertStatus(200);
        
        $tasksByStatus = $response->viewData('tasksByStatus');
        $allTasks = collect($tasksByStatus)->flatten();
        
        $this->assertCount(1, $allTasks);
        $this->assertTrue($allTasks->first()->tags->contains($tag1));
    }

    public function test_index_applies_multiple_filters(): void
    {
        $assignee = User::factory()->create();
        $tag = Tag::factory()->create();

        $matchingTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $assignee->id,
            'priority' => TaskPriority::HIGH,
            'status' => TaskStatus::TODO,
        ]);
        $matchingTask->tags()->attach($tag);

        // Create non-matching tasks
        Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $assignee->id,
            'priority' => TaskPriority::LOW, // Different priority
            'status' => TaskStatus::TODO,
        ]);

        $response = $this->actingAs($this->user)->get(route('kanban.index', [
            'project_id' => $this->project->id,
            'assignee_id' => $assignee->id,
            'priority' => TaskPriority::HIGH->value,
            'tags' => [$tag->id],
        ]));

        $response->assertStatus(200);
        
        $tasksByStatus = $response->viewData('tasksByStatus');
        $allTasks = collect($tasksByStatus)->flatten();
        
        $this->assertCount(1, $allTasks);
        $this->assertEquals($matchingTask->id, $allTasks->first()->id);
    }

    public function test_update_status_changes_task_status(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'author_id' => $this->manager->id, // Manager is the author
            'status' => TaskStatus::TODO,
        ]);

        $response = $this->actingAs($this->manager)
            ->patchJson(route('kanban.update-status', $task), [
                'status' => TaskStatus::IN_PROGRESS->value,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'task' => [
                'id' => $task->id,
                'status' => TaskStatus::IN_PROGRESS->value,
            ],
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::IN_PROGRESS->value,
        ]);
    }

    public function test_update_status_validates_status_transitions(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'author_id' => $this->manager->id, // Manager is the author
            'status' => TaskStatus::DONE,
        ]);

        $response = $this->actingAs($this->manager)
            ->patchJson(route('kanban.update-status', $task), [
                'status' => TaskStatus::TODO->value,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);

        // Status should not change
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::DONE->value,
        ]);
    }

    public function test_update_status_requires_valid_status(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'author_id' => $this->manager->id, // Manager is the author
            'status' => TaskStatus::TODO,
        ]);

        $response = $this->actingAs($this->manager)
            ->patchJson(route('kanban.update-status', $task), [
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('status');
    }

    public function test_update_status_requires_authentication(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::TODO,
        ]);

        $response = $this->patchJson(route('kanban.update-status', $task), [
            'status' => TaskStatus::IN_PROGRESS->value,
        ]);

        $response->assertStatus(401);
    }

    public function test_update_status_requires_authorization(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => TaskStatus::TODO,
        ]);

        // User without update permission
        $unauthorizedUser = User::factory()->create();

        $response = $this->actingAs($unauthorizedUser)
            ->patchJson(route('kanban.update-status', $task), [
                'status' => TaskStatus::IN_PROGRESS->value,
            ]);

        $response->assertStatus(403);
    }

    public function test_update_status_returns_json_response(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'author_id' => $this->manager->id, // Manager is the author
            'status' => TaskStatus::TODO,
        ]);

        $response = $this->actingAs($this->manager)
            ->patchJson(route('kanban.update-status', $task), [
                'status' => TaskStatus::IN_PROGRESS->value,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'task' => [
                'id',
                'status',
            ],
        ]);
    }

    public function test_kanban_board_loads_task_relationships(): void
    {
        $assignee = User::factory()->create();
        $tag = Tag::factory()->create();

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $assignee->id,
            'status' => TaskStatus::TODO,
        ]);
        $task->tags()->attach($tag);

        $response = $this->actingAs($this->user)->get(route('kanban.index'));

        $response->assertStatus(200);
        
        $tasksByStatus = $response->viewData('tasksByStatus');
        $loadedTask = $tasksByStatus[TaskStatus::TODO->value]->first();
        
        // Check that relationships are loaded
        $this->assertTrue($loadedTask->relationLoaded('project'));
        $this->assertTrue($loadedTask->relationLoaded('assignee'));
        $this->assertTrue($loadedTask->relationLoaded('tags'));
    }
}
