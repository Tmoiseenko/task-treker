@extends('layouts.app')

@section('title', $document->title)

@section('content')
<div class="mb-6 flex justify-between items-start">
    <div>
        <div class="flex items-center gap-2 mb-2">
            <a href="{{ route('documents.index') }}" class="text-gray-500 hover:text-gray-700">
                ← База знаний
            </a>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $document->title }}</h1>
        <div class="flex items-center gap-3">
            @if($document->category)
                <span class="px-3 py-1 text-sm font-semibold rounded-full {{ match($document->category) {
                    \App\Enums\DocumentCategory::API_DOCUMENTATION => 'bg-blue-100 text-blue-800',
                    \App\Enums\DocumentCategory::ARCHITECTURE => 'bg-purple-100 text-purple-800',
                    \App\Enums\DocumentCategory::INTEGRATION_GUIDE => 'bg-green-100 text-green-800',
                    \App\Enums\DocumentCategory::GENERAL_NOTES => 'bg-gray-100 text-gray-800',
                } }}">
                    {{ match($document->category) {
                        \App\Enums\DocumentCategory::API_DOCUMENTATION => 'API документация',
                        \App\Enums\DocumentCategory::ARCHITECTURE => 'Архитектурные решения',
                        \App\Enums\DocumentCategory::INTEGRATION_GUIDE => 'Инструкции по интеграции',
                        \App\Enums\DocumentCategory::GENERAL_NOTES => 'Общие заметки',
                    } }}
                </span>
            @endif
            <span class="text-sm text-gray-500">Версия {{ $document->version }}</span>
        </div>
    </div>

    <div class="flex gap-2">
        <a href="{{ route('documents.edit', $document) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg">
            Редактировать
        </a>
        
        <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Вы уверены, что хотите удалить этот документ?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg">
                Удалить
            </button>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Document Content with Markdown Rendering -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="prose max-w-none" x-data="{ content: @js($document->content) }" x-html="renderMarkdown(content)">
            </div>
        </div>

        <!-- Version History -->
        @if($document->versions->count() > 0)
            <div class="bg-white rounded-lg shadow-sm p-6" x-data="{ showHistory: false }">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">История версий</h2>
                    <button 
                        @click="showHistory = !showHistory"
                        class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                        <span x-show="!showHistory">Показать историю ({{ $document->versions->count() }})</span>
                        <span x-show="showHistory">Скрыть историю</span>
                    </button>
                </div>
                
                <div x-show="showHistory" x-collapse class="space-y-3">
                    @foreach($document->versions->sortByDesc('version') as $version)
                        <div class="border border-gray-200 rounded-lg p-4 {{ $version->version === $document->version ? 'bg-indigo-50 border-indigo-300' : '' }}">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="font-semibold text-gray-900">Версия {{ $version->version }}</span>
                                    @if($version->version === $document->version)
                                        <span class="ml-2 px-2 py-0.5 text-xs font-semibold rounded-full bg-indigo-600 text-white">
                                            Текущая
                                        </span>
                                    @endif
                                </div>
                                <span class="text-sm text-gray-500">{{ $version->created_at->format('d.m.Y H:i') }}</span>
                            </div>
                            <p class="text-sm text-gray-600">
                                <strong>Автор изменений:</strong> {{ $version->user->name }}
                            </p>
                            <div class="mt-3 text-sm text-gray-700 bg-gray-50 p-3 rounded max-h-40 overflow-y-auto">
                                {{ Str::limit($version->content, 300) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Linked Tasks -->
        @if($document->tasks->count() > 0)
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Связанные задачи</h2>
                <div class="space-y-2">
                    @foreach($document->tasks as $task)
                        <a href="{{ route('tasks.show', $task) }}" class="block p-3 hover:bg-gray-50 rounded border border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $task->title }}</p>
                                    <p class="text-sm text-gray-500">{{ $task->project->name }}</p>
                                </div>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ match($task->status) {
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
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Document Info -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Информация</h2>
            <dl class="space-y-3">
                @if($document->project)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Проект</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $document->project->name }}</dd>
                    </div>
                @endif

                <div>
                    <dt class="text-sm font-medium text-gray-500">Автор</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $document->author->name }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500">Версия</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $document->version }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500">Создано</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $document->created_at->format('d.m.Y H:i') }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500">Обновлено</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $document->updated_at->format('d.m.Y H:i') }}</dd>
                </div>
            </dl>
        </div>

        <!-- Attach to Task -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Прикрепить к задаче</h2>
            <form method="POST" action="{{ route('documents.attach-task', $document) }}">
                @csrf
                <div class="space-y-3">
                    <select name="task_id" 
                            id="task_id" 
                            required
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Выберите задачу</option>
                        @foreach(\App\Models\Task::with('project')->get() as $task)
                            <option value="{{ $task->id }}">
                                {{ $task->title }} ({{ $task->project->name }})
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg">
                        Прикрепить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Markdown Rendering Script -->
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
function renderMarkdown(content) {
    return marked.parse(content);
}
</script>
@endsection
