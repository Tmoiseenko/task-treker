<?php

use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Enums\TaskStatus;

beforeEach(function () {
    // Seed roles and permissions before each test
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\PermissionSeeder']);
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder']);
});

test('administrator can view any project', function () {
    $admin = User::factory()->create();
    $adminRole = Role::where('name', 'administrator')->first();
    $admin->roles()->attach($adminRole);

    $project = Project::factory()->create();

    expect($admin->can('view', $project))->toBeTrue();
});

test('project manager can view any project', function () {
    $manager = User::factory()->create();
    $managerRole = Role::where('name', 'project_manager')->first();
    $manager->roles()->attach($managerRole);

    $project = Project::factory()->create();

    expect($manager->can('view', $project))->toBeTrue();
});

test('specialist can only view projects they are members of', function () {
    $developer = User::factory()->create();
    $developerRole = Role::where('name', 'developer')->first();
    $developer->roles()->attach($developerRole);

    $project = Project::factory()->create();

    // Developer is not a member, should not be able to view
    expect($developer->can('view', $project))->toBeFalse();

    // Add developer as member
    $project->members()->attach($developer);
    $project->refresh(); // Refresh to reload relationships

    // Now developer should be able to view
    expect($developer->can('view', $project))->toBeTrue();
});

test('project manager can create projects', function () {
    $manager = User::factory()->create();
    $managerRole = Role::where('name', 'project_manager')->first();
    $manager->roles()->attach($managerRole);

    expect($manager->can('create', Project::class))->toBeTrue();
});

test('developer cannot create projects', function () {
    $developer = User::factory()->create();
    $developerRole = Role::where('name', 'developer')->first();
    $developer->roles()->attach($developerRole);

    expect($developer->can('create', Project::class))->toBeFalse();
});

test('project manager can update projects', function () {
    $manager = User::factory()->create();
    $managerRole = Role::where('name', 'project_manager')->first();
    $manager->roles()->attach($managerRole);

    $project = Project::factory()->create();

    expect($manager->can('update', $project))->toBeTrue();
});

test('project manager can delete projects', function () {
    $manager = User::factory()->create();
    $managerRole = Role::where('name', 'project_manager')->first();
    $manager->roles()->attach($managerRole);

    $project = Project::factory()->create();

    expect($manager->can('delete', $project))->toBeTrue();
});

test('project manager can manage project members', function () {
    $manager = User::factory()->create();
    $managerRole = Role::where('name', 'project_manager')->first();
    $manager->roles()->attach($managerRole);

    $project = Project::factory()->create();

    expect($manager->can('manageMembers', $project))->toBeTrue();
});

test('administrator can view any task', function () {
    $admin = User::factory()->create();
    $adminRole = Role::where('name', 'administrator')->first();
    $admin->roles()->attach($adminRole);

    $project = Project::factory()->create();
    $task = Task::factory()->create(['project_id' => $project->id]);

    expect($admin->can('view', $task))->toBeTrue();
});

test('specialist can only view tasks in their projects', function () {
    $developer = User::factory()->create();
    $developerRole = Role::where('name', 'developer')->first();
    $developer->roles()->attach($developerRole);

    $project = Project::factory()->create();
    $task = Task::factory()->create(['project_id' => $project->id]);

    // Developer is not a member, should not be able to view
    expect($developer->can('view', $task))->toBeFalse();

    // Add developer as member
    $project->members()->attach($developer);
    $task->refresh(); // Refresh to reload relationships

    // Now developer should be able to view
    expect($developer->can('view', $task))->toBeTrue();
});

test('project manager can create tasks', function () {
    $manager = User::factory()->create();
    $managerRole = Role::where('name', 'project_manager')->first();
    $manager->roles()->attach($managerRole);

    expect($manager->can('create', Task::class))->toBeTrue();
});

