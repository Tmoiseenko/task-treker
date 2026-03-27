<?php

use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use App\Enums\TaskStatus;
use App\Enums\TaskPriority;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    $this->seed(\Database\Seeders\StageSeeder::class);
});

describe('Task Query Scopes', function () {
    
    describe('byProject scope', function () {
        test('filters tasks by project id', function () {
            $project1 = Project::factory()->create();
            $project2 = Project::factory()->create();
            
            $task1 = Task::factory()->create(['project_id' => $project1->id]);
            $task2 = Task::factory()->create(['project_id' => $project2->id]);
            $task3 = Task::factory()->create(['project_id' => $project1->id]);
            
            $results = Task::byProject($project1->id)->get();
            
            expect($results)->toHaveCount(2)
                ->and($results->pluck('id')->toArray())->toContain($task1->id, $task3->id)
                ->and($results->pluck('id')->toArray())->not->toContain($task2->id);
        });
        
        test('returns empty collection when no tasks match project', function () {
            $project = Project::factory()->create();
            Task::factory()->count(3)->create();
            
            $results = Task::byProject($project->id)->get();
            
            expect($results)->toBeEmpty();
        });
    });
    
    describe('byStatus scope', function () {
        test('filters tasks by status', function () {
            $task1 = Task::factory()->create(['status' => TaskStatus::TODO]);
            $task2 = Task::factory()->create(['status' => TaskStatus::IN_PROGRESS]);
            $task3 = Task::factory()->create(['status' => TaskStatus::TODO]);
            
            $results = Task::byStatus(TaskStatus::TODO)->get();
            
            expect($results)->toHaveCount(2)
                ->and($results->pluck('id')->toArray())->toContain($task1->id, $task3->id)
                ->and($results->pluck('id')->toArray())->not->toContain($task2->id);
        });
        
        test('works with string status value', function () {
            Task::factory()->create(['status' => TaskStatus::DONE]);
            Task::factory()->create(['status' => TaskStatus::IN_PROGRESS]);
            
            $results = Task::byStatus('done')->get();
            
            expect($results)->toHaveCount(1);
        });
    });
    
    describe('byPriority scope', function () {
        test('filters tasks by priority', function () {
            $task1 = Task::factory()->create(['priority' => TaskPriority::HIGH]);
            $task2 = Task::factory()->create(['priority' => TaskPriority::LOW]);
            $task3 = Task::factory()->create(['priority' => TaskPriority::HIGH]);
            
            $results = Task::byPriority(TaskPriority::HIGH)->get();
            
            expect($results)->toHaveCount(2)
                ->and($results->pluck('id')->toArray())->toContain($task1->id, $task3->id)
                ->and($results->pluck('id')->toArray())->not->toContain($task2->id);
        });
        
        test('works with string priority value', function () {
            Task::factory()->create(['priority' => TaskPriority::MEDIUM]);
            Task::factory()->create(['priority' => TaskPriority::LOW]);
            
            $results = Task::byPriority('medium')->get();
            
            expect($results)->toHaveCount(1);
        });
    });
    
    describe('byAssignee scope', function () {
        test('filters tasks by assignee id', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            
            $task1 = Task::factory()->create(['assignee_id' => $user1->id]);
            $task2 = Task::factory()->create(['assignee_id' => $user2->id]);
            $task3 = Task::factory()->create(['assignee_id' => $user1->id]);
            
            $results = Task::byAssignee($user1->id)->get();
            
            expect($results)->toHaveCount(2)
                ->and($results->pluck('id')->toArray())->toContain($task1->id, $task3->id)
                ->and($results->pluck('id')->toArray())->not->toContain($task2->id);
        });
        
        test('includes tasks with null assignee when filtering by null', function () {
            $user = User::factory()->create();
            
            $task1 = Task::factory()->create(['assignee_id' => null]);
            $task2 = Task::factory()->create(['assignee_id' => $user->id]);
            $task3 = Task::factory()->create(['assignee_id' => null]);
            
            $results = Task::byAssignee(null)->get();
            
            expect($results)->toHaveCount(2)
                ->and($results->pluck('id')->toArray())->toContain($task1->id, $task3->id);
        });
    });
    
    describe('byAuthor scope', function () {
        test('filters tasks by author id', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            
            $task1 = Task::factory()->create(['author_id' => $user1->id]);
            $task2 = Task::factory()->create(['author_id' => $user2->id]);
            $task3 = Task::factory()->create(['author_id' => $user1->id]);
            
            $results = Task::byAuthor($user1->id)->get();
            
            expect($results)->toHaveCount(2)
                ->and($results->pluck('id')->toArray())->toContain($task1->id, $task3->id)
                ->and($results->pluck('id')->toArray())->not->toContain($task2->id);
        });
    });
    
    describe('byTags scope', function () {
        test('filters tasks by single tag id', function () {
            $tag1 = Tag::factory()->create();
            $tag2 = Tag::factory()->create();
            
            $task1 = Task::factory()->create();
            $task1->tags()->attach($tag1);
            
            $task2 = Task::factory()->create();
            $task2->tags()->attach($tag2);
            
            $task3 = Task::factory()->create();
            $task3->tags()->attach($tag1);
            
            $results = Task::byTags($tag1->id)->get();
            
            expect($results)->toHaveCount(2)
                ->and($results->pluck('id')->toArray())->toContain($task1->id, $task3->id)
                ->and($results->pluck('id')->toArray())->not->toContain($task2->id);
        });
        
        test('filters tasks by multiple tag ids', function () {
            $tag1 = Tag::factory()->create();
            $tag2 = Tag::factory()->create();
            $tag3 = Tag::factory()->create();
            
            $task1 = Task::factory()->create();
            $task1->tags()->attach([$tag1->id, $tag2->id]);
            
            $task2 = Task::factory()->create();
            $task2->tags()->attach($tag3);
            
            $task3 = Task::factory()->create();
            $task3->tags()->attach($tag1);
            
            $results = Task::byTags([$tag1->id, $tag2->id])->get();
            
            expect($results)->toHaveCount(2)
                ->and($results->pluck('id')->toArray())->toContain($task1->id, $task3->id);
        });
        
        test('returns empty collection when no tasks have specified tags', function () {
            $tag = Tag::factory()->create();
            Task::factory()->count(3)->create();
            
            $results = Task::byTags($tag->id)->get();
            
            expect($results)->toBeEmpty();
        });
    });
    
    describe('search scope', function () {
        test('searches tasks by title', function () {
            $task1 = Task::factory()->create(['title' => 'Implement user authentication']);
            $task2 = Task::factory()->create(['title' => 'Fix bug in payment system']);
            $task3 = Task::factory()->create(['title' => 'Add user profile page']);
            
            $results = Task::search('user')->get();
            
            expect($results)->toHaveCount(2)
                ->and($results->pluck('id')->toArray())->toContain($task1->id, $task3->id)
                ->and($results->pluck('id')->toArray())->not->toContain($task2->id);
        });
        
        test('searches tasks by description', function () {
            $task1 = Task::factory()->create([
                'title' => 'Task 1',
                'description' => 'This task involves database optimization'
            ]);
            $task2 = Task::factory()->create([
                'title' => 'Task 2',
                'description' => 'This task involves frontend work'
            ]);
            $task3 = Task::factory()->create([
                'title' => 'Task 3',
                'description' => 'Database migration and seeding'
            ]);
            
            $results = Task::search('database')->get();
            
            expect($results)->toHaveCount(2)
                ->and($results->pluck('id')->toArray())->toContain($task1->id, $task3->id);
        });
        
        test('searches tasks by both title and description', function () {
            $task1 = Task::factory()->create([
                'title' => 'API endpoint implementation',
                'description' => 'Create REST API'
            ]);
            $task2 = Task::factory()->create([
                'title' => 'Frontend work',
                'description' => 'Build API integration'
            ]);
            $task3 = Task::factory()->create([
                'title' => 'Database work',
                'description' => 'Schema design'
            ]);
            
            $results = Task::search('API')->get();
            
            expect($results)->toHaveCount(2)
                ->and($results->pluck('id')->toArray())->toContain($task1->id, $task2->id);
        });
        
        test('search is case insensitive', function () {
            $task1 = Task::factory()->create(['title' => 'URGENT: Fix Production Bug']);
            $task2 = Task::factory()->create(['title' => 'Regular task']);
            
            $results = Task::search('urgent')->get();
            
            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($task1->id);
        });
        
        test('returns empty collection when no matches found', function () {
            Task::factory()->count(3)->create();
            
            $results = Task::search('nonexistent')->get();
            
            expect($results)->toBeEmpty();
        });
    });
    
    describe('scope chaining', function () {
        test('can chain multiple scopes together', function () {
            $project = Project::factory()->create();
            $user = User::factory()->create();
            $tag = Tag::factory()->create();
            
            $task1 = Task::factory()->create([
                'project_id' => $project->id,
                'assignee_id' => $user->id,
                'status' => TaskStatus::IN_PROGRESS,
                'priority' => TaskPriority::HIGH,
                'title' => 'Important task'
            ]);
            $task1->tags()->attach($tag);
            
            $task2 = Task::factory()->create([
                'project_id' => $project->id,
                'assignee_id' => $user->id,
                'status' => TaskStatus::TODO,
                'priority' => TaskPriority::HIGH,
                'title' => 'Another task'
            ]);
            
            $task3 = Task::factory()->create([
                'project_id' => $project->id,
                'assignee_id' => $user->id,
                'status' => TaskStatus::IN_PROGRESS,
                'priority' => TaskPriority::LOW,
                'title' => 'Important task'
            ]);
            
            $results = Task::byProject($project->id)
                ->byAssignee($user->id)
                ->byStatus(TaskStatus::IN_PROGRESS)
                ->byPriority(TaskPriority::HIGH)
                ->byTags($tag->id)
                ->search('Important')
                ->get();
            
            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($task1->id);
        });
        
        test('chaining scopes with no results returns empty collection', function () {
            $project = Project::factory()->create();
            $user = User::factory()->create();
            
            Task::factory()->create([
                'project_id' => $project->id,
                'status' => TaskStatus::TODO
            ]);
            
            $results = Task::byProject($project->id)
                ->byAssignee($user->id)
                ->byStatus(TaskStatus::IN_PROGRESS)
                ->get();
            
            expect($results)->toBeEmpty();
        });
    });
});
