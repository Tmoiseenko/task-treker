<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskStage;
use App\Models\TimeEntry;
use App\Models\MoonshineUser;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TimeTrackingService
{
    /**
     * Запуск таймера для этапа задачи
     * Сохраняет время начала в кэше
     */
    public function startTimer(TaskStage $taskStage, MoonshineUser $user): void
    {
        $cacheKey = $this->getTimerCacheKey($taskStage, $user);
        
        Cache::put($cacheKey, [
            'started_at' => now()->toIso8601String(),
            'task_stage_id' => $taskStage->id,
            'moonshine_user_id' => $user->id,
        ], now()->addDays(7)); // Таймер хранится 7 дней
    }

    /**
     * Остановка таймера и создание TimeEntry
     * Возвращает созданную запись времени
     */
    public function stopTimer(TaskStage $taskStage, MoonshineUser $user): TimeEntry
    {
        $cacheKey = $this->getTimerCacheKey($taskStage, $user);
        $timerData = Cache::get($cacheKey);
        
        if (!$timerData) {
            throw new \RuntimeException('Timer not found or already stopped');
        }
        
        $startedAt = Carbon::parse($timerData['started_at']);
        $stoppedAt = now();
        
        // Вычисляем количество часов (положительное значение)
        $hours = abs($startedAt->diffInMinutes($stoppedAt)) / 60;
        
        // Создаем запись времени
        $timeEntry = $this->addManualEntry([
            'task_stage_id' => $taskStage->id,
            'moonshine_user_id' => $user->id,
            'hours' => round($hours, 2),
            'date' => $stoppedAt->toDateString(),
            'description' => 'Timer tracking',
        ]);
        
        // Удаляем таймер из кэша
        Cache::forget($cacheKey);
        
        return $timeEntry;
    }

    /**
     * Ручное добавление записи времени
     */
    public function addManualEntry(array $data): TimeEntry
    {
        return DB::transaction(function () use ($data) {
            // Получаем пользователя для расчета стоимости
            $user = MoonshineUser::findOrFail($data['moonshine_user_id']);
            
            // Рассчитываем стоимость
            $cost = $this->calculateCostForEntry($data['hours'], $user->hourly_rate);
            
            // Создаем запись времени
            $timeEntry = TimeEntry::create([
                'task_stage_id' => $data['task_stage_id'],
                'moonshine_user_id' => $data['moonshine_user_id'],
                'hours' => $data['hours'],
                'date' => $data['date'] ?? now()->toDateString(),
                'description' => $data['description'] ?? null,
                'cost' => $cost,
            ]);
            
            return $timeEntry;
        });
    }

    /**
     * Расчет стоимости на основе часов и ставки
     */
    public function calculateCost(TimeEntry $entry): float
    {
        return $this->calculateCostForEntry($entry->hours, $entry->user->hourly_rate);
    }

    /**
     * Вспомогательный метод для расчета стоимости
     */
    private function calculateCostForEntry(float $hours, float $hourlyRate): float
    {
        return round($hours * $hourlyRate, 2);
    }

    /**
     * Получить общее количество часов по задаче
     */
    public function getTaskTotalHours(Task $task): float
    {
        return (float) TimeEntry::whereHas('taskStage', function ($query) use ($task) {
            $query->where('task_id', $task->id);
        })->sum('hours');
    }

    /**
     * Получить общее количество часов по проекту
     */
    public function getProjectTotalHours(Project $project): float
    {
        return (float) TimeEntry::whereHas('taskStage.task', function ($query) use ($project) {
            $query->where('project_id', $project->id);
        })->sum('hours');
    }

    /**
     * Получить общее количество часов пользователя за период
     * 
     * @param MoonshineUser $user
     * @param Carbon|null $from Начало периода (включительно)
     * @param Carbon|null $to Конец периода (включительно)
     * @return float
     */
    public function getUserTotalHours(MoonshineUser $user, ?Carbon $from = null, ?Carbon $to = null): float
    {
        $query = TimeEntry::where('moonshine_user_id', $user->id);
        
        if ($from) {
            $query->where('date', '>=', $from->toDateString());
        }
        
        if ($to) {
            $query->where('date', '<=', $to->toDateString());
        }
        
        return (float) $query->sum('hours');
    }

    /**
     * Проверить, запущен ли таймер для этапа задачи и пользователя
     */
    public function isTimerRunning(TaskStage $taskStage, MoonshineUser $user): bool
    {
        $cacheKey = $this->getTimerCacheKey($taskStage, $user);
        return Cache::has($cacheKey);
    }

    /**
     * Получить данные активного таймера
     */
    public function getTimerData(TaskStage $taskStage, MoonshineUser $user): ?array
    {
        $cacheKey = $this->getTimerCacheKey($taskStage, $user);
        return Cache::get($cacheKey);
    }

    /**
     * Получить ключ кэша для таймера
     */
    private function getTimerCacheKey(TaskStage $taskStage, MoonshineUser $user): string
    {
        return "timer:task_stage_{$taskStage->id}:user_{$user->id}";
    }
}
