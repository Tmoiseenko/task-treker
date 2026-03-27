<?php

namespace App\Providers;

use App\Events\CommentAdded;
use App\Events\DeadlineApproaching;
use App\Events\TaskAssigned;
use App\Events\TaskStatusChanged;
use App\Listeners\SendCommentAddedNotification;
use App\Listeners\SendDeadlineApproachingNotification;
use App\Listeners\SendTaskAssignedNotification;
use App\Listeners\SendTaskStatusChangedNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        TaskAssigned::class => [
            SendTaskAssignedNotification::class,
        ],
        TaskStatusChanged::class => [
            SendTaskStatusChangedNotification::class,
        ],
        CommentAdded::class => [
            SendCommentAddedNotification::class,
        ],
        DeadlineApproaching::class => [
            SendDeadlineApproachingNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
