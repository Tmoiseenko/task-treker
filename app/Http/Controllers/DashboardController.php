<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Enums\TaskPriority;
use App\Models\MoonshineUser;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with statistics
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Collect general statistics
        $statistics = [
            'active_projects_count' => $this->getActiveProjectsCount(),
            'tasks_by_status' => $this->getTasksByStatus(),
            'tasks_by_priority' => $this->getTasksByPriority(),
            'top_specialists' => $this->getTopSpecialists(),
            'current_month_hours' => $this->getCurrentMonthHours(),
            'current_month_payments' => $this->getCurrentMonthPayments(),
        ];

        // Add personal statistics for specialists
        if ($this->isSpecialist($user)) {
            $statistics['personal_stats'] = $this->getPersonalStatistics($user);
        }

        return view('dashboard.index', compact('statistics'));
    }

    /**
     * Get count of active projects
     *
     * @return int
     */
    private function getActiveProjectsCount(): int
    {
        return Project::where('status', ProjectStatus::ACTIVE)->count();
    }

    /**
     * Get tasks grouped by status
     *
     * @return array
     */
    private function getTasksByStatus(): array
    {
        $tasksByStatus = Task::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status->value => $item->count];
            })
            ->toArray();

        // Ensure all statuses are present
        $result = [];
        foreach (TaskStatus::cases() as $status) {
            $result[$status->value] = $tasksByStatus[$status->value] ?? 0;
        }

        return $result;
    }

    /**
     * Get tasks grouped by priority
     *
     * @return array
     */
    private function getTasksByPriority(): array
    {
        $tasksByPriority = Task::select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->priority->value => $item->count];
            })
            ->toArray();

        // Ensure all priorities are present
        $result = [];
        foreach (TaskPriority::cases() as $priority) {
            $result[$priority->value] = $tasksByPriority[$priority->value] ?? 0;
        }

        return $result;
    }

    /**
     * Get top specialists by completed tasks
     *
     * @param int $limit
     * @return array
     */
    private function getTopSpecialists(int $limit = 10): array
    {
        return MoonshineUser::select('users.id', 'users.name', DB::raw('count(tasks.id) as completed_tasks'))
            ->join('tasks', 'users.id', '=', 'tasks.assignee_id')
            ->where('tasks.status', TaskStatus::DONE)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('completed_tasks')
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'completed_tasks' => $user->completed_tasks,
                ];
            })
            ->toArray();
    }

    /**
     * Get total hours for current month
     *
     * @return float
     */
    private function getCurrentMonthHours(): float
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $totalHours = TimeEntry::whereBetween('date', [
            $startOfMonth->toDateString(),
            $endOfMonth->toDateString()
        ])->sum('hours');

        return round((float) $totalHours, 2);
    }

    /**
     * Get total payments for current month
     *
     * @return float
     */
    private function getCurrentMonthPayments(): float
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $totalPayments = TimeEntry::whereBetween('date', [
            $startOfMonth->toDateString(),
            $endOfMonth->toDateString()
        ])->sum('cost');

        return round((float) $totalPayments, 2);
    }

    /**
     * Get personal statistics for a specialist
     *
     * @param User $user
     * @return array
     */
    private function getPersonalStatistics(User $user): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Tasks statistics
        $assignedTasks = Task::where('assignee_id', $user->id)->count();
        $completedTasks = Task::where('assignee_id', $user->id)
            ->where('status', TaskStatus::DONE)
            ->count();
        $inProgressTasks = Task::where('assignee_id', $user->id)
            ->where('status', TaskStatus::IN_PROGRESS)
            ->count();

        // Time and payment statistics for current month
        $monthlyHours = TimeEntry::where('user_id', $user->id)
            ->whereBetween('date', [
                $startOfMonth->toDateString(),
                $endOfMonth->toDateString()
            ])
            ->sum('hours');

        $monthlyPayment = TimeEntry::where('user_id', $user->id)
            ->whereBetween('date', [
                $startOfMonth->toDateString(),
                $endOfMonth->toDateString()
            ])
            ->sum('cost');

        return [
            'assigned_tasks' => $assignedTasks,
            'completed_tasks' => $completedTasks,
            'in_progress_tasks' => $inProgressTasks,
            'monthly_hours' => round((float) $monthlyHours, 2),
            'monthly_payment' => round((float) $monthlyPayment, 2),
        ];
    }

    /**
     * Check if user is a specialist (not admin or project manager)
     *
     * @param User $user
     * @return bool
     */
    private function isSpecialist(User $user): bool
    {
        $specialistRoles = ['designer', 'developer', 'tester'];

        return $user->roles()
            ->whereIn('name', $specialistRoles)
            ->exists();
    }
}