test('task assignee can update their task', function () {
    $developer = User::factory()->create();
    $developerRole = Role::where('name', 'developer')->first();
    $developer->roles()->attach($developerRole);

    $project = Project::factory()->create();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assignee_id' => $developer->id,
    ]);

    expect($developer->can('update', $task))->toBeTrue();
});

test('task author can update their task', function () {
    $manager = User::factory()->create();
    $managerRole = Role::where('name', 'project_manager')->first();
    $manager->roles()->attach($managerRole);

    $project = Project::factory()->create();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'author_id' => $manager->id,
    ]);

    expect($manager->can('update', $task))->toBeTrue();
});

test('only project manager can delete tasks', function () {
    $manager = User::factory()->create();
    $managerRole = Role::where('name', 'project_manager')->first();
    $manager->roles()->attach($managerRole);

    $developer = User::factory()->create();
    $developerRole = Role::where('name', 'developer')->first();
    $developer->roles()->attach($developerRole);

    $project = Project::factory()->create();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assignee_id' => $developer->id,
    ]);

    expect($manager->can('delete', $task))->toBeTrue();
    expect($developer->can('delete', $task))->toBeFalse();
});

test('only project manager can assign tasks', function () {
    $manager = User::factory()->create();
    $managerRole = Role::where('name', 'project_manager')->first();
    $manager->roles()->attach($managerRole);

    $developer = User::factory()->create();
    $developerRole = Role::where('name', 'developer')->first();
    $developer->roles()->attach($developerRole);

    $project = Project::factory()->create();
    $task = Task::factory()->create(['project_id' => $project->id]);

    expect($manager->can('assign', $task))->toBeTrue();
    expect($developer->can('assign', $task))->toBeFalse();
});

test('user can take unassigned task with TODO status', function () {
    $developer = User::factory()->create();
    $developerRole = Role::where('name', 'developer')->first();
    $developer->roles()->attach($developerRole);

    $project = Project::factory()->create();
    $project->members()->attach($developer);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assignee_id' => null,
        'status' => TaskStatus::TODO,
    ]);

    expect($developer->can('take', $task))->toBeTrue();
});

test('user cannot take already assigned task', function () {
    $developer = User::factory()->create();
    $developerRole = Role::where('name', 'developer')->first();
    $developer->roles()->attach($developerRole);

    $otherUser = User::factory()->create();

    $project = Project::factory()->create();
    $project->members()->attach($developer);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assignee_id' => $otherUser->id,
        'status' => TaskStatus::TODO,
    ]);

    expect($developer->can('take', $task))->toBeFalse();
});

test('user cannot take task with non-TODO status', function () {
    $developer = User::factory()->create();
    $developerRole = Role::where('name', 'developer')->first();
    $developer->roles()->attach($developerRole);

    $project = Project::factory()->create();
    $project->members()->attach($developer);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assignee_id' => null,
        'status' => TaskStatus::IN_PROGRESS,
    ]);

    expect($developer->can('take', $task))->toBeFalse();
});

test('task assignee can change status', function () {
    $developer = User::factory()->create();
    $developerRole = Role::where('name', 'developer')->first();
    $developer->roles()->attach($developerRole);

    $project = Project::factory()->create();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'assignee_id' => $developer->id,
    ]);

    expect($developer->can('changeStatus', [$task, TaskStatus::IN_PROGRESS]))->toBeTrue();
});

test('tester can change status to testing statuses', function () {
    $tester = User::factory()->create();
    $testerRole = Role::where('name', 'tester')->first();
    $tester->roles()->attach($testerRole);

    $project = Project::factory()->create();
    $task = Task::factory()->create(['project_id' => $project->id]);

    expect($tester->can('changeStatus', [$task, TaskStatus::IN_TESTING]))->toBeTrue();
    expect($tester->can('changeStatus', [$task, TaskStatus::TEST_FAILED]))->toBeTrue();
    expect($tester->can('changeStatus', [$task, TaskStatus::DONE]))->toBeTrue();
});
