<?php

namespace App\Events;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskStatusChanged
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Task $task,
        public TaskStatus $oldStatus,
        public TaskStatus $newStatus
    ) {}
}
