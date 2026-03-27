{{-- Checklist Section with Create Form and Progress --}}
<div class="bg-white rounded-lg shadow-sm p-6" x-data="checklistManager()">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-gray-900">Чек-лист</h2>
        @can('update', $task)
            @if($task->status === \App\Enums\TaskStatus::IN_TESTING)
                <button 
                    @click="showForm = !showForm"
                    type="button"
                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                    <span x-show="!showForm">+ Создать чек-лист</span>
                    <span x-show="showForm">Отмена</span>
                </button>
            @endif
        @endcan
    </div>

    {{-- Create Checklist Form --}}
    @can('update', $task)
        @if($task->status === \App\Enums\TaskStatus::IN_TESTING && $task->checklistItems->count() === 0)
            <div x-show="showForm" x-collapse class="mb-4">
                <form @submit.prevent="createChecklist">
                    <div class="space-y-2 mb-3">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="flex gap-2">
                                <input 
                                    type="text" 
                                    x-model="items[index]"
                                    placeholder="Пункт чек-листа"
                                    class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                                <button 
                                    type="button"
                                    @click="removeItem(index)"
                                    x-show="items.length > 1"
                                    class="text-red-600 hover:text-red-800">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    
                    <div class="flex gap-2">
                        <button 
                            type="button"
                            @click="addItem"
                            class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                            + Добавить пункт
                        </button>
                        <button 
                            type="submit"
                            :disabled="loading"
                            class="ml-auto bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg disabled:opacity-50">
                            <span x-show="!loading">Создать чек-лист</span>
                            <span x-show="loading">Создание...</span>
                        </button>
                    </div>
                </form>
            </div>
        @endif
    @endcan

    {{-- Checklist Display --}}
    @if($task->checklistItems->count() > 0)
        <div>
            {{-- Progress Bar --}}
            <div class="mb-4">
                <div class="flex justify-between text-sm text-gray-600 mb-1">
                    <span>Прогресс</span>
                    <span x-text="`${completed}/${total} (${progress}%)`"></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div 
                        class="bg-green-600 h-2 rounded-full transition-all duration-300" 
                        :style="`width: ${progress}%`"></div>
                </div>
            </div>

            {{-- Checklist Items --}}
            <div class="space-y-2">
                @foreach($task->checklistItems as $item)
                    <div class="flex items-center gap-2 group">
                        @can('update', $task)
                            <input 
                                type="checkbox" 
                                {{ $item->is_completed ? 'checked' : '' }}
                                @change="toggleItem({{ $item->id }}, $event.target.checked)"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        @else
                            <input 
                                type="checkbox" 
                                {{ $item->is_completed ? 'checked' : '' }}
                                disabled
                                class="rounded border-gray-300">
                        @endcan
                        <span 
                            class="text-sm flex-1"
                            :class="checklistItems[{{ $item->id }}] ? 'line-through text-gray-500' : 'text-gray-900'">
                            {{ $item->title }}
                        </span>
                        @can('update', $task)
                            <button 
                                type="button"
                                @click="deleteItem({{ $item->id }})"
                                class="opacity-0 group-hover:opacity-100 text-red-600 hover:text-red-800 transition-opacity">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        @endcan
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <p class="text-sm text-gray-500">Чек-лист не создан</p>
    @endif
</div>

@push('scripts')
<script>
function checklistManager() {
    return {
        showForm: false,
        loading: false,
        items: [''],
        checklistItems: @json($task->checklistItems->pluck('is_completed', 'id')),
        completed: {{ $task->checklistItems->where('is_completed', true)->count() }},
        total: {{ $task->checklistItems->count() }},
        progress: {{ $task->checklistItems->count() > 0 ? round(($task->checklistItems->where('is_completed', true)->count() / $task->checklistItems->count()) * 100) : 0 }},

        addItem() {
            this.items.push('');
        },

        removeItem(index) {
            this.items.splice(index, 1);
        },

        async createChecklist() {
            if (this.loading) return;
            
            const validItems = this.items.filter(item => item.trim() !== '');
            if (validItems.length === 0) {
                alert('Добавьте хотя бы один пункт');
                return;
            }

            this.loading = true;
            
            try {
                const response = await fetch('{{ route('checklist.store', $task) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ items: validItems })
                });

                const data = await response.json();

                if (response.ok) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Ошибка при создании чек-листа');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Произошла ошибка при создании чек-листа');
            } finally {
                this.loading = false;
            }
        },

        async toggleItem(itemId, isChecked) {
            try {
                const response = await fetch(`/checklist/${itemId}/toggle`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok) {
                    this.checklistItems[itemId] = isChecked;
                    this.updateProgress(data.progress);
                } else {
                    alert('Ошибка при обновлении пункта');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Произошла ошибка');
            }
        },

        async deleteItem(itemId) {
            if (!confirm('Удалить этот пункт?')) return;

            try {
                const response = await fetch(`/checklist/${itemId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    window.location.reload();
                } else {
                    alert('Ошибка при удалении пункта');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Произошла ошибка');
            }
        },

        updateProgress(newProgress) {
            this.progress = newProgress;
            this.completed = Math.round((newProgress / 100) * this.total);
        }
    }
}
</script>
@endpush
