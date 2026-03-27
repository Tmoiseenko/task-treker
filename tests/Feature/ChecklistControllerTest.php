<?php

namespace Tests\Feature;

use App\Models\ChecklistItem;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'project-manager']);
        $this->user->roles()->attach($role);

        $project = Project::factory()->create();
        $project->members()->attach($this->user);
        
        $this->task = Task::factory()->create([
            'project_id' => $project->id,
            'author_id' => $this->user->id,
        ]);
    }

    /**
     * @test
     * Requirements: 11.1, 11.2
     */
    public function it_can_create_checklist_for_task()
    {
        $items = [
            'Проверить функциональность',
            'Проверить дизайн',
            'Проверить производительность',
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('checklist.store', $this->task), [
                'items' => $items,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'checklist',
                'progress',
            ]);

        $this->assertDatabaseCount('checklist_items', 3);
        
        foreach ($items as $index => $item) {
            $this->assertDatabaseHas('checklist_items', [
                'task_id' => $this->task->id,
                'title' => $item,
                'is_completed' => false,
                'order' => $index + 1,
            ]);
        }
    }

    /**
     * @test
     * Requirements: 11.1
     */
    public function it_requires_items_to_create_checklist()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('checklist.store', $this->task), [
                'items' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    /**
     * @test
     * Requirements: 11.3
     */
    public function it_can_toggle_checklist_item()
    {
        $checklistItem = ChecklistItem::factory()->create([
            'task_id' => $this->task->id,
            'is_completed' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson(route('checklist.toggle', $checklistItem));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'item',
                'progress',
            ]);

        $this->assertDatabaseHas('checklist_items', [
            'id' => $checklistItem->id,
            'is_completed' => true,
        ]);

        // Toggle back
        $response = $this->actingAs($this->user)
            ->patchJson(route('checklist.toggle', $checklistItem));

        $response->assertStatus(200);

        $this->assertDatabaseHas('checklist_items', [
            'id' => $checklistItem->id,
            'is_completed' => false,
        ]);
    }

    /**
     * @test
     * Requirements: 11.4
     */
    public function it_can_get_checklist_progress()
    {
        // Create 4 items, complete 2
        ChecklistItem::factory()->count(2)->create([
            'task_id' => $this->task->id,
            'is_completed' => true,
        ]);

        ChecklistItem::factory()->count(2)->create([
            'task_id' => $this->task->id,
            'is_completed' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('checklist.progress', $this->task));

        $response->assertStatus(200)
            ->assertJson([
                'progress' => 50.0,
                'total' => 4,
                'completed' => 2,
            ]);
    }

    /**
     * @test
     * Requirements: 11.4
     */
    public function it_returns_zero_progress_for_empty_checklist()
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('checklist.progress', $this->task));

        $response->assertStatus(200)
            ->assertJson([
                'progress' => 0.0,
                'total' => 0,
                'completed' => 0,
            ]);
    }

    /**
     * @test
     */
    public function it_can_delete_checklist_item()
    {
        $checklistItem = ChecklistItem::factory()->create([
            'task_id' => $this->task->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('checklist.destroy', $checklistItem));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'progress',
            ]);

        $this->assertDatabaseMissing('checklist_items', [
            'id' => $checklistItem->id,
        ]);
    }

    /**
     * @test
     */
    public function unauthorized_user_cannot_create_checklist()
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->postJson(route('checklist.store', $this->task), [
                'items' => ['Test item'],
            ]);

        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function unauthorized_user_cannot_toggle_checklist_item()
    {
        $otherUser = User::factory()->create();
        $checklistItem = ChecklistItem::factory()->create([
            'task_id' => $this->task->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->patchJson(route('checklist.toggle', $checklistItem));

        $response->assertStatus(403);
    }
}
