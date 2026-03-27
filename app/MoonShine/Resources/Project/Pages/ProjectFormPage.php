<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Project\Pages;

use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Project;
use App\Models\Stage;
use App\MoonShine\Resources\MoonShineUser\MoonShineUserResource;
use App\MoonShine\Resources\Stage\StageResource;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Laravel\Pages\Crud\FormPage;
use App\MoonShine\Resources\Project\ProjectResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends FormPage<ProjectResource, Project>
 */
final class ProjectFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),

                Text::make('Название', 'name')
                    ->required()
                    ->hint('Название проекта'),

                Textarea::make('Описание', 'description')
                    ->hint('Описание проекта'),

                Enum::make('Тип', 'type')
                    ->attach(ProjectType::class)
                    ->required()
                    ->hint('Тип проекта определяет предлагаемые этапы')
                    ->reactive(),

                Enum::make('Статус', 'status')
                    ->attach(ProjectStatus::class)
                    ->required()
                    ->default(ProjectStatus::ACTIVE->value),

                BelongsToMany::make('Этапы', 'stages', resource: StageResource::class)
                    ->hint('Выберите этапы для проекта. Система автоматически предложит этапы на основе типа проекта.')
                    ->selectMode(),

                BelongsToMany::make('Участники', 'members', resource: MoonShineUserResource::class)
                    ->hint('Участники проекта')
                    ->selectMode(),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string'],
            'status' => ['required', 'string'],
        ];
    }

    protected function onAfterFill(DataWrapperContract $item): DataWrapperContract
    {
        // Если проект новый и тип выбран, предлагаем этапы
        if (!$item->getKey() && $item->type) {
            $projectType = ProjectType::from($item->type);
            $defaultStageNames = $projectType->getDefaultStages();

            // Получаем ID этапов по названиям
            $stageIds = Stage::whereIn('name', $defaultStageNames)->pluck('id')->toArray();

            // Устанавливаем предложенные этапы
            if (!empty($stageIds)) {
                $item->stages = $stageIds;
            }
        }

        return $item;
    }
}
