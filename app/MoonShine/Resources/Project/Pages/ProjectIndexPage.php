<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Project\Pages;

use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use App\MoonShine\Resources\Project\ProjectResource;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends IndexPage<ProjectResource>
 */
final class ProjectIndexPage extends IndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),

            Text::make('Название', 'name')->sortable(),

            Textarea::make('Описание', 'description'),

            Enum::make('Тип', 'type')
                ->attach(ProjectType::class)
                ->sortable(),

            Enum::make('Статус', 'status')
                ->attach(ProjectStatus::class)
                ->sortable(),

        ];
    }

    protected function filters(): iterable
    {
        return [
            Text::make('Название', 'name'),
            Enum::make('Тип', 'type')->attach(ProjectType::class),
            Enum::make('Статус', 'status')->attach(ProjectStatus::class),
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
