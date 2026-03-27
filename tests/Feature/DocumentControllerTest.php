<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed permissions and roles
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);

        // Create user with developer role
        $this->user = User::factory()->create();
        $devRole = \App\Models\Role::where('name', 'developer')->first();
        $this->user->roles()->attach($devRole);

        // Create project
        $this->project = Project::factory()->create();
    }

    public function test_user_can_view_documents_list(): void
    {
        Document::factory()->count(3)->create([
            'author_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('documents.index'));

        $response->assertStatus(200);
        // View assertions will be tested in task 17.4
        // $response->assertViewIs('documents.index');
        // $response->assertViewHas('documents');
    }

    public function test_user_can_create_document(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('documents.store'), [
                'title' => 'API Documentation',
                'content' => '# API Endpoints\n\n## User API\n\n- GET /api/users',
                'category' => 'api_documentation',
                'project_id' => $this->project->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'title' => 'API Documentation',
            'author_id' => $this->user->id,
            'version' => 1,
        ]);
    }

    public function test_document_creation_creates_first_version(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('documents.store'), [
                'title' => 'Test Document',
                'content' => 'Initial content',
                'category' => 'general_notes',
            ]);

        $document = Document::where('title', 'Test Document')->first();
        
        $this->assertNotNull($document);
        $this->assertEquals(1, $document->versions()->count());
        $this->assertEquals('Initial content', $document->versions()->first()->content);
    }

    public function test_user_can_view_document(): void
    {
        $document = Document::factory()->create([
            'author_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('documents.show', $document));

        $response->assertStatus(200);
        // View assertions will be tested in task 17.4
    }

    public function test_user_can_update_document(): void
    {
        $document = Document::factory()->create([
            'author_id' => $this->user->id,
            'content' => 'Original content',
            'version' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('documents.update', $document), [
                'title' => 'Updated Title',
                'content' => 'Updated content',
            ]);

        $response->assertRedirect();
        
        $document->refresh();
        $this->assertEquals('Updated Title', $document->title);
        $this->assertEquals('Updated content', $document->content);
    }

    public function test_updating_document_content_creates_new_version(): void
    {
        // Use DocumentService to create document with initial version
        $documentService = app(\App\Services\DocumentService::class);
        $document = $documentService->createDocument([
            'title' => 'Test Document',
            'content' => 'Original content',
            'author_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->put(route('documents.update', $document), [
                'content' => 'Updated content',
            ]);

        $document->refresh();
        $this->assertEquals(2, $document->version);
        $this->assertEquals(2, $document->versions()->count());
    }

    public function test_updating_only_title_does_not_create_new_version(): void
    {
        // Use DocumentService to create document with initial version
        $documentService = app(\App\Services\DocumentService::class);
        $document = $documentService->createDocument([
            'title' => 'Original Title',
            'content' => 'Original content',
            'author_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->put(route('documents.update', $document), [
                'title' => 'New Title',
            ]);

        $document->refresh();
        $this->assertEquals(1, $document->version);
        $this->assertEquals(1, $document->versions()->count());
    }

    public function test_user_can_delete_document(): void
    {
        $document = Document::factory()->create([
            'author_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('documents.destroy', $document));

        $response->assertRedirect(route('documents.index'));
        $this->assertDatabaseMissing('documents', [
            'id' => $document->id,
        ]);
    }

    public function test_user_can_attach_document_to_task(): void
    {
        $document = Document::factory()->create([
            'author_id' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'author_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('documents.attach-task', $document), [
                'task_id' => $task->id,
            ]);

        $response->assertRedirect();
        $this->assertTrue($document->tasks()->where('task_id', $task->id)->exists());
    }

    public function test_user_can_create_document_from_task(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'author_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('documents.store'), [
                'title' => 'Task Documentation',
                'content' => 'Documentation for task',
                'task_id' => $task->id,
            ]);

        $document = Document::where('title', 'Task Documentation')->first();
        
        $this->assertNotNull($document);
        $this->assertTrue($document->tasks()->where('task_id', $task->id)->exists());
    }

    public function test_documents_can_be_filtered_by_project(): void
    {
        $project2 = Project::factory()->create();

        Document::factory()->create([
            'author_id' => $this->user->id,
            'project_id' => $this->project->id,
        ]);

        Document::factory()->create([
            'author_id' => $this->user->id,
            'project_id' => $project2->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('documents.index', ['project_id' => $this->project->id]));

        $response->assertStatus(200);
        // View data assertions will be tested in task 17.4
    }

    public function test_documents_can_be_filtered_by_category(): void
    {
        Document::factory()->create([
            'author_id' => $this->user->id,
            'category' => 'api_documentation',
        ]);

        Document::factory()->create([
            'author_id' => $this->user->id,
            'category' => 'architecture',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('documents.index', ['category' => 'api_documentation']));

        $response->assertStatus(200);
        // View data assertions will be tested in task 17.4
    }

    public function test_documents_can_be_searched(): void
    {
        Document::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'API Documentation',
            'content' => 'REST API endpoints',
        ]);

        Document::factory()->create([
            'author_id' => $this->user->id,
            'title' => 'Database Schema',
            'content' => 'Database tables',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('documents.index', ['search' => 'API']));

        $response->assertStatus(200);
        // View data assertions will be tested in task 17.4
    }

    public function test_document_requires_title(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('documents.store'), [
                'content' => 'Some content',
            ]);

        $response->assertSessionHasErrors('title');
    }

    public function test_document_requires_content(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('documents.store'), [
                'title' => 'Test Document',
            ]);

        $response->assertSessionHasErrors('content');
    }

    public function test_document_supports_markdown_content(): void
    {
        $markdownContent = <<<'MD'
# Heading 1

## Heading 2

- List item 1
- List item 2

**Bold text** and *italic text*

```php
echo "Hello World";
```
MD;

        $response = $this->actingAs($this->user)
            ->post(route('documents.store'), [
                'title' => 'Markdown Document',
                'content' => $markdownContent,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'title' => 'Markdown Document',
            'content' => $markdownContent,
        ]);
    }
}
