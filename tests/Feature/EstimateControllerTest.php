<?php

namespace Tests\Feature;

use App\Models\Estimate;
use App\Models\Project;
use App\Models\Stage;
use App\Models\Task;
use App\Models\TaskStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimateControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private TaskStage $taskStage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['hourly_rate' => 50]);
        
        $project = Project::factory()->create();
        $stage = Stage::factory()->create();
        $project->stages()->attach($stage);
        
        $task = Task::factory()->create(['project_id' => $project->id]);
        $this->taskStage = TaskStage::factory()->create([
            'task_id' => $task->id,
            'stage_id' => $stage->id,
        ]);
    }

    public function test_user_can_create_estimate()
    {
        $response = $this->actingAs($this->user)
            ->post(route('estimates.store', $this->taskStage), [
                'hours' => 5.5,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('estimates', [
            'task_stage_id' => $this->taskStage->id,
            'user_id' => $this->user->id,
            'hours' => 5.5,
        ]);
    }

    public function test_user_can_update_their_own_estimate()
    {
        $estimate = Estimate::factory()->create([
            'task_stage_id' => $this->taskStage->id,
            'user_id' => $this->user->id,
            'hours' => 3,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('estimates.store', $this->taskStage), [
                'hours' => 8,
            ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('estimates', [
            'id' => $estimate->id,
            'hours' => 8,
        ]);
    }

    public function test_multiple_users_can_estimate_same_stage()
    {
        $user2 = User::factory()->create();

        // Первый пользователь создает оценку
        $this->actingAs($this->user)
            ->post(route('estimates.store', $this->taskStage), [
                'hours' => 5,
            ]);

        // Второй пользователь создает свою оценку
        $this->actingAs($user2)
            ->post(route('estimates.store', $this->taskStage), [
                'hours' => 7,
            ]);

        // Обе оценки должны существовать
        $this->assertDatabaseHas('estimates', [
            'task_stage_id' => $this->taskStage->id,
            'user_id' => $this->user->id,
            'hours' => 5,
        ]);

        $this->assertDatabaseHas('estimates', [
            'task_stage_id' => $this->taskStage->id,
            'user_id' => $user2->id,
            'hours' => 7,
        ]);
    }

    public function test_user_cannot_update_another_users_estimate()
    {
        $otherUser = User::factory()->create();
        $estimate = Estimate::factory()->create([
            'task_stage_id' => $this->taskStage->id,
            'user_id' => $otherUser->id,
            'hours' => 5,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('estimates.update', $estimate), [
                'hours' => 10,
            ]);

        $response->assertForbidden();
    }

    public function test_user_cannot_delete_another_users_estimate()
    {
        $otherUser = User::factory()->create();
        $estimate = Estimate::factory()->create([
            'task_stage_id' => $this->taskStage->id,
            'user_id' => $otherUser->id,
            'hours' => 5,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('estimates.destroy', $estimate));

        $response->assertForbidden();
    }

    public function test_estimate_requires_valid_hours()
    {
        $response = $this->actingAs($this->user)
            ->post(route('estimates.store', $this->taskStage), [
                'hours' => -1,
            ]);

        $response->assertSessionHasErrors('hours');
    }

    public function test_estimate_hours_cannot_exceed_maximum()
    {
        $response = $this->actingAs($this->user)
            ->post(route('estimates.store', $this->taskStage), [
                'hours' => 1001,
            ]);

        $response->assertSessionHasErrors('hours');
    }
}
