<?php

namespace App\Observers;

use App\Models\Task;
use App\Services\AuditService;

class TaskObserver
{
    /**
     * The audit service instance.
     *
     * @var AuditService
     */
    protected AuditService $auditService;

    /**
     * Create a new observer instance.
     *
     * @param AuditService $auditService
     */
    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle the Task "created" event.
     * Automatically creates TaskStage records for all stages associated with the task's project.
     *
     * @param Task $task
     * @return void
     */
    public function created(Task $task): void
    {
        // Get all stages associated with the task's project
        $projectStages = $task->project->stages;

        // Create TaskStage for each project stage
        foreach ($projectStages as $index => $stage) {
            $task->taskStages()->create([
                'stage_id' => $stage->id,
                'status' => 'not_started',
                'order' => $index + 1,
            ]);
        }
    }

    /**
     * Handle the Task "updated" event.
     * Logs all changes to the task in the audit log using AuditService.
     *
     * @param Task $task
     * @return void
     */
    public function updated(Task $task): void
    {
        // Get the original (dirty) attributes
        $changes = $task->getChanges();
        $original = $task->getOriginal();

        // Log each changed field
        foreach ($changes as $field => $newValue) {
            // Skip timestamps and updated_at field
            if (in_array($field, ['created_at', 'updated_at'])) {
                continue;
            }

            $oldValue = $original[$field] ?? null;

            // Use AuditService to log the change
            $this->auditService->logChange($task, $field, $oldValue, $newValue);
        }
    }
}
