<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Task\Pages;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use App\MoonShine\Resources\Task\TaskResource;
use App\MoonShine\Resources\Project\ProjectResource;
use App\MoonShine\Resources\MoonShineUser\MoonShineUserResource;
use App\MoonShine\Resources\Tag\TagResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends FormPage<TaskResource, Task>
 */
final class TaskFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),

                Text::make('Название', 'title')
                    ->required()
                    ->hint('Название задачи'),

                Textarea::make('Описание', 'description')
                    ->hint('Подробное описание задачи'),

                BelongsTo::make('Проект', 'project', resource: ProjectResource::class)
                    ->required()
                    ->searchable()
                    ->hint('Проект, к которому относится задача'),

                BelongsTo::make('Автор', 'author', resource: MoonShineUserResource::class)
                    ->searchable()
                    ->hint('Автор задачи'),

                BelongsTo::make('Исполнитель', 'assignee', resource: MoonShineUserResource::class)
                    ->searchable()
                    ->nullable()
                    ->hint('Исполнитель задачи'),

                Enum::make('Приоритет', 'priority')
                    ->attach(TaskPriority::class)
                    ->required()
                    ->default(TaskPriority::MEDIUM->value)
                    ->hint('Приоритет задачи'),

                Enum::make('Статус', 'status')
                    ->attach(TaskStatus::class)
                    ->required()
                    ->default(TaskStatus::TODO->value)
                    ->hint('Текущий статус задачи'),

                Date::make('Срок выполнения', 'due_date')
                    ->nullable()
                    ->hint('Дата, до которой задача должна быть выполнена'),

                BelongsToMany::make('Теги', 'tags', resource: TagResource::class)
                    ->hint('Теги для категоризации задачи')
                    ->selectMode(),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'project_id' => ['required', 'exists:projects,id'],
            'moonshine_author_id' => ['nullable', 'exists:moonshine_users,id'],
            'moonshine_assignee_id' => ['nullable', 'exists:moonshine_users,id'],
            'priority' => ['required', 'string'],
            'status' => ['required', 'string'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
