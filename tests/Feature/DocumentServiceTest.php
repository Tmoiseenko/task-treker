<?php

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->documentService = new DocumentService();
});

describe('createDocument', function () {
    test('creates document with all required fields', function () {
        $author = User::factory()->create();
        $project = Project::factory()->create();
        
        $documentData = [
            'title' => 'API Documentation',
            'content' => 'This is the API documentation content',
            'category' => 'api_documentation',
            'project_id' => $project->id,
            'author_id' => $author->id,
        ];
        
        $document = $this->documentService->createDocument($documentData);
        
        expect($document)->toBeInstanceOf(Document::class)
            ->and($document->title)->toBe('API Documentation')
            ->and($document->content)->toBe('This is the API documentation content')
            ->and($document->category)->toBe(DocumentCategory::API_DOCUMENTATION)
            ->and($document->project_id)->toBe($project->id)
            ->and($document->author_id)->toBe($author->id)
            ->and($document->version)->toBe(1);
    });

    test('creates document without project', function () {
        $author = User::factory()->create();
        
        $documentData = [
            'title' => 'General Notes',
            'content' => 'Some general notes',
            'author_id' => $author->id,
        ];
        
        $document = $this->documentService->createDocument($documentData);
        
        expect($document->title)->toBe('General Notes')
            ->and($document->project_id)->toBeNull();
    });

    test('creates document without category', function () {
        $author = User::factory()->create();
        
        $documentData = [
            'title' => 'Uncategorized Document',
            'content' => 'Content without category',
            'author_id' => $author->id,
        ];
        
        $document = $this->documentService->createDocument($documentData);
        
        expect($document->category)->toBeNull();
    });

    test('automatically creates first version when document is created', function () {
        $author = User::factory()->create();
        
        $documentData = [
            'title' => 'Test Document',
            'content' => 'Initial content',
            'author_id' => $author->id,
        ];
        
        $document = $this->documentService->createDocument($documentData);
        
        expect($document->versions)->toHaveCount(1)
            ->and($document->versions->first()->version)->toBe(1)
            ->and($document->versions->first()->content)->toBe('Initial content')
            ->and($document->versions->first()->user_id)->toBe($author->id);
    });

    test('sets initial version to 1', function () {
        $author = User::factory()->create();
        
        $documentData = [
            'title' => 'Test Document',
            'content' => 'Content',
            'author_id' => $author->id,
        ];
        
        $document = $this->documentService->createDocument($documentData);
        
        expect($document->version)->toBe(1);
    });
});

