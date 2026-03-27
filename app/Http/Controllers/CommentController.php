<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Task;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Store a newly created comment in storage.
     */
    public function store(StoreCommentRequest $request, Task $task): RedirectResponse
    {
        $comment = $task->comments()->create([
            'user_id' => auth()->id(),
            'content' => $request->validated()['content'],
        ]);

        return redirect()
            ->route('tasks.show', $task)
            ->with('success', 'Комментарий успешно добавлен');
    }

    /**
     * Remove the specified comment from storage (soft delete).
     */
    public function destroy(Task $task, Comment $comment): RedirectResponse
    {
        // Ensure comment belongs to the task
        if ($comment->task_id !== $task->id) {
            abort(404);
        }

        // Only the comment author can delete their own comment
        if ($comment->user_id !== auth()->id()) {
            abort(403, 'Вы можете удалять только свои комментарии');
        }

        // Soft delete by setting deleted_at timestamp
        $comment->update(['deleted_at' => now()]);

        return redirect()
            ->route('tasks.show', $task)
            ->with('success', 'Комментарий успешно удален');
    }
}
