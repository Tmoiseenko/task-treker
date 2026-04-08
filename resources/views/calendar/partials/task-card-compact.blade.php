{{-- Compact Task Card for Month View --}}
@php
    $isOverdue = $task->due_date &&
                $task->due_date->isPast() &&
                $task->status !== \App\Enums\TaskStatus::DONE;
@endphp

<div class="text-xs p-1.5 rounded border-l-2 cursor-pointer hover:bg-gray-50 transition {{ $isOverdue ? 'border-red-500 bg-red-50' : 'border-indigo-500 bg-indigo-50' }}"
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

    <div class="font-semibold text-gray-900 truncate">
        {{ $task->title }}
    </div>

    <div class="flex items-center gap-1 mt-0.5">
        <!-- Status Indicator -->
        <span class="w-2 h-2 rounded-full {{ match($task->status) {
            \App\Enums\TaskStatus::TODO => 'bg-gray-400',
            \App\Enums\TaskStatus::IN_PROGRESS => 'bg-blue-500',
            \App\Enums\TaskStatus::IN_TESTING => 'bg-purple-500',
            \App\Enums\TaskStatus::TEST_FAILED => 'bg-red-500',
            \App\Enums\TaskStatus::FOR_UNLOADING => 'bg-orange-500',
            \App\Enums\TaskStatus::DONE => 'bg-green-500',
        } }}"></span>

        <span class="text-gray-600 truncate">{{ $task->project->name }}</span>
    </div>
</div>