describe('updateDocument', function () {
    test('updates document title without creating new version', function () {
        $author = User::factory()->create();
        $document = Document::factory()->create([
            'title' => 'Old Title',
            'content' => 'Content',
            'author_id' => $author->id,
            'version' => 1,
        ]);
        
        $updatedDocument = $this->documentService->updateDocument($document, [
            'title' => 'New Title',
        ]);
        
        expect($updatedDocument->title)->toBe('New Title')
            ->and($updatedDocument->version)->toBe(1)
            ->and($updatedDocument->versions)->toHaveCount(0);
    });

    test('updates document category without creating new version', function () {
        $author = User::factory()->create();
        $document = Document::factory()->create([
            'category' => 'general_notes',
            'content' => 'Content',
            'author_id' => $author->id,
            'version' => 1,
        ]);
        
        $updatedDocument = $this->documentService->updateDocument($document, [
            'category' => 'api_documentation',
        ]);
        
        expect($updatedDocument->category)->toBe(DocumentCategory::API_DOCUMENTATION)
            ->and($updatedDocument->version)->toBe(1);
    });

    test('updates document content and creates new version', function () {
        $author = User::factory()->create();
        $editor = User::factory()->create();
        $document = Document::factory()->create([
            'content' => 'Old content',
            'author_id' => $author->id,
            'version' => 1,
        ]);
        
        $updatedDocument = $this->documentService->updateDocument($document, [
            'content' => 'New content',
            'user_id' => $editor->id,
        ]);
        
        expect($updatedDocument->content)->toBe('New content')
            ->and($updatedDocument->version)->toBe(2)
            ->and($updatedDocument->versions)->toHaveCount(1);
    });

    test('increments version number when content changes', function () {
        $author = User::factory()->create();
        $document = Document::factory()->create([
            'content' => 'Version 1',
            'author_id' => $author->id,
            'version' => 1,
        ]);
        
        $this->documentService->updateDocument($document, [
            'content' => 'Version 2',
            'user_id' => $author->id,
        ]);
        
        $document->refresh();
        expect($document->version)->toBe(2);
        
        $this->documentService->updateDocument($document, [
            'content' => 'Version 3',
            'user_id' => $author->id,
        ]);
        
        $document->refresh();
        expect($document->version)->toBe(3);
    });

    test('does not create version when content is unchanged', function () {
        $author = User::factory()->create();
        $document = Document::factory()->create([
            'title' => 'Title',
            'content' => 'Same content',
            'author_id' => $author->id,
            'version' => 1,
        ]);
        
        $updatedDocument = $this->documentService->updateDocument($document, [
            'title' => 'New Title',
            'content' => 'Same content',
            'user_id' => $author->id,
        ]);
        
        expect($updatedDocument->version)->toBe(1)
            ->and($updatedDocument->versions)->toHaveCount(0);
    });

    test('updates project_id without creating new version', function () {
        $author = User::factory()->create();
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();
        $document = Document::factory()->create([
            'project_id' => $project1->id,
            'author_id' => $author->id,
            'version' => 1,
        ]);
        
        $updatedDocument = $this->documentService->updateDocument($document, [
            'project_id' => $project2->id,
        ]);
        
        expect($updatedDocument->project_id)->toBe($project2->id)
            ->and($updatedDocument->version)->toBe(1);
    });

    test('can update multiple fields at once', function () {
        $author = User::factory()->create();
        $editor = User::factory()->create();
        $project = Project::factory()->create();
        $document = Document::factory()->create([
            'title' => 'Old Title',
            'content' => 'Old content',
            'category' => 'general_notes',
            'author_id' => $author->id,
            'version' => 1,
        ]);
        
        $updatedDocument = $this->documentService->updateDocument($document, [
            'title' => 'New Title',
            'content' => 'New content',
            'category' => 'api_documentation',
            'project_id' => $project->id,
            'user_id' => $editor->id,
        ]);
        
        expect($updatedDocument->title)->toBe('New Title')
            ->and($updatedDocument->content)->toBe('New content')
            ->and($updatedDocument->category)->toBe(DocumentCategory::API_DOCUMENTATION)
            ->and($updatedDocument->project_id)->toBe($project->id)
            ->and($updatedDocument->version)->toBe(2);
    });
});

describe('attachToTask', function () {
    test('attaches document to task', function () {
        $document = Document::factory()->create();
        $task = Task::factory()->create();
        
        $this->documentService->attachToTask($document, $task);
        
        expect($document->tasks)->toHaveCount(1)
            ->and($document->tasks->first()->id)->toBe($task->id);
    });

    test('does not create duplicate attachment', function () {
        $document = Document::factory()->create();
        $task = Task::factory()->create();
        
        $this->documentService->attachToTask($document, $task);
        $this->documentService->attachToTask($document, $task);
        
        expect($document->fresh()->tasks)->toHaveCount(1);
    });

    test('can attach same document to multiple tasks', function () {
        $document = Document::factory()->create();
        $task1 = Task::factory()->create();
        $task2 = Task::factory()->create();
        
        $this->documentService->attachToTask($document, $task1);
        $this->documentService->attachToTask($document, $task2);
        
        expect($document->fresh()->tasks)->toHaveCount(2);
    });

    test('task can have multiple documents attached', function () {
        $document1 = Document::factory()->create();
        $document2 = Document::factory()->create();
        $task = Task::factory()->create();
        
        $this->documentService->attachToTask($document1, $task);
        $this->documentService->attachToTask($document2, $task);
        
        expect($task->fresh()->documents)->toHaveCount(2);
    });
});

