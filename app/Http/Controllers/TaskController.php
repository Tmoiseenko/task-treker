<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\ChangeTaskStatusRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private TaskService $taskService
    ) {}

    /**
     * Display a listing of tasks.
     */
    public function index(Request $request): View
    {
        $query = Task::with(['project', 'author', 'assignee', 'tags']);

        // Apply filters using query scopes
        if ($request->filled('project_id')) {
            $query->byProject($request->project_id);
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('priority')) {
            $query->byPriority($request->priority);
        }

        if ($request->filled('assignee_id')) {
            $query->byAssignee($request->assignee_id);
        }

        if ($request->filled('author_id')) {
            $query->byAuthor($request->author_id);
        }

        if ($request->filled('tags')) {
            $tags = is_array($request->tags)
                ? $request->tags
                : explode(',', $request->tags);
            $query->byTags(array_filter($tags));
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $tasks = $query->latest()->paginate(20);

        return view('tasks.index', compact('tasks'));
    }

    /**
     * Show the form for creating a new task.
     */
    public function create(): View
    {
        $this->authorize('create', Task::class);

        return view('tasks.create');
    }

    /**
     * Store a newly created task in storage.
     */
    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Set author_id to current user
        $data['author_id'] = auth()->id();

        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = TaskStatus::TODO;
        }

        $task = $this->taskService->createTask($data);

        // Attach tags if provided
        if ($request->filled('tags')) {
            $task->tags()->sync($request->tags);
        }

        return redirect()
            ->route('tasks.show', $task)
            ->with('success', 'Задача успешно создана');
    }

    /**
     * Display the specified task.
     */
    public function show(Task $task): View
    {
        $this->authorize('view', $task);

        $task->load([
            'project',
            'author',
            'assignee',
            'tags',
            'taskStages.stage',
            'taskStages.estimates.user',
            'taskStages.timeEntries.user',
            'comments.user',
            'attachments',
            'auditLogs.user',
            'checklistItems',
            'bugReports',
            'parentTask',
            'documents'
        ]);

        return view('tasks.show', compact('task'));
    }

    /**
     * Show the form for editing the specified task.
     */
    public function edit(Task $task): View
    {
        $this->authorize('update', $task);

        $task->load(['project', 'tags']);

        return view('tasks.edit', compact('task'));
    }

    /**
     * Update the specified task in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $data = $request->validated();

        $this->taskService->updateTask($task, $data);

        // Sync tags if provided
        if ($request->has('tags')) {
            $task->tags()->sync($request->tags ?? []);
        }

        return redirect()
            ->route('tasks.show', $task)
            ->with('success', 'Задача успешно обновлена');
    }

    /**
     * Remove the specified task from storage.
     */
    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Задача успешно удалена');
    }

    /**
     * Assign a task to a user.
     */
    public function assignTask(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('assign', $task);

        $request->validate([
            'assignee_id' => ['required', 'exists:users,id'],
        ]);

        $user = \App\Models\MoonshineUser::findOrFail($request->assignee_id);

        $this->taskService->assignTask($task, $user);

        return redirect()
            ->route('tasks.show', $task)
            ->with('success', 'Задача успешно назначена');
    }

    /**
     * Take a task (assign to current user and set status to in_progress).
     */
    public function takeTask(Task $task): RedirectResponse
    {
        $this->authorize('take', $task);

        $this->taskService->takeTask($task, auth()->user());

        return redirect()
            ->route('tasks.show', $task)
            ->with('success', 'Вы взяли задачу в работу');
    }

    /**
     * Change the status of a task.
     */
    public function changeStatus(ChangeTaskStatusRequest $request, Task $task): RedirectResponse
    {
        $newStatus = TaskStatus::from($request->validated()['status']);

        try {
            $this->taskService->changeStatus($task, $newStatus);

            return redirect()
                ->route('tasks.show', $task)
                ->with('success', 'Статус задачи успешно изменен');
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }
}
