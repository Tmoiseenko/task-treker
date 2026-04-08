<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use App\MoonShine\Pages\Kanban;
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
use MoonShine\AssetManager\InlineCss;
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
            InlineCss::make(<<<'CSS'
                /* Tailwind utility classes missing from MoonShine compiled CSS (needed for Kanban board) */
                .overflow-x-auto { overflow-x: auto; }
                .overflow-y-auto { overflow-y: auto; }
                .flex-1 { flex: 1 1 0%; }
                .flex-none { flex: none; }
                .min-h-0 { min-height: 0px; }
                .pb-4 { padding-bottom: calc(var(--spacing, .25rem) * 4); }
                .w-100 { width: calc(var(--spacing, .25rem) * 100); }
            CSS),
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
            MenuItem::make(Kanban::class)
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
