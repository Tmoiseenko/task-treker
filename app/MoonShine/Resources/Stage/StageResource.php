<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Stage;

use App\Models\Stage;
use MoonShine\Laravel\Resources\ModelResource;
use App\MoonShine\Resources\Stage\Pages\StageFormPage;
use App\MoonShine\Resources\Stage\Pages\StageIndexPage;

/**
 * @extends ModelResource<Stage, StageIndexPage, StageFormPage, null>
 */
class StageResource extends ModelResource
{
    protected string $model = Stage::class;

    protected string $column = 'name';

    protected bool $simplePaginate = true;

    public function getTitle(): string
    {
        return 'Этапы';
    }

    protected function pages(): array
    {
        return [
            StageIndexPage::class,
            StageFormPage::class,
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'name',
            'description',
        ];
    }
}
