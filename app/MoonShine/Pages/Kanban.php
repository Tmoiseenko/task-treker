<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\MoonShine\Components\KanbanBoard;
use App\MoonShine\Components\KanbanColumn;
use App\MoonShine\Components\TaskCardsBuilder;
use App\MoonShine\Resources\Task\Pages\TaskFormPage;
use App\MoonShine\Resources\Task\TaskResource;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Support\Attributes\Icon;

#[Icon('clipboard-document-list')]
class Kanban extends Page
{
    public function getBreadcrumbs(): array
    {
        return ['#' => $this->getTitle()];
    }

    public function getTitle(): string
    {
        return $this->title ?: 'Доска';
    }

    /** @return list<ComponentContract> */
    protected function components(): iterable
    {
        // Загружаем задачи со всеми связями, нужными для карточек
        $tasksByStatus = Task::query()
            ->with(['assignee', 'tags'])
            ->orderBy('id')
            ->get()
            ->groupBy(fn(Task $task) => $task->status->value);

        $columns = array_map(function (TaskStatus $status) use ($tasksByStatus): KanbanColumn {
            $tasks = $tasksByStatus->get($status->value, collect());

            $cards = TaskCardsBuilder::make($tasks)
                ->url(fn(Task $task) => toPage(
                    page: TaskFormPage::class,
                    resource: TaskResource::class,
                    params: ['resourceItem' => $task->id],
                ));

            return KanbanColumn::make(
                labelOrComponents: "{$status->label()} ({$tasks->count()})",
                components: [$cards],
            );
        }, TaskStatus::cases());

        return [
            KanbanBoard::make($columns),
        ];
    }
}
