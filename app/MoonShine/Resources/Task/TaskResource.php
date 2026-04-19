<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Task;

use App\Models\Task;
use App\MoonShine\Resources\Task\Pages\TaskDetailPage;
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

    protected bool $usePagination = false;

    protected bool $isAsync = false;

    public function getTitle(): string
    {
        return 'Задачи';
    }

    protected function pages(): array
    {
        return [
            TaskIndexPage::class,
            TaskFormPage::class,
            TaskDetailPage::class,
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
