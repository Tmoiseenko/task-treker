<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use MoonShine\ColorManager\Palettes\PurplePalette;
use MoonShine\Laravel\Layouts\AppLayout;
use MoonShine\ColorManager\ColorManager;
use MoonShine\Contracts\ColorManager\ColorManagerContract;
use MoonShine\Contracts\ColorManager\PaletteContract;
use App\MoonShine\Resources\Document\DocumentResource;
use App\MoonShine\Resources\Project\ProjectResource;
use App\MoonShine\Resources\Task\TaskResource;
use App\MoonShine\Resources\Stage\StageResource;
use App\MoonShine\Resources\Tag\TagResource;
use MoonShine\AssetManager\InlineCss;
use MoonShine\MenuManager\MenuItem;
use MoonShine\MenuManager\MenuGroup;
use App\MoonShine\Resources\Comment\CommentResource;

final class MoonShineLayout extends AppLayout
{
    /**
     * @var null|class-string<PaletteContract>
     */
    protected ?string $palette = PurplePalette::class;

    protected function assets(): array
    {
        return [
            ...parent::assets(),
            InlineCss::make(<<<'CSS'
                /* Tailwind utility classes missing from MoonShine compiled CSS (needed for Kanban board) */

                /* ── ЦВЕТА (MoonShine\Support\Enums\Color) ─────────────────────────
                 *  bg-*            : background
                 *  bg-*-foreground : foreground-текст поверх bg-*
                 *  border-*        : border
                 *  Семантика  → --ms-cm-{color}        (primary/secondary/success/error/warning/info)
                 *  Палитра    → --color-{color}-500    (purple/pink/blue/green/yellow/red/gray)
                 * ────────────────────────────────────────────────────────────────── */

                /* — семантические цвета — */
                .bg-primary   { background-color: var(--ms-cm-primary,   oklch(0%   0  0)); }
                .bg-secondary { background-color: var(--ms-cm-secondary,  oklch(92%  0  0)); }
                .bg-success   { background-color: var(--ms-cm-success,    oklch(64% .22 142.49)); }
                .bg-error     { background-color: var(--ms-cm-error,      oklch(58% .21  26.855)); }
                .bg-warning   { background-color: var(--ms-cm-warning,    oklch(75% .17  75.35)); }
                .bg-info      { background-color: var(--ms-cm-info,       oklch(60% .219 257.63)); }

                /* bg-*-foreground — цвет текста, который читается поверх bg-* */
                .bg-primary-foreground   { color: var(--ms-cm-primary-text,   oklch(100% 0 0)); }
                .bg-secondary-foreground { color: var(--ms-cm-secondary-text,  oklch(0%   0 0)); }
                .bg-success-foreground   { color: var(--ms-cm-success-text,    oklch(100% 0 0)); }
                .bg-error-foreground     { color: var(--ms-cm-error-text,      oklch(100% 0 0)); }
                .bg-warning-foreground   { color: var(--ms-cm-warning-text,    oklch(0%   0 0)); }
                .bg-info-foreground      { color: var(--ms-cm-info-text,       oklch(100% 0 0)); }

                /* border семантика */
                .border-primary   { border-color: var(--ms-cm-primary,   oklch(0%   0  0)); }
                .border-secondary { border-color: var(--ms-cm-secondary,  oklch(92%  0  0)); }
                .border-success   { border-color: var(--ms-cm-success,    oklch(64% .22 142.49)); }
                .border-error     { border-color: var(--ms-cm-error,      oklch(58% .21  26.855)); }
                .border-warning   { border-color: var(--ms-cm-warning,    oklch(75% .17  75.35)); }
                .border-info      { border-color: var(--ms-cm-info,       oklch(60% .219 257.63)); }

                /* — палитрные цвета — */
                .bg-purple { background-color: var(--color-purple-500); }
                .bg-pink   { background-color: var(--color-pink-500);   }
                .bg-blue   { background-color: var(--color-blue-500);   }
                .bg-green  { background-color: var(--color-green-500);  }
                .bg-yellow { background-color: var(--color-yellow-500); }
                .bg-red    { background-color: var(--color-red-500);    }
                .bg-gray   { background-color: var(--color-gray-500);   }

                /* border палитра */
                .border-purple { border-color: var(--color-purple-500); }
                .border-pink   { border-color: var(--color-pink-500);   }
                .border-blue   { border-color: var(--color-blue-500);   }
                .border-green  { border-color: var(--color-green-500);  }
                .border-yellow { border-color: var(--color-yellow-500); }
                .border-red    { border-color: var(--color-red-500);    }
                .border-gray   { border-color: var(--color-gray-500);   }

                /* ── ШЕЙДЫ ПАЛИТРНЫХ ЦВЕТОВ (100–900) ──────────────────────────────
                 *  Переменные --color-{name}-{shade} встроены в MoonShine через
                 *  Tailwind CSS. Шейд 50 недоступен для палитрных цветов.
                 * ──────────────────────────────────────────────────────────────── */

                /* purple */
                .bg-purple-100 { background-color: var(--color-purple-100); } .text-purple-100 { color: var(--color-purple-100); } .border-purple-100 { border-color: var(--color-purple-100); }
                .bg-purple-200 { background-color: var(--color-purple-200); } .text-purple-200 { color: var(--color-purple-200); } .border-purple-200 { border-color: var(--color-purple-200); }
                .bg-purple-300 { background-color: var(--color-purple-300); } .text-purple-300 { color: var(--color-purple-300); } .border-purple-300 { border-color: var(--color-purple-300); }
                .bg-purple-400 { background-color: var(--color-purple-400); } .text-purple-400 { color: var(--color-purple-400); } .border-purple-400 { border-color: var(--color-purple-400); }
                .bg-purple-500 { background-color: var(--color-purple-500); } .text-purple-500 { color: var(--color-purple-500); } .border-purple-500 { border-color: var(--color-purple-500); }
                .bg-purple-600 { background-color: var(--color-purple-600); } .text-purple-600 { color: var(--color-purple-600); } .border-purple-600 { border-color: var(--color-purple-600); }
                .bg-purple-700 { background-color: var(--color-purple-700); } .text-purple-700 { color: var(--color-purple-700); } .border-purple-700 { border-color: var(--color-purple-700); }
                .bg-purple-800 { background-color: var(--color-purple-800); } .text-purple-800 { color: var(--color-purple-800); } .border-purple-800 { border-color: var(--color-purple-800); }
                .bg-purple-900 { background-color: var(--color-purple-900); } .text-purple-900 { color: var(--color-purple-900); } .border-purple-900 { border-color: var(--color-purple-900); }

                /* pink */
                .bg-pink-100 { background-color: var(--color-pink-100); } .text-pink-100 { color: var(--color-pink-100); } .border-pink-100 { border-color: var(--color-pink-100); }
                .bg-pink-200 { background-color: var(--color-pink-200); } .text-pink-200 { color: var(--color-pink-200); } .border-pink-200 { border-color: var(--color-pink-200); }
                .bg-pink-300 { background-color: var(--color-pink-300); } .text-pink-300 { color: var(--color-pink-300); } .border-pink-300 { border-color: var(--color-pink-300); }
                .bg-pink-400 { background-color: var(--color-pink-400); } .text-pink-400 { color: var(--color-pink-400); } .border-pink-400 { border-color: var(--color-pink-400); }
                .bg-pink-500 { background-color: var(--color-pink-500); } .text-pink-500 { color: var(--color-pink-500); } .border-pink-500 { border-color: var(--color-pink-500); }
                .bg-pink-600 { background-color: var(--color-pink-600); } .text-pink-600 { color: var(--color-pink-600); } .border-pink-600 { border-color: var(--color-pink-600); }
                .bg-pink-700 { background-color: var(--color-pink-700); } .text-pink-700 { color: var(--color-pink-700); } .border-pink-700 { border-color: var(--color-pink-700); }
                .bg-pink-800 { background-color: var(--color-pink-800); } .text-pink-800 { color: var(--color-pink-800); } .border-pink-800 { border-color: var(--color-pink-800); }
                .bg-pink-900 { background-color: var(--color-pink-900); } .text-pink-900 { color: var(--color-pink-900); } .border-pink-900 { border-color: var(--color-pink-900); }

                /* blue */
                .bg-blue-100 { background-color: var(--color-blue-100); } .text-blue-100 { color: var(--color-blue-100); } .border-blue-100 { border-color: var(--color-blue-100); }
                .bg-blue-200 { background-color: var(--color-blue-200); } .text-blue-200 { color: var(--color-blue-200); } .border-blue-200 { border-color: var(--color-blue-200); }
                .bg-blue-300 { background-color: var(--color-blue-300); } .text-blue-300 { color: var(--color-blue-300); } .border-blue-300 { border-color: var(--color-blue-300); }
                .bg-blue-400 { background-color: var(--color-blue-400); } .text-blue-400 { color: var(--color-blue-400); } .border-blue-400 { border-color: var(--color-blue-400); }
                .bg-blue-500 { background-color: var(--color-blue-500); } .text-blue-500 { color: var(--color-blue-500); } .border-blue-500 { border-color: var(--color-blue-500); }
                .bg-blue-600 { background-color: var(--color-blue-600); } .text-blue-600 { color: var(--color-blue-600); } .border-blue-600 { border-color: var(--color-blue-600); }
                .bg-blue-700 { background-color: var(--color-blue-700); } .text-blue-700 { color: var(--color-blue-700); } .border-blue-700 { border-color: var(--color-blue-700); }
                .bg-blue-800 { background-color: var(--color-blue-800); } .text-blue-800 { color: var(--color-blue-800); } .border-blue-800 { border-color: var(--color-blue-800); }
                .bg-blue-900 { background-color: var(--color-blue-900); } .text-blue-900 { color: var(--color-blue-900); } .border-blue-900 { border-color: var(--color-blue-900); }

                /* green */
                .bg-green-100 { background-color: var(--color-green-100); } .text-green-100 { color: var(--color-green-100); } .border-green-100 { border-color: var(--color-green-100); }
                .bg-green-200 { background-color: var(--color-green-200); } .text-green-200 { color: var(--color-green-200); } .border-green-200 { border-color: var(--color-green-200); }
                .bg-green-300 { background-color: var(--color-green-300); } .text-green-300 { color: var(--color-green-300); } .border-green-300 { border-color: var(--color-green-300); }
                .bg-green-400 { background-color: var(--color-green-400); } .text-green-400 { color: var(--color-green-400); } .border-green-400 { border-color: var(--color-green-400); }
                .bg-green-500 { background-color: var(--color-green-500); } .text-green-500 { color: var(--color-green-500); } .border-green-500 { border-color: var(--color-green-500); }
                .bg-green-600 { background-color: var(--color-green-600); } .text-green-600 { color: var(--color-green-600); } .border-green-600 { border-color: var(--color-green-600); }
                .bg-green-700 { background-color: var(--color-green-700); } .text-green-700 { color: var(--color-green-700); } .border-green-700 { border-color: var(--color-green-700); }
                .bg-green-800 { background-color: var(--color-green-800); } .text-green-800 { color: var(--color-green-800); } .border-green-800 { border-color: var(--color-green-800); }
                .bg-green-900 { background-color: var(--color-green-900); } .text-green-900 { color: var(--color-green-900); } .border-green-900 { border-color: var(--color-green-900); }

                /* yellow */
                .bg-yellow-100 { background-color: var(--color-yellow-100); } .text-yellow-100 { color: var(--color-yellow-100); } .border-yellow-100 { border-color: var(--color-yellow-100); }
                .bg-yellow-200 { background-color: var(--color-yellow-200); } .text-yellow-200 { color: var(--color-yellow-200); } .border-yellow-200 { border-color: var(--color-yellow-200); }
                .bg-yellow-300 { background-color: var(--color-yellow-300); } .text-yellow-300 { color: var(--color-yellow-300); } .border-yellow-300 { border-color: var(--color-yellow-300); }
                .bg-yellow-400 { background-color: var(--color-yellow-400); } .text-yellow-400 { color: var(--color-yellow-400); } .border-yellow-400 { border-color: var(--color-yellow-400); }
                .bg-yellow-500 { background-color: var(--color-yellow-500); } .text-yellow-500 { color: var(--color-yellow-500); } .border-yellow-500 { border-color: var(--color-yellow-500); }
                .bg-yellow-600 { background-color: var(--color-yellow-600); } .text-yellow-600 { color: var(--color-yellow-600); } .border-yellow-600 { border-color: var(--color-yellow-600); }
                .bg-yellow-700 { background-color: var(--color-yellow-700); } .text-yellow-700 { color: var(--color-yellow-700); } .border-yellow-700 { border-color: var(--color-yellow-700); }
                .bg-yellow-800 { background-color: var(--color-yellow-800); } .text-yellow-800 { color: var(--color-yellow-800); } .border-yellow-800 { border-color: var(--color-yellow-800); }
                .bg-yellow-900 { background-color: var(--color-yellow-900); } .text-yellow-900 { color: var(--color-yellow-900); } .border-yellow-900 { border-color: var(--color-yellow-900); }

                /* red */
                .bg-red-100 { background-color: var(--color-red-100); } .text-red-100 { color: var(--color-red-100); } .border-red-100 { border-color: var(--color-red-100); }
                .bg-red-200 { background-color: var(--color-red-200); } .text-red-200 { color: var(--color-red-200); } .border-red-200 { border-color: var(--color-red-200); }
                .bg-red-300 { background-color: var(--color-red-300); } .text-red-300 { color: var(--color-red-300); } .border-red-300 { border-color: var(--color-red-300); }
                .bg-red-400 { background-color: var(--color-red-400); } .text-red-400 { color: var(--color-red-400); } .border-red-400 { border-color: var(--color-red-400); }
                .bg-red-500 { background-color: var(--color-red-500); } .text-red-500 { color: var(--color-red-500); } .border-red-500 { border-color: var(--color-red-500); }
                .bg-red-600 { background-color: var(--color-red-600); } .text-red-600 { color: var(--color-red-600); } .border-red-600 { border-color: var(--color-red-600); }
                .bg-red-700 { background-color: var(--color-red-700); } .text-red-700 { color: var(--color-red-700); } .border-red-700 { border-color: var(--color-red-700); }
                .bg-red-800 { background-color: var(--color-red-800); } .text-red-800 { color: var(--color-red-800); } .border-red-800 { border-color: var(--color-red-800); }
                .bg-red-900 { background-color: var(--color-red-900); } .text-red-900 { color: var(--color-red-900); } .border-red-900 { border-color: var(--color-red-900); }

                /* gray */
                .bg-gray-100 { background-color: var(--color-gray-100); } .text-gray-100 { color: var(--color-gray-100); } .border-gray-100 { border-color: var(--color-gray-100); }
                .bg-gray-200 { background-color: var(--color-gray-200); } .text-gray-200 { color: var(--color-gray-200); } .border-gray-200 { border-color: var(--color-gray-200); }
                .bg-gray-300 { background-color: var(--color-gray-300); } .text-gray-300 { color: var(--color-gray-300); } .border-gray-300 { border-color: var(--color-gray-300); }
                .bg-gray-400 { background-color: var(--color-gray-400); } .text-gray-400 { color: var(--color-gray-400); } .border-gray-400 { border-color: var(--color-gray-400); }
                .bg-gray-500 { background-color: var(--color-gray-500); } .text-gray-500 { color: var(--color-gray-500); } .border-gray-500 { border-color: var(--color-gray-500); }
                .bg-gray-600 { background-color: var(--color-gray-600); } .text-gray-600 { color: var(--color-gray-600); } .border-gray-600 { border-color: var(--color-gray-600); }
                .bg-gray-700 { background-color: var(--color-gray-700); } .text-gray-700 { color: var(--color-gray-700); } .border-gray-700 { border-color: var(--color-gray-700); }
                .bg-gray-800 { background-color: var(--color-gray-800); } .text-gray-800 { color: var(--color-gray-800); } .border-gray-800 { border-color: var(--color-gray-800); }
                .bg-gray-900 { background-color: var(--color-gray-900); } .text-gray-900 { color: var(--color-gray-900); } .border-gray-900 { border-color: var(--color-gray-900); }

                /* ── ШЕЙДЫ СЕМАНТИЧЕСКИХ ЦВЕТОВ (base 50–900) ──────────────────────
                 *  Переменные --ms-cm-base-{shade} генерируются ColorManager-ом
                 *  (см. colors() в MoonShineLayout). Используются для UI-поверхностей.
                 * ──────────────────────────────────────────────────────────────── */
                .bg-base-50  { background-color: var(--ms-cm-base-50);  }
                .bg-base-100 { background-color: var(--ms-cm-base-100); }
                .bg-base-200 { background-color: var(--ms-cm-base-200); }
                .bg-base-300 { background-color: var(--ms-cm-base-300); }
                .bg-base-400 { background-color: var(--ms-cm-base-400); }
                .bg-base-500 { background-color: var(--ms-cm-base-500); }
                .bg-base-600 { background-color: var(--ms-cm-base-600); }
                .bg-base-700 { background-color: var(--ms-cm-base-700); }
                .bg-base-800 { background-color: var(--ms-cm-base-800); }
                .bg-base-900 { background-color: var(--ms-cm-base-900); }

                .overflow-x-auto { overflow-x: auto; }
                .overflow-y-auto { overflow-y: auto; }
                .flex-1 { flex: 1 1 0%; }
                .flex-none { flex: none; }
                .min-h-0 { min-height: 0px; }
                .w-100 { width: calc(var(--spacing, .25rem) * 100); }

                /* ── PADDING (все направления, шкала Tailwind) ───────────────────── */
                /* p – все стороны */
                .p-0  { padding: 0px; }
                .p-1  { padding: calc(var(--spacing,.25rem)*1);  }
                .p-2  { padding: calc(var(--spacing,.25rem)*2);  }
                .p-3  { padding: calc(var(--spacing,.25rem)*3);  }
                .p-4  { padding: calc(var(--spacing,.25rem)*4);  }
                .p-5  { padding: calc(var(--spacing,.25rem)*5);  }
                .p-6  { padding: calc(var(--spacing,.25rem)*6);  }
                .p-7  { padding: calc(var(--spacing,.25rem)*7);  }
                .p-8  { padding: calc(var(--spacing,.25rem)*8);  }
                .p-9  { padding: calc(var(--spacing,.25rem)*9);  }
                .p-10 { padding: calc(var(--spacing,.25rem)*10); }
                .p-11 { padding: calc(var(--spacing,.25rem)*11); }
                .p-12 { padding: calc(var(--spacing,.25rem)*12); }
                .p-14 { padding: calc(var(--spacing,.25rem)*14); }
                .p-16 { padding: calc(var(--spacing,.25rem)*16); }
                .p-20 { padding: calc(var(--spacing,.25rem)*20); }
                .p-24 { padding: calc(var(--spacing,.25rem)*24); }
                .p-28 { padding: calc(var(--spacing,.25rem)*28); }
                .p-32 { padding: calc(var(--spacing,.25rem)*32); }
                .p-36 { padding: calc(var(--spacing,.25rem)*36); }
                .p-40 { padding: calc(var(--spacing,.25rem)*40); }
                .p-48 { padding: calc(var(--spacing,.25rem)*48); }
                .p-56 { padding: calc(var(--spacing,.25rem)*56); }
                .p-64 { padding: calc(var(--spacing,.25rem)*64); }
                .p-80 { padding: calc(var(--spacing,.25rem)*80); }
                .p-96 { padding: calc(var(--spacing,.25rem)*96); }

                /* px – левый + правый */
                .px-0  { padding-left: 0px;                          padding-right: 0px; }
                .px-1  { padding-left: calc(var(--spacing,.25rem)*1);  padding-right: calc(var(--spacing,.25rem)*1);  }
                .px-2  { padding-left: calc(var(--spacing,.25rem)*2);  padding-right: calc(var(--spacing,.25rem)*2);  }
                .px-3  { padding-left: calc(var(--spacing,.25rem)*3);  padding-right: calc(var(--spacing,.25rem)*3);  }
                .px-4  { padding-left: calc(var(--spacing,.25rem)*4);  padding-right: calc(var(--spacing,.25rem)*4);  }
                .px-5  { padding-left: calc(var(--spacing,.25rem)*5);  padding-right: calc(var(--spacing,.25rem)*5);  }
                .px-6  { padding-left: calc(var(--spacing,.25rem)*6);  padding-right: calc(var(--spacing,.25rem)*6);  }
                .px-7  { padding-left: calc(var(--spacing,.25rem)*7);  padding-right: calc(var(--spacing,.25rem)*7);  }
                .px-8  { padding-left: calc(var(--spacing,.25rem)*8);  padding-right: calc(var(--spacing,.25rem)*8);  }
                .px-9  { padding-left: calc(var(--spacing,.25rem)*9);  padding-right: calc(var(--spacing,.25rem)*9);  }
                .px-10 { padding-left: calc(var(--spacing,.25rem)*10); padding-right: calc(var(--spacing,.25rem)*10); }
                .px-11 { padding-left: calc(var(--spacing,.25rem)*11); padding-right: calc(var(--spacing,.25rem)*11); }
                .px-12 { padding-left: calc(var(--spacing,.25rem)*12); padding-right: calc(var(--spacing,.25rem)*12); }
                .px-14 { padding-left: calc(var(--spacing,.25rem)*14); padding-right: calc(var(--spacing,.25rem)*14); }
                .px-16 { padding-left: calc(var(--spacing,.25rem)*16); padding-right: calc(var(--spacing,.25rem)*16); }
                .px-20 { padding-left: calc(var(--spacing,.25rem)*20); padding-right: calc(var(--spacing,.25rem)*20); }
                .px-24 { padding-left: calc(var(--spacing,.25rem)*24); padding-right: calc(var(--spacing,.25rem)*24); }
                .px-28 { padding-left: calc(var(--spacing,.25rem)*28); padding-right: calc(var(--spacing,.25rem)*28); }
                .px-32 { padding-left: calc(var(--spacing,.25rem)*32); padding-right: calc(var(--spacing,.25rem)*32); }
                .px-40 { padding-left: calc(var(--spacing,.25rem)*40); padding-right: calc(var(--spacing,.25rem)*40); }
                .px-48 { padding-left: calc(var(--spacing,.25rem)*48); padding-right: calc(var(--spacing,.25rem)*48); }
                .px-64 { padding-left: calc(var(--spacing,.25rem)*64); padding-right: calc(var(--spacing,.25rem)*64); }
                .px-96 { padding-left: calc(var(--spacing,.25rem)*96); padding-right: calc(var(--spacing,.25rem)*96); }

                /* py – верхний + нижний */
                .py-0  { padding-top: 0px;                          padding-bottom: 0px; }
                .py-1  { padding-top: calc(var(--spacing,.25rem)*1);  padding-bottom: calc(var(--spacing,.25rem)*1);  }
                .py-2  { padding-top: calc(var(--spacing,.25rem)*2);  padding-bottom: calc(var(--spacing,.25rem)*2);  }
                .py-3  { padding-top: calc(var(--spacing,.25rem)*3);  padding-bottom: calc(var(--spacing,.25rem)*3);  }
                .py-4  { padding-top: calc(var(--spacing,.25rem)*4);  padding-bottom: calc(var(--spacing,.25rem)*4);  }
                .py-5  { padding-top: calc(var(--spacing,.25rem)*5);  padding-bottom: calc(var(--spacing,.25rem)*5);  }
                .py-6  { padding-top: calc(var(--spacing,.25rem)*6);  padding-bottom: calc(var(--spacing,.25rem)*6);  }
                .py-7  { padding-top: calc(var(--spacing,.25rem)*7);  padding-bottom: calc(var(--spacing,.25rem)*7);  }
                .py-8  { padding-top: calc(var(--spacing,.25rem)*8);  padding-bottom: calc(var(--spacing,.25rem)*8);  }
                .py-9  { padding-top: calc(var(--spacing,.25rem)*9);  padding-bottom: calc(var(--spacing,.25rem)*9);  }
                .py-10 { padding-top: calc(var(--spacing,.25rem)*10); padding-bottom: calc(var(--spacing,.25rem)*10); }
                .py-11 { padding-top: calc(var(--spacing,.25rem)*11); padding-bottom: calc(var(--spacing,.25rem)*11); }
                .py-12 { padding-top: calc(var(--spacing,.25rem)*12); padding-bottom: calc(var(--spacing,.25rem)*12); }
                .py-14 { padding-top: calc(var(--spacing,.25rem)*14); padding-bottom: calc(var(--spacing,.25rem)*14); }
                .py-16 { padding-top: calc(var(--spacing,.25rem)*16); padding-bottom: calc(var(--spacing,.25rem)*16); }
                .py-20 { padding-top: calc(var(--spacing,.25rem)*20); padding-bottom: calc(var(--spacing,.25rem)*20); }
                .py-24 { padding-top: calc(var(--spacing,.25rem)*24); padding-bottom: calc(var(--spacing,.25rem)*24); }
                .py-28 { padding-top: calc(var(--spacing,.25rem)*28); padding-bottom: calc(var(--spacing,.25rem)*28); }
                .py-32 { padding-top: calc(var(--spacing,.25rem)*32); padding-bottom: calc(var(--spacing,.25rem)*32); }
                .py-40 { padding-top: calc(var(--spacing,.25rem)*40); padding-bottom: calc(var(--spacing,.25rem)*40); }
                .py-48 { padding-top: calc(var(--spacing,.25rem)*48); padding-bottom: calc(var(--spacing,.25rem)*48); }
                .py-64 { padding-top: calc(var(--spacing,.25rem)*64); padding-bottom: calc(var(--spacing,.25rem)*64); }
                .py-96 { padding-top: calc(var(--spacing,.25rem)*96); padding-bottom: calc(var(--spacing,.25rem)*96); }

                /* pt – top */
                .pt-0  { padding-top: 0px; }
                .pt-1  { padding-top: calc(var(--spacing,.25rem)*1);  }
                .pt-2  { padding-top: calc(var(--spacing,.25rem)*2);  }
                .pt-3  { padding-top: calc(var(--spacing,.25rem)*3);  }
                .pt-4  { padding-top: calc(var(--spacing,.25rem)*4);  }
                .pt-5  { padding-top: calc(var(--spacing,.25rem)*5);  }
                .pt-6  { padding-top: calc(var(--spacing,.25rem)*6);  }
                .pt-7  { padding-top: calc(var(--spacing,.25rem)*7);  }
                .pt-8  { padding-top: calc(var(--spacing,.25rem)*8);  }
                .pt-9  { padding-top: calc(var(--spacing,.25rem)*9);  }
                .pt-10 { padding-top: calc(var(--spacing,.25rem)*10); }
                .pt-11 { padding-top: calc(var(--spacing,.25rem)*11); }
                .pt-12 { padding-top: calc(var(--spacing,.25rem)*12); }
                .pt-14 { padding-top: calc(var(--spacing,.25rem)*14); }
                .pt-16 { padding-top: calc(var(--spacing,.25rem)*16); }
                .pt-20 { padding-top: calc(var(--spacing,.25rem)*20); }
                .pt-24 { padding-top: calc(var(--spacing,.25rem)*24); }
                .pt-28 { padding-top: calc(var(--spacing,.25rem)*28); }
                .pt-32 { padding-top: calc(var(--spacing,.25rem)*32); }
                .pt-40 { padding-top: calc(var(--spacing,.25rem)*40); }
                .pt-48 { padding-top: calc(var(--spacing,.25rem)*48); }
                .pt-64 { padding-top: calc(var(--spacing,.25rem)*64); }
                .pt-96 { padding-top: calc(var(--spacing,.25rem)*96); }

                /* pr – right */
                .pr-0  { padding-right: 0px; }
                .pr-1  { padding-right: calc(var(--spacing,.25rem)*1);  }
                .pr-2  { padding-right: calc(var(--spacing,.25rem)*2);  }
                .pr-3  { padding-right: calc(var(--spacing,.25rem)*3);  }
                .pr-4  { padding-right: calc(var(--spacing,.25rem)*4);  }
                .pr-5  { padding-right: calc(var(--spacing,.25rem)*5);  }
                .pr-6  { padding-right: calc(var(--spacing,.25rem)*6);  }
                .pr-7  { padding-right: calc(var(--spacing,.25rem)*7);  }
                .pr-8  { padding-right: calc(var(--spacing,.25rem)*8);  }
                .pr-9  { padding-right: calc(var(--spacing,.25rem)*9);  }
                .pr-10 { padding-right: calc(var(--spacing,.25rem)*10); }
                .pr-11 { padding-right: calc(var(--spacing,.25rem)*11); }
                .pr-12 { padding-right: calc(var(--spacing,.25rem)*12); }
                .pr-14 { padding-right: calc(var(--spacing,.25rem)*14); }
                .pr-16 { padding-right: calc(var(--spacing,.25rem)*16); }
                .pr-20 { padding-right: calc(var(--spacing,.25rem)*20); }
                .pr-24 { padding-right: calc(var(--spacing,.25rem)*24); }
                .pr-28 { padding-right: calc(var(--spacing,.25rem)*28); }
                .pr-32 { padding-right: calc(var(--spacing,.25rem)*32); }
                .pr-40 { padding-right: calc(var(--spacing,.25rem)*40); }
                .pr-48 { padding-right: calc(var(--spacing,.25rem)*48); }
                .pr-64 { padding-right: calc(var(--spacing,.25rem)*64); }
                .pr-96 { padding-right: calc(var(--spacing,.25rem)*96); }

                /* pb – bottom */
                .pb-0  { padding-bottom: 0px; }
                .pb-1  { padding-bottom: calc(var(--spacing,.25rem)*1);  }
                .pb-2  { padding-bottom: calc(var(--spacing,.25rem)*2);  }
                .pb-3  { padding-bottom: calc(var(--spacing,.25rem)*3);  }
                .pb-4  { padding-bottom: calc(var(--spacing,.25rem)*4);  }
                .pb-5  { padding-bottom: calc(var(--spacing,.25rem)*5);  }
                .pb-6  { padding-bottom: calc(var(--spacing,.25rem)*6);  }
                .pb-7  { padding-bottom: calc(var(--spacing,.25rem)*7);  }
                .pb-8  { padding-bottom: calc(var(--spacing,.25rem)*8);  }
                .pb-9  { padding-bottom: calc(var(--spacing,.25rem)*9);  }
                .pb-10 { padding-bottom: calc(var(--spacing,.25rem)*10); }
                .pb-11 { padding-bottom: calc(var(--spacing,.25rem)*11); }
                .pb-12 { padding-bottom: calc(var(--spacing,.25rem)*12); }
                .pb-14 { padding-bottom: calc(var(--spacing,.25rem)*14); }
                .pb-16 { padding-bottom: calc(var(--spacing,.25rem)*16); }
                .pb-20 { padding-bottom: calc(var(--spacing,.25rem)*20); }
                .pb-24 { padding-bottom: calc(var(--spacing,.25rem)*24); }
                .pb-28 { padding-bottom: calc(var(--spacing,.25rem)*28); }
                .pb-32 { padding-bottom: calc(var(--spacing,.25rem)*32); }
                .pb-40 { padding-bottom: calc(var(--spacing,.25rem)*40); }
                .pb-48 { padding-bottom: calc(var(--spacing,.25rem)*48); }
                .pb-64 { padding-bottom: calc(var(--spacing,.25rem)*64); }
                .pb-96 { padding-bottom: calc(var(--spacing,.25rem)*96); }

                /* pl – left */
                .pl-0  { padding-left: 0px; }
                .pl-1  { padding-left: calc(var(--spacing,.25rem)*1);  }
                .pl-2  { padding-left: calc(var(--spacing,.25rem)*2);  }
                .pl-3  { padding-left: calc(var(--spacing,.25rem)*3);  }
                .pl-4  { padding-left: calc(var(--spacing,.25rem)*4);  }
                .pl-5  { padding-left: calc(var(--spacing,.25rem)*5);  }
                .pl-6  { padding-left: calc(var(--spacing,.25rem)*6);  }
                .pl-7  { padding-left: calc(var(--spacing,.25rem)*7);  }
                .pl-8  { padding-left: calc(var(--spacing,.25rem)*8);  }
                .pl-9  { padding-left: calc(var(--spacing,.25rem)*9);  }
                .pl-10 { padding-left: calc(var(--spacing,.25rem)*10); }
                .pl-11 { padding-left: calc(var(--spacing,.25rem)*11); }
                .pl-12 { padding-left: calc(var(--spacing,.25rem)*12); }
                .pl-14 { padding-left: calc(var(--spacing,.25rem)*14); }
                .pl-16 { padding-left: calc(var(--spacing,.25rem)*16); }
                .pl-20 { padding-left: calc(var(--spacing,.25rem)*20); }
                .pl-24 { padding-left: calc(var(--spacing,.25rem)*24); }
                .pl-28 { padding-left: calc(var(--spacing,.25rem)*28); }
                .pl-32 { padding-left: calc(var(--spacing,.25rem)*32); }
                .pl-40 { padding-left: calc(var(--spacing,.25rem)*40); }
                .pl-48 { padding-left: calc(var(--spacing,.25rem)*48); }
                .pl-64 { padding-left: calc(var(--spacing,.25rem)*64); }
                .pl-96 { padding-left: calc(var(--spacing,.25rem)*96); }

                /* ── MARGIN (все направления, шкала Tailwind) ────────────────────── */
                /* m – все стороны */
                .m-auto { margin: auto; }
                .m-0  { margin: 0px; }
                .m-1  { margin: calc(var(--spacing,.25rem)*1);  }
                .m-2  { margin: calc(var(--spacing,.25rem)*2);  }
                .m-3  { margin: calc(var(--spacing,.25rem)*3);  }
                .m-4  { margin: calc(var(--spacing,.25rem)*4);  }
                .m-5  { margin: calc(var(--spacing,.25rem)*5);  }
                .m-6  { margin: calc(var(--spacing,.25rem)*6);  }
                .m-7  { margin: calc(var(--spacing,.25rem)*7);  }
                .m-8  { margin: calc(var(--spacing,.25rem)*8);  }
                .m-9  { margin: calc(var(--spacing,.25rem)*9);  }
                .m-10 { margin: calc(var(--spacing,.25rem)*10); }
                .m-11 { margin: calc(var(--spacing,.25rem)*11); }
                .m-12 { margin: calc(var(--spacing,.25rem)*12); }
                .m-14 { margin: calc(var(--spacing,.25rem)*14); }
                .m-16 { margin: calc(var(--spacing,.25rem)*16); }
                .m-20 { margin: calc(var(--spacing,.25rem)*20); }
                .m-24 { margin: calc(var(--spacing,.25rem)*24); }
                .m-28 { margin: calc(var(--spacing,.25rem)*28); }
                .m-32 { margin: calc(var(--spacing,.25rem)*32); }
                .m-36 { margin: calc(var(--spacing,.25rem)*36); }
                .m-40 { margin: calc(var(--spacing,.25rem)*40); }
                .m-48 { margin: calc(var(--spacing,.25rem)*48); }
                .m-56 { margin: calc(var(--spacing,.25rem)*56); }
                .m-64 { margin: calc(var(--spacing,.25rem)*64); }
                .m-80 { margin: calc(var(--spacing,.25rem)*80); }
                .m-96 { margin: calc(var(--spacing,.25rem)*96); }

                /* mx – левый + правый */
                .mx-auto { margin-left: auto; margin-right: auto; }
                .mx-0  { margin-left: 0px;                          margin-right: 0px; }
                .mx-1  { margin-left: calc(var(--spacing,.25rem)*1);  margin-right: calc(var(--spacing,.25rem)*1);  }
                .mx-2  { margin-left: calc(var(--spacing,.25rem)*2);  margin-right: calc(var(--spacing,.25rem)*2);  }
                .mx-3  { margin-left: calc(var(--spacing,.25rem)*3);  margin-right: calc(var(--spacing,.25rem)*3);  }
                .mx-4  { margin-left: calc(var(--spacing,.25rem)*4);  margin-right: calc(var(--spacing,.25rem)*4);  }
                .mx-5  { margin-left: calc(var(--spacing,.25rem)*5);  margin-right: calc(var(--spacing,.25rem)*5);  }
                .mx-6  { margin-left: calc(var(--spacing,.25rem)*6);  margin-right: calc(var(--spacing,.25rem)*6);  }
                .mx-7  { margin-left: calc(var(--spacing,.25rem)*7);  margin-right: calc(var(--spacing,.25rem)*7);  }
                .mx-8  { margin-left: calc(var(--spacing,.25rem)*8);  margin-right: calc(var(--spacing,.25rem)*8);  }
                .mx-9  { margin-left: calc(var(--spacing,.25rem)*9);  margin-right: calc(var(--spacing,.25rem)*9);  }
                .mx-10 { margin-left: calc(var(--spacing,.25rem)*10); margin-right: calc(var(--spacing,.25rem)*10); }
                .mx-11 { margin-left: calc(var(--spacing,.25rem)*11); margin-right: calc(var(--spacing,.25rem)*11); }
                .mx-12 { margin-left: calc(var(--spacing,.25rem)*12); margin-right: calc(var(--spacing,.25rem)*12); }
                .mx-14 { margin-left: calc(var(--spacing,.25rem)*14); margin-right: calc(var(--spacing,.25rem)*14); }
                .mx-16 { margin-left: calc(var(--spacing,.25rem)*16); margin-right: calc(var(--spacing,.25rem)*16); }
                .mx-20 { margin-left: calc(var(--spacing,.25rem)*20); margin-right: calc(var(--spacing,.25rem)*20); }
                .mx-24 { margin-left: calc(var(--spacing,.25rem)*24); margin-right: calc(var(--spacing,.25rem)*24); }
                .mx-28 { margin-left: calc(var(--spacing,.25rem)*28); margin-right: calc(var(--spacing,.25rem)*28); }
                .mx-32 { margin-left: calc(var(--spacing,.25rem)*32); margin-right: calc(var(--spacing,.25rem)*32); }
                .mx-40 { margin-left: calc(var(--spacing,.25rem)*40); margin-right: calc(var(--spacing,.25rem)*40); }
                .mx-48 { margin-left: calc(var(--spacing,.25rem)*48); margin-right: calc(var(--spacing,.25rem)*48); }
                .mx-64 { margin-left: calc(var(--spacing,.25rem)*64); margin-right: calc(var(--spacing,.25rem)*64); }
                .mx-96 { margin-left: calc(var(--spacing,.25rem)*96); margin-right: calc(var(--spacing,.25rem)*96); }

                /* my – верхний + нижний */
                .my-auto { margin-top: auto; margin-bottom: auto; }
                .my-0  { margin-top: 0px;                          margin-bottom: 0px; }
                .my-1  { margin-top: calc(var(--spacing,.25rem)*1);  margin-bottom: calc(var(--spacing,.25rem)*1);  }
                .my-2  { margin-top: calc(var(--spacing,.25rem)*2);  margin-bottom: calc(var(--spacing,.25rem)*2);  }
                .my-3  { margin-top: calc(var(--spacing,.25rem)*3);  margin-bottom: calc(var(--spacing,.25rem)*3);  }
                .my-4  { margin-top: calc(var(--spacing,.25rem)*4);  margin-bottom: calc(var(--spacing,.25rem)*4);  }
                .my-5  { margin-top: calc(var(--spacing,.25rem)*5);  margin-bottom: calc(var(--spacing,.25rem)*5);  }
                .my-6  { margin-top: calc(var(--spacing,.25rem)*6);  margin-bottom: calc(var(--spacing,.25rem)*6);  }
                .my-7  { margin-top: calc(var(--spacing,.25rem)*7);  margin-bottom: calc(var(--spacing,.25rem)*7);  }
                .my-8  { margin-top: calc(var(--spacing,.25rem)*8);  margin-bottom: calc(var(--spacing,.25rem)*8);  }
                .my-9  { margin-top: calc(var(--spacing,.25rem)*9);  margin-bottom: calc(var(--spacing,.25rem)*9);  }
                .my-10 { margin-top: calc(var(--spacing,.25rem)*10); margin-bottom: calc(var(--spacing,.25rem)*10); }
                .my-11 { margin-top: calc(var(--spacing,.25rem)*11); margin-bottom: calc(var(--spacing,.25rem)*11); }
                .my-12 { margin-top: calc(var(--spacing,.25rem)*12); margin-bottom: calc(var(--spacing,.25rem)*12); }
                .my-14 { margin-top: calc(var(--spacing,.25rem)*14); margin-bottom: calc(var(--spacing,.25rem)*14); }
                .my-16 { margin-top: calc(var(--spacing,.25rem)*16); margin-bottom: calc(var(--spacing,.25rem)*16); }
                .my-20 { margin-top: calc(var(--spacing,.25rem)*20); margin-bottom: calc(var(--spacing,.25rem)*20); }
                .my-24 { margin-top: calc(var(--spacing,.25rem)*24); margin-bottom: calc(var(--spacing,.25rem)*24); }
                .my-28 { margin-top: calc(var(--spacing,.25rem)*28); margin-bottom: calc(var(--spacing,.25rem)*28); }
                .my-32 { margin-top: calc(var(--spacing,.25rem)*32); margin-bottom: calc(var(--spacing,.25rem)*32); }
                .my-40 { margin-top: calc(var(--spacing,.25rem)*40); margin-bottom: calc(var(--spacing,.25rem)*40); }
                .my-48 { margin-top: calc(var(--spacing,.25rem)*48); margin-bottom: calc(var(--spacing,.25rem)*48); }
                .my-64 { margin-top: calc(var(--spacing,.25rem)*64); margin-bottom: calc(var(--spacing,.25rem)*64); }
                .my-96 { margin-top: calc(var(--spacing,.25rem)*96); margin-bottom: calc(var(--spacing,.25rem)*96); }

                /* mt – top */
                .mt-auto { margin-top: auto; }
                .mt-0  { margin-top: 0px; }
                .mt-1  { margin-top: calc(var(--spacing,.25rem)*1);  }
                .mt-2  { margin-top: calc(var(--spacing,.25rem)*2);  }
                .mt-3  { margin-top: calc(var(--spacing,.25rem)*3);  }
                .mt-4  { margin-top: calc(var(--spacing,.25rem)*4);  }
                .mt-5  { margin-top: calc(var(--spacing,.25rem)*5);  }
                .mt-6  { margin-top: calc(var(--spacing,.25rem)*6);  }
                .mt-7  { margin-top: calc(var(--spacing,.25rem)*7);  }
                .mt-8  { margin-top: calc(var(--spacing,.25rem)*8);  }
                .mt-9  { margin-top: calc(var(--spacing,.25rem)*9);  }
                .mt-10 { margin-top: calc(var(--spacing,.25rem)*10); }
                .mt-11 { margin-top: calc(var(--spacing,.25rem)*11); }
                .mt-12 { margin-top: calc(var(--spacing,.25rem)*12); }
                .mt-14 { margin-top: calc(var(--spacing,.25rem)*14); }
                .mt-16 { margin-top: calc(var(--spacing,.25rem)*16); }
                .mt-20 { margin-top: calc(var(--spacing,.25rem)*20); }
                .mt-24 { margin-top: calc(var(--spacing,.25rem)*24); }
                .mt-28 { margin-top: calc(var(--spacing,.25rem)*28); }
                .mt-32 { margin-top: calc(var(--spacing,.25rem)*32); }
                .mt-40 { margin-top: calc(var(--spacing,.25rem)*40); }
                .mt-48 { margin-top: calc(var(--spacing,.25rem)*48); }
                .mt-64 { margin-top: calc(var(--spacing,.25rem)*64); }
                .mt-96 { margin-top: calc(var(--spacing,.25rem)*96); }

                /* mr – right */
                .mr-auto { margin-right: auto; }
                .mr-0  { margin-right: 0px; }
                .mr-1  { margin-right: calc(var(--spacing,.25rem)*1);  }
                .mr-2  { margin-right: calc(var(--spacing,.25rem)*2);  }
                .mr-3  { margin-right: calc(var(--spacing,.25rem)*3);  }
                .mr-4  { margin-right: calc(var(--spacing,.25rem)*4);  }
                .mr-5  { margin-right: calc(var(--spacing,.25rem)*5);  }
                .mr-6  { margin-right: calc(var(--spacing,.25rem)*6);  }
                .mr-7  { margin-right: calc(var(--spacing,.25rem)*7);  }
                .mr-8  { margin-right: calc(var(--spacing,.25rem)*8);  }
                .mr-9  { margin-right: calc(var(--spacing,.25rem)*9);  }
                .mr-10 { margin-right: calc(var(--spacing,.25rem)*10); }
                .mr-11 { margin-right: calc(var(--spacing,.25rem)*11); }
                .mr-12 { margin-right: calc(var(--spacing,.25rem)*12); }
                .mr-14 { margin-right: calc(var(--spacing,.25rem)*14); }
                .mr-16 { margin-right: calc(var(--spacing,.25rem)*16); }
                .mr-20 { margin-right: calc(var(--spacing,.25rem)*20); }
                .mr-24 { margin-right: calc(var(--spacing,.25rem)*24); }
                .mr-28 { margin-right: calc(var(--spacing,.25rem)*28); }
                .mr-32 { margin-right: calc(var(--spacing,.25rem)*32); }
                .mr-40 { margin-right: calc(var(--spacing,.25rem)*40); }
                .mr-48 { margin-right: calc(var(--spacing,.25rem)*48); }
                .mr-64 { margin-right: calc(var(--spacing,.25rem)*64); }
                .mr-96 { margin-right: calc(var(--spacing,.25rem)*96); }

                /* mb – bottom */
                .mb-auto { margin-bottom: auto; }
                .mb-0  { margin-bottom: 0px; }
                .mb-1  { margin-bottom: calc(var(--spacing,.25rem)*1);  }
                .mb-2  { margin-bottom: calc(var(--spacing,.25rem)*2);  }
                .mb-3  { margin-bottom: calc(var(--spacing,.25rem)*3);  }
                .mb-4  { margin-bottom: calc(var(--spacing,.25rem)*4);  }
                .mb-5  { margin-bottom: calc(var(--spacing,.25rem)*5);  }
                .mb-6  { margin-bottom: calc(var(--spacing,.25rem)*6);  }
                .mb-7  { margin-bottom: calc(var(--spacing,.25rem)*7);  }
                .mb-8  { margin-bottom: calc(var(--spacing,.25rem)*8);  }
                .mb-9  { margin-bottom: calc(var(--spacing,.25rem)*9);  }
                .mb-10 { margin-bottom: calc(var(--spacing,.25rem)*10); }
                .mb-11 { margin-bottom: calc(var(--spacing,.25rem)*11); }
                .mb-12 { margin-bottom: calc(var(--spacing,.25rem)*12); }
                .mb-14 { margin-bottom: calc(var(--spacing,.25rem)*14); }
                .mb-16 { margin-bottom: calc(var(--spacing,.25rem)*16); }
                .mb-20 { margin-bottom: calc(var(--spacing,.25rem)*20); }
                .mb-24 { margin-bottom: calc(var(--spacing,.25rem)*24); }
                .mb-28 { margin-bottom: calc(var(--spacing,.25rem)*28); }
                .mb-32 { margin-bottom: calc(var(--spacing,.25rem)*32); }
                .mb-40 { margin-bottom: calc(var(--spacing,.25rem)*40); }
                .mb-48 { margin-bottom: calc(var(--spacing,.25rem)*48); }
                .mb-64 { margin-bottom: calc(var(--spacing,.25rem)*64); }
                .mb-96 { margin-bottom: calc(var(--spacing,.25rem)*96); }

                /* ml – left */
                .ml-auto { margin-left: auto; }
                .ml-0  { margin-left: 0px; }
                .ml-1  { margin-left: calc(var(--spacing,.25rem)*1);  }
                .ml-2  { margin-left: calc(var(--spacing,.25rem)*2);  }
                .ml-3  { margin-left: calc(var(--spacing,.25rem)*3);  }
                .ml-4  { margin-left: calc(var(--spacing,.25rem)*4);  }
                .ml-5  { margin-left: calc(var(--spacing,.25rem)*5);  }
                .ml-6  { margin-left: calc(var(--spacing,.25rem)*6);  }
                .ml-7  { margin-left: calc(var(--spacing,.25rem)*7);  }
                .ml-8  { margin-left: calc(var(--spacing,.25rem)*8);  }
                .ml-9  { margin-left: calc(var(--spacing,.25rem)*9);  }
                .ml-10 { margin-left: calc(var(--spacing,.25rem)*10); }
                .ml-11 { margin-left: calc(var(--spacing,.25rem)*11); }
                .ml-12 { margin-left: calc(var(--spacing,.25rem)*12); }
                .ml-14 { margin-left: calc(var(--spacing,.25rem)*14); }
                .ml-16 { margin-left: calc(var(--spacing,.25rem)*16); }
                .ml-20 { margin-left: calc(var(--spacing,.25rem)*20); }
                .ml-24 { margin-left: calc(var(--spacing,.25rem)*24); }
                .ml-28 { margin-left: calc(var(--spacing,.25rem)*28); }
                .ml-32 { margin-left: calc(var(--spacing,.25rem)*32); }
                .ml-40 { margin-left: calc(var(--spacing,.25rem)*40); }
                .ml-48 { margin-left: calc(var(--spacing,.25rem)*48); }
                .ml-64 { margin-left: calc(var(--spacing,.25rem)*64); }
                .ml-96 { margin-left: calc(var(--spacing,.25rem)*96); }

            CSS),
        ];
    }

