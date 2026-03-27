{{-- Bug Reports Section --}}
<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-gray-900">
            Баг-репорты
            @if($task->bugReports->count() > 0)
                <span class="text-sm font-normal text-gray-500">({{ $task->bugReports->count() }})</span>
            @endif
        </h2>
        
        <div class="flex gap-2">
            @if($task->bugReports->count() > 0)
                <a 
                    href="{{ route('bug-reports.index', $task) }}"
                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                    Все баг-репорты →
                </a>
            @endif
            
            @can('view', $task)
                @if($task->status === \App\Enums\TaskStatus::IN_TESTING || $task->status === \App\Enums\TaskStatus::TEST_FAILED)
                    <a 
                        href="{{ route('bug-reports.create', $task) }}"
                        class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-1 px-3 rounded">
                        + Создать баг-репорт
                    </a>
                @endif
            @endcan
        </div>
    </div>

    @if($task->bugReports->count() > 0)
        {{-- Status Summary --}}
        @php
            $allBugsFixed = $task->bugReports->every(fn($bug) => $bug->status === \App\Enums\TaskStatus::DONE);
            $fixedCount = $task->bugReports->where('status', \App\Enums\TaskStatus::DONE)->count();
            $totalCount = $task->bugReports->count();
        @endphp
        
        <div class="mb-4 p-3 rounded-lg {{ $allBugsFixed ? 'bg-green-50 border border-green-200' : 'bg-orange-50 border border-orange-200' }}">
            <div class="flex items-center gap-2">
                @if($allBugsFixed)
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-semibold text-green-800">Все баги исправлены ({{ $fixedCount }}/{{ $totalCount }})</span>
                @else
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <span class="text-sm font-semibold text-orange-800">Исправлено {{ $fixedCount }} из {{ $totalCount }} багов</span>
                @endif
            </div>
        </div>

        {{-- Bug Reports List --}}
        <div class="space-y-2">
            @foreach($task->bugReports->take(5) as $bugReport)
                <a href="{{ route('tasks.show', $bugReport) }}" class="block p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-medium text-gray-900 truncate">{{ $bugReport->title }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs">
                                {{-- Status --}}
                                <span class="px-2 py-0.5 font-semibold rounded-full {{ match($bugReport->status) {
                                    \App\Enums\TaskStatus::DONE => 'bg-green-100 text-green-800',
                                    \App\Enums\TaskStatus::IN_PROGRESS => 'bg-blue-100 text-blue-800',
                                    default => 'bg-gray-100 text-gray-800',
                                } }}">
                                    {{ match($bugReport->status) {
                                        \App\Enums\TaskStatus::TODO => 'Не выполнено',
                                        \App\Enums\TaskStatus::IN_PROGRESS => 'В работе',
                                        \App\Enums\TaskStatus::DONE => 'Исправлено',
                                        default => $bugReport->status->value,
                                    } }}
                                </span>
                                
                                {{-- Priority --}}
                                <span class="px-2 py-0.5 font-semibold rounded-full {{ match($bugReport->priority) {
                                    \App\Enums\TaskPriority::HIGH => 'bg-red-100 text-red-800',
                                    \App\Enums\TaskPriority::MEDIUM => 'bg-yellow-100 text-yellow-800',
                                    \App\Enums\TaskPriority::LOW => 'bg-green-100 text-green-800',
                                    \App\Enums\TaskPriority::FROZEN => 'bg-gray-100 text-gray-800',
                                } }}">
                                    {{ match($bugReport->priority) {
                                        \App\Enums\TaskPriority::HIGH => 'Высокий',
                                        \App\Enums\TaskPriority::MEDIUM => 'Средний',
                                        \App\Enums\TaskPriority::LOW => 'Низкий',
                                        \App\Enums\TaskPriority::FROZEN => 'Заморожено',
                                    } }}
                                </span>

                                {{-- Assignee --}}
                                @if($bugReport->assignee)
                                    <span class="text-gray-600">{{ $bugReport->assignee->name }}</span>
                                @else
                                    <span class="text-orange-600">Не назначен</span>
                                @endif
                            </div>
                        </div>
                        
                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </a>
            @endforeach
        </div>

        @if($task->bugReports->count() > 5)
            <div class="mt-3 text-center">
                <a 
                    href="{{ route('bug-reports.index', $task) }}"
                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                    Показать все {{ $task->bugReports->count() }} баг-репортов →
                </a>
            </div>
        @endif
    @else
        <div class="text-center py-6">
            <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="text-sm text-gray-500 mb-3">Баг-репорты не найдены</p>
            @can('view', $task)
                @if($task->status === \App\Enums\TaskStatus::IN_TESTING || $task->status === \App\Enums\TaskStatus::TEST_FAILED)
                    <a 
                        href="{{ route('bug-reports.create', $task) }}"
                        class="inline-block text-sm bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg">
                        Создать баг-репорт
                    </a>
                @endif
            @endcan
        </div>
    @endif
</div>
