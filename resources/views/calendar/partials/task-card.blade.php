{{-- Task Card for Week View --}}
@php
    $isOverdue = $task->due_date &&
                $task->due_date->isPast() &&
                $task->status !== \App\Enums\TaskStatus::DONE;
@endphp

<div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow p-3 border-l-4 cursor-pointer {{ $isOverdue ? 'border-red-500 bg-red-50' : 'border-indigo-500' }}"
     @click="$dispatch('task-clicked', {
         title: '{{ addslashes($task->title) }}',
         project: '{{ addslashes($task->project->name) }}',
         status: '{{ match($task->status) {
             \App\Enums\TaskStatus::TODO => 'Не выполнено',
             \App\Enums\TaskStatus::IN_PROGRESS => 'В работе',
             \App\Enums\TaskStatus::IN_TESTING => 'На тестировании',
             \App\Enums\TaskStatus::TEST_FAILED => 'Тест провален',
             \App\Enums\TaskStatus::FOR_UNLOADING => 'Готово к выгрузке',
             \App\Enums\TaskStatus::DONE => 'Выполнено',
         } }}',
         assignee: '{{ $task->assignee ? addslashes($task->assignee->name) : '' }}',
         due_date: '{{ $task->due_date->format('d.m.Y') }}',
         url: '{{ route('tasks.show', $task) }}'
     })">

    <!-- Task Title -->
    <div class="font-semibold text-sm text-gray-900 mb-2 line-clamp-2">
        {{ $task->title }}
    </div>

    <!-- Project -->
    <div class="text-xs text-gray-600 mb-2">
        {{ $task->project->name }}
    </div>

    <!-- Status Badge -->
    <div class="mb-2">
        <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded-full {{ match($task->status) {
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

    <!-- Assignee -->
    @if($task->assignee)
        <div class="flex items-center gap-1 text-xs text-gray-600">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            <span class="truncate">{{ $task->assignee->name }}</span>
        </div>
    @endif

    <!-- Priority Badge (if high) -->
    @if($task->priority === \App\Enums\TaskPriority::HIGH)
        <div class="mt-2">
            <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                Высокий приоритет
            </span>
        </div>
    @endif

    @if($isOverdue)
        <div class="mt-2 text-xs text-red-600 font-semibold">
            Просрочено
        </div>
    @endif
</div>
