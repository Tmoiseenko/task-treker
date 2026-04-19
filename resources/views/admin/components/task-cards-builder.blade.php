{{--
    TaskCardsBuilder — список задач карточками.
    Стиль взят из resources/views/tasks/index.blade.php.

    Переменные из компонента:
      $tasks       — Collection<Task>
      $urlResolver — Closure(Task): string | string
--}}
@if($tasks->isNotEmpty())
    <div class="space-elements">
        @foreach($tasks as $task)
            @php
                $urlDetail      = value($urlDetail, $task);
                $urlEdit     = value($urlEdit, $task);
                $overdue  = $task->due_date
                    && $task->due_date->isPast()
                    && $task->status !== \App\Enums\TaskStatus::DONE
                    && $task->status !== \App\Enums\TaskStatus::FOR_UNLOADING;
            @endphp

            <div class="box p-2 {{ $overdue ? 'border-pink bg-pink-100' : '' }}" style="max-width: 320px">
                <div class="box-body">

                    {{-- Заголовок + бейджи --}}
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <a href="{{ $urlDetail }}"
                           class="font-semibold hover:underline leading-tight flex-1 min-w-0">
                            {{ $task->title }}
                        </a>
                        {{-- ID --}}
                        <span class="badge badge-primary">
                            ID: {{ $task->id }}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        {{-- Приоритет --}}
                        <span class="badge badge-{{ $task->priority->color() }}">
                            {{ $task->priority->label() }}
                        </span>
                    </div>


                    {{-- Мета: исполнитель, срок --}}
                    <div class="flex flex-col gap-y-1 text-xs opacity-60">
                        @if($task->assignee)
                            <span class="flex items-center gap-1">
                                <x-moonshine::icon icon="user" path="moonshine::icons.s" size="4"/>
                                {{ $task->assignee->name }}
                            </span>
                        @endif

                        @if($task->due_date)
                            <span
                                class="flex items-center gap-1 {{ $overdue ? 'text-red font-semibold opacity-100' : '' }}">
                                <x-moonshine::icon icon="calendar-days" path="moonshine::icons.s" size="4"/>
                                {{ $task->due_date->format('d.m.Y') }}
                            </span>
                        @endif
                    </div>

                    {{-- Теги --}}
                    @if($task->relationLoaded('tags') && $task->tags->isNotEmpty())
                        <div class="flex gap-4 items-start justify-between flex-wrap">
                            <span>
                                @foreach($task->tags as $tag)
                                    <span {{ $attributes->merge(['class' => 'badge'.($tag->color ? ' badge-'.$tag->color : '')])}}>
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                            </span>
                            <span>
                                <a href="{{ $urlEdit }}"
                                   class="font-semibold hover:underline leading-tight flex-1 min-w-0 badge badge-primary">
                                    <x-moonshine::icon icon="pencil-square" path="moonshine::icons.s" size="5"/>
                                </a>
                            </span>
                        </div>
                    @endif

                </div>{{-- /.box-body --}}
            </div>{{-- /.box --}}
        @endforeach
    </div>
@else
    <p class="text-sm opacity-50 text-center py-4">Нет задач</p>
@endif

