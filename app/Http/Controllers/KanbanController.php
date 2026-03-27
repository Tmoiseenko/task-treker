<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KanbanController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private TaskService $taskService
    ) {}

    /**
     * Display the Kanban board.
     */
    public function index(Request $request): View
    {
        $query = Task::with(['project', 'assignee', 'tags']);

        // Apply filters using query scopes
        if ($request->filled('project_id')) {
            $query->byProject($request->project_id);
        }

        if ($request->filled('assignee_id')) {
            $query->byAssignee($request->assignee_id);
        }

        if ($request->filled('priority')) {
            $query->byPriority($request->priority);
        }

        if ($request->filled('tags')) {
            $tags = is_array($request->tags) 
                ? $request->tags 
                : explode(',', $request->tags);
            $query->byTags(array_filter($tags));
        }

        // Get all tasks and group by status
        $tasks = $query->get();

        // Group tasks by status
        $tasksByStatus = [
            TaskStatus::TODO->value => $tasks->where('status', TaskStatus::TODO),
            TaskStatus::IN_PROGRESS->value => $tasks->where('status', TaskStatus::IN_PROGRESS),
            TaskStatus::IN_TESTING->value => $tasks->where('status', TaskStatus::IN_TESTING),
            TaskStatus::TEST_FAILED->value => $tasks->where('status', TaskStatus::TEST_FAILED),
            TaskStatus::DONE->value => $tasks->where('status', TaskStatus::DONE),
        ];

        // Get available projects for filter
        $projects = Project::orderBy('name')->get();

        return view('kanban.index', compact('tasksByStatus', 'projects'));
    }

    /**
     * Update task status via drag-and-drop.
     */
    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', array_column(TaskStatus::cases(), 'value'))],
        ]);

        $newStatus = TaskStatus::from($request->status);

        try {
            $this->taskService->changeStatus($task, $newStatus);

            return response()->json([
                'success' => true,
                'message' => 'Статус задачи успешно изменен',
                'task' => [
                    'id' => $task->id,
                    'status' => $task->status->value,
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
