<?php

declare(strict_types=1);

namespace App\MoonShine\Components;

use Closure;
use Illuminate\Support\Collection;
use MoonShine\UI\Components\MoonShineComponent;

/**
 * Рендерит коллекцию задач в виде карточек,
 * стилизованных под tasks/index.blade.php.
 *
 * Использование:
 *   TaskCardsBuilder::make($tasks)
 *       ->url(fn(Task $task) => route('tasks.show', $task))
 *
 * @method static static make(iterable $tasks = [])
 */
class TaskCardsBuilder extends MoonShineComponent
{
    protected string $view = 'admin.components.task-cards-builder';

    /**
     * Замыкание или строка-URL для заголовка карточки.
     * Получает объект Task первым аргументом.
     */
    protected Closure|string $urlDetail = '#';
    protected Closure|string $urlEdit = '#';

    public function __construct(
        protected iterable $tasks = [],
    ) {
        parent::__construct();
    }

    /** Задать генератор URL детальной страницы для каждой карточки */
    public function urlDetail(Closure|string $url): static
    {
        $this->urlDetail = $url;

        return $this;
    }

    /** Задать генератор URL страницы редактирования для каждой карточки */
    public function urlEdit(Closure|string $url): static
    {
        $this->urlEdit = $url;

        return $this;
    }

    protected function viewData(): array
    {
        return [
            'tasks' => $this->tasks instanceof Collection
                ? $this->tasks
                : collect($this->tasks),
            'urlDetail' => $this->urlDetail,
            'urlEdit' => $this->urlEdit,
        ];
    }
}

