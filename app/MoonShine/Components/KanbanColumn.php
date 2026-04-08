<?php

declare(strict_types=1);

namespace App\MoonShine\Components;

use Closure;
use Illuminate\View\ComponentSlot;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\HasIconContract;
use MoonShine\Contracts\UI\HasLabelContract;
use MoonShine\UI\Components\AbstractWithComponents;
use MoonShine\UI\Traits\Components\WithColumnSpan;
use MoonShine\UI\Traits\WithIcon;
use MoonShine\UI\Traits\WithLabel;
use Throwable;

/**
 * A Kanban column that looks like a MoonShine Box but:
 *  - has a fixed width and never shrinks (flex-none), enabling horizontal scroll in KanbanBoard
 *  - scrolls its body independently (overflow-y-auto)
 *  - supports columnSpan() for optional placement inside a Grid
 *  - supports label(), icon(), dark() exactly like Box
 *  - supports headerClass() for per-column accent colour on the title bar
 *
 * @method static static make(Closure|string|iterable $labelOrComponents = [], iterable $components = [])
 */
class KanbanColumn extends AbstractWithComponents implements HasIconContract, HasLabelContract
{
    use WithLabel;
    use WithIcon;
    use WithColumnSpan;

    protected string $view = 'admin.components.kanban-column';

    /**
     * @param (Closure(static): string)|string|iterable<array-key, ComponentContract> $labelOrComponents
     * @param iterable<array-key, ComponentContract> $components
     * @throws Throwable
     */
    public function __construct(
        Closure|string|iterable $labelOrComponents = [],
        iterable $components = [],
        private bool $dark = false,
        private string $width = 'w-80',
        private string $headerClass = '',
        int $colSpan = 12,
        int $adaptiveColSpan = 12,
    ) {
        // Mirror Box constructor: first arg can be a label OR the components list
        if (is_iterable($labelOrComponents)) {
            /** @var iterable<array-key, ComponentContract> $labelOrComponents */
            $components = $labelOrComponents;
        } else {
            $this->setLabel($labelOrComponents);
        }

        $this->columnSpan($colSpan, $adaptiveColSpan);

        parent::__construct($components);
    }

    // ── Box-compatible API ────────────────────────────────────────────────────

    public function dark(): static
    {
        $this->dark = true;

        return $this;
    }

    public function isDark(): bool
    {
        return $this->dark;
    }

    // ── KanbanColumn-specific API ─────────────────────────────────────────────

    /**
     * Tailwind width class for the column (default: 'w-80' = 320px).
     * Examples: 'w-72', 'w-96', 'w-[360px]'
     */
    public function width(string $class): static
    {
        $this->width = $class;

        return $this;
    }

    /**
     * Extra Tailwind / CSS classes applied to the title bar only.
     * Use for per-column accent colours, e.g. 'text-blue-600'.
     */
    public function headerClass(string $class): static
    {
        $this->headerClass = $class;

        return $this;
    }

    // ── View data ─────────────────────────────────────────────────────────────

    protected function viewData(): array
    {
        return [
            'label'           => $this->getLabel(),
            'dark'            => $this->isDark(),
            'width'           => $this->width,
            'headerClass'     => $this->headerClass,
            'colSpan'         => $this->getColumnSpanValue(),
            'adaptiveColSpan' => $this->getAdaptiveColumnSpanValue(),
        ];
    }
}

