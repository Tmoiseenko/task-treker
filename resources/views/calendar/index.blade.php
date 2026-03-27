@extends('layouts.app')

@section('title', 'Календарь задач')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">Календарь задач</h1>
</div>

<!-- Filters and View Mode -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6" 
     x-data="calendarFilters()" 
     x-init="initFromUrl()">
    <div class="space-y-4">
        <!-- View Mode Toggle -->
        <div class="flex items-center justify-between border-b pb-4">
            <div class="flex gap-2">
                <button @click="setViewMode('week')" 
                        :class="filters.view === 'week' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'"
                        class="font-semibold py-2 px-4 rounded-lg transition">
                    Неделя
                </button>
                <button @click="setViewMode('month')" 
                        :class="filters.view === 'month' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'"
                        class="font-semibold py-2 px-4 rounded-lg transition">
                    Месяц
                </button>
            </div>

            <!-- Date Navigation -->
            <div class="flex items-center gap-4">
                <button @click="navigatePrevious()" 
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded-lg">
                    ← Назад
                </button>
                <button @click="navigateToday()" 
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-3 rounded-lg">
                    Сегодня
                </button>
                <button @click="navigateNext()" 
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded-lg">
                    Вперед →
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Project Filter -->
            <div>
                <label for="project_id" class="block text-sm font-medium text-gray-700 mb-1">Проект</label>
                <select x-model="filters.project_id" 
                        @change="applyFilters()" 
                        id="project_id" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Все проекты</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Assignee Filter -->
            <div>
                <label for="assignee_id" class="block text-sm font-medium text-gray-700 mb-1">Исполнитель</label>
                <select x-model="filters.assignee_id" 
                        @change="applyFilters()" 
                        id="assignee_id" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Все исполнители</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex gap-2">
            <button @click="resetFilters()" 
                    type="button" 
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded-lg">
                Сбросить фильтры
            </button>
            <span class="text-sm text-gray-500 flex items-center" x-show="hasActiveFilters()" x-cloak>
                Активные фильтры применены
            </span>
        </div>
    </div>
</div>

<!-- Calendar Grid -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    @if($viewMode === 'week')
        <!-- Week View -->
        <div class="grid grid-cols-7 border-b border-gray-200">
            @php
                $daysOfWeek = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
                $currentDate = $startDate->copy();
            @endphp
            
            @foreach($daysOfWeek as $dayName)
                <div class="p-4 text-center font-semibold text-gray-700 border-r border-gray-200 last:border-r-0">
                    {{ $dayName }}
                    <div class="text-sm font-normal text-gray-500 mt-1">
                        {{ $currentDate->format('d.m') }}
                    </div>
                </div>
                @php $currentDate->addDay(); @endphp
            @endforeach
        </div>

        <div class="grid grid-cols-7 divide-x divide-gray-200">
            @php $currentDate = $startDate->copy(); @endphp
            
            @for($i = 0; $i < 7; $i++)
                @php
                    $dateKey = $currentDate->format('Y-m-d');
                    $dayTasks = $tasksByDate->get($dateKey, collect());
                    $isToday = $currentDate->isToday();
                @endphp
                
                <div class="min-h-[400px] p-3 {{ $isToday ? 'bg-blue-50' : '' }}">
                    @if($isToday)
                        <div class="text-xs font-semibold text-blue-600 mb-2">Сегодня</div>
                    @endif
                    
                    <div class="space-y-2">
                        @foreach($dayTasks as $task)
                            @include('calendar.partials.task-card', ['task' => $task])
                        @endforeach
                        
                        @if($dayTasks->isEmpty())
                            <div class="text-xs text-gray-400 text-center py-4">
                                Нет задач
                            </div>
                        @endif
                    </div>
                </div>
                
                @php $currentDate->addDay(); @endphp
            @endfor
        </div>
    @else
        <!-- Month View -->
        @php
            $firstDayOfMonth = $startDate->copy()->startOfMonth();
            $lastDayOfMonth = $endDate->copy()->endOfMonth();
            $startOfCalendar = $firstDayOfMonth->copy()->startOfWeek();
            $endOfCalendar = $lastDayOfMonth->copy()->endOfWeek();
            $totalDays = $startOfCalendar->diffInDays($endOfCalendar) + 1;
            $weeks = ceil($totalDays / 7);
        @endphp

        <!-- Month Header -->
        <div class="p-4 border-b border-gray-200 text-center">
            <h2 class="text-xl font-bold text-gray-900">
                {{ $date->locale('ru')->isoFormat('MMMM YYYY') }}
            </h2>
        </div>

        <!-- Days of Week Header -->
        <div class="grid grid-cols-7 border-b border-gray-200">
            @foreach(['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'] as $dayName)
                <div class="p-2 text-center font-semibold text-gray-700 border-r border-gray-200 last:border-r-0">
                    {{ $dayName }}
                </div>
            @endforeach
        </div>

        <!-- Calendar Grid -->
        @php $currentDate = $startOfCalendar->copy(); @endphp
        
        @for($week = 0; $week < $weeks; $week++)
            <div class="grid grid-cols-7 divide-x divide-gray-200 border-b border-gray-200 last:border-b-0">
                @for($day = 0; $day < 7; $day++)
                    @php
                        $dateKey = $currentDate->format('Y-m-d');
                        $dayTasks = $tasksByDate->get($dateKey, collect());
                        $isToday = $currentDate->isToday();
                        $isCurrentMonth = $currentDate->month === $date->month;
                    @endphp
                    
                    <div class="min-h-[120px] p-2 {{ $isToday ? 'bg-blue-50' : '' }} {{ !$isCurrentMonth ? 'bg-gray-50' : '' }}">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-semibold {{ $isToday ? 'text-blue-600' : ($isCurrentMonth ? 'text-gray-900' : 'text-gray-400') }}">
                                {{ $currentDate->format('d') }}
                            </span>
                            @if($dayTasks->count() > 0)
                                <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded-full">
                                    {{ $dayTasks->count() }}
                                </span>
                            @endif
                        </div>
                        
                        <div class="space-y-1">
                            @foreach($dayTasks->take(3) as $task)
                                @include('calendar.partials.task-card-compact', ['task' => $task])
                            @endforeach
                            
                            @if($dayTasks->count() > 3)
                                <div class="text-xs text-gray-500 text-center py-1">
                                    +{{ $dayTasks->count() - 3 }} еще
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    @php $currentDate->addDay(); @endphp
                @endfor
            </div>
        @endfor
    @endif
