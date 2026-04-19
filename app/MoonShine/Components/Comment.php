<?php

declare(strict_types=1);

namespace App\MoonShine\Components;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use MoonShine\UI\Components\MoonShineComponent;

/**
 * @method static static make()
 */
final class Comment extends MoonShineComponent
{
    protected string $view = 'admin.components.comment';

    public function __construct(Collection $comments)
    {
        parent::__construct();
        $this->comments = $comments;
    }

    /*
     * @return array<string, mixed>
     */
    protected function viewData(): array
    {
        return [
            'comments' => $this->comments->sortByDesc('created_at'),
        ];
    }
}
