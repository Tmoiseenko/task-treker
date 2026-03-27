<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Attachment;
use App\Models\Task;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Store a newly uploaded attachment.
     */
    public function store(StoreAttachmentRequest $request, Task $task): RedirectResponse
    {
        $file = $request->file('file');
        
        // Generate unique filename
        $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
        
        // Store file in storage/app/attachments
        $path = $file->storeAs('attachments', $filename);

        // Create attachment record
        $task->attachments()->create([
            'user_id' => auth()->id(),
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
        ]);

        return redirect()
            ->route('tasks.show', $task)
            ->with('success', 'Файл успешно загружен');
    }

    /**
     * Download the specified attachment.
     */
    public function download(Task $task, Attachment $attachment): StreamedResponse
    {
        // Ensure attachment belongs to the task
        if ($attachment->task_id !== $task->id) {
            abort(404);
        }

        // User must be able to view the task to download attachments
        $this->authorize('view', $task);

        // Check if file exists
        if (!Storage::exists($attachment->path)) {
            abort(404, 'Файл не найден');
        }

        return Storage::download($attachment->path, $attachment->original_name);
    }

    /**
     * Remove the specified attachment from storage.
     */
    public function destroy(Task $task, Attachment $attachment): RedirectResponse
    {
        // Ensure attachment belongs to the task
        if ($attachment->task_id !== $task->id) {
            abort(404);
        }

        // Only the attachment uploader or task author/assignee can delete
        if ($attachment->user_id !== auth()->id() && 
            $task->author_id !== auth()->id() && 
            $task->assignee_id !== auth()->id()) {
            abort(403, 'У вас нет прав для удаления этого файла');
        }

        // Delete file from storage
        if (Storage::exists($attachment->path)) {
            Storage::delete($attachment->path);
        }

        // Delete attachment record
        $attachment->delete();

        return redirect()
            ->route('tasks.show', $task)
            ->with('success', 'Файл успешно удален');
    }
}
