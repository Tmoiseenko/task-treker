@extends('layouts.app')

@section('title', 'База знаний')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h1 class="text-3xl font-bold text-gray-900">База знаний</h1>
    <a href="{{ route('documents.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg">
        Создать документ
    </a>
</div>

<!-- Search and Filters -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <form method="GET" action="{{ route('documents.index') }}" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search -->
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Поиск</label>
                <input type="text" 
                       name="search" 
                       id="search" 
                       value="{{ request('search') }}"
                       placeholder="Название или содержание..." 
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <!-- Project Filter -->
            <div>
                <label for="project_id" class="block text-sm font-medium text-gray-700 mb-1">Проект</label>
                <select name="project_id" 
                        id="project_id" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Все проекты</option>
                    @foreach(\App\Models\Project::all() as $project)
                        <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Category Filter -->
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Категория</label>
                <select name="category" 
                        id="category" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Все категории</option>
                    @foreach(\App\Enums\DocumentCategory::cases() as $category)
                        <option value="{{ $category->value }}" {{ request('category') == $category->value ? 'selected' : '' }}>
                            {{ match($category) {
                                \App\Enums\DocumentCategory::API_DOCUMENTATION => 'API документация',
                                \App\Enums\DocumentCategory::ARCHITECTURE => 'Архитектурные решения',
                                \App\Enums\DocumentCategory::INTEGRATION_GUIDE => 'Инструкции по интеграции',
                                \App\Enums\DocumentCategory::GENERAL_NOTES => 'Общие заметки',
                            } }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg">
                Применить фильтры
            </button>
            <a href="{{ route('documents.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded-lg">
                Сбросить
            </a>
        </div>
    </form>
</div>

<!-- Documents List -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    @if($documents->count() > 0)
        <div class="divide-y divide-gray-200">
            @foreach($documents as $document)
                <div class="p-6 hover:bg-gray-50 transition">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <a href="{{ route('documents.show', $document) }}" class="text-lg font-semibold text-gray-900 hover:text-indigo-600">
                                    {{ $document->title }}
                                </a>
                                
                                @if($document->category)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ match($document->category) {
                                        \App\Enums\DocumentCategory::API_DOCUMENTATION => 'bg-blue-100 text-blue-800',
                                        \App\Enums\DocumentCategory::ARCHITECTURE => 'bg-purple-100 text-purple-800',
                                        \App\Enums\DocumentCategory::INTEGRATION_GUIDE => 'bg-green-100 text-green-800',
                                        \App\Enums\DocumentCategory::GENERAL_NOTES => 'bg-gray-100 text-gray-800',
                                    } }}">
                                        {{ match($document->category) {
                                            \App\Enums\DocumentCategory::API_DOCUMENTATION => 'API',
                                            \App\Enums\DocumentCategory::ARCHITECTURE => 'Архитектура',
                                            \App\Enums\DocumentCategory::INTEGRATION_GUIDE => 'Интеграция',
                                            \App\Enums\DocumentCategory::GENERAL_NOTES => 'Заметки',
                                        } }}
                                    </span>
                                @endif

                                <span class="text-xs text-gray-500">
                                    Версия {{ $document->version }}
                                </span>
                            </div>

                            <p class="text-gray-600 text-sm mb-3">{{ Str::limit(strip_tags($document->content), 200) }}</p>

                            <div class="flex items-center gap-4 text-sm text-gray-500">
                                @if($document->project)
                                    <span>
                                        <strong>Проект:</strong> {{ $document->project->name }}
                                    </span>
                                @endif
                                <span>
                                    <strong>Автор:</strong> {{ $document->author->name }}
                                </span>
                                <span>
                                    <strong>Обновлено:</strong> {{ $document->updated_at->format('d.m.Y H:i') }}
                                </span>
                            </div>
                        </div>

                        <div class="ml-4 flex gap-2">
                            <a href="{{ route('documents.show', $document) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                Просмотр →
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 bg-gray-50">
            {{ $documents->links() }}
        </div>
    @else
        <div class="p-12 text-center text-gray-500">
            <p class="text-lg">Документы не найдены</p>
            <p class="text-sm mt-2">Попробуйте изменить фильтры или создайте новый документ</p>
        </div>
    @endif
</div>
@endsection
