<?php

namespace App\Listeners;

use App\Events\TaskAssigned;
use App\Services\NotificationService;

class SendTaskAssignedNotification
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
    public function handle(TaskAssigned $event): void
    {
        $this->notificationService->notifyTaskAssigned($event->task);
    }
}
