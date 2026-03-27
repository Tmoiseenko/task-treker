<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\MoonshineUser;
use Illuminate\Support\Facades\DB;

class TaskService
{
    /**
     * Создание задачи с автоматическим созданием TaskStage
     * Note: TaskStages are automatically created by TaskObserver
     */
    public function createTask(array $data): Task
    {
        return DB::transaction(function () use ($data) {
            $task = Task::create($data);
            
            // TaskStages are automatically created by TaskObserver
            // No need to call createTaskStages() here
            
            return $task;
        });
    }

    /**
     * Обновление задачи
     */
    public function updateTask(Task $task, array $data): Task
    {
        $task->update($data);
        
        return $task->fresh();
    }

    /**
     * Назначение исполнителя на задачу
     */
    public function assignTask(Task $task, MoonshineUser $user): void
    {
        $task->update([
            'moonshine_assignee_id' => $user->id,
        ]);
    }

    /**
     * Взятие задачи в работу
     * Устанавливает исполнителя и меняет статус на "в работе"
     */
    public function takeTask(Task $task, MoonshineUser $user): void
    {
        $task->update([
            'moonshine_assignee_id' => $user->id,
            'status' => TaskStatus::IN_PROGRESS,
        ]);
    }

    /**
     * Изменение статуса задачи с валидацией переходов
     */
    public function changeStatus(Task $task, TaskStatus $newStatus): void
    {
        if (!$task->status->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$task->status->value} to {$newStatus->value}"
            );
        }

        $task->update([
            'status' => $newStatus,
        ]);
    }

    /**
     * Создание этапов для задачи на основе этапов проекта
     */
    public function createTaskStages(Task $task): void
    {
        $project = $task->project;
        
        // Получаем все этапы проекта
        $projectStages = $project->stages()->orderBy('order')->get();
        
        // Создаем TaskStage для каждого этапа проекта
        foreach ($projectStages as $index => $stage) {
            $task->taskStages()->create([
                'stage_id' => $stage->id,
                'order' => $index + 1,
            ]);
        }
    }
}
