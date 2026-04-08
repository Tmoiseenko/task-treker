<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\MoonShine\Components\KanbanBoard;
use App\MoonShine\Components\KanbanColumn;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Support\Attributes\Icon;
use MoonShine\UI\Components\Card;
use MoonShine\UI\Components\Layout\Box;

#[Icon('clipboard-document-list')]
class Kanban extends Page
{
    public function getBreadcrumbs(): array
    {
        return ['#' => $this->getTitle()];
    }

    public function getTitle(): string
    {
        return $this->title ?: 'Доска';
    }

    /** @return list<ComponentContract> */
    protected function components(): iterable
    {
        return [
            KanbanBoard::make([

                // Первый аргумент — лейбл (как в Box::make('Заголовок', [...]))
                KanbanColumn::make('Новая задача', [
                    Card::make('Задача 1'),
                    Card::make('Задача 1'),
                    Card::make('Задача 1'),
                    Card::make('Задача 1'),
                    Card::make('Задача 1'),
                    Card::make('Задача 1'),
                    Card::make('Задача 1'),
                    Card::make('Задача 1'),
                    Card::make('Задача 1'),
                    Card::make('Задача 1'),
                    Card::make('Задача 1'),
                    Card::make('Задача 1'),
                    Card::make('Задача 1'),
                    Card::make('Задача 1'),
                    Card::make('Задача 1'),
                    Card::make('Задача 1'),
                    Card::make('Задача 1'),
                ])->columnSpan(4),

                KanbanColumn::make('К работе', [
                ])
                    ->columnSpan(4),

                KanbanColumn::make('В работе')
                    ->columnSpan(4),

                KanbanColumn::make('На тестировании')
                    ->columnSpan(4),

                KanbanColumn::make('На выгрузку')
                    ->columnSpan(4),


            ]),
        ];
    }
}
