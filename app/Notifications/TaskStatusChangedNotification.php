<?php

namespace App\Notifications;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Task $task,
        public TaskStatus $oldStatus
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $statusText = match ($this->task->status) {
            TaskStatus::TODO => 'не выполнено',
            TaskStatus::IN_PROGRESS => 'в работе',
            TaskStatus::IN_TESTING => 'на тестировании',
            TaskStatus::TEST_FAILED => 'тест провален',
            TaskStatus::FOR_UNLOADING => 'готово к выгрузке',
            TaskStatus::DONE => 'выполнено',
        };

        return [
            'type' => 'status_changed',
            'title' => 'Изменен статус задачи',
            'message' => "Статус задачи \"{$this->task->title}\" изменен на: {$statusText}",
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'old_status' => $this->oldStatus->value,
            'new_status' => $this->task->status->value,
        ];
    }
}