describe('createVersion', function () {
    test('creates new document version', function () {
        $author = User::factory()->create();
        $document = Document::factory()->create([
            'version' => 2,
            'author_id' => $author->id,
        ]);
        
        $version = $this->documentService->createVersion(
            $document,
            'Version 2 content',
            $author
        );
        
        expect($version)->toBeInstanceOf(DocumentVersion::class)
            ->and($version->document_id)->toBe($document->id)
            ->and($version->content)->toBe('Version 2 content')
            ->and($version->version)->toBe(2)
            ->and($version->user_id)->toBe($author->id)
            ->and($version->created_at)->not->toBeNull();
    });

    test('version is linked to document', function () {
        $author = User::factory()->create();
        $document = Document::factory()->create([
            'version' => 1,
            'author_id' => $author->id,
        ]);
        
        $version = $this->documentService->createVersion(
            $document,
            'Content',
            $author
        );
        
        expect($document->fresh()->versions)->toHaveCount(1)
            ->and($document->versions->first()->id)->toBe($version->id);
    });

    test('version stores user who made the change', function () {
        $author = User::factory()->create();
        $editor = User::factory()->create();
        $document = Document::factory()->create([
            'version' => 1,
            'author_id' => $author->id,
        ]);
        
        $version = $this->documentService->createVersion(
            $document,
            'Edited content',
            $editor
        );
        
        expect($version->user_id)->toBe($editor->id)
            ->and($version->user->id)->toBe($editor->id);
    });

    test('multiple versions can be created for same document', function () {
        $author = User::factory()->create();
        $document = Document::factory()->create([
            'version' => 1,
            'author_id' => $author->id,
        ]);
        
        $this->documentService->createVersion($document, 'Version 1', $author);
        
        $document->version = 2;
        $document->save();
        $this->documentService->createVersion($document, 'Version 2', $author);
        
        $document->version = 3;
        $document->save();
        $this->documentService->createVersion($document, 'Version 3', $author);
        
        expect($document->fresh()->versions)->toHaveCount(3);
    });
});

describe('searchDocuments', function () {
    test('finds documents by title', function () {
        Document::factory()->create(['title' => 'API Documentation']);
        Document::factory()->create(['title' => 'User Guide']);
        Document::factory()->create(['title' => 'Architecture Notes']);
        
        $results = $this->documentService->searchDocuments('API Documentation');
        
        expect($results)->toHaveCount(1)
            ->and($results->first()->title)->toBe('API Documentation');
    });

    test('finds documents by content', function () {
        Document::factory()->create([
            'title' => 'Document 1',
            'content' => 'This contains Laravel framework information',
        ]);
        Document::factory()->create([
            'title' => 'Document 2',
            'content' => 'This is about React',
        ]);
        
        $results = $this->documentService->searchDocuments('Laravel');
        
        expect($results)->toHaveCount(1)
            ->and($results->first()->title)->toBe('Document 1');
    });

    test('search is case insensitive', function () {
        Document::factory()->create(['title' => 'API Documentation']);
        
        $results = $this->documentService->searchDocuments('api');
        
        expect($results)->toHaveCount(1);
    });

    test('finds documents with partial match', function () {
        Document::factory()->create(['title' => 'Authentication Guide']);
        Document::factory()->create(['title' => 'Authorization Rules']);
        
        $results = $this->documentService->searchDocuments('Auth');
        
        expect($results)->toHaveCount(2);
    });

    test('returns empty collection when no matches found', function () {
        Document::factory()->create(['title' => 'API Documentation']);
        
        $results = $this->documentService->searchDocuments('NonExistent');
        
        expect($results)->toHaveCount(0);
    });

    test('searches in both title and content', function () {
        Document::factory()->create([
            'title' => 'User Guide',
            'content' => 'Information about authentication',
        ]);
        Document::factory()->create([
            'title' => 'Authentication API',
            'content' => 'API endpoints',
        ]);
        
        $results = $this->documentService->searchDocuments('authentication');
        
        expect($results)->toHaveCount(2);
    });

    test('includes author relationship in search results', function () {
        $author = User::factory()->create(['name' => 'John Doe']);
        Document::factory()->create([
            'title' => 'Test Document',
            'author_id' => $author->id,
        ]);
        
        $results = $this->documentService->searchDocuments('Test');
        
        expect($results->first()->author)->not->toBeNull()
            ->and($results->first()->author->name)->toBe('John Doe');
    });

    test('includes project relationship in search results', function () {
        $project = Project::factory()->create(['name' => 'Test Project']);
        Document::factory()->create([
            'title' => 'Test Document',
            'project_id' => $project->id,
        ]);
        
        $results = $this->documentService->searchDocuments('Test');
        
        expect($results->first()->project)->not->toBeNull()
            ->and($results->first()->project->name)->toBe('Test Project');
    });
});

