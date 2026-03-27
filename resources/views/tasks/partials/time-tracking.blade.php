{{-- Time Tracking Section --}}
<div class="bg-white rounded-lg shadow-sm p-6" x-data="timeTracking({{ $taskStage->id }})">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900">{{ $taskStage->stage->name }}</h3>
        <span class="text-sm text-gray-500">{{ $taskStage->status->value }}</span>
    </div>

    {{-- Timer Section --}}
    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
        <div class="flex items-center justify-between mb-3">
            <h4 class="font-medium text-gray-900">Таймер</h4>
            <div class="text-2xl font-mono font-bold text-gray-900" x-text="displayTime"></div>
        </div>
        
        <div class="flex gap-2">
            <button 
                @click="startTimer"
                x-show="!isRunning"
                type="button"
                class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Запустить
            </button>
            
            <button 
                @click="stopTimer"
                x-show="isRunning"
                type="button"
                class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"></path>
                </svg>
                Остановить
            </button>
        </div>
    </div>

    {{-- Estimate Form --}}
    <div class="mb-6">
        <h4 class="font-medium text-gray-900 mb-3">Оценка времени</h4>
        
        @php
            $userEstimate = $taskStage->estimates->where('user_id', auth()->id())->first();
        @endphp
        
        <form method="POST" action="{{ route('estimates.store', $taskStage) }}" class="flex gap-2">
            @csrf
            <div class="flex-1">
                <input 
                    type="number" 
                    name="hours" 
                    step="0.5" 
                    min="0.1" 
                    max="1000"
                    value="{{ $userEstimate?->hours ?? '' }}"
                    placeholder="Часы"
                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    required>
            </div>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                {{ $userEstimate ? 'Обновить' : 'Добавить' }}
            </button>
        </form>
        
        {{-- Display all estimates --}}
        @if($taskStage->estimates->count() > 0)
            <div class="mt-3 space-y-1">
                @foreach($taskStage->estimates as $estimate)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">{{ $estimate->user->name }}:</span>
                        <span class="font-medium text-gray-900">{{ $estimate->hours }} ч</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Manual Time Entry Form --}}
    <div class="mb-6">
        <h4 class="font-medium text-gray-900 mb-3">Добавить время вручную</h4>
        
        <form method="POST" action="{{ route('time-entries.store', $taskStage) }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Часы</label>
                    <input 
                        type="number" 
                        name="hours" 
                        step="0.1" 
                        min="0.1" 
                        max="24"
                        placeholder="0.0"
                        class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                        required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Дата</label>
                    <input 
                        type="date" 
                        name="date" 
                        value="{{ date('Y-m-d') }}"
                        max="{{ date('Y-m-d') }}"
                        class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                        required>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Описание (опционально)</label>
                <textarea 
                    name="description" 
                    rows="2"
                    placeholder="Что было сделано..."
                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                Добавить запись
            </button>
        </form>
    </div>

    {{-- Time Entries List --}}
    @if($taskStage->timeEntries->count() > 0)
        <div>
            <h4 class="font-medium text-gray-900 mb-3">Записи времени</h4>
            <div class="space-y-2">
                @foreach($taskStage->timeEntries as $entry)
                    <div class="flex justify-between items-start p-3 bg-gray-50 rounded-lg">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-medium text-gray-900">{{ $entry->user->name }}</span>
                                <span class="text-sm text-gray-500">{{ $entry->date->format('d.m.Y') }}</span>
                            </div>
                            @if($entry->description)
                                <p class="text-sm text-gray-600">{{ $entry->description }}</p>
                            @endif
                        </div>
                        <div class="text-right ml-4">
                            <div class="font-semibold text-gray-900">{{ $entry->hours }} ч</div>
                            <div class="text-sm text-gray-600">{{ number_format($entry->cost, 2) }} ₽</div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            {{-- Total for this stage --}}
            <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="flex justify-between items-center">
                    <span class="font-semibold text-gray-900">Итого по этапу:</span>
                    <div class="text-right">
                        <div class="font-bold text-lg text-gray-900">{{ $taskStage->timeEntries->sum('hours') }} ч</div>
                        <div class="text-sm text-gray-600">{{ number_format($taskStage->timeEntries->sum('cost'), 2) }} ₽</div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
function timeTracking(taskStageId) {
    return {
        isRunning: false,
        startedAt: null,
        displayTime: '00:00:00',
        interval: null,
        
        init() {
            this.checkTimerStatus();
            // Check status every 5 seconds
            setInterval(() => this.checkTimerStatus(), 5000);
        },
        
        async checkTimerStatus() {
            try {
                const response = await fetch(`/task-stages/${taskStageId}/timer/status`);
                const data = await response.json();
                
                this.isRunning = data.is_running;
                
                if (this.isRunning && data.timer_data) {
                    this.startedAt = new Date(data.timer_data.started_at);
                    this.startDisplayTimer();
                } else {
                    this.stopDisplayTimer();
                }
            } catch (error) {
                console.error('Error checking timer status:', error);
            }
        },
        
        async startTimer() {
            try {
                const response = await fetch(`/task-stages/${taskStageId}/timer/start`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                if (response.ok) {
                    await this.checkTimerStatus();
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error starting timer:', error);
            }
        },
        
        async stopTimer() {
            try {
                const response = await fetch(`/task-stages/${taskStageId}/timer/stop`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                if (response.ok) {
                    this.stopDisplayTimer();
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error stopping timer:', error);
            }
        },
        
        startDisplayTimer() {
            if (this.interval) return;
            
            this.updateDisplay();
            this.interval = setInterval(() => this.updateDisplay(), 1000);
        },
        
        stopDisplayTimer() {
            if (this.interval) {
                clearInterval(this.interval);
                this.interval = null;
            }
            this.displayTime = '00:00:00';
        },
        
        updateDisplay() {
            if (!this.startedAt) return;
            
            const now = new Date();
            const diff = Math.floor((now - this.startedAt) / 1000);
            
            const hours = Math.floor(diff / 3600);
            const minutes = Math.floor((diff % 3600) / 60);
            const seconds = diff % 60;
            
            this.displayTime = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }
    }
}
</script>
