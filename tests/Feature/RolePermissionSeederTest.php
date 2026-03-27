<?php

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

test('permission seeder creates all required permissions', function () {
    // Запускаем сидер разрешений
    $this->seed(PermissionSeeder::class);

    // Проверяем, что создано 6 разрешений
    expect(Permission::count())->toBe(6);

    // Проверяем наличие каждого разрешения
    $expectedPermissions = [
        'manage_users',
        'manage_projects',
        'manage_tasks',
        'manage_stages',
        'view_finances',
        'manage_knowledge_base',
    ];

    foreach ($expectedPermissions as $permissionName) {
        expect(Permission::where('name', $permissionName)->exists())->toBeTrue();
    }
});

test('role seeder creates all built-in roles', function () {
    // Сначала создаем разрешения
    $this->seed(PermissionSeeder::class);
    
    // Затем создаем роли
    $this->seed(RoleSeeder::class);

    // Проверяем, что создано 5 ролей
    expect(Role::count())->toBe(5);

    // Проверяем наличие каждой роли
    $expectedRoles = [
        'administrator',
        'project_manager',
        'designer',
        'developer',
        'tester',
    ];

    foreach ($expectedRoles as $roleName) {
        expect(Role::where('name', $roleName)->exists())->toBeTrue();
    }
});

test('administrator role has all permissions', function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $adminRole = Role::where('name', 'administrator')->first();
    
    // Администратор должен иметь все 6 разрешений
    expect($adminRole->permissions()->count())->toBe(6);
    
    // Проверяем наличие каждого разрешения
    $expectedPermissions = [
        'manage_users',
        'manage_projects',
        'manage_tasks',
        'manage_stages',
        'view_finances',
        'manage_knowledge_base',
    ];

    foreach ($expectedPermissions as $permissionName) {
        expect($adminRole->permissions()->where('name', $permissionName)->exists())
            ->toBeTrue("Administrator role should have {$permissionName} permission");
    }
});

test('project manager role has correct permissions', function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $pmRole = Role::where('name', 'project_manager')->first();
    
    // Проект-менеджер должен иметь 4 разрешения
    expect($pmRole->permissions()->count())->toBe(4);
    
    // Проверяем наличие правильных разрешений
    $expectedPermissions = [
        'manage_projects',
        'manage_tasks',
        'view_finances',
        'manage_knowledge_base',
    ];

    foreach ($expectedPermissions as $permissionName) {
        expect($pmRole->permissions()->where('name', $permissionName)->exists())
            ->toBeTrue("Project manager role should have {$permissionName} permission");
    }

    // Проверяем, что НЕТ разрешения на управление пользователями
    expect($pmRole->permissions()->where('name', 'manage_users')->exists())
        ->toBeFalse("Project manager role should NOT have manage_users permission");
});

test('specialist roles have knowledge base permission', function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $specialistRoles = ['designer', 'developer', 'tester'];

    foreach ($specialistRoles as $roleName) {
        $role = Role::where('name', $roleName)->first();
        
        // Каждая роль специалиста должна иметь доступ к базе знаний
        expect($role->permissions()->where('name', 'manage_knowledge_base')->exists())
            ->toBeTrue("{$roleName} role should have manage_knowledge_base permission");
    }
});

test('seeder is idempotent', function () {
    // Запускаем сидеры первый раз
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    
    $firstPermissionCount = Permission::count();
    $firstRoleCount = Role::count();

    // Запускаем сидеры второй раз
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    
    // Количество записей не должно измениться (firstOrCreate предотвращает дубликаты)
    expect(Permission::count())->toBe($firstPermissionCount);
    expect(Role::count())->toBe($firstRoleCount);
});
