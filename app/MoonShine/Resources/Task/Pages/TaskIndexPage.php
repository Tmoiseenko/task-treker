<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Task\Pages;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use App\MoonShine\Resources\Task\TaskResource;
use App\MoonShine\Resources\Project\ProjectResource;
use App\MoonShine\Resources\MoonShineUser\MoonShineUserResource;
use App\MoonShine\Resources\Tag\TagResource;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends IndexPage<TaskResource>
 */
final class TaskIndexPage extends IndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),

            Text::make('Название', 'title')->sortable(),

            Textarea::make('Описание', 'description'),

            BelongsTo::make('Проект', 'project', resource: ProjectResource::class)
                ->sortable(),

            BelongsTo::make('Автор', 'author', resource: MoonShineUserResource::class),

            BelongsTo::make('Исполнитель', 'assignee', resource: MoonShineUserResource::class)
                ->sortable(),

            Enum::make('Приоритет', 'priority')
                ->attach(TaskPriority::class)
                ->sortable(),

            Enum::make('Статус', 'status')
                ->attach(TaskStatus::class)
                ->sortable(),

            Date::make('Срок выполнения', 'due_date')
                ->sortable(),

            BelongsToMany::make('Теги', 'tags', resource: TagResource::class)->changePreview(
                fn($tags) => $tags->pluck('name')->join(', ')
            ),
        ];
    }

    protected function filters(): iterable
    {
        return [
            Text::make('Название', 'title'),

            BelongsTo::make('Проект', 'project', resource: ProjectResource::class)
                ->searchable(),

            Enum::make('Статус', 'status')
                ->attach(TaskStatus::class),

            Enum::make('Приоритет', 'priority')
                ->attach(TaskPriority::class),

            BelongsTo::make('Исполнитель', 'assignee', resource: MoonShineUserResource::class)
                ->searchable(),
        ];
    }

    /**
     * @param  TableBuilder  $component
     *
     * @return TableBuilder
     */
    protected function modifyListComponent(ComponentContract $component): TableBuilder
    {
        return $component->columnSelection();
    }
}
