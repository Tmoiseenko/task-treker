<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\MoonshineUser;

class ProjectPolicy
{
    /**
     * Determine if the user can view the project.
     */
    public function view(MoonshineUser $user, Project $project): bool
    {
        // Users can view projects if they are members
        return $project->members->contains($user);
    }

    /**
     * Determine if the user can create projects.
     */
    public function create(MoonshineUser $user): bool
    {
        // TODO: Check permissions using HasMoonShinePermissions
        return true; // Temporary: allow all authenticated users
    }

    /**
     * Determine if the user can update the project.
     */
    public function update(MoonshineUser $user, Project $project): bool
    {
        // TODO: Check admin/manager permissions using HasMoonShinePermissions
        return true; // Temporary: allow all
    }

    /**
     * Determine if the user can delete the project.
     */
    public function delete(MoonshineUser $user, Project $project): bool
    {
        // TODO: Check admin/manager permissions using HasMoonShinePermissions
        return false; // Temporary: restrict deletion
    }

    /**
     * Determine if the user can manage project members.
     */
    public function manageMembers(MoonshineUser $user, Project $project): bool
    {
        // TODO: Check admin/manager permissions using HasMoonShinePermissions
        return true; // Temporary: allow all
    }
}
