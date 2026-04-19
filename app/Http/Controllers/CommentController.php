<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Task;
use App\MoonShine\Resources\Task\Pages\TaskDetailPage;
use App\MoonShine\Resources\Task\Pages\TaskFormPage;
use App\MoonShine\Resources\Task\TaskResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response;

class CommentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Store a newly created comment in storage.
     */
    public function store(StoreCommentRequest $request)
    {
        $validated = $request->validated();

        Comment::create($validated);

        return response()->json([
            'message' => 'Комментарий успешно добавлен',
            'status' => Response::HTTP_CREATED
        ]);
    }

    /**
     * Remove the specified comment from storage (soft delete).
     */
    public function destroy(Comment $comment): RedirectResponse
    {
        $taskId = $comment->task_id;
        // Only the comment author can delete their own comment
        if ($comment->moonshine_user_id !== auth()->id()) {
            abort(403, 'Вы можете удалять только свои комментарии');
        }

        // Soft delete by setting deleted_at timestamp
        $comment->delete();

        return redirect()->back();
    }
}
