@extends('layouts.app')

@section('title', 'Список задач')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold text-gray-900">Задачи</h1>
    @can('create', App\Models\Task::class)
        <a href="{{ route('tasks.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg">
            Создать задачу
        </a>
    @endcan
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6"
     x-data="taskFilters()"
     x-init="initFromUrl()">
    <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search -->
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Поиск</label>
                <input type="text"
                       x-model.debounce.500ms="filters.search"
                       @input="applyFilters()"
                       id="search"
                       placeholder="Название или описание..."
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <!-- Project Filter -->
            <div>
                <label for="project_id" class="block text-sm font-medium text-gray-700 mb-1">Проект</label>
                <select x-model="filters.project_id"
                        @change="applyFilters()"
                        id="project_id"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Все проекты</option>
                    @foreach(\App\Models\Project::all() as $project)
                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
                <select x-model="filters.status"
                        @change="applyFilters()"
                        id="status"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Все статусы</option>
                    @foreach(\App\Enums\TaskStatus::cases() as $status)
                        <option value="{{ $status->value }}">
                            {{ match($status) {
                                \App\Enums\TaskStatus::TODO => 'Не выполнено',
                                \App\Enums\TaskStatus::IN_PROGRESS => 'В работе',
                                \App\Enums\TaskStatus::IN_TESTING => 'На тестировании',
                                \App\Enums\TaskStatus::TEST_FAILED => 'Тест провален',
                                \App\Enums\TaskStatus::FOR_UNLOADING => 'Готово к выгрузке',
                                \App\Enums\TaskStatus::DONE => 'Выполнено',
                            } }}
                        </option>
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

            <!-- Author Filter -->
            <div>
                <label for="author_id" class="block text-sm font-medium text-gray-700 mb-1">Автор</label>
                <select x-model="filters.author_id"
                        @change="applyFilters()"
                        id="author_id"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Все авторы</option>
                    @foreach(\App\Models\MoonshineUser::all() as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
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
                <p class="text-xs text-gray-500 mt-1">Удерживайте Ctrl/Cmd для выбора нескольких</p>
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

<script>
function taskFilters() {
    return {
        filters: {
            search: '',
            project_id: '',
            status: '',
            priority: '',
            assignee_id: '',
            author_id: '',
            tags: []
        },

        initFromUrl() {
            const urlParams = new URLSearchParams(window.location.search);
            this.filters.search = urlParams.get('search') || '';
            this.filters.project_id = urlParams.get('project_id') || '';
            this.filters.status = urlParams.get('status') || '';
            this.filters.priority = urlParams.get('priority') || '';
            this.filters.assignee_id = urlParams.get('assignee_id') || '';
            this.filters.author_id = urlParams.get('author_id') || '';

            // Handle tags array
            const tagsParam = urlParams.get('tags');
            if (tagsParam) {
                this.filters.tags = tagsParam.split(',').filter(t => t);
            }
        },

        applyFilters() {
            const params = new URLSearchParams();

            // Add non-empty filters to URL
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

            // Update URL and reload page
            const newUrl = params.toString() ?
                `${window.location.pathname}?${params.toString()}` :
                window.location.pathname;

            window.location.href = newUrl;
        },

        resetFilters() {
            this.filters = {
                search: '',
                project_id: '',
                status: '',
                priority: '',
                assignee_id: '',
                author_id: '',
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
</script>

<!-- Tasks List -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    @if($tasks->count() > 0)
        <div class="divide-y divide-gray-200">
            @foreach($tasks as $task)
                <div class="p-6 hover:bg-gray-50 transition {{ $task->due_date && $task->due_date->isPast() && $task->status !== \App\Enums\TaskStatus::DONE ? 'bg-red-50' : '' }}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <a href="{{ route('tasks.show', $task) }}" class="text-lg font-semibold text-gray-900 hover:text-indigo-600">
                                    {{ $task->title }}
                                </a>

                                <!-- Priority Badge -->
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ match($task->priority) {
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

                                <!-- Status Badge -->
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ match($task->status) {
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

                            <p class="text-gray-600 text-sm mb-3">{{ Str::limit($task->description, 150) }}</p>

                            <div class="flex items-center gap-4 text-sm text-gray-500">
                                <span>
                                    <strong>Проект:</strong> {{ $task->project->name }}
                                </span>
                                @if($task->assignee)
                                    <span>
                                        <strong>Исполнитель:</strong> {{ $task->assignee->name }}
                                    </span>
                                @endif
                                @if($task->due_date)
                                    <span class="{{ $task->due_date->isPast() && $task->status !== \App\Enums\TaskStatus::DONE ? 'text-red-600 font-semibold' : '' }}">
                                        <strong>Срок:</strong> {{ $task->due_date->format('d.m.Y') }}
                                    </span>
                                @endif
                            </div>

                            @if($task->tags->count() > 0)
                                <div class="flex gap-2 mt-3">
                                    @foreach($task->tags as $tag)
                                        <span class="px-2 py-1 text-xs rounded-full" style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
                                            {{ $tag->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="ml-4">
                            <a href="{{ route('tasks.show', $task) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                Подробнее →
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 bg-gray-50">
            {{ $tasks->links() }}
        </div>
    @else
        <div class="p-12 text-center text-gray-500">
            <p class="text-lg">Задачи не найдены</p>
            <p class="text-sm mt-2">Попробуйте изменить фильтры или создайте новую задачу</p>
        </div>
    @endif
</div>
@endsection
