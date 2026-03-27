@extends('layouts.app')

@section('title', $task->title)

@section('content')
<div class="mb-6 flex justify-between items-start">
    <div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $task->title }}</h1>
        <div class="flex items-center gap-3">
            <!-- Priority Badge -->
            <span class="px-3 py-1 text-sm font-semibold rounded-full {{ match($task->priority) {
                \App\Enums\TaskPriority::HIGH => 'bg-red-100 text-red-800',
                \App\Enums\TaskPriority::MEDIUM => 'bg-yellow-100 text-yellow-800',
                \App\Enums\TaskPriority::LOW => 'bg-green-100 text-green-800',
                \App\Enums\TaskPriority::FROZEN => 'bg-gray-100 text-gray-800',
            } }}">
                {{ match($task->priority) {
                    \App\Enums\TaskPriority::HIGH => 'Высокий приоритет',
                    \App\Enums\TaskPriority::MEDIUM => 'Средний приоритет',
                    \App\Enums\TaskPriority::LOW => 'Низкий приоритет',
                    \App\Enums\TaskPriority::FROZEN => 'Заморожено',
                } }}
            </span>

            <!-- Status Badge -->
            <span class="px-3 py-1 text-sm font-semibold rounded-full {{ match($task->status) {
                \App\Enums\TaskStatus::TODO => 'bg-gray-100 text-gray-800',
                \App\Enums\TaskStatus::IN_PROGRESS => 'bg-blue-100 text-blue-800',
                \App\Enums\TaskStatus::IN_TESTING => 'bg-purple-100 text-purple-800',
                \App\Enums\TaskStatus::TEST_FAILED => 'bg-red-100 text-red-800',
                \App\Enums\TaskStatus::DONE => 'bg-green-100 text-green-800',
            } }}">
                {{ match($task->status) {
                    \App\Enums\TaskStatus::TODO => 'Не выполнено',
                    \App\Enums\TaskStatus::IN_PROGRESS => 'В работе',
                    \App\Enums\TaskStatus::IN_TESTING => 'На тестировании',
                    \App\Enums\TaskStatus::TEST_FAILED => 'Тест провален',
                    \App\Enums\TaskStatus::DONE => 'Выполнено',
                } }}
            </span>
        </div>
    </div>

    <div class="flex gap-2">
        @can('update', $task)
            <a href="{{ route('tasks.edit', $task) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg">
                Редактировать
            </a>
        @endcan
        
        @can('delete', $task)
            <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Вы уверены, что хотите удалить эту задачу?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg">
                    Удалить
                </button>
            </form>
        @endcan
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Description -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Описание</h2>
            <div class="text-gray-700 whitespace-pre-wrap">{{ $task->description ?: 'Описание отсутствует' }}</div>
        </div>

        <!-- Task Stages with Time Tracking -->
        @if($task->taskStages->count() > 0)
            <div class="bg-white rounded-lg shadow-sm p-6" x-data="{ showTimeTracking: false }">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Этапы задачи</h2>
                    <button 
                        @click="showTimeTracking = !showTimeTracking"
                        class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                        <span x-show="!showTimeTracking">Показать учет времени</span>
                        <span x-show="showTimeTracking">Скрыть учет времени</span>
                    </button>
                </div>
                
                <div class="space-y-4">
                    @foreach($task->taskStages as $taskStage)
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            {{-- Stage Header --}}
                            <div class="p-4 bg-gray-50">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-semibold text-gray-900">{{ $taskStage->stage->name }}</h3>
                                    <span class="text-sm text-gray-500">{{ $taskStage->status->value }}</span>
                                </div>
                                @if($taskStage->stage->description)
                                    <p class="text-sm text-gray-600 mt-2">{{ $taskStage->stage->description }}</p>
                                @endif
                            </div>
                            
                            {{-- Time Tracking Section (collapsible) --}}
                            <div x-show="showTimeTracking" x-collapse class="p-4 border-t border-gray-200">
                                @include('tasks.partials.time-tracking', ['taskStage' => $taskStage])
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Comments -->
        @include('tasks.partials.comments', ['task' => $task])

        <!-- Attachments -->
        @include('tasks.partials.attachments', ['task' => $task])

        <!-- Bug Reports -->
        @include('tasks.partials.bug-reports', ['task' => $task])

        <!-- Audit Log -->
        @include('tasks.partials.audit-history', ['task' => $task])
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Time Summary -->
        @php
            $totalHours = $task->taskStages->sum(function($stage) {
                return $stage->timeEntries->sum('hours');
            });
            $totalCost = $task->taskStages->sum(function($stage) {
                return $stage->timeEntries->sum('cost');
            });
        @endphp
        @include('tasks.partials.time-summary', [
            'task' => $task,
            'totalHours' => $totalHours,
            'totalCost' => $totalCost
        ])
        
        <!-- Actions -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Действия</h2>
            <div class="space-y-2">
                @can('take', $task)
                    @if($task->status === \App\Enums\TaskStatus::TODO && !$task->assignee_id)
                        <form method="POST" action="{{ route('tasks.take', $task) }}">
                            @csrf
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg">
                                Взять в работу
                            </button>
                        </form>
                    @endif
                @endcan

                @can('changeStatus', [$task, \App\Enums\TaskStatus::IN_TESTING])
                    @if($task->status === \App\Enums\TaskStatus::IN_PROGRESS)
                        <form method="POST" action="{{ route('tasks.change-status', $task) }}">
                            @csrf
                            <input type="hidden" name="status" value="{{ \App\Enums\TaskStatus::IN_TESTING->value }}">
                            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded-lg">
                                Отправить на тестирование
                            </button>
                        </form>
                    @endif
                @endcan

                @can('changeStatus', [$task, \App\Enums\TaskStatus::DONE])
                    @if($task->status === \App\Enums\TaskStatus::IN_TESTING)
                        <form method="POST" action="{{ route('tasks.change-status', $task) }}">
                            @csrf
                            <input type="hidden" name="status" value="{{ \App\Enums\TaskStatus::DONE->value }}">
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg">
                                Завершить задачу
                            </button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>

        <!-- Task Info -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Информация</h2>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Проект</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $task->project->name }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500">Автор</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $task->author->name }}</dd>
                </div>

                @if($task->assignee)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Исполнитель</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $task->assignee->name }}</dd>
                    </div>
                @else
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Исполнитель</dt>
                        <dd class="mt-1 text-sm text-gray-500">Не назначен</dd>
                    </div>
                @endif

                @if($task->due_date)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Срок выполнения</dt>
                        <dd class="mt-1 text-sm {{ $task->due_date->isPast() && $task->status !== \App\Enums\TaskStatus::DONE ? 'text-red-600 font-semibold' : 'text-gray-900' }}">
                            {{ $task->due_date->format('d.m.Y') }}
                            @if($task->due_date->isPast() && $task->status !== \App\Enums\TaskStatus::DONE)
                                <span class="block text-xs">Просрочено</span>
                            @endif
                        </dd>
                    </div>
                @endif

                <div>
                    <dt class="text-sm font-medium text-gray-500">Создано</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $task->created_at->format('d.m.Y H:i') }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500">Обновлено</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $task->updated_at->format('d.m.Y H:i') }}</dd>
                </div>
            </dl>
        </div>

        <!-- Tags -->
        @if($task->tags->count() > 0)
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Теги</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach($task->tags as $tag)
                        <span class="px-3 py-1 text-sm rounded-full" style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
                            {{ $tag->name }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Documents -->
        @if($task->documents->count() > 0)
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Документы</h2>
                <div class="space-y-2">
                    @foreach($task->documents as $document)
                        <a href="{{ route('documents.show', $document) }}" class="block p-2 hover:bg-gray-50 rounded">
                            <p class="text-sm font-medium text-indigo-600">{{ $document->title }}</p>
                            @if($document->category)
                                <p class="text-xs text-gray-500">
                                    {{ match($document->category) {
                                        \App\Enums\DocumentCategory::API_DOCUMENTATION => 'API документация',
                                        \App\Enums\DocumentCategory::ARCHITECTURE => 'Архитектурные решения',
                                        \App\Enums\DocumentCategory::INTEGRATION_GUIDE => 'Инструкции по интеграции',
                                        \App\Enums\DocumentCategory::GENERAL_NOTES => 'Общие заметки',
                                    } }}
                                </p>
                            @endif
                        </a>
                    @endforeach
                </div>
                <a href="{{ route('documents.create', ['task_id' => $task->id]) }}" class="mt-3 block text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                    + Создать новый документ
                </a>
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Документы</h2>
                <p class="text-sm text-gray-500 mb-3">Нет прикрепленных документов</p>
                <a href="{{ route('documents.create', ['task_id' => $task->id]) }}" class="block text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                    + Создать документ для этой задачи
                </a>
            </div>
        @endif

        <!-- Checklist -->
        @if($task->status === \App\Enums\TaskStatus::IN_TESTING || $task->checklistItems->count() > 0)
            @include('tasks.partials.checklist', ['task' => $task])
        @endif
    </div>
</div>
@endsection
