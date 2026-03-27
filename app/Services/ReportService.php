<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Отчет по времени на задачу
     * Возвращает разбивку по специалистам и этапам
     * 
     * @param Task $task
     * @return array
     */
    public function getTaskTimeReport(Task $task): array
    {
        // Получаем все записи времени для задачи с группировкой по пользователям и этапам
        $timeEntries = TimeEntry::with(['user', 'taskStage.stage'])
            ->whereHas('taskStage', function ($query) use ($task) {
                $query->where('task_id', $task->id);
            })
            ->get();

        // Группируем по пользователям
        $byUser = $timeEntries->groupBy('user_id')->map(function ($entries, $userId) {
            $user = $entries->first()->user;
            
            return [
                'user_id' => $userId,
                'user_name' => $user->name,
                'hourly_rate' => $user->hourly_rate,
                'total_hours' => round($entries->sum('hours'), 2),
                'total_cost' => round($entries->sum('cost'), 2),
                'stages' => $entries->groupBy('task_stage_id')->map(function ($stageEntries) {
                    $stage = $stageEntries->first()->taskStage->stage;
                    
                    return [
                        'stage_id' => $stage->id,
                        'stage_name' => $stage->name,
                        'hours' => round($stageEntries->sum('hours'), 2),
                        'cost' => round($stageEntries->sum('cost'), 2),
                    ];
                })->values()->toArray(),
            ];
        })->values();

        return [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'total_hours' => round($timeEntries->sum('hours'), 2),
            'total_cost' => round($timeEntries->sum('cost'), 2),
            'by_user' => $byUser->toArray(),
        ];
    }

    /**
     * Отчет по времени на проект
     * Возвращает разбивку по задачам и специалистам
     * 
     * @param Project $project
     * @return array
     */
    public function getProjectTimeReport(Project $project): array
    {
        // Получаем все записи времени для проекта
        $timeEntries = TimeEntry::with(['user', 'taskStage.task', 'taskStage.stage'])
            ->whereHas('taskStage.task', function ($query) use ($project) {
                $query->where('project_id', $project->id);
            })
            ->get();

        // Группируем по задачам
        $byTask = $timeEntries->groupBy(function ($entry) {
            return $entry->taskStage->task_id;
        })->map(function ($entries, $taskId) {
            $task = $entries->first()->taskStage->task;
            
            return [
                'task_id' => $taskId,
                'task_title' => $task->title,
                'total_hours' => round($entries->sum('hours'), 2),
                'total_cost' => round($entries->sum('cost'), 2),
            ];
        })->values();

        // Группируем по пользователям
        $byUser = $timeEntries->groupBy('user_id')->map(function ($entries, $userId) {
            $user = $entries->first()->user;
            
            return [
                'user_id' => $userId,
                'user_name' => $user->name,
                'hourly_rate' => $user->hourly_rate,
                'total_hours' => round($entries->sum('hours'), 2),
                'total_cost' => round($entries->sum('cost'), 2),
            ];
        })->values();

        return [
            'project_id' => $project->id,
            'project_name' => $project->name,
            'total_hours' => round($timeEntries->sum('hours'), 2),
            'total_cost' => round($timeEntries->sum('cost'), 2),
            'by_task' => $byTask->toArray(),
            'by_user' => $byUser->toArray(),
        ];
    }

    /**
     * Отчет по выплатам пользователю за период
     * 
     * @param User $user
     * @param Carbon $from Начало периода (включительно)
     * @param Carbon $to Конец периода (включительно)
     * @return array
     */
    public function getUserPaymentReport(User $user, Carbon $from, Carbon $to): array
    {
        // Получаем все записи времени пользователя за период
        $timeEntries = TimeEntry::with(['taskStage.task.project', 'taskStage.stage'])
            ->where('user_id', $user->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get();

        // Группируем по проектам
        $byProject = $timeEntries->groupBy(function ($entry) {
            return $entry->taskStage->task->project_id;
        })->map(function ($entries, $projectId) {
            $project = $entries->first()->taskStage->task->project;
            
            return [
                'project_id' => $projectId,
                'project_name' => $project->name,
                'total_hours' => round($entries->sum('hours'), 2),
                'total_cost' => round($entries->sum('cost'), 2),
            ];
        })->values();

        // Группируем по датам
        $byDate = $timeEntries->groupBy('date')->map(function ($entries, $date) {
            return [
                'date' => $date,
                'total_hours' => round($entries->sum('hours'), 2),
                'total_cost' => round($entries->sum('cost'), 2),
            ];
        })->values();

        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'hourly_rate' => $user->hourly_rate,
            'period_from' => $from->toDateString(),
            'period_to' => $to->toDateString(),
            'total_hours' => round($timeEntries->sum('hours'), 2),
            'total_payment' => round($timeEntries->sum('cost'), 2),
            'by_project' => $byProject->toArray(),
            'by_date' => $byDate->toArray(),
        ];
    }

    /**
     * Отчет по выплатам команде за период
     * Возвращает разбивку по всем специалистам
     * 
     * @param Carbon $from Начало периода (включительно)
     * @param Carbon $to Конец периода (включительно)
     * @return array
     */
    public function getTeamPaymentReport(Carbon $from, Carbon $to): array
    {
        // Получаем все записи времени за период
        $timeEntries = TimeEntry::with(['user', 'taskStage.task.project'])
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get();

        // Группируем по пользователям
        $byUser = $timeEntries->groupBy('user_id')->map(function ($entries, $userId) {
            $user = $entries->first()->user;
            
            // Группируем по проектам для каждого пользователя
            $byProject = $entries->groupBy(function ($entry) {
                return $entry->taskStage->task->project_id;
            })->map(function ($projectEntries, $projectId) {
                $project = $projectEntries->first()->taskStage->task->project;
                
                return [
                    'project_id' => $projectId,
                    'project_name' => $project->name,
                    'hours' => round($projectEntries->sum('hours'), 2),
                    'cost' => round($projectEntries->sum('cost'), 2),
                ];
            })->values();
            
            return [
                'user_id' => $userId,
                'user_name' => $user->name,
                'hourly_rate' => $user->hourly_rate,
                'total_hours' => round($entries->sum('hours'), 2),
                'total_payment' => round($entries->sum('cost'), 2),
                'by_project' => $byProject->toArray(),
            ];
        })->values();

        // Группируем по проектам
        $byProject = $timeEntries->groupBy(function ($entry) {
            return $entry->taskStage->task->project_id;
        })->map(function ($entries, $projectId) {
            $project = $entries->first()->taskStage->task->project;
            
            return [
                'project_id' => $projectId,
                'project_name' => $project->name,
                'total_hours' => round($entries->sum('hours'), 2),
                'total_cost' => round($entries->sum('cost'), 2),
            ];
        })->values();

        return [
            'period_from' => $from->toDateString(),
            'period_to' => $to->toDateString(),
            'total_hours' => round($timeEntries->sum('hours'), 2),
            'total_payment' => round($timeEntries->sum('cost'), 2),
            'by_user' => $byUser->toArray(),
            'by_project' => $byProject->toArray(),
        ];
    }
}