</div>

<!-- Task Details Modal -->
<div x-data="{ showModal: false, selectedTask: null }" 
     @task-clicked.window="showModal = true; selectedTask = $event.detail"
     x-cloak>
    <div x-show="showModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         @click.self="showModal = false">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            
            <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full p-6">
                <div class="flex items-start justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-900" x-text="selectedTask?.title"></h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-3">
                    <div>
                        <span class="text-sm font-medium text-gray-700">Проект:</span>
                        <span class="text-sm text-gray-900" x-text="selectedTask?.project"></span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-700">Статус:</span>
                        <span class="text-sm text-gray-900" x-text="selectedTask?.status"></span>
                    </div>
                    <div x-show="selectedTask?.assignee">
                        <span class="text-sm font-medium text-gray-700">Исполнитель:</span>
                        <span class="text-sm text-gray-900" x-text="selectedTask?.assignee"></span>
                    </div>
                    <div x-show="selectedTask?.due_date">
                        <span class="text-sm font-medium text-gray-700">Срок:</span>
                        <span class="text-sm text-gray-900" x-text="selectedTask?.due_date"></span>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <a :href="selectedTask?.url" 
                       class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg">
                        Открыть задачу
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function calendarFilters() {
    return {
        filters: {
            view: '{{ $viewMode }}',
            date: '{{ $date->format('Y-m-d') }}',
            project_id: '{{ request('project_id', '') }}',
            assignee_id: '{{ request('assignee_id', '') }}'
        },
        
        initFromUrl() {
            const urlParams = new URLSearchParams(window.location.search);
            this.filters.view = urlParams.get('view') || 'month';
            this.filters.date = urlParams.get('date') || '{{ $date->format('Y-m-d') }}';
            this.filters.project_id = urlParams.get('project_id') || '';
            this.filters.assignee_id = urlParams.get('assignee_id') || '';
        },
        
        setViewMode(mode) {
            this.filters.view = mode;
            this.applyFilters();
        },
        
        navigatePrevious() {
            const currentDate = new Date(this.filters.date);
            if (this.filters.view === 'week') {
                currentDate.setDate(currentDate.getDate() - 7);
            } else {
                currentDate.setMonth(currentDate.getMonth() - 1);
            }
            this.filters.date = currentDate.toISOString().split('T')[0];
            this.applyFilters();
        },
        
        navigateNext() {
            const currentDate = new Date(this.filters.date);
            if (this.filters.view === 'week') {
                currentDate.setDate(currentDate.getDate() + 7);
            } else {
                currentDate.setMonth(currentDate.getMonth() + 1);
            }
            this.filters.date = currentDate.toISOString().split('T')[0];
            this.applyFilters();
        },
        
        navigateToday() {
            this.filters.date = new Date().toISOString().split('T')[0];
            this.applyFilters();
        },
        
        applyFilters() {
            const params = new URLSearchParams();
            
            Object.keys(this.filters).forEach(key => {
                const value = this.filters[key];
                if (value !== '' && value !== null) {
                    params.set(key, value);
                }
            });
            
            const newUrl = `${window.location.pathname}?${params.toString()}`;
            window.location.href = newUrl;
        },
        
        resetFilters() {
            this.filters.project_id = '';
            this.filters.assignee_id = '';
            this.applyFilters();
        },
        
        hasActiveFilters() {
            return this.filters.project_id !== '' || this.filters.assignee_id !== '';
        }
    }
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
@endsection