    protected function menu(): array
    {
        return [
            ...parent::menu(),

            MenuGroup::make('Управление проектами', [
                MenuItem::make(ProjectResource::class),
                MenuItem::make(TaskResource::class),
                MenuItem::make(StageResource::class),
                MenuItem::make(TagResource::class),
            ]),

            MenuItem::make(DocumentResource::class),
            MenuItem::make(CommentResource::class, 'Comments'),
        ];
    }

    /**
     * Настройка цветовой схемы MoonShine.
     *
     * ColorManager генерирует CSS-переменные вида --ms-cm-{name} и --ms-cm-{name}-{shade}.
     * Массив с ключом 'default' + числовые ключи шейдов → набор шейдованных переменных.
     * Это правильный способ добавить шейды к семантическим цветам с поддержкой dark mode.
     *
     * Пример добавления шейдов к primary:
     *   $colorManager->set('primary', [
     *       'default' => '0.58 0.24 293.756',   // --ms-cm-primary
     *       50  => '0.95 0.06 293.756',          // --ms-cm-primary-50
     *       100 => '0.90 0.10 293.756',          // --ms-cm-primary-100
     *       ...
     *       900 => '0.30 0.22 293.756',          // --ms-cm-primary-900
     *   ]);
     *
     * @param ColorManager $colorManager
     */
    protected function colors(ColorManagerContract $colorManager): void
    {
        parent::colors($colorManager);

        // $colorManager->primary('0.58 0.24 293.756');
    }
}
