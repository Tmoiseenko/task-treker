<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use MoonShine\Laravel\Layouts\AppLayout;
use MoonShine\ColorManager\Palettes\LimePalette;
use MoonShine\ColorManager\ColorManager;
use MoonShine\Contracts\ColorManager\ColorManagerContract;
use MoonShine\Contracts\ColorManager\PaletteContract;
use App\MoonShine\Resources\Document\DocumentResource;
use App\MoonShine\Resources\Project\ProjectResource;
use App\MoonShine\Resources\Task\TaskResource;
use App\MoonShine\Resources\Stage\StageResource;
use App\MoonShine\Resources\Tag\TagResource;
use MoonShine\MenuManager\MenuItem;
use MoonShine\MenuManager\MenuGroup;

final class MoonShineLayout extends AppLayout
{
    /**
     * @var null|class-string<PaletteContract>
     */
    protected ?string $palette = LimePalette::class;

    protected function assets(): array
    {
        return [
            ...parent::assets(),
        ];
    }

    protected function menu(): array
    {
        return [
            ...parent::menu(),

            MenuGroup::make('Управление проектами', [
                MenuItem::make(ProjectResource::class),
                MenuItem::make(TaskResource::class),
                MenuItem::make(StageResource::class),
                MenuItem::make(TagResource::class)
            ]),

            MenuItem::make(DocumentResource::class),
        ];
    }

    /**
     * @param ColorManager $colorManager
     */
    protected function colors(ColorManagerContract $colorManager): void
    {
        parent::colors($colorManager);

        // $colorManager->primary('#00000');
    }
}
