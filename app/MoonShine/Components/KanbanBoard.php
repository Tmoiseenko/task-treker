<?php

declare(strict_types=1);

namespace App\MoonShine\Components;

use MoonShine\UI\Components\AbstractWithComponents;

/**
 * Horizontal-scrolling Kanban board wrapper.
 * Each child should be a KanbanColumn component.
 *
 * @method static static make(iterable $components = [])
 */
class KanbanBoard extends AbstractWithComponents
{
    protected string $view = 'admin.components.kanban-board';
}

