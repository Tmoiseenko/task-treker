@props([
    'components'         => [],
    'label'              => false,
    'dark'               => false,
    'icon'               => null,
    'width'              => 'w-100',
    'headerClass'        => '',
    'colSpan'            => 12,
    'adaptiveColSpan'    => 12,
])

{{--
    KanbanColumn — визуально идентичен .box MoonShine, но с независимым вертикальным скроллом.

    Структура:
    ┌─────────────────────────────────────────────────┐  ← .box (border, bg, radius)
    │ .box-title ← заголовок (shrink-0, никогда       │     display переопределён в flex
    │              не скроллится)                      │     padding обнулён через CSS-vars
    ├─────────────────────────────────────────────────┤     чтобы управлять им поотдельно
    │ тело — flex-1 min-h-0 overflow-y-auto           │
    │ (скроллится независимо)                         │
    └─────────────────────────────────────────────────┘

    Трюки:
    • display:flex + flex-direction:column  — переопределяет display:block из .box
    • --ms-box-padding-y/x: 0              — убирает общий padding .box;
      padding добавляется отдельно на заголовок и тело, иначе body не скроллится до края
    • margin-bottom: 0                     — в KanbanBoard не нужны отступы между строками
    • flex-none + col-span-*               — фиксированная ширина для горизонтального скролла
      (col-span используется если колонка вложена в Grid вместо KanbanBoard)
    • min-h-0 на теле                      — убирает min-height:auto flexbox-детей,
      иначе overflow-y-auto никогда не сработает
--}}
<div {{ $attributes->class([
        'box overflow-hidden flex-none',
        $width,
        'box--dark'                                    => $dark,
        "col-span-$adaptiveColSpan xl:col-span-$colSpan",
     ])
     ->merge(['style' => 'display:flex; flex-direction:column; margin-bottom:0; --ms-box-padding-y:0; --ms-box-padding-x:0; min-width: 320px;'])
}}>

    {{-- Заголовок: .box-title даёт border-bottom, font-weight, gap для иконки.
         p-4 возвращают горизонтальный/верхний отступы, которые убрал .box.
         style="margin-bottom:0" — убираем margin между flex-детьми, отступ уже в padding. --}}
    @if($label || ($icon && $icon->isNotEmpty()))
    <h2 class="box-title p-4 {{ $headerClass }}">
        {{ $icon ?? '' }}{{ $label ?? '' }}
    </h2>
    @endif

    {{-- Тело колонки — независимый вертикальный скролл.
         p-4 заменяет padding .box; space-elements — стандартный MoonShine gap между элементами --}}
    <div class="flex-1 min-h-0 overflow-y-auto p-4 space-elements">
        <x-moonshine::components :components="$components" />

        {{ $slot ?? '' }}
    </div>

</div>

