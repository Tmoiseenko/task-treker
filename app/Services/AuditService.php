<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Log a change to a task field.
     *
     * @param Task $task The task being modified
     * @param string $field The field that changed
     * @param mixed $oldValue The old value
     * @param mixed $newValue The new value
     * @return AuditLog
     */
    public function logChange(Task $task, string $field, mixed $oldValue, mixed $newValue): AuditLog
    {
        // Convert enum values to their string representation
        if ($oldValue instanceof \BackedEnum) {
            $oldValue = $oldValue->value;
        }
        if ($newValue instanceof \BackedEnum) {
            $newValue = $newValue->value;
        }

        return AuditLog::create([
            'task_id' => $task->id,
            'moonshine_user_id' => Auth::id(),
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
    }

    /**
     * Get the complete history of changes for a task.
     *
     * @param Task $task The task to get history for
     * @return Collection Collection of AuditLog entries ordered by created_at descending
     */
    public function getTaskHistory(Task $task): Collection
    {
        return $task->auditLogs()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
