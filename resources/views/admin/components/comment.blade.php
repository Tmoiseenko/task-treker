<div>
    @foreach($comments as $comment)
        <div class="box p-2 ">
            <div class="box-body">

                <div class="flex flex-wrap items-center gap-2 mb-2">
                <span class="font-semibold hover:underline leading-tight flex-1 min-w-0">
                    {!! $comment->content !!}
                </span>
                </div>

                <div class="flex gap-4 items-start justify-between flex-wrap">
                    <div class="flex flex-col gap-y-1 text-xs opacity-60">
                        <span class="flex items-center gap-1">
                                <x-moonshine::icon icon="user" path="moonshine::icons.s" size="4"/>
                                {{ $comment->user->name }}
                        </span>

                        <span class="flex items-center gap-1 ">
                            <x-moonshine::icon icon="calendar-days" path="moonshine::icons.s" size="4"/>
                            {{ $comment->created_at->format('d.m.Y H:m') }}
                        </span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span>
                            <a href="{{ toPage(
                    page: \App\MoonShine\Resources\Comment\Pages\CommentFormPage::class,
                    resource: \App\MoonShine\Resources\Comment\CommentResource::class,
                    params: ['resourceItem' => $comment->id],
                ) }}"
                               class="btn btn-square js-edit-button btn-primary">
                                <x-moonshine::icon icon="pencil" path="moonshine::icons" size="5"/>
                            </a>

                                <a href="{{ route('comments.destroy', $comment->id) }}"
                                   class="btn btn-square btn-error">
                                    <x-moonshine::icon icon="trash" path="moonshine::icons" size="4"/>
                                </a>
                        </span>
                    </div>
                </div>

            </div>
        </div>
    @endforeach
</div>
