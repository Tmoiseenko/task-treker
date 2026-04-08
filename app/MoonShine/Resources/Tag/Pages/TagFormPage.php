<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Tag\Pages;

use App\Models\Tag;
use Illuminate\Support\Collection;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use App\MoonShine\Resources\Tag\TagResource;
use MoonShine\UI\Components\Badge;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\Support\Enums\Color as ColorEnum;

/**
 * @extends FormPage<TagResource, Tag>
 */
final class TagFormPage extends FormPage
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
                    ->hint('Название тега'),

                Enum::make('Цвет из набора', 'color')->attach(ColorEnum::class)
                    ->native()
                    ->hint('Выберите цвет из набора Enum'),

                Box::make(Collection::make(ColorEnum::cases())->map(static function (ColorEnum $color) {
                    return Badge::make($color->name, $color->value);
                }))
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7'],
        ];
    }
}
