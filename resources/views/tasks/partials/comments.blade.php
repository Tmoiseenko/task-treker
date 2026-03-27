{{-- Comments Section --}}
<div class="bg-white rounded-lg shadow-sm p-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Комментарии ({{ $task->comments->whereNull('deleted_at')->count() }})</h2>
    
    {{-- Comment Form --}}
    <form method="POST" action="{{ route('comments.store', $task) }}" class="mb-6">
        @csrf
        <div class="mb-3">
            <label for="comment-content" class="block text-sm font-medium text-gray-700 mb-2">
                Добавить комментарий
            </label>
            <textarea 
                id="comment-content"
                name="content" 
                rows="3" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('content') border-red-500 @enderror"
                placeholder="Введите ваш комментарий..."
                required
            >{{ old('content') }}</textarea>
            @error('content')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div class="flex justify-end">
            <button 
                type="submit" 
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors"
            >
                Отправить
            </button>
        </div>
    </form>

    {{-- Comments List --}}
    @if($task->comments->whereNull('deleted_at')->count() > 0)
        <div class="space-y-4">
            @foreach($task->comments as $comment)
                @if(!$comment->deleted_at)
                    <div class="border-l-4 border-indigo-500 pl-4 py-2 bg-gray-50 rounded-r">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <span class="font-semibold text-gray-900">{{ $comment->user->name }}</span>
                                <span class="text-sm text-gray-500 ml-2">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            @if($comment->user_id === auth()->id())
                                <form method="POST" action="{{ route('comments.destroy', [$task, $comment]) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button 
                                        type="submit" 
                                        class="text-red-600 hover:text-red-800 text-sm"
                                        onclick="return confirm('Вы уверены, что хотите удалить этот комментарий?')"
                                    >
                                        Удалить
                                    </button>
                                </form>
                            @endif
                        </div>
                        <p class="text-gray-700 whitespace-pre-wrap">{{ $comment->content }}</p>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <p class="text-gray-500 text-center py-4">Комментариев пока нет. Будьте первым!</p>
    @endif
</div>
