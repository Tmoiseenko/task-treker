<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Task;
use App\Models\MoonshineUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DocumentService
{
    /**
     * Создание нового документа
     *
     * @param array $data
     * @return Document
     */
    public function createDocument(array $data): Document
    {
        return DB::transaction(function () use ($data) {
            $document = Document::create([
                'title' => $data['title'],
                'content' => $data['content'],
                'category' => $data['category'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'moonshine_author_id' => $data['moonshine_author_id'],
                'version' => 1,
            ]);

            // Создаем первую версию документа
            $this->createVersion($document, $data['content'], MoonshineUser::find($data['moonshine_author_id']));

            return $document->fresh();
        });
    }

    /**
     * Обновление документа с версионированием
     *
     * @param Document $document
     * @param array $data
     * @return Document
     */
    public function updateDocument(Document $document, array $data): Document
    {
        return DB::transaction(function () use ($document, $data) {
            $contentChanged = isset($data['content']) && $data['content'] !== $document->content;

            // Обновляем основные поля документа
            $document->update([
                'title' => $data['title'] ?? $document->title,
                'content' => $data['content'] ?? $document->content,
                'category' => $data['category'] ?? $document->category,
                'project_id' => $data['project_id'] ?? $document->project_id,
            ]);

            // Если содержимое изменилось, создаем новую версию
            if ($contentChanged && isset($data['moonshine_user_id'])) {
                $document->version = $document->version + 1;
                $document->save();

                $this->createVersion($document, $data['content'], MoonshineUser::find($data['moonshine_user_id']));
            }

            return $document->fresh();
        });
    }

    /**
     * Прикрепление документа к задаче
     *
     * @param Document $document
     * @param Task $task
     * @return void
     */
    public function attachToTask(Document $document, Task $task): void
    {
        if (!$document->tasks()->where('task_id', $task->id)->exists()) {
            $document->tasks()->attach($task->id);
        }
    }

    /**
     * Создание новой версии документа
     *
     * @param Document $document
     * @param string $content
     * @param MoonshineUser $user
     * @return DocumentVersion
     */
    public function createVersion(Document $document, string $content, MoonshineUser $user): DocumentVersion
    {
        return DocumentVersion::create([
            'document_id' => $document->id,
            'content' => $content,
            'version' => $document->version,
            'moonshine_user_id' => $user->id,
            'created_at' => now(),
        ]);
    }

    /**
     * Поиск документов по названию и содержанию
     *
     * @param string $query
     * @return Collection
     */
    public function searchDocuments(string $query): Collection
    {
        return Document::where('title', 'like', "%{$query}%")
            ->orWhere('content', 'like', "%{$query}%")
            ->with(['author', 'project'])
            ->get();
    }
}
