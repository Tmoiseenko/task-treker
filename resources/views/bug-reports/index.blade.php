@extends('layouts.app')

@section('title', 'Баг-репорты')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-6 flex justify-between items-start">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Баг-репорты</h1>
            <p class="text-gray-600">Для задачи: <a href="{{ route('tasks.show', $task) }}" class="text-indigo-600 hover:text-indigo-800">{{ $task->title }}</a></p>
        </div>
        
        @can('view', $task)
            @if($task->status === \App\Enums\TaskStatus::IN_TESTING || $task->status === \App\Enums\TaskStatus::TEST_FAILED)
                <a 
                    href="{{ route('bug-reports.create', $task) }}"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg">
                    + Создать баг-репорт
                </a>
            @endif
        @endcan
    </div>

    {{-- Status Summary --}}
    @if($bugReports->count() > 0)
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-1">Статус баг-репортов</h2>
                    <p class="text-sm text-gray-600">
                        Всего: {{ $bugReports->count() }} | 
                        Исправлено: {{ $bugReports->where('status', \App\Enums\TaskStatus::DONE)->count() }} | 
                        В работе: {{ $bugReports->whereIn('status', [\App\Enums\TaskStatus::TODO, \App\Enums\TaskStatus::IN_PROGRESS])->count() }}
                    </p>
                </div>
                
                @if($allBugsFixed)
                    <div class="flex items-center gap-2 text-green-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-semibold">Все баги исправлены</span>
                    </div>
                @else
                    <div class="flex items-center gap-2 text-orange-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <span class="font-semibold">Есть открытые баги</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Bug Reports List --}}
    @if($bugReports->count() > 0)
        <div class="space-y-4">
            @foreach($bugReports as $bugReport)
                <div class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <a href="{{ route('tasks.show', $bugReport) }}" class="text-lg font-semibold text-gray-900 hover:text-indigo-600">
                                {{ $bugReport->title }}
                            </a>
                            <div class="flex items-center gap-3 mt-2">
                                {{-- Status Badge --}}
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ match($bugReport->status) {
                                    \App\Enums\TaskStatus::TODO => 'bg-gray-100 text-gray-800',
                                    \App\Enums\TaskStatus::IN_PROGRESS => 'bg-blue-100 text-blue-800',
                                    \App\Enums\TaskStatus::DONE => 'bg-green-100 text-green-800',
                                    default => 'bg-red-100 text-red-800',
                                } }}">
                                    {{ match($bugReport->status) {
                                        \App\Enums\TaskStatus::TODO => 'Не выполнено',
                                        \App\Enums\TaskStatus::IN_PROGRESS => 'В работе',
                                        \App\Enums\TaskStatus::DONE => 'Исправлено',
                                        default => $bugReport->status->value,
                                    } }}
                                </span>

                                {{-- Priority Badge --}}
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ match($bugReport->priority) {
                                    \App\Enums\TaskPriority::HIGH => 'bg-red-100 text-red-800',
                                    \App\Enums\TaskPriority::MEDIUM => 'bg-yellow-100 text-yellow-800',
                                    \App\Enums\TaskPriority::LOW => 'bg-green-100 text-green-800',
                                    \App\Enums\TaskPriority::FROZEN => 'bg-gray-100 text-gray-800',
                                } }}">
                                    {{ match($bugReport->priority) {
                                        \App\Enums\TaskPriority::HIGH => 'Высокий',
                                        \App\Enums\TaskPriority::MEDIUM => 'Средний',
                                        \App\Enums\TaskPriority::LOW => 'Низкий',
                                        \App\Enums\TaskPriority::FROZEN => 'Заморожено',
                                    } }}
                                </span>
                            </div>
                        </div>

                        @can('update', $bugReport)
                            <a 
                                href="{{ route('tasks.show', $bugReport) }}"
                                class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                Открыть →
                            </a>
                        @endcan
                    </div>

                    {{-- Description Preview --}}
                    @if($bugReport->description)
                        <div class="text-sm text-gray-600 mb-3 line-clamp-2">
                            {{ Str::limit(strip_tags($bugReport->description), 200) }}
                        </div>
                    @endif

                    {{-- Meta Information --}}
                    <div class="flex items-center gap-4 text-sm text-gray-500">
                        @if($bugReport->assignee)
                            <div class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span>{{ $bugReport->assignee->name }}</span>
                            </div>
                        @else
                            <div class="flex items-center gap-1 text-orange-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <span>Не назначен</span>
                            </div>
                        @endif

                        <div class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span>Создан {{ $bugReport->created_at->format('d.m.Y') }}</span>
                        </div>

                        @if($bugReport->due_date)
                            <div class="flex items-center gap-1 {{ $bugReport->due_date->isPast() && $bugReport->status !== \App\Enums\TaskStatus::DONE ? 'text-red-600 font-semibold' : '' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Срок: {{ $bugReport->due_date->format('d.m.Y') }}</span>
                                @if($bugReport->due_date->isPast() && $bugReport->status !== \App\Enums\TaskStatus::DONE)
                                    <span class="text-xs">(просрочено)</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Нет баг-репортов</h3>
            <p class="text-gray-600 mb-4">Для этой задачи еще не создано ни одного баг-репорта</p>
            @can('view', $task)
                @if($task->status === \App\Enums\TaskStatus::IN_TESTING || $task->status === \App\Enums\TaskStatus::TEST_FAILED)
                    <a 
                        href="{{ route('bug-reports.create', $task) }}"
                        class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg">
                        Создать первый баг-репорт
                    </a>
                @endif
            @endcan
        </div>
    @endif

    {{-- Back Button --}}
    <div class="mt-6">
        <a 
            href="{{ route('tasks.show', $task) }}"
            class="text-indigo-600 hover:text-indigo-800 font-medium">
            ← Вернуться к задаче
        </a>
    </div>
</div>
@endsection
