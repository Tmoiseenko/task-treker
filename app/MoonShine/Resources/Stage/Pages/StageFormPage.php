<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Stage\Pages;

use App\Models\Stage;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use App\MoonShine\Resources\Stage\StageResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends FormPage<StageResource, Stage>
 */
final class StageFormPage extends FormPage
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
                    ->hint('Название этапа (например: Дизайн, Бэкенд, Фронтенд)'),

                Textarea::make('Описание', 'description')
                    ->hint('Описание этапа работы'),

                Number::make('Порядок', 'order')
                    ->required()
                    ->default(0)
                    ->hint('Порядок отображения этапа (меньшее число - выше в списке)'),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'order' => ['required', 'integer', 'min:0'],
        ];
    }
}
