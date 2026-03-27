<?php

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Models\Task;
use App\Services\TestingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Контроллер для создания и управления баг-репортами
 * 
 * Requirements: 11.5, 11.6, 11.7, 11.12
 */
class BugReportController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private TestingService $testingService
    ) {}

    /**
     * Отображение формы создания баг-репорта
     * 
     * @param Task $task Исходная задача
     * @return View
     * 
     * Requirements: 11.5
     */
    public function create(Task $task): View
    {
        $this->authorize('view', $task);

        return view('bug-reports.create', [
            'task' => $task,
            'priorities' => TaskPriority::cases(),
        ]);
    }

    /**
     * Создание баг-репорта
     * 
     * @param Request $request
     * @param Task $task Исходная задача
     * @return RedirectResponse
     * 
     * Requirements: 11.5, 11.6, 11.7
     */
    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('view', $task);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'steps_to_reproduce' => 'nullable|string',
            'expected_result' => 'nullable|string',
            'actual_result' => 'nullable|string',
            'assignee_id' => 'nullable|exists:users,id',
            'priority' => 'required|in:' . implode(',', array_column(TaskPriority::cases(), 'value')),
            'due_date' => 'nullable|date|after:today',
        ]);

        // Формируем описание баг-репорта
        $description = $validated['description'];
        
        if (!empty($validated['steps_to_reproduce'])) {
            $description .= "\n\n**Шаги воспроизведения:**\n" . $validated['steps_to_reproduce'];
        }
        
        if (!empty($validated['expected_result'])) {
            $description .= "\n\n**Ожидаемый результат:**\n" . $validated['expected_result'];
        }
        
        if (!empty($validated['actual_result'])) {
            $description .= "\n\n**Фактический результат:**\n" . $validated['actual_result'];
        }

        $bugReport = $this->testingService->createBugReport($task, [
            'title' => $validated['title'],
            'description' => $description,
            'author_id' => auth()->id(),
            'assignee_id' => $validated['assignee_id'] ?? $task->assignee_id,
            'priority' => TaskPriority::from($validated['priority']),
            'due_date' => $validated['due_date'] ?? null,
        ]);

        return redirect()
            ->route('tasks.show', $task)
            ->with('success', 'Баг-репорт успешно создан');
    }

    /**
     * Отображение списка баг-репортов для задачи
     * 
     * @param Task $task
     * @return View
     * 
     * Requirements: 11.11
     */
    public function index(Task $task): View
    {
        $this->authorize('view', $task);

        $bugReports = $task->bugReports()
            ->with(['assignee', 'author'])
            ->orderBy('created_at', 'desc')
            ->get();

        $allBugsFixed = $this->testingService->checkAllBugsFixed($task);

        return view('bug-reports.index', [
            'task' => $task,
            'bugReports' => $bugReports,
            'allBugsFixed' => $allBugsFixed,
        ]);
    }

    /**
     * Назначение баг-репорта на разработчика
     * 
     * @param Request $request
     * @param Task $bugReport
     * @return RedirectResponse
     * 
     * Requirements: 11.12
     */
    public function assign(Request $request, Task $bugReport): RedirectResponse
    {
        // Check if user can assign tasks (either update permission or is project member)
        $this->authorize('assign', $bugReport);

        $validated = $request->validate([
            'assignee_id' => 'required|exists:users,id',
        ]);

        $bugReport->update([
            'assignee_id' => $validated['assignee_id'],
        ]);

        return redirect()
            ->back()
            ->with('success', 'Баг-репорт назначен на разработчика');
    }
}
