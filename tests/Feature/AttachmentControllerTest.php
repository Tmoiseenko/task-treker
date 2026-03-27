<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentControllerTest extends TestCase
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

        // Use fake storage for testing
        Storage::fake('local');

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

    public function test_project_member_can_upload_attachment(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->developer)
            ->post(route('attachments.store', $this->task), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('tasks.show', $this->task));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('attachments', [
            'task_id' => $this->task->id,
            'user_id' => $this->developer->id,
            'original_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
        ]);

        // Verify file was stored
        $attachment = Attachment::where('task_id', $this->task->id)->first();
        Storage::assertExists($attachment->path);
    }

    public function test_attachment_requires_file(): void
    {
        $response = $this->actingAs($this->developer)
            ->post(route('attachments.store', $this->task), []);

        $response->assertSessionHasErrors('file');
    }

    public function test_attachment_file_size_cannot_exceed_limit(): void
    {
        $file = UploadedFile::fake()->create('large.pdf', 11000); // 11MB

        $response = $this->actingAs($this->developer)
            ->post(route('attachments.store', $this->task), [
                'file' => $file,
            ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_non_project_member_cannot_upload_attachment(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->outsider)
            ->post(route('attachments.store', $this->task), [
                'file' => $file,
            ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('attachments', [
            'task_id' => $this->task->id,
            'user_id' => $this->outsider->id,
        ]);
    }

    public function test_project_member_can_download_attachment(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);
        
        $this->actingAs($this->developer)
            ->post(route('attachments.store', $this->task), [
                'file' => $file,
            ]);

        $attachment = Attachment::where('task_id', $this->task->id)->first();

        $response = $this->actingAs($this->projectManager)
            ->get(route('attachments.download', [$this->task, $attachment]));

        $response->assertOk();
        $response->assertDownload('document.pdf');
    }

    public function test_non_project_member_cannot_download_attachment(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);
        
        $this->actingAs($this->developer)
            ->post(route('attachments.store', $this->task), [
                'file' => $file,
            ]);

        $attachment = Attachment::where('task_id', $this->task->id)->first();

        $response = $this->actingAs($this->outsider)
            ->get(route('attachments.download', [$this->task, $attachment]));

        $response->assertForbidden();
    }

    public function test_uploader_can_delete_own_attachment(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);
        
        $this->actingAs($this->developer)
            ->post(route('attachments.store', $this->task), [
                'file' => $file,
            ]);

        $attachment = Attachment::where('task_id', $this->task->id)->first();

        $response = $this->actingAs($this->developer)
            ->delete(route('attachments.destroy', [$this->task, $attachment]));

        $response->assertRedirect(route('tasks.show', $this->task));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('attachments', [
            'id' => $attachment->id,
        ]);

        Storage::assertMissing($attachment->path);
    }

    public function test_task_author_can_delete_any_attachment(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);
        
        $this->actingAs($this->developer)
            ->post(route('attachments.store', $this->task), [
                'file' => $file,
            ]);

        $attachment = Attachment::where('task_id', $this->task->id)->first();

        // Task author (projectManager) can delete developer's attachment
        $response = $this->actingAs($this->projectManager)
            ->delete(route('attachments.destroy', [$this->task, $attachment]));

        $response->assertRedirect(route('tasks.show', $this->task));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('attachments', [
            'id' => $attachment->id,
        ]);
    }

    public function test_task_assignee_can_delete_any_attachment(): void
    {
        $this->task->update(['assignee_id' => $this->developer->id]);

        $file = UploadedFile::fake()->create('document.pdf', 100);
        
        $this->actingAs($this->projectManager)
            ->post(route('attachments.store', $this->task), [
                'file' => $file,
            ]);

        $attachment = Attachment::where('task_id', $this->task->id)->first();

        // Task assignee (developer) can delete PM's attachment
        $response = $this->actingAs($this->developer)
            ->delete(route('attachments.destroy', [$this->task, $attachment]));

        $response->assertRedirect(route('tasks.show', $this->task));
        $response->assertSessionHas('success');
    }

    public function test_other_user_cannot_delete_attachment(): void
    {
        $otherDeveloper = User::factory()->create();
        $devRole = \App\Models\Role::where('name', 'developer')->first();
        $otherDeveloper->roles()->attach($devRole);
        $this->project->members()->attach($otherDeveloper->id);

        $file = UploadedFile::fake()->create('document.pdf', 100);
        
        $this->actingAs($this->developer)
            ->post(route('attachments.store', $this->task), [
                'file' => $file,
            ]);

        $attachment = Attachment::where('task_id', $this->task->id)->first();

        $response = $this->actingAs($otherDeveloper)
            ->delete(route('attachments.destroy', [$this->task, $attachment]));

        $response->assertForbidden();

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->id,
        ]);
    }

    public function test_cannot_download_attachment_from_different_task(): void
    {
        $otherTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'author_id' => $this->projectManager->id,
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 100);
        
        $this->actingAs($this->developer)
            ->post(route('attachments.store', $otherTask), [
                'file' => $file,
            ]);

        $attachment = Attachment::where('task_id', $otherTask->id)->first();

        $response = $this->actingAs($this->developer)
            ->get(route('attachments.download', [$this->task, $attachment]));

        $response->assertNotFound();
    }

    public function test_cannot_delete_attachment_from_different_task(): void
    {
        $otherTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'author_id' => $this->projectManager->id,
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 100);
        
        $this->actingAs($this->developer)
            ->post(route('attachments.store', $otherTask), [
                'file' => $file,
            ]);

        $attachment = Attachment::where('task_id', $otherTask->id)->first();

        $response = $this->actingAs($this->developer)
            ->delete(route('attachments.destroy', [$this->task, $attachment]));

        $response->assertNotFound();
    }

    public function test_multiple_files_can_be_attached_to_task(): void
    {
        $file1 = UploadedFile::fake()->create('document1.pdf', 100);
        $file2 = UploadedFile::fake()->create('document2.pdf', 100);

        $this->actingAs($this->developer)
            ->post(route('attachments.store', $this->task), ['file' => $file1]);

        $this->actingAs($this->developer)
            ->post(route('attachments.store', $this->task), ['file' => $file2]);

        $this->assertEquals(2, $this->task->attachments()->count());
    }

    public function test_download_returns_404_if_file_not_found_in_storage(): void
    {
        $attachment = Attachment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->developer->id,
            'path' => 'attachments/nonexistent.pdf',
        ]);

        $response = $this->actingAs($this->developer)
            ->get(route('attachments.download', [$this->task, $attachment]));

        $response->assertNotFound();
    }
}
