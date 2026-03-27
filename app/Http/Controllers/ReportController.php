<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService
    ) {}

    /**
     * Отчет по времени на задачу
     * 
     * @param Task $task
     * @return \Illuminate\View\View
     */
    public function taskTimeReport(Task $task)
    {
        // Проверка доступа к задаче через policy
        Gate::authorize('view', $task);

        $report = $this->reportService->getTaskTimeReport($task);

        return view('reports.task-time', [
            'task' => $task,
            'report' => $report,
        ]);
    }

    /**
     * Отчет по времени на проект
     * 
     * @param Project $project
     * @return \Illuminate\View\View
     */
    public function projectTimeReport(Project $project)
    {
        // Проверка доступа к проекту через policy
        Gate::authorize('view', $project);

        $report = $this->reportService->getProjectTimeReport($project);

        return view('reports.project-time', [
            'project' => $project,
            'report' => $report,
        ]);
    }

    /**
     * Отчет по выплатам пользователю
     * 
     * @param Request $request
     * @param User|null $user
     * @return \Illuminate\View\View
     */
    public function userPaymentReport(Request $request, User $user = null)
    {
        // Если пользователь не указан, показываем отчет для текущего пользователя
        $user = $user ?? Auth::user();

        // Проверка прав: пользователь может видеть только свой отчет,
        // если у него нет прав на просмотр финансов
        if ($user->id !== Auth::id()) {
            // Проверяем, есть ли у текущего пользователя разрешение view-finances
            $hasPermission = Auth::user()->roles()
                ->whereHas('permissions', function ($query) {
                    $query->where('action', 'view-finances');
                })
                ->exists();
            
            if (!$hasPermission) {
                abort(403, 'Unauthorized action.');
            }
        }

        // Получаем период из запроса или используем текущий месяц
        $from = $request->input('from') 
            ? Carbon::parse($request->input('from')) 
            : Carbon::now()->startOfMonth();
        
        $to = $request->input('to') 
            ? Carbon::parse($request->input('to')) 
            : Carbon::now()->endOfMonth();

        $report = $this->reportService->getUserPaymentReport($user, $from, $to);

        return view('reports.user-payment', [
            'user' => $user,
            'report' => $report,
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * Отчет по выплатам команде
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function teamPaymentReport(Request $request)
    {
        // Проверка прав на просмотр финансов
        $hasPermission = Auth::user()->roles()
            ->whereHas('permissions', function ($query) {
                $query->where('action', 'view-finances');
            })
            ->exists();
        
        if (!$hasPermission) {
            abort(403, 'Unauthorized action.');
        }

        // Получаем период из запроса или используем текущий месяц
        $from = $request->input('from') 
            ? Carbon::parse($request->input('from')) 
            : Carbon::now()->startOfMonth();
        
        $to = $request->input('to') 
            ? Carbon::parse($request->input('to')) 
            : Carbon::now()->endOfMonth();

        $report = $this->reportService->getTeamPaymentReport($from, $to);

        return view('reports.team-payment', [
            'report' => $report,
            'from' => $from,
            'to' => $to,
        ]);
    }
}
