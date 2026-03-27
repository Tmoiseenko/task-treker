{{-- Time Summary Widget --}}
<div class="bg-white rounded-lg shadow-sm p-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Учет времени</h2>
    
    {{-- Task Total Time --}}
    <div class="mb-6">
        <div class="flex justify-between items-center mb-2">
            <span class="text-sm font-medium text-gray-700">Всего по задаче:</span>
            <div class="text-right">
                <div class="text-2xl font-bold text-indigo-600">{{ $totalHours }} ч</div>
                <div class="text-sm text-gray-600">{{ number_format($totalCost, 2) }} ₽</div>
            </div>
        </div>
    </div>
    
    {{-- Breakdown by Stage --}}
    @if($task->taskStages->count() > 0)
        <div class="space-y-3">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">По этапам:</h3>
            @foreach($task->taskStages as $taskStage)
                @php
                    $stageHours = $taskStage->timeEntries->sum('hours');
                    $stageCost = $taskStage->timeEntries->sum('cost');
                    $stageEstimate = $taskStage->estimates->avg('hours');
                @endphp
                
                @if($stageHours > 0 || $stageEstimate > 0)
                    <div class="border-l-4 border-indigo-300 pl-3 py-2">
                        <div class="flex justify-between items-start mb-1">
                            <span class="text-sm font-medium text-gray-900">{{ $taskStage->stage->name }}</span>
                            <div class="text-right">
                                <div class="text-sm font-semibold text-gray-900">{{ $stageHours }} ч</div>
                                @if($stageCost > 0)
                                    <div class="text-xs text-gray-600">{{ number_format($stageCost, 2) }} ₽</div>
                                @endif
                            </div>
                        </div>
                        
                        {{-- Progress bar if estimate exists --}}
                        @if($stageEstimate > 0)
                            @php
                                $progress = min(100, ($stageHours / $stageEstimate) * 100);
                                $progressColor = $progress > 100 ? 'bg-red-500' : ($progress > 80 ? 'bg-yellow-500' : 'bg-green-500');
                            @endphp
                            <div class="mt-2">
                                <div class="flex justify-between text-xs text-gray-600 mb-1">
                                    <span>Оценка: {{ $stageEstimate }} ч</span>
                                    <span>{{ round($progress) }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                    <div class="{{ $progressColor }} h-1.5 rounded-full transition-all" style="width: {{ min(100, $progress) }}%"></div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    @endif
    
    {{-- Breakdown by User --}}
    @php
        $userBreakdown = [];
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
    @endphp
    
    @if(count($userBreakdown) > 0)
        <div class="mt-6 pt-6 border-t border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">По исполнителям:</h3>
            <div class="space-y-2">
                @foreach($userBreakdown as $userData)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-700">{{ $userData['name'] }}</span>
                        <div class="text-right">
                            <div class="text-sm font-semibold text-gray-900">{{ $userData['hours'] }} ч</div>
                            <div class="text-xs text-gray-600">{{ number_format($userData['cost'], 2) }} ₽</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    
    {{-- Quick Actions --}}
    <div class="mt-6 pt-6 border-t border-gray-200">
        <button 
            @click="showTimeTracking = !showTimeTracking"
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition">
            <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Управление временем
        </button>
    </div>
</div>
