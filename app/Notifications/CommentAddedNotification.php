<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CommentAddedNotification extends Notification
{
    use Queueable;

    public function __construct(public Comment $comment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $task = $this->comment->task;
        $commenterName = $this->comment->user->name;

        return [
            'type' => 'comment_added',
            'title' => 'Новый комментарий',
            'message' => "{$commenterName} добавил комментарий к задаче \"{$task->title}\"",
            'task_id' => $task->id,
            'task_title' => $task->title,
            'comment_id' => $this->comment->id,
            'commenter_id' => $this->comment->moonshine_user_id,
            'commenter_name' => $commenterName,
        ];
    }
}
