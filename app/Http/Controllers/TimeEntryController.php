<?php

namespace App\Http\Controllers;

use App\Models\TaskStage;
use App\Models\TimeEntry;
use App\Services\TimeTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TimeEntryController extends Controller
{
    public function __construct(
        private TimeTrackingService $timeTrackingService
    ) {}

    /**
     * Запустить таймер для этапа задачи
     */
    public function startTimer(TaskStage $taskStage)
    {
        $user = Auth::user();

        // Проверяем, не запущен ли уже таймер
        if ($this->timeTrackingService->isTimerRunning($taskStage, $user)) {
            return redirect()->back()->with('error', 'Таймер уже запущен для этого этапа');
        }

        $this->timeTrackingService->startTimer($taskStage, $user);

        return redirect()->back()->with('success', 'Таймер запущен');
    }

    /**
     * Остановить таймер для этапа задачи
     */
    public function stopTimer(TaskStage $taskStage)
    {
        $user = Auth::user();

        try {
            $timeEntry = $this->timeTrackingService->stopTimer($taskStage, $user);
            
            return redirect()->back()->with('success', 
                sprintf('Таймер остановлен. Записано %.2f часов', $timeEntry->hours)
            );
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', 'Таймер не найден или уже остановлен');
        }
    }

    /**
     * Получить статус таймера для этапа задачи
     */
    public function timerStatus(TaskStage $taskStage)
    {
        $user = Auth::user();
        
        $isRunning = $this->timeTrackingService->isTimerRunning($taskStage, $user);
        $timerData = $this->timeTrackingService->getTimerData($taskStage, $user);

        return response()->json([
            'is_running' => $isRunning,
            'timer_data' => $timerData,
        ]);
    }

    /**
     * Создать запись времени вручную
     */
    public function store(Request $request, TaskStage $taskStage)
    {
        $validated = $request->validate([
            'hours' => 'required|numeric|min:0.1|max:24',
            'date' => 'required|date|before_or_equal:today',
            'description' => 'nullable|string|max:500',
        ]);

        $timeEntry = $this->timeTrackingService->addManualEntry([
            'task_stage_id' => $taskStage->id,
            'user_id' => Auth::id(),
            'hours' => $validated['hours'],
            'date' => $validated['date'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->back()->with('success', 
            sprintf('Добавлено %.2f часов. Стоимость: %.2f', $timeEntry->hours, $timeEntry->cost)
        );
    }

    /**
     * Обновить запись времени
     */
    public function update(Request $request, TimeEntry $timeEntry)
    {
        // Проверяем, что пользователь может обновлять только свои записи
        if ($timeEntry->user_id !== Auth::id()) {
            abort(403, 'Вы можете обновлять только свои записи времени');
        }

        $validated = $request->validate([
            'hours' => 'required|numeric|min:0.1|max:24',
            'date' => 'required|date|before_or_equal:today',
            'description' => 'nullable|string|max:500',
        ]);

        // Пересчитываем стоимость
        $user = Auth::user();
        $cost = $validated['hours'] * $user->hourly_rate;

        $timeEntry->update([
            'hours' => $validated['hours'],
            'date' => $validated['date'],
            'description' => $validated['description'] ?? null,
            'cost' => round($cost, 2),
        ]);

        return redirect()->back()->with('success', 'Запись времени обновлена');
    }

    /**
     * Удалить запись времени
     */
    public function destroy(TimeEntry $timeEntry)
    {
        // Проверяем, что пользователь может удалять только свои записи
        if ($timeEntry->user_id !== Auth::id()) {
            abort(403, 'Вы можете удалять только свои записи времени');
        }

        $timeEntry->delete();

        return redirect()->back()->with('success', 'Запись времени удалена');
    }
}
