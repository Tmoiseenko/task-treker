<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Tag;

use App\Models\Tag;
use MoonShine\Laravel\Resources\ModelResource;
use App\MoonShine\Resources\Tag\Pages\TagFormPage;
use App\MoonShine\Resources\Tag\Pages\TagIndexPage;

/**
 * @extends ModelResource<Tag, TagIndexPage, TagFormPage, null>
 */
class TagResource extends ModelResource
{
    protected string $model = Tag::class;

    protected string $column = 'name';

    protected bool $simplePaginate = true;

    public function getTitle(): string
    {
        return 'Теги';
    }

    protected function pages(): array
    {
        return [
            TagIndexPage::class,
            TagFormPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'name',
        ];
    }
}
