@extends('layouts.app')

@section('title', 'Редактировать задачу')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">Редактировать задачу</h1>
</div>

<div class="bg-white rounded-lg shadow-sm p-6">
    <form method="POST" action="{{ route('tasks.update', $task) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Title -->
        <div>
            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                Название <span class="text-red-500">*</span>
            </label>
            <input type="text" name="title" id="title" value="{{ old('title', $task->title) }}" required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('title') border-red-500 @enderror">
            @error('title')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                Описание
            </label>
            <textarea name="description" id="description" rows="5"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('description') border-red-500 @enderror">{{ old('description', $task->description) }}</textarea>
            @error('description')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Project -->
        <div>
            <label for="project_id" class="block text-sm font-medium text-gray-700 mb-1">
                Проект <span class="text-red-500">*</span>
            </label>
            <select name="project_id" id="project_id" required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('project_id') border-red-500 @enderror">
                <option value="">Выберите проект</option>
                @foreach(\App\Models\Project::all() as $project)
                    <option value="{{ $project->id }}" {{ old('project_id', $task->project_id) == $project->id ? 'selected' : '' }}>
                        {{ $project->name }}
                    </option>
                @endforeach
            </select>
            @error('project_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Priority -->
            <div>
                <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">
                    Приоритет <span class="text-red-500">*</span>
                </label>
                <select name="priority" id="priority" required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('priority') border-red-500 @enderror">
                    @foreach(\App\Enums\TaskPriority::cases() as $priority)
                        <option value="{{ $priority->value }}" {{ old('priority', $task->priority->value) == $priority->value ? 'selected' : '' }}>
                            {{ match($priority) {
                                \App\Enums\TaskPriority::HIGH => 'Высокий',
                                \App\Enums\TaskPriority::MEDIUM => 'Средний',
                                \App\Enums\TaskPriority::LOW => 'Низкий',
                                \App\Enums\TaskPriority::FROZEN => 'Заморожено',
                            } }}
                        </option>
                    @endforeach
                </select>
                @error('priority')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Due Date -->
            <div>
                <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">
                    Срок выполнения
                </label>
                <input type="date" name="due_date" id="due_date" value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('due_date') border-red-500 @enderror">
                @error('due_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Assignee -->
        <div>
            <label for="assignee_id" class="block text-sm font-medium text-gray-700 mb-1">
                Исполнитель
            </label>
            <select name="assignee_id" id="assignee_id"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('assignee_id') border-red-500 @enderror">
                <option value="">Не назначен</option>
                @foreach(\App\Models\MoonshineUser::all() as $user)
                    <option value="{{ $user->id }}" {{ old('assignee_id', $task->assignee_id) == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
            @error('assignee_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Tags -->
        <div>
            <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">
                Теги
            </label>
            <select name="tags[]" id="tags" multiple
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('tags') border-red-500 @enderror">
                @foreach(\App\Models\Tag::all() as $tag)
                    <option value="{{ $tag->id }}" {{ in_array($tag->id, old('tags', $task->tags->pluck('id')->toArray())) ? 'selected' : '' }}>
                        {{ $tag->name }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-sm text-gray-500">Удерживайте Ctrl (Cmd на Mac) для выбора нескольких тегов</p>
            @error('tags')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Actions -->
        <div class="flex gap-3 pt-4">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-6 rounded-lg">
                Сохранить изменения
            </button>
            <a href="{{ route('tasks.show', $task) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-6 rounded-lg">
                Отмена
            </a>
        </div>
    </form>
</div>
@endsection
