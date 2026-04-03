<?php

namespace App\Http\Controllers;

use App\Models\MoonshineUser;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    /**
     * Display the calendar view.
     */
    public function index(Request $request): View
    {
        // Validate view mode
        $viewMode = $request->input('view', 'month');
        if (!in_array($viewMode, ['week', 'month'])) {
            $viewMode = 'month';
        }

        // Get the date to display (default to current date)
        $date = $request->filled('date')
            ? Carbon::parse($request->date)
            : Carbon::now();

        // Calculate date range based on view mode
        if ($viewMode === 'week') {
            $startDate = $date->copy()->startOfWeek();
            $endDate = $date->copy()->endOfWeek();
        } else {
            $startDate = $date->copy()->startOfMonth();
            $endDate = $date->copy()->endOfMonth();
        }

        // Build query for tasks with due dates
        $query = Task::with(['project', 'assignee', 'tags'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$startDate, $endDate]);

        // Apply filters
        if ($request->filled('project_id')) {
            $query->byProject($request->project_id);
        }

        if ($request->filled('assignee_id')) {
            $query->byAssignee($request->assignee_id);
        }

        // Get tasks and group by date
        $tasks = $query->orderBy('due_date')->get();

        // Group tasks by due date
        $tasksByDate = $tasks->groupBy(function ($task) {
            return $task->due_date->format('Y-m-d');
        });

        // Get available projects and users for filters
        $projects = Project::orderBy('name')->get();
        $users = MoonshineUser::orderBy('name')->get();

        return view('calendar.index', compact(
            'tasksByDate',
            'projects',
            'users',
            'viewMode',
            'date',
            'startDate',
            'endDate'
        ));
    }
}
