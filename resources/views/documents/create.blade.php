@extends('layouts.app')

@section('title', 'Создать документ')

@section('content')
<div class="mb-6">
    <div class="flex items-center gap-2 mb-2">
        <a href="{{ route('documents.index') }}" class="text-gray-500 hover:text-gray-700">
            ← База знаний
        </a>
    </div>
    <h1 class="text-3xl font-bold text-gray-900">Создать документ</h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Form -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <form method="POST" action="{{ route('documents.store') }}" x-data="documentForm()">
                @csrf

                @if(isset($task))
                    <input type="hidden" name="task_id" value="{{ $task->id }}">
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-sm text-blue-800">
                            <strong>Документ будет прикреплен к задаче:</strong> {{ $task->title }}
                        </p>
                    </div>
                @endif

                <div class="space-y-6">
                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                            Название <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="title" 
                               id="title" 
                               value="{{ old('title') }}"
                               required
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('title') border-red-500 @enderror">
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">
                            Категория
                        </label>
                        <select name="category" 
                                id="category" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('category') border-red-500 @enderror">
                            <option value="">Без категории</option>
                            @foreach(\App\Enums\DocumentCategory::cases() as $category)
                                <option value="{{ $category->value }}" {{ old('category') == $category->value ? 'selected' : '' }}>
                                    {{ match($category) {
                                        \App\Enums\DocumentCategory::API_DOCUMENTATION => 'API документация',
                                        \App\Enums\DocumentCategory::ARCHITECTURE => 'Архитектурные решения',
                                        \App\Enums\DocumentCategory::INTEGRATION_GUIDE => 'Инструкции по интеграции',
                                        \App\Enums\DocumentCategory::GENERAL_NOTES => 'Общие заметки',
                                    } }}
                                </option>
                            @endforeach
                        </select>
                        @error('category')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Project -->
                    <div>
                        <label for="project_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Проект
                        </label>
                        <select name="project_id" 
                                id="project_id" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('project_id') border-red-500 @enderror">
                            <option value="">Без проекта</option>
                            @foreach(\App\Models\Project::all() as $project)
                                <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('project_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Content with Markdown Editor -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label for="content" class="block text-sm font-medium text-gray-700">
                                Содержание <span class="text-red-500">*</span>
                            </label>
                            <button type="button" 
                                    @click="showPreview = !showPreview"
                                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                <span x-show="!showPreview">Показать предпросмотр</span>
                                <span x-show="showPreview">Показать редактор</span>
                            </button>
                        </div>
                        
                        <!-- Editor -->
                        <div x-show="!showPreview">
                            <textarea name="content" 
                                      id="content" 
                                      x-model="content"
                                      rows="20" 
                                      required
                                      placeholder="Используйте Markdown для форматирования текста..."
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm @error('content') border-red-500 @enderror">{{ old('content') }}</textarea>
                            @error('content')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-sm text-gray-500">
                                Поддерживается Markdown: **жирный**, *курсив*, `код`, [ссылка](url), # заголовки, и т.д.
                            </p>
                        </div>

                        <!-- Preview -->
                        <div x-show="showPreview" 
                             class="w-full min-h-[500px] rounded-md border border-gray-300 p-4 bg-gray-50">
                            <div class="prose max-w-none" x-html="renderMarkdown(content)"></div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-6 rounded-lg">
                            Создать документ
                        </button>
                        <a href="{{ route('documents.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-6 rounded-lg">
                            Отмена
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Sidebar with Markdown Help -->
    <div>
        <div class="bg-white rounded-lg shadow-sm p-6 sticky top-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Справка по Markdown</h2>
            <div class="space-y-3 text-sm">
                <div>
                    <p class="font-medium text-gray-700">Заголовки</p>
                    <code class="block mt-1 text-xs bg-gray-100 p-2 rounded"># H1<br>## H2<br>### H3</code>
                </div>
                <div>
                    <p class="font-medium text-gray-700">Форматирование</p>
                    <code class="block mt-1 text-xs bg-gray-100 p-2 rounded">**жирный**<br>*курсив*<br>~~зачеркнутый~~</code>
                </div>
                <div>
                    <p class="font-medium text-gray-700">Списки</p>
                    <code class="block mt-1 text-xs bg-gray-100 p-2 rounded">- Пункт 1<br>- Пункт 2<br><br>1. Первый<br>2. Второй</code>
                </div>
                <div>
                    <p class="font-medium text-gray-700">Ссылки</p>
                    <code class="block mt-1 text-xs bg-gray-100 p-2 rounded">[текст](url)</code>
                </div>
                <div>
                    <p class="font-medium text-gray-700">Код</p>
                    <code class="block mt-1 text-xs bg-gray-100 p-2 rounded">`inline code`<br><br>```<br>code block<br>```</code>
                </div>
                <div>
                    <p class="font-medium text-gray-700">Цитаты</p>
                    <code class="block mt-1 text-xs bg-gray-100 p-2 rounded">&gt; Цитата</code>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Markdown Rendering Script -->
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
function documentForm() {
    return {
        content: '{{ old('content') }}',
        showPreview: false
    }
}

function renderMarkdown(content) {
    return marked.parse(content || '');
}
</script>
@endsection
