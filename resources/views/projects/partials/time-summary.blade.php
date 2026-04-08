{{-- Project Time Summary Widget --}}
<div class="bg-white rounded-lg shadow-sm p-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Учет времени по проекту</h2>

    {{-- Project Total Time --}}
    <div class="mb-6 p-4 bg-indigo-50 rounded-lg">
        <div class="flex justify-between items-center">
            <span class="text-sm font-medium text-gray-700">Всего по проекту:</span>
            <div class="text-right">
                <div class="text-3xl font-bold text-indigo-600">{{ $totalHours }} ч</div>
                <div class="text-sm text-gray-600">{{ number_format($totalCost, 2) }} ₽</div>
            </div>
        </div>
    </div>

    {{-- Breakdown by Task --}}
    @if($project->tasks->count() > 0)
        <div class="space-y-3">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">По задачам:</h3>
            @foreach($project->tasks as $task)
                @php
                    $taskHours = $task->taskStages->sum(function($stage) {
                        return $stage->timeEntries->sum('hours');
                    });
                    $taskCost = $task->taskStages->sum(function($stage) {
                        return $stage->timeEntries->sum('cost');
                    });
                @endphp

                @if($taskHours > 0)
                    <a href="{{ route('tasks.show', $task) }}" class="block border-l-4 border-indigo-300 pl-3 py-2 hover:bg-gray-50 transition">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-900">{{ $task->title }}</span>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ match($task->status) {
                                        \App\Enums\TaskStatus::TODO => 'bg-gray-100 text-gray-800',
                                        \App\Enums\TaskStatus::IN_PROGRESS => 'bg-blue-100 text-blue-800',
                                        \App\Enums\TaskStatus::IN_TESTING => 'bg-purple-100 text-purple-800',
                                        \App\Enums\TaskStatus::TEST_FAILED => 'bg-red-100 text-red-800',
                                        \App\Enums\TaskStatus::FOR_UNLOADING => 'bg-orange-100 text-orange-800',
                                        \App\Enums\TaskStatus::DONE => 'bg-green-100 text-green-800',
                                    } }}">
                                        {{ match($task->status) {
                                            \App\Enums\TaskStatus::TODO => 'Не выполнено',
                                            \App\Enums\TaskStatus::IN_PROGRESS => 'В работе',
                                            \App\Enums\TaskStatus::IN_TESTING => 'На тестировании',
                                            \App\Enums\TaskStatus::TEST_FAILED => 'Тест провален',
                                            \App\Enums\TaskStatus::FOR_UNLOADING => 'Готово к выгрузке',
                                            \App\Enums\TaskStatus::DONE => 'Выполнено',
                                        } }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right ml-4">
                                <div class="text-sm font-semibold text-gray-900">{{ $taskHours }} ч</div>
                                <div class="text-xs text-gray-600">{{ number_format($taskCost, 2) }} ₽</div>
                            </div>
                        </div>
                    </a>
                @endif
            @endforeach
        </div>
    @endif

    {{-- Breakdown by User --}}
    @php
        $userBreakdown = [];
        foreach($project->tasks as $task) {
            foreach($task->taskStages as $taskStage) {
                foreach($taskStage->timeEntries as $entry) {
                    if (!isset($userBreakdown[$entry->user_id])) {
                        $userBreakdown[$entry->user_id] = [
                            'name' => $entry->user->name,
                            'hours' => 0,
                            'cost' => 0
                        ];
                    }
                    $userBreakdown[$entry->user_id]['hours'] += $entry->hours;
                    $userBreakdown[$entry->user_id]['cost'] += $entry->cost;
                }
            }
        }
    @endphp

    @if(count($userBreakdown) > 0)
        <div class="mt-6 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">По исполнителям:</h3>
            <div class="space-y-2">
                @foreach($userBreakdown as $userData)
                    <div class="flex justify-between items-center p-2 hover:bg-gray-50 rounded">
                        <span class="text-sm text-gray-700">{{ $userData['name'] }}</span>
                        <div class="text-right">
                            <div class="text-sm font-semibold text-gray-900">{{ round($userData['hours'], 2) }} ч</div>
                            <div class="text-xs text-gray-600">{{ number_format($userData['cost'], 2) }} ₽</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Breakdown by Stage --}}
    @php
        $stageBreakdown = [];
        foreach($project->tasks as $task) {
            foreach($task->taskStages as $taskStage) {
                $stageName = $taskStage->stage->name;
                if (!isset($stageBreakdown[$stageName])) {
                    $stageBreakdown[$stageName] = [
                        'hours' => 0,
                        'cost' => 0
                    ];
                }
                $stageBreakdown[$stageName]['hours'] += $taskStage->timeEntries->sum('hours');
                $stageBreakdown[$stageName]['cost'] += $taskStage->timeEntries->sum('cost');
            }
        }
    @endphp

    @if(count($stageBreakdown) > 0)
        <div class="mt-6 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">По этапам:</h3>
            <div class="space-y-2">
                @foreach($stageBreakdown as $stageName => $stageData)
                    @if($stageData['hours'] > 0)
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-700">{{ $stageName }}</span>
                            <div class="text-right">
                                <div class="text-sm font-semibold text-gray-900">{{ round($stageData['hours'], 2) }} ч</div>
                                <div class="text-xs text-gray-600">{{ number_format($stageData['cost'], 2) }} ₽</div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>
