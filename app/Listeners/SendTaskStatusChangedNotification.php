<?php

namespace App\Listeners;

use App\Events\TaskStatusChanged;
use App\Services\NotificationService;

class SendTaskStatusChangedNotification
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(TaskStatusChanged $event): void
    {
        $this->notificationService->notifyStatusChanged($event->task, $event->oldStatus);
    }
}
