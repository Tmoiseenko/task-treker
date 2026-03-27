<?php

namespace App\Listeners;

use App\Events\DeadlineApproaching;
use App\Services\NotificationService;

class SendDeadlineApproachingNotification
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
    public function handle(DeadlineApproaching $event): void
    {
        $this->notificationService->notifyDeadlineApproaching($event->task);
    }
}
