{{-- Attachments Section --}}
<div class="bg-white rounded-lg shadow-sm p-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Прикрепленные файлы ({{ $task->attachments->count() }})</h2>
    
    {{-- File Upload Form --}}
    <form method="POST" action="{{ route('attachments.store', $task) }}" enctype="multipart/form-data" class="mb-6">
        @csrf
        <div class="mb-3">
            <label for="file-upload" class="block text-sm font-medium text-gray-700 mb-2">
                Загрузить файл
            </label>
            <div class="flex items-center gap-3">
                <label class="flex-1 flex items-center justify-center px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-indigo-500 transition-colors">
                    <div class="text-center">
                        <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <span class="mt-2 block text-sm text-gray-600">
                            <span class="font-semibold text-indigo-600">Выберите файл</span> или перетащите сюда
                        </span>
                        <span class="mt-1 block text-xs text-gray-500">
                            Максимальный размер: 10 МБ
                        </span>
                    </div>
                    <input 
                        id="file-upload" 
                        name="file" 
                        type="file" 
                        class="sr-only" 
                        required
                        onchange="document.getElementById('file-name').textContent = this.files[0]?.name || 'Файл не выбран'"
                    >
                </label>
            </div>
            <p id="file-name" class="mt-2 text-sm text-gray-600">Файл не выбран</p>
            @error('file')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div class="flex justify-end">
            <button 
                type="submit" 
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors"
            >
                Загрузить
            </button>
        </div>
    </form>

    {{-- Attachments List --}}
    @if($task->attachments->count() > 0)
        <div class="space-y-2">
            @foreach($task->attachments as $attachment)
                <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-3 flex-1">
                        <svg class="w-8 h-8 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 truncate">{{ $attachment->original_name }}</p>
                            <div class="flex items-center gap-3 text-sm text-gray-500">
                                <span>{{ number_format($attachment->size / 1024, 2) }} КБ</span>
                                <span>•</span>
                                <span>{{ $attachment->user->name }}</span>
                                <span>•</span>
                                <span>{{ $attachment->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 ml-4">
                        <a 
                            href="{{ route('attachments.download', [$task, $attachment]) }}" 
                            class="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                        >
                            Скачать
                        </a>
                        @if($attachment->user_id === auth()->id() || $task->author_id === auth()->id() || $task->assignee_id === auth()->id())
                            <form method="POST" action="{{ route('attachments.destroy', [$task, $attachment]) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button 
                                    type="submit" 
                                    class="text-red-600 hover:text-red-800 text-sm font-medium"
                                    onclick="return confirm('Вы уверены, что хотите удалить этот файл?')"
                                >
                                    Удалить
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-gray-500 text-center py-4">Файлы не прикреплены</p>
    @endif
</div>
