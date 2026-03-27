<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use App\Models\TaskStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EstimateController extends Controller
{
    /**
     * Создать или обновить оценку времени для этапа задачи
     */
    public function store(Request $request, TaskStage $taskStage)
    {
        $validated = $request->validate([
            'hours' => 'required|numeric|min:0.1|max:1000',
        ]);

        $user = Auth::user();

        // Проверяем, есть ли уже оценка от этого пользователя для этого этапа
        $estimate = Estimate::where('task_stage_id', $taskStage->id)
            ->where('user_id', $user->id)
            ->first();

        if ($estimate) {
            // Обновляем существующую оценку
            $estimate->update([
                'hours' => $validated['hours'],
            ]);
        } else {
            // Создаем новую оценку
            $estimate = Estimate::create([
                'task_stage_id' => $taskStage->id,
                'user_id' => $user->id,
                'hours' => $validated['hours'],
            ]);
        }

        return redirect()->back()->with('success', 'Оценка времени сохранена');
    }

    /**
     * Обновить существующую оценку
     */
    public function update(Request $request, Estimate $estimate)
    {
        // Проверяем, что пользователь может обновлять только свою оценку
        if ($estimate->user_id !== Auth::id()) {
            abort(403, 'Вы можете обновлять только свои оценки');
        }

        $validated = $request->validate([
            'hours' => 'required|numeric|min:0.1|max:1000',
        ]);

        $estimate->update($validated);

        return redirect()->back()->with('success', 'Оценка времени обновлена');
    }

    /**
     * Удалить оценку
     */
    public function destroy(Estimate $estimate)
    {
        // Проверяем, что пользователь может удалять только свою оценку
        if ($estimate->user_id !== Auth::id()) {
            abort(403, 'Вы можете удалять только свои оценки');
        }

        $estimate->delete();

        return redirect()->back()->with('success', 'Оценка времени удалена');
    }
}
