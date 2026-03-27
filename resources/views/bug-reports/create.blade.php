@extends('layouts.app')

@section('title', 'Создать баг-репорт')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Создать баг-репорт</h1>
        <p class="text-gray-600">Для задачи: <a href="{{ route('tasks.show', $task) }}" class="text-indigo-600 hover:text-indigo-800">{{ $task->title }}</a></p>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ route('bug-reports.store', $task) }}">
            @csrf

            {{-- Title --}}
            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                    Название баг-репорта <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    name="title" 
                    id="title" 
                    value="{{ old('title', 'Bug: ' . $task->title) }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('title') border-red-500 @enderror"
                    required>
                @error('title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Описание ошибки <span class="text-red-500">*</span>
                </label>
                <textarea 
                    name="description" 
                    id="description" 
                    rows="4"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('description') border-red-500 @enderror"
                    required>{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Steps to Reproduce --}}
            <div class="mb-4">
                <label for="steps_to_reproduce" class="block text-sm font-medium text-gray-700 mb-2">
                    Шаги воспроизведения
                </label>
                <textarea 
                    name="steps_to_reproduce" 
                    id="steps_to_reproduce" 
                    rows="4"
                    placeholder="1. Открыть страницу...&#10;2. Нажать на кнопку...&#10;3. Наблюдать ошибку..."
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('steps_to_reproduce') border-red-500 @enderror">{{ old('steps_to_reproduce') }}</textarea>
                @error('steps_to_reproduce')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Expected Result --}}
            <div class="mb-4">
                <label for="expected_result" class="block text-sm font-medium text-gray-700 mb-2">
                    Ожидаемый результат
                </label>
                <textarea 
                    name="expected_result" 
                    id="expected_result" 
                    rows="3"
                    placeholder="Что должно было произойти..."
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('expected_result') border-red-500 @enderror">{{ old('expected_result') }}</textarea>
                @error('expected_result')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Actual Result --}}
            <div class="mb-4">
                <label for="actual_result" class="block text-sm font-medium text-gray-700 mb-2">
                    Фактический результат
                </label>
                <textarea 
                    name="actual_result" 
                    id="actual_result" 
                    rows="3"
                    placeholder="Что произошло на самом деле..."
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('actual_result') border-red-500 @enderror">{{ old('actual_result') }}</textarea>
                @error('actual_result')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                {{-- Priority --}}
                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">
                        Приоритет <span class="text-red-500">*</span>
                    </label>
                    <select 
                        name="priority" 
                        id="priority"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('priority') border-red-500 @enderror"
                        required>
                        @foreach($priorities as $priority)
                            <option value="{{ $priority->value }}" {{ old('priority', $task->priority->value) === $priority->value ? 'selected' : '' }}>
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

                {{-- Due Date --}}
                <div>
                    <label for="due_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Срок исправления
                    </label>
                    <input 
                        type="date" 
                        name="due_date" 
                        id="due_date" 
                        value="{{ old('due_date') }}"
                        min="{{ now()->addDay()->format('Y-m-d') }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('due_date') border-red-500 @enderror">
                    @error('due_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Assignee --}}
            @if($task->project->members->count() > 0)
                <div class="mb-6">
                    <label for="assignee_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Назначить на разработчика
                    </label>
                    <select 
                        name="assignee_id" 
                        id="assignee_id"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('assignee_id') border-red-500 @enderror">
                        <option value="">Не назначен</option>
                        @foreach($task->project->members as $member)
                            <option value="{{ $member->id }}" {{ old('assignee_id', $task->assignee_id) == $member->id ? 'selected' : '' }}>
                                {{ $member->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('assignee_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            {{-- Actions --}}
            <div class="flex gap-3">
                <button 
                    type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-6 rounded-lg">
                    Создать баг-репорт
                </button>
                <a 
                    href="{{ route('tasks.show', $task) }}"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-6 rounded-lg">
                    Отмена
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
