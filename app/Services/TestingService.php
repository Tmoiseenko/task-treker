<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\ChecklistItem;
use App\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TestingService
{
    /**
     * Создание чек-листа для задачи
     * 
     * @param Task $task Задача для которой создается чек-лист
     * @param array $items Массив пунктов чек-листа ['title' => 'Название пункта']
     * @return Collection Коллекция созданных ChecklistItem
     * 
     * Requirements: 11.1, 11.2
     */
    public function createChecklist(Task $task, array $items): Collection
    {
        return DB::transaction(function () use ($task, $items) {
            $checklistItems = collect();
            
            foreach ($items as $index => $item) {
                $checklistItem = $task->checklistItems()->create([
                    'title' => is_array($item) ? $item['title'] : $item,
                    'is_completed' => false,
                    'order' => $index + 1,
                ]);
                
                $checklistItems->push($checklistItem);
            }
            
            return $checklistItems;
        });
    }

    /**
     * Переключение статуса пункта чек-листа
     * 
     * @param ChecklistItem $item Пункт чек-листа
     * @return void
     * 
     * Requirements: 11.3
     */
    public function toggleChecklistItem(ChecklistItem $item): void
    {
        $item->update([
            'is_completed' => !$item->is_completed,
        ]);
    }

    /**
     * Расчет прогресса выполнения чек-листа
     * 
     * @param Task $task Задача
     * @return float Процент выполнения (0-100)
     * 
     * Requirements: 11.4
     */
    public function getChecklistProgress(Task $task): float
    {
        $totalItems = $task->checklistItems()->count();
        
        if ($totalItems === 0) {
            return 0.0;
        }
        
        $completedItems = $task->checklistItems()
            ->where('is_completed', true)
            ->count();
        
        return round(($completedItems / $totalItems) * 100, 2);
    }

    /**
     * Создание баг-репорта как связанной задачи
     * 
     * @param Task $task Исходная задача
     * @param array $data Данные баг-репорта
     * @return Task Созданный баг-репорт
     * 
     * Requirements: 11.5, 11.7
     */
    public function createBugReport(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data) {
            // Создаем баг-репорт как новую задачу
            $bugReport = Task::create([
                'title' => $data['title'] ?? 'Bug: ' . $task->title,
                'description' => $data['description'] ?? '',
                'project_id' => $task->project_id,
                'author_id' => $data['author_id'] ?? auth()->id(),
                'assignee_id' => $data['assignee_id'] ?? $task->assignee_id,
                'priority' => $data['priority'] ?? $task->priority,
                'status' => TaskStatus::TODO,
                'parent_task_id' => $task->id,
                'due_date' => $data['due_date'] ?? null,
            ]);
            
            return $bugReport;
        });
    }

    /**
     * Связывание баг-репорта с исходной задачей
     * 
     * @param Task $bugReport Баг-репорт
     * @param Task $originalTask Исходная задача
     * @return void
     * 
     * Requirements: 11.6
     */
    public function linkBugReport(Task $bugReport, Task $originalTask): void
    {
        $bugReport->update([
            'parent_task_id' => $originalTask->id,
        ]);
    }

    /**
     * Проверка закрытия всех баг-репортов задачи
     * 
     * @param Task $task Задача
     * @return bool True если все баг-репорты закрыты
     * 
     * Requirements: 11.13
     */
    public function checkAllBugsFixed(Task $task): bool
    {
        $bugReports = $task->bugReports;
        
        // Если нет баг-репортов, возвращаем true
        if ($bugReports->isEmpty()) {
            return true;
        }
        
        // Проверяем, что все баг-репорты имеют статус DONE
        return $bugReports->every(function ($bugReport) {
            return $bugReport->status === TaskStatus::DONE;
        });
    }
}
