<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Stage;
use App\Models\Task;
use App\Models\TaskStage;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\TimeTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TimeEntryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private TaskStage $taskStage;
    private TimeTrackingService $timeTrackingService;

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

        $this->timeTrackingService = app(TimeTrackingService::class);
    }

    public function test_user_can_start_timer()
    {
        $response = $this->actingAs($this->user)
            ->post(route('timer.start', $this->taskStage));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue(
            $this->timeTrackingService->isTimerRunning($this->taskStage, $this->user)
        );
    }

    public function test_user_cannot_start_timer_twice()
    {
        // Запускаем таймер первый раз
        $this->timeTrackingService->startTimer($this->taskStage, $this->user);

        // Пытаемся запустить второй раз
        $response = $this->actingAs($this->user)
            ->post(route('timer.start', $this->taskStage));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_user_can_stop_timer()
    {
        // Запускаем таймер
        $this->timeTrackingService->startTimer($this->taskStage, $this->user);

        // Ждем немного
        sleep(1);

        // Останавливаем таймер
        $response = $this->actingAs($this->user)
            ->post(route('timer.stop', $this->taskStage));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Проверяем, что создана запись времени
        $this->assertDatabaseHas('time_entries', [
            'task_stage_id' => $this->taskStage->id,
            'user_id' => $this->user->id,
        ]);

        // Проверяем, что таймер остановлен
        $this->assertFalse(
            $this->timeTrackingService->isTimerRunning($this->taskStage, $this->user)
        );
    }

    public function test_stopping_timer_without_starting_returns_error()
    {
        $response = $this->actingAs($this->user)
            ->post(route('timer.stop', $this->taskStage));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_timer_status_returns_correct_data()
    {
        // Без запущенного таймера
        $response = $this->actingAs($this->user)
            ->get(route('timer.status', $this->taskStage));

        $response->assertJson([
            'is_running' => false,
            'timer_data' => null,
        ]);

        // С запущенным таймером
        $this->timeTrackingService->startTimer($this->taskStage, $this->user);

        $response = $this->actingAs($this->user)
            ->get(route('timer.status', $this->taskStage));

        $response->assertJson([
            'is_running' => true,
        ]);
        
        $response->assertJsonStructure([
            'is_running',
            'timer_data' => [
                'started_at',
                'task_stage_id',
                'user_id',
            ],
        ]);
    }

    public function test_user_can_create_manual_time_entry()
    {
        $response = $this->actingAs($this->user)
            ->post(route('time-entries.store', $this->taskStage), [
                'hours' => 3.5,
                'date' => now()->toDateString(),
                'description' => 'Manual entry test',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('time_entries', [
            'task_stage_id' => $this->taskStage->id,
            'user_id' => $this->user->id,
            'hours' => 3.5,
            'cost' => 175.0, // 3.5 * 50
            'description' => 'Manual entry test',
        ]);
    }

    public function test_time_entry_cost_is_calculated_correctly()
    {
        $response = $this->actingAs($this->user)
            ->post(route('time-entries.store', $this->taskStage), [
                'hours' => 5,
                'date' => now()->toDateString(),
            ]);

        $timeEntry = TimeEntry::where('user_id', $this->user->id)->first();
        
        $this->assertEquals(250.0, $timeEntry->cost); // 5 * 50
    }

    public function test_user_can_update_their_own_time_entry()
    {
        $timeEntry = TimeEntry::factory()->create([
            'task_stage_id' => $this->taskStage->id,
            'user_id' => $this->user->id,
            'hours' => 3,
            'cost' => 150,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('time-entries.update', $timeEntry), [
                'hours' => 5,
                'date' => now()->toDateString(),
                'description' => 'Updated entry',
            ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('time_entries', [
            'id' => $timeEntry->id,
            'hours' => 5,
            'cost' => 250.0, // 5 * 50
            'description' => 'Updated entry',
        ]);
    }

    public function test_user_cannot_update_another_users_time_entry()
    {
        $otherUser = User::factory()->create();
        $timeEntry = TimeEntry::factory()->create([
            'task_stage_id' => $this->taskStage->id,
            'user_id' => $otherUser->id,
            'hours' => 3,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('time-entries.update', $timeEntry), [
                'hours' => 5,
                'date' => now()->toDateString(),
            ]);

        $response->assertForbidden();
    }

    public function test_user_can_delete_their_own_time_entry()
    {
        $timeEntry = TimeEntry::factory()->create([
            'task_stage_id' => $this->taskStage->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('time-entries.destroy', $timeEntry));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('time_entries', [
            'id' => $timeEntry->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_time_entry()
    {
        $otherUser = User::factory()->create();
        $timeEntry = TimeEntry::factory()->create([
            'task_stage_id' => $this->taskStage->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('time-entries.destroy', $timeEntry));

        $response->assertForbidden();
    }

    public function test_time_entry_requires_valid_hours()
    {
        $response = $this->actingAs($this->user)
            ->post(route('time-entries.store', $this->taskStage), [
                'hours' => -1,
                'date' => now()->toDateString(),
            ]);

        $response->assertSessionHasErrors('hours');
    }

    public function test_time_entry_hours_cannot_exceed_24()
    {
        $response = $this->actingAs($this->user)
            ->post(route('time-entries.store', $this->taskStage), [
                'hours' => 25,
                'date' => now()->toDateString(),
            ]);

        $response->assertSessionHasErrors('hours');
    }

    public function test_time_entry_date_cannot_be_in_future()
    {
        $response = $this->actingAs($this->user)
            ->post(route('time-entries.store', $this->taskStage), [
                'hours' => 5,
                'date' => now()->addDay()->toDateString(),
            ]);

        $response->assertSessionHasErrors('date');
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
