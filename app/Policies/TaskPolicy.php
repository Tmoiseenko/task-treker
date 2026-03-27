<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\MoonshineUser;
use App\Enums\TaskStatus;

class TaskPolicy
{
    /**
     * Determine if the user can view the task.
     */
    public function view(MoonshineUser $user, Task $task): bool
    {
        // TODO: Implement permission checks using HasMoonShinePermissions
        // For now, allow if user is a project member
        return $task->project->members->contains($user);
    }

    /**
     * Determine if the user can create tasks.
     */
    public function create(MoonshineUser $user): bool
    {
        // TODO: Check permissions using $user->moonShinePermissions()
        return true; // Temporary: allow all authenticated users
    }

    /**
     * Determine if the user can update the task.
     */
    public function update(MoonshineUser $user, Task $task): bool
    {
        // Task author can update their own tasks
        if ($task->moonshine_author_id === $user->id) {
            return true;
        }

        // Task assignee can update assigned tasks
        if ($task->moonshine_assignee_id === $user->id) {
            return true;
        }

        // TODO: Check admin/manager permissions
        return false;
    }

    /**
     * Determine if the user can delete the task.
     */
    public function delete(MoonshineUser $user, Task $task): bool
    {
        // TODO: Check admin/manager permissions using HasMoonShinePermissions
        return false; // Temporary: restrict deletion
    }

    /**
     * Determine if the user can assign the task to someone.
     */
    public function assign(MoonshineUser $user, Task $task): bool
    {
        // TODO: Check permissions
        return true; // Temporary: allow all
    }

    /**
     * Determine if the user can take the task.
     */
    public function take(MoonshineUser $user, Task $task): bool
    {
        // Task must not have an assignee and status must be TODO
        if ($task->moonshine_assignee_id !== null || $task->status !== TaskStatus::TODO) {
            return false;
        }

        // User must be a member of the project
        return $task->project->members->contains($user);
    }

    /**
     * Determine if the user can change the task status.
     */
    public function changeStatus(MoonshineUser $user, Task $task, TaskStatus $newStatus): bool
    {
        // Task assignee can change status
        if ($task->moonshine_assignee_id === $user->id) {
            return true;
        }

        // TODO: Check tester/admin permissions
        return false;
    }
}
