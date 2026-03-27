<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DeadlineApproachingNotification extends Notification
{
    use Queueable;

    public function __construct(public Task $task)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $dueDate = $this->task->due_date->format('d.m.Y H:i');

        return [
            'type' => 'deadline_approaching',
            'title' => 'Приближается срок выполнения',
            'message' => "Задача \"{$this->task->title}\" должна быть выполнена до {$dueDate}",
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'due_date' => $this->task->due_date->toIso8601String(),
        ];
    }
}
