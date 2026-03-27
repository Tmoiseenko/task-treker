<?php

namespace Tests\Feature\MoonShine;

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_can_create_document_with_all_fields(): void
    {
        $project = Project::factory()->create();
        $author = User::factory()->create();

        $document = Document::create([
            'title' => 'API Documentation',
            'content' => '# API Endpoints\n\n## GET /api/users',
            'category' => DocumentCategory::API_DOCUMENTATION->value,
            'project_id' => $project->id,
            'author_id' => $author->id,
            'version' => 1,
        ]);

        $this->assertDatabaseHas('documents', [
            'title' => 'API Documentation',
            'category' => DocumentCategory::API_DOCUMENTATION->value,
            'project_id' => $project->id,
            'author_id' => $author->id,
            'version' => 1,
        ]);

        $this->assertEquals('# API Endpoints\n\n## GET /api/users', $document->content);
    }

    public function test_can_create_document_without_optional_fields(): void
    {
        $author = User::factory()->create();

        $document = Document::create([
            'title' => 'General Notes',
            'content' => 'Some general notes',
            'author_id' => $author->id,
            'version' => 1,
        ]);

        $this->assertDatabaseHas('documents', [
            'title' => 'General Notes',
            'author_id' => $author->id,
        ]);

        $this->assertNull($document->category);
        $this->assertNull($document->project_id);
    }

    public function test_document_belongs_to_project(): void
    {
        $project = Project::factory()->create();
        $document = Document::factory()->create([
            'project_id' => $project->id,
        ]);

        $this->assertInstanceOf(Project::class, $document->project);
        $this->assertEquals($project->id, $document->project->id);
    }

    public function test_document_belongs_to_author(): void
    {
        $author = User::factory()->create();
        $document = Document::factory()->create([
            'author_id' => $author->id,
        ]);

        $this->assertInstanceOf(User::class, $document->author);
        $this->assertEquals($author->id, $document->author->id);
    }

    public function test_document_supports_markdown_content(): void
    {
        $markdownContent = <<<'MARKDOWN'
# API Documentation

## Authentication

All API requests require authentication using Bearer token.

### Example Request

```bash
curl -H "Authorization: Bearer TOKEN" https://api.example.com/users
```

## Endpoints

### GET /api/users

Returns a list of users.

**Response:**

```json
{
  "data": [
    {"id": 1, "name": "John Doe"}
  ]
}
```
MARKDOWN;

        $document = Document::factory()->create([
            'content' => $markdownContent,
        ]);

        $this->assertEquals($markdownContent, $document->content);
        $this->assertStringContainsString('# API Documentation', $document->content);
        $this->assertStringContainsString('```bash', $document->content);
        $this->assertStringContainsString('```json', $document->content);
    }

    public function test_document_has_all_category_options(): void
    {
        $categories = [
            DocumentCategory::API_DOCUMENTATION,
            DocumentCategory::ARCHITECTURE,
            DocumentCategory::INTEGRATION_GUIDE,
            DocumentCategory::GENERAL_NOTES,
        ];

        foreach ($categories as $category) {
            $document = Document::factory()->create([
                'category' => $category->value,
            ]);

            $this->assertEquals($category, $document->category);
        }
    }

    public function test_document_version_defaults_to_one(): void
    {
        $document = Document::factory()->create([
            'version' => 1,
        ]);

        $this->assertEquals(1, $document->version);
    }
}
