<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskViewPartialsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
        $this->project->members()->attach($this->user);
        $this->task = Task::factory()->create([
            'project_id' => $this->project->id,
            'author_id' => $this->user->id,
        ]);
    }

    public function test_task_show_page_includes_comments_partial(): void
    {
        Comment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'content' => 'Test comment',
        ]);

        $response = $this->actingAs($this->user)->get(route('tasks.show', $this->task));

        $response->assertStatus(200);
        $response->assertSee('Комментарии');
        $response->assertSee('Test comment');
        $response->assertSee('Добавить комментарий');
    }

    public function test_task_show_page_includes_attachments_partial(): void
    {
        Attachment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'original_name' => 'test-file.pdf',
        ]);

        $response = $this->actingAs($this->user)->get(route('tasks.show', $this->task));

        $response->assertStatus(200);
        $response->assertSee('Прикрепленные файлы');
        $response->assertSee('test-file.pdf');
        $response->assertSee('Загрузить файл');
    }

    public function test_task_show_page_includes_audit_history_partial(): void
    {
        AuditLog::create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'field' => 'status',
            'old_value' => 'todo',
            'new_value' => 'in_progress',
        ]);

        $response = $this->actingAs($this->user)->get(route('tasks.show', $this->task));

        $response->assertStatus(200);
        $response->assertSee('История изменений');
        $response->assertSee('status');
        $response->assertSee('todo');
        $response->assertSee('in_progress');
    }

    public function test_comments_partial_shows_comment_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('tasks.show', $this->task));

        $response->assertStatus(200);
        $response->assertSee('name="content"', false);
        $response->assertSee('Отправить');
    }

    public function test_attachments_partial_shows_upload_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('tasks.show', $this->task));

        $response->assertStatus(200);
        $response->assertSee('name="file"', false);
        $response->assertSee('enctype="multipart/form-data"', false);
    }

    public function test_comments_partial_shows_delete_button_for_own_comments(): void
    {
        Comment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'content' => 'My comment',
        ]);

        $response = $this->actingAs($this->user)->get(route('tasks.show', $this->task));

        $response->assertStatus(200);
        $response->assertSee('Удалить');
    }

    public function test_comments_partial_does_not_show_deleted_comments(): void
    {
        Comment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'content' => 'Deleted comment',
            'deleted_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get(route('tasks.show', $this->task));

        $response->assertStatus(200);
        $response->assertDontSee('Deleted comment');
    }

    public function test_attachments_partial_shows_download_link(): void
    {
        $attachment = Attachment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'original_name' => 'document.pdf',
        ]);

        $response = $this->actingAs($this->user)->get(route('tasks.show', $this->task));

        $response->assertStatus(200);
        $response->assertSee('Скачать');
        $response->assertSee(route('attachments.download', [$this->task, $attachment]));
    }

    public function test_audit_history_partial_shows_timeline_format(): void
    {
        AuditLog::create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'field' => 'priority',
            'old_value' => 'low',
            'new_value' => 'high',
        ]);

        $response = $this->actingAs($this->user)->get(route('tasks.show', $this->task));

        $response->assertStatus(200);
        $response->assertSee($this->user->name);
        $response->assertSee('изменил(а)');
        $response->assertSee('priority');
    }

    public function test_audit_history_partial_limits_to_20_entries(): void
    {
        // Create 25 audit logs
        for ($i = 1; $i <= 25; $i++) {
            AuditLog::create([
                'task_id' => $this->task->id,
                'user_id' => $this->user->id,
                'field' => 'field_' . $i,
                'old_value' => 'old_' . $i,
                'new_value' => 'new_' . $i,
            ]);
        }

        $response = $this->actingAs($this->user)->get(route('tasks.show', $this->task));

        $response->assertStatus(200);
        $response->assertSee('Показано 20 из 25 изменений');
    }

    public function test_empty_comments_shows_placeholder_message(): void
    {
        $response = $this->actingAs($this->user)->get(route('tasks.show', $this->task));

        $response->assertStatus(200);
        $response->assertSee('Комментариев пока нет');
    }

    public function test_empty_attachments_shows_placeholder_message(): void
    {
        $response = $this->actingAs($this->user)->get(route('tasks.show', $this->task));

        $response->assertStatus(200);
        $response->assertSee('Файлы не прикреплены');
    }

    public function test_empty_audit_history_shows_placeholder_message(): void
    {
        $response = $this->actingAs($this->user)->get(route('tasks.show', $this->task));

        $response->assertStatus(200);
        $response->assertSee('История изменений пуста');
    }
}

