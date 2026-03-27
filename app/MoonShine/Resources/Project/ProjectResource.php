<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Project;

use App\Models\Project;
use MoonShine\Laravel\Resources\ModelResource;
use App\MoonShine\Resources\Project\Pages\ProjectFormPage;
use App\MoonShine\Resources\Project\Pages\ProjectIndexPage;

/**
 * @extends ModelResource<Project, ProjectIndexPage, ProjectFormPage, null>
 */
class ProjectResource extends ModelResource
{
    protected string $model = Project::class;

    protected string $column = 'name';

    protected bool $simplePaginate = true;

    public function getTitle(): string
    {
        return 'Проекты';
    }

    protected function pages(): array
    {
        return [
            ProjectIndexPage::class,
            ProjectFormPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'name',
            'description',
        ];
    }
}
