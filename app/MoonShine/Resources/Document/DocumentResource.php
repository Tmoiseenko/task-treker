<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Document;

use Illuminate\Database\Eloquent\Model;
use App\Models\Document;
use App\MoonShine\Resources\Document\Pages\DocumentIndexPage;
use App\MoonShine\Resources\Document\Pages\DocumentFormPage;
use App\MoonShine\Resources\Document\Pages\DocumentDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<Document, DocumentIndexPage, DocumentFormPage, DocumentDetailPage>
 */
class DocumentResource extends ModelResource
{
    protected string $model = Document::class;

    protected string $title = 'Documents';
    
    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            DocumentIndexPage::class,
            DocumentFormPage::class,
            DocumentDetailPage::class,
        ];
    }
}
