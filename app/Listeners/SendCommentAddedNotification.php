<?php

namespace App\Listeners;

use App\Events\CommentAdded;
use App\Services\NotificationService;

class SendCommentAddedNotification
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
    public function handle(CommentAdded $event): void
    {
        $this->notificationService->notifyCommentAdded($event->comment);
    }
}
