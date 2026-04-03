@extends('layouts.app')

@section('title', 'Kanban доска')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">Kanban доска</h1>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6"
     x-data="kanbanFilters()"
     x-init="initFromUrl()">
    <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                    @foreach(\App\Models\MoonshineUser::all() as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Priority Filter -->
            <div>
                <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Приоритет</label>
                <select x-model="filters.priority"
                        @change="applyFilters()"
                        id="priority"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Все приоритеты</option>
                    @foreach(\App\Enums\TaskPriority::cases() as $priority)
                        <option value="{{ $priority->value }}">
                            {{ match($priority) {
                                \App\Enums\TaskPriority::HIGH => 'Высокий',
                                \App\Enums\TaskPriority::MEDIUM => 'Средний',
                                \App\Enums\TaskPriority::LOW => 'Низкий',
                                \App\Enums\TaskPriority::FROZEN => 'Заморожено',
                            } }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Tags Filter -->
            <div>
                <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">Теги</label>
                <select x-model="filters.tags"
                        @change="applyFilters()"
                        id="tags"
                        multiple
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach(\App\Models\Tag::all() as $tag)
                        <option value="{{ $tag->id }}">{{ $tag->name }}</option>
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

<!-- Kanban Board -->
<div class="overflow-x-auto pb-4">
    <div class="inline-flex gap-4 min-w-full"
         x-data="kanbanBoard()"
         x-init="initSortable()">

        @foreach([
            ['status' => \App\Enums\TaskStatus::TODO, 'label' => 'Не выполнено', 'color' => 'gray'],
            ['status' => \App\Enums\TaskStatus::IN_PROGRESS, 'label' => 'В работе', 'color' => 'blue'],
            ['status' => \App\Enums\TaskStatus::IN_TESTING, 'label' => 'На тестировании', 'color' => 'purple'],
            ['status' => \App\Enums\TaskStatus::TEST_FAILED, 'label' => 'Тест провален', 'color' => 'red'],
            ['status' => \App\Enums\TaskStatus::DONE, 'label' => 'Выполнено', 'color' => 'green'],
        ] as $column)
            <div class="flex-shrink-0 w-80">
                <!-- Column Header -->
                <div class="bg-{{ $column['color'] }}-100 rounded-t-lg px-4 py-3 border-b-2 border-{{ $column['color'] }}-300">
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold text-{{ $column['color'] }}-900">{{ $column['label'] }}</h2>
                        <span class="bg-{{ $column['color'] }}-200 text-{{ $column['color'] }}-800 text-xs font-semibold px-2 py-1 rounded-full">
                            {{ $tasksByStatus[$column['status']->value]->count() }}
                        </span>
                    </div>
                </div>

                <!-- Column Content -->
                <div class="bg-{{ $column['color'] }}-50 rounded-b-lg p-4 min-h-[600px]"
                     data-status="{{ $column['status']->value }}"
                     data-sortable-column>
                    <div class="space-y-3">
                        @foreach($tasksByStatus[$column['status']->value] as $task)
                            @php
                                $isOverdue = $task->due_date &&
                                            $task->due_date->isPast() &&
                                            $task->status !== \App\Enums\TaskStatus::DONE;
                            @endphp

                            <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow cursor-move border-l-4 {{ $isOverdue ? 'border-red-500 bg-red-50' : 'border-gray-200' }}"
                                 data-task-id="{{ $task->id }}"
                                 data-sortable-item>
                                <div class="p-4">
                                    <!-- Task Title -->
                                    <a href="{{ route('tasks.show', $task) }}"
                                       class="block font-semibold text-gray-900 hover:text-indigo-600 mb-2"
                                       onclick="event.stopPropagation()">
                                        {{ $task->title }}
                                    </a>

                                    <!-- Project -->
                                    <div class="text-xs text-gray-600 mb-2">
                                        <span class="font-medium">{{ $task->project->name }}</span>
                                    </div>

                                    <!-- Priority Badge -->
                                    <div class="mb-3">
                                        <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full {{ match($task->priority) {
                                            \App\Enums\TaskPriority::HIGH => 'bg-red-100 text-red-800',
                                            \App\Enums\TaskPriority::MEDIUM => 'bg-yellow-100 text-yellow-800',
                                            \App\Enums\TaskPriority::LOW => 'bg-green-100 text-green-800',
                                            \App\Enums\TaskPriority::FROZEN => 'bg-gray-100 text-gray-800',
                                        } }}">
                                            {{ match($task->priority) {
                                                \App\Enums\TaskPriority::HIGH => 'Высокий',
                                                \App\Enums\TaskPriority::MEDIUM => 'Средний',
                                                \App\Enums\TaskPriority::LOW => 'Низкий',
                                                \App\Enums\TaskPriority::FROZEN => 'Заморожено',
                                            } }}
                                        </span>
                                    </div>

                                    <!-- Assignee -->
                                    @if($task->assignee)
                                        <div class="flex items-center gap-2 text-sm text-gray-600 mb-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            <span>{{ $task->assignee->name }}</span>
                                        </div>
                                    @endif

                                    <!-- Due Date -->
                                    @if($task->due_date)
                                        <div class="flex items-center gap-2 text-sm {{ $isOverdue ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <span>{{ $task->due_date->format('d.m.Y') }}</span>
                                            @if($isOverdue)
                                                <span class="text-xs">(просрочено)</span>
                                            @endif
                                        </div>
                                    @endif

                                    <!-- Tags -->
                                    @if($task->tags->count() > 0)
                                        <div class="flex flex-wrap gap-1 mt-3">
                                            @foreach($task->tags as $tag)
                                                <span class="px-2 py-1 text-xs rounded-full"
                                                      style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
                                                    {{ $tag->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Sortable.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
// Filter management
function kanbanFilters() {
    return {
        filters: {
            project_id: '',
            assignee_id: '',
            priority: '',
            tags: []
        },

        initFromUrl() {
            const urlParams = new URLSearchParams(window.location.search);
            this.filters.project_id = urlParams.get('project_id') || '';
            this.filters.assignee_id = urlParams.get('assignee_id') || '';
            this.filters.priority = urlParams.get('priority') || '';

            const tagsParam = urlParams.get('tags');
            if (tagsParam) {
                this.filters.tags = tagsParam.split(',').filter(t => t);
            }
        },

        applyFilters() {
            const params = new URLSearchParams();

            Object.keys(this.filters).forEach(key => {
                const value = this.filters[key];
                if (Array.isArray(value)) {
                    if (value.length > 0) {
                        params.set(key, value.join(','));
                    }
                } else if (value !== '' && value !== null) {
                    params.set(key, value);
                }
            });

            const newUrl = params.toString() ?
                `${window.location.pathname}?${params.toString()}` :
                window.location.pathname;

            window.location.href = newUrl;
        },

        resetFilters() {
            this.filters = {
                project_id: '',
                assignee_id: '',
                priority: '',
                tags: []
            };
            window.location.href = window.location.pathname;
        },

        hasActiveFilters() {
            return Object.values(this.filters).some(value => {
                if (Array.isArray(value)) {
                    return value.length > 0;
                }
                return value !== '' && value !== null;
            });
        }
    }
}

// Kanban board with drag-and-drop
function kanbanBoard() {
    return {
        sortableInstances: [],

        initSortable() {
            const columns = document.querySelectorAll('[data-sortable-column]');

            columns.forEach(column => {
                const sortable = Sortable.create(column, {
                    group: 'kanban',
                    animation: 150,
                    ghostClass: 'opacity-50',
                    dragClass: 'shadow-2xl',
                    handle: '[data-sortable-item]',
                    onEnd: (evt) => {
                        this.handleDrop(evt);
                    }
                });

                this.sortableInstances.push(sortable);
            });
        },

        async handleDrop(evt) {
            const taskId = evt.item.dataset.taskId;
            const newStatus = evt.to.dataset.status;

            try {
                const response = await fetch(`/kanban/tasks/${taskId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: newStatus })
                });

                const data = await response.json();

                if (!data.success) {
                    // Revert the move
                    evt.item.remove();
                    evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex]);

                    alert(data.message || 'Не удалось изменить статус задачи');
                }
            } catch (error) {
                console.error('Error updating task status:', error);

                // Revert the move
                evt.item.remove();
                evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex]);

                alert('Произошла ошибка при изменении статуса задачи');
            }
        }
    }
}
</script>

<style>
[x-cloak] { display: none !important; }

/* Sortable ghost styles */
.sortable-ghost {
    opacity: 0.5;
}

/* Ensure proper color classes are loaded */
.bg-gray-100, .bg-gray-50, .border-gray-300, .text-gray-900, .text-gray-800 {}
.bg-blue-100, .bg-blue-50, .border-blue-300, .text-blue-900, .text-blue-800 {}
.bg-purple-100, .bg-purple-50, .border-purple-300, .text-purple-900, .text-purple-800 {}
.bg-red-100, .bg-red-50, .border-red-300, .text-red-900, .text-red-800 {}
.bg-green-100, .bg-green-50, .border-green-300, .text-green-900, .text-green-800 {}
</style>
@endsection