// Requirement 14.2: Document creation with title, content, and category
test('validates requirement 14.2: document creation', function () {
    $author = User::factory()->create();
    
    $documentData = [
        'title' => 'API Endpoints',
        'content' => '# API Documentation\n\n## Endpoints\n\n- GET /api/users',
        'category' => 'api_documentation',
        'author_id' => $author->id,
    ];
    
    $document = $this->documentService->createDocument($documentData);
    
    expect($document->title)->toBe('API Endpoints')
        ->and($document->content)->toContain('API Documentation')
        ->and($document->category)->toBe(DocumentCategory::API_DOCUMENTATION);
});

// Requirement 14.3: Markdown formatting support
test('validates requirement 14.3: markdown content storage', function () {
    $author = User::factory()->create();
    
    $markdownContent = "# Heading\n\n## Subheading\n\n- List item 1\n- List item 2\n\n```php\necho 'Hello';\n```";
    
    $document = $this->documentService->createDocument([
        'title' => 'Markdown Document',
        'content' => $markdownContent,
        'author_id' => $author->id,
    ]);
    
    expect($document->content)->toBe($markdownContent);
});

// Requirement 14.5: Attaching documents to tasks
test('validates requirement 14.5: document attachment to tasks', function () {
    $document = Document::factory()->create();
    $task = Task::factory()->create();
    
    $this->documentService->attachToTask($document, $task);
    
    $task->refresh();
    expect($task->documents)->toHaveCount(1)
        ->and($task->documents->first()->id)->toBe($document->id);
});

// Requirement 14.10: Search documents by title and content
test('validates requirement 14.10: document search', function () {
    Document::factory()->create(['title' => 'Laravel API']);
    Document::factory()->create(['title' => 'React Components']);
    Document::factory()->create(['content' => 'Laravel framework guide']);
    
    $results = $this->documentService->searchDocuments('Laravel');
    
    expect($results)->toHaveCount(2);
});

// Requirement 14.12: Document versioning
test('validates requirement 14.12: document versioning on edit', function () {
    $author = User::factory()->create();
    $document = Document::factory()->create([
        'content' => 'Version 1',
        'author_id' => $author->id,
        'version' => 1,
    ]);
    
    $this->documentService->updateDocument($document, [
        'content' => 'Version 2',
        'user_id' => $author->id,
    ]);
    
    $document->refresh();
    
    expect($document->version)->toBe(2)
        ->and($document->versions)->toHaveCount(1)
        ->and($document->versions->first()->version)->toBe(2)
        ->and($document->versions->first()->content)->toBe('Version 2');
});

