<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Task\Pages;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\MoonShine\Components\Comment;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Crud\Components\Fragment;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Pages\Crud\FormPage;
use App\MoonShine\Resources\Task\TaskResource;
use App\MoonShine\Resources\Project\ProjectResource;
use App\MoonShine\Resources\MoonShineUser\MoonShineUserResource;
use App\MoonShine\Resources\Tag\TagResource;
use MoonShine\Support\AlpineJs;
use MoonShine\Support\Enums\FormMethod;
use MoonShine\Support\Enums\JsEvent;
use MoonShine\TinyMce\Fields\TinyMce;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Collapse;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Components\Tabs;
use MoonShine\UI\Components\Tabs\Tab;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\Hidden;
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
            Tabs::make([
                Tab::make('Основные настройки', [
                    Grid::make([
                        Column::make([
                            Box::make([
                                Text::make('Название', 'title')
                                    ->required()
                                    ->hint('Название задачи'),

                                TinyMce::make('Описание', 'description')
                                    ->hint('Подробное описание задачи'),
                            ])
                        ])->columnSpan(6),
                        Column::make([
                            Box::make([
                                Grid::make([
                                    Column::make([
                                        BelongsTo::make('Проект', 'project', resource: ProjectResource::class)
                                            ->required()
                                            ->hint('Проект, к которому относится задача'),
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

                                    ])->columnSpan(6),
                                    Column::make([
                                        BelongsTo::make('Автор', 'author', resource: MoonShineUserResource::class)
                                            ->hint('Автор задачи')
                                            ->disabled(),

                                        BelongsTo::make('Исполнитель', 'assignee', resource: MoonShineUserResource::class)
                                            ->nullable()
                                            ->hint('Исполнитель задачи'),
                                        Date::make('Срок выполнения', 'due_date')
                                            ->nullable()
                                            ->hint('Дата, до которой задача должна быть выполнена'),
                                    ])->columnSpan(6),
                                ]),
                                BelongsToMany::make('Теги', 'tags', resource: TagResource::class)
                                    ->hint('Теги для категоризации задачи')
                                    ->selectMode(),
                            ])
                        ])->columnSpan(6),
                    ]),
                ]),
                Tab::make('Комментарии      ', [
                    ActionButton::make('Добавить комментарий')
                        ->primary()
                        ->inModal(
                            title: 'Добавить комментарий',
                            components: [
                                FormBuilder::make(
                                    action: route('comments.store'),
                                    method: FormMethod::POST,
                                    fields: [
                                        TinyMce::make('Комментарий', 'content')
                                            ->required(),
                                        Hidden::make('Задача', 'task_id')
                                            ->setValue($this->getItem()->id)
                                            ->required(),
                                        Hidden::make('Автор', 'moonshine_user_id')
                                            ->setValue(auth()->id())
                                            ->required(),
                                    ],
                                )->async(
                                    events: [
                                        AlpineJs::event(JsEvent::FRAGMENT_UPDATED, 'comments-fragment'),
                                    ]
                                )
                            ],
                            name: 'my-modal',
                        ),
                    Fragment::make([
                        Comment::make($this->getItem()->comments)
                    ])
                        ->name('comments-fragment')
                        ->updateWith(['resourceItem' => $this->getItem()->id])
                ]),
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
