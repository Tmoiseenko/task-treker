<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Task;

use App\Models\Task;
use MoonShine\Laravel\Resources\ModelResource;
use App\MoonShine\Resources\Task\Pages\TaskFormPage;
use App\MoonShine\Resources\Task\Pages\TaskIndexPage;
use MoonShine\MenuManager\Attributes\Group;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;

/**
 * @extends ModelResource<Task, TaskIndexPage, TaskFormPage, null>
 */
// #[Icon('clipboard-check')]
#[Group('Управление проектами', 'projects')]
#[Order(15)]
class TaskResource extends ModelResource
{
    protected string $model = Task::class;

    protected string $column = 'title';

    protected bool $simplePaginate = true;

    public function getTitle(): string
    {
        return 'Задачи';
    }

    protected function pages(): array
    {
        return [
            TaskIndexPage::class,
            TaskFormPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'title',
            'description',
        ];
    }
}
