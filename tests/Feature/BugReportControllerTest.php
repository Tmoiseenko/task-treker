<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BugReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'tester']);
        $this->user->roles()->attach($role);

        $project = Project::factory()->create();
        $project->members()->attach($this->user);
        
        $this->task = Task::factory()->create([
            'project_id' => $project->id,
            'author_id' => $this->user->id,
            'status' => TaskStatus::IN_TESTING,
        ]);
    }

    /**
     * @test
     * Requirements: 11.5
     */
    public function it_can_display_bug_report_creation_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('bug-reports.create', $this->task));

        $response->assertStatus(200)
            ->assertViewIs('bug-reports.create')
            ->assertViewHas('task', $this->task)
            ->assertViewHas('priorities');
    }

    /**
     * @test
     * Requirements: 11.5, 11.6, 11.7
     */
    public function it_can_create_bug_report()
    {
        $developer = User::factory()->create();

        $bugData = [
            'title' => 'Кнопка не работает',
            'description' => 'При нажатии на кнопку ничего не происходит',
            'steps_to_reproduce' => '1. Открыть страницу\n2. Нажать кнопку',
            'expected_result' => 'Должна открыться форма',
            'actual_result' => 'Ничего не происходит',
            'assignee_id' => $developer->id,
            'priority' => TaskPriority::HIGH->value,
            'due_date' => now()->addDays(3)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->post(route('bug-reports.store', $this->task), $bugData);

        $response->assertRedirect(route('tasks.show', $this->task))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Кнопка не работает',
            'project_id' => $this->task->project_id,
            'parent_task_id' => $this->task->id,
            'assignee_id' => $developer->id,
            'priority' => TaskPriority::HIGH->value,
            'status' => TaskStatus::TODO->value,
        ]);

        // Check that description includes all sections
        $bugReport = Task::where('parent_task_id', $this->task->id)->first();
        $this->assertStringContainsString('При нажатии на кнопку ничего не происходит', $bugReport->description);
        $this->assertStringContainsString('Шаги воспроизведения:', $bugReport->description);
        $this->assertStringContainsString('Ожидаемый результат:', $bugReport->description);
        $this->assertStringContainsString('Фактический результат:', $bugReport->description);
    }

    /**
     * @test
     * Requirements: 11.5
     */
    public function it_requires_title_and_description_for_bug_report()
    {
        $response = $this->actingAs($this->user)
            ->post(route('bug-reports.store', $this->task), [
                'priority' => TaskPriority::MEDIUM->value,
            ]);

        $response->assertSessionHasErrors(['title', 'description']);
    }

    /**
     * @test
     * Requirements: 11.7
     */
    public function bug_report_inherits_assignee_from_original_task_if_not_specified()
    {
        $developer = User::factory()->create();
        $this->task->update(['assignee_id' => $developer->id]);

        $bugData = [
            'title' => 'Bug title',
            'description' => 'Bug description',
            'priority' => TaskPriority::MEDIUM->value,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('bug-reports.store', $this->task), $bugData);

        $response->assertRedirect();

        $bugReport = Task::where('parent_task_id', $this->task->id)->first();
        $this->assertEquals($developer->id, $bugReport->assignee_id);
    }

    /**
     * @test
     * Requirements: 11.11
     */
    public function it_can_display_bug_reports_list()
    {
        // Create some bug reports
        $bugReport1 = Task::factory()->create([
            'project_id' => $this->task->project_id,
            'parent_task_id' => $this->task->id,
            'status' => TaskStatus::TODO,
        ]);

        $bugReport2 = Task::factory()->create([
            'project_id' => $this->task->project_id,
            'parent_task_id' => $this->task->id,
            'status' => TaskStatus::DONE,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('bug-reports.index', $this->task));

        $response->assertStatus(200)
            ->assertViewIs('bug-reports.index')
            ->assertViewHas('task', $this->task)
            ->assertViewHas('bugReports')
            ->assertViewHas('allBugsFixed');

        $bugReports = $response->viewData('bugReports');
        $this->assertCount(2, $bugReports);
    }

    /**
     * @test
     * Requirements: 11.13
     */
    public function it_checks_if_all_bugs_are_fixed()
    {
        // Create bug reports - all done
        Task::factory()->count(2)->create([
            'project_id' => $this->task->project_id,
            'parent_task_id' => $this->task->id,
            'status' => TaskStatus::DONE,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('bug-reports.index', $this->task));

        $response->assertStatus(200);
        $this->assertTrue($response->viewData('allBugsFixed'));
    }

    /**
     * @test
     * Requirements: 11.13
     */
    public function it_detects_when_not_all_bugs_are_fixed()
    {
        // Create bug reports - one not done
        Task::factory()->create([
            'project_id' => $this->task->project_id,
            'parent_task_id' => $this->task->id,
            'status' => TaskStatus::DONE,
        ]);

        Task::factory()->create([
            'project_id' => $this->task->project_id,
            'parent_task_id' => $this->task->id,
            'status' => TaskStatus::IN_PROGRESS,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('bug-reports.index', $this->task));

        $response->assertStatus(200);
        $this->assertFalse($response->viewData('allBugsFixed'));
    }

    /**
     * @test
     * Requirements: 11.12
     */
    public function it_can_assign_bug_report_to_developer()
    {
        $developer = User::factory()->create();
        $bugReport = Task::factory()->create([
            'project_id' => $this->task->project_id,
            'parent_task_id' => $this->task->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('bug-reports.assign', $bugReport), [
                'assignee_id' => $developer->id,
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tasks', [
            'id' => $bugReport->id,
            'assignee_id' => $developer->id,
        ]);
    }

    /**
     * @test
     * Requirements: 11.12
     */
    public function it_requires_valid_assignee_id()
    {
        $bugReport = Task::factory()->create([
            'project_id' => $this->task->project_id,
            'parent_task_id' => $this->task->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('bug-reports.assign', $bugReport), [
                'assignee_id' => 99999,
            ]);

        $response->assertSessionHasErrors(['assignee_id']);
    }

    /**
     * @test
     */
    public function unauthorized_user_cannot_create_bug_report()
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->get(route('bug-reports.create', $this->task));

        $response->assertStatus(403);
    }
}
