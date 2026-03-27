<?php

namespace App\Http\Controllers;

use App\Models\ChecklistItem;
use App\Models\Task;
use App\Services\TestingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Контроллер для управления чек-листами задач
 * 
 * Requirements: 11.1, 11.2, 11.3, 11.4
 */
class ChecklistController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private TestingService $testingService
    ) {}

    /**
     * Создание чек-листа для задачи
     * 
     * @param Request $request
     * @param Task $task
     * @return JsonResponse
     * 
     * Requirements: 11.1, 11.2
     */
    public function store(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*' => 'required|string|max:255',
        ]);

        $checklistItems = $this->testingService->createChecklist(
            $task,
            $validated['items']
        );

        return response()->json([
            'message' => 'Чек-лист успешно создан',
            'checklist' => $checklistItems,
            'progress' => $this->testingService->getChecklistProgress($task),
        ], 201);
    }

    /**
     * Переключение статуса пункта чек-листа
     * 
     * @param ChecklistItem $checklistItem
     * @return JsonResponse
     * 
     * Requirements: 11.3
     */
    public function toggle(ChecklistItem $checklistItem): JsonResponse
    {
        $this->authorize('update', $checklistItem->task);

        $this->testingService->toggleChecklistItem($checklistItem);

        return response()->json([
            'message' => 'Статус пункта обновлен',
            'item' => $checklistItem->fresh(),
            'progress' => $this->testingService->getChecklistProgress($checklistItem->task),
        ]);
    }

    /**
     * Получение прогресса выполнения чек-листа
     * 
     * @param Task $task
     * @return JsonResponse
     * 
     * Requirements: 11.4
     */
    public function progress(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $progress = $this->testingService->getChecklistProgress($task);

        return response()->json([
            'progress' => $progress,
            'total' => $task->checklistItems()->count(),
            'completed' => $task->checklistItems()->where('is_completed', true)->count(),
        ]);
    }

    /**
     * Удаление пункта чек-листа
     * 
     * @param ChecklistItem $checklistItem
     * @return JsonResponse
     */
    public function destroy(ChecklistItem $checklistItem): JsonResponse
    {
        $this->authorize('update', $checklistItem->task);

        $task = $checklistItem->task;
        $checklistItem->delete();

        return response()->json([
            'message' => 'Пункт чек-листа удален',
            'progress' => $this->testingService->getChecklistProgress($task),
        ]);
    }
}
