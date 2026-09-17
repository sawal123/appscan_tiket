@props([
    'title',
    'value',
    'subtitle' => null,
    'icon' => 'qr',
    'accent' => 'blue',
    'testId' => null,
])

@php
    $accentClasses = [
        'blue' => [
            'bar' => 'bg-blue-500',
            'subtitle' => 'text-blue-600 dark:text-blue-400',
            'icon' => 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300',
        ],
        'emerald' => [
            'bar' => 'bg-emerald-500',
            'subtitle' => 'text-emerald-600 dark:text-emerald-400',
            'icon' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
        ],
        'amber' => [
            'bar' => 'bg-amber-500',
            'subtitle' => 'text-amber-600 dark:text-amber-400',
            'icon' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
        ],
        'indigo' => [
            'bar' => 'bg-indigo-500',
            'subtitle' => 'text-indigo-600 dark:text-indigo-400',
            'icon' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-300',
        ],
    ][$accent] ?? [
        'bar' => 'bg-blue-500',
        'subtitle' => 'text-blue-600 dark:text-blue-400',
        'icon' => 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300',
    ];
@endphp

<article @if ($testId) data-testid="{{ $testId }}" @endif class="relative overflow-hidden rounded-lg border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/40 sm:p-5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
    <div class="absolute inset-y-0 left-0 w-1 {{ $accentClasses['bar'] }}"></div>
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 sm:text-xs dark:text-slate-400">{{ $title }}</p>
            <p class="mt-2 text-2xl font-extrabold text-slate-950 sm:text-3xl dark:text-white">{!! $value !!}</p>
            @if ($subtitle)
                <p class="mt-1 text-xs font-semibold {{ $accentClasses['subtitle'] }}">{{ $subtitle }}</p>
            @endif
        </div>
        <span class="grid size-10 shrink-0 place-items-center rounded-lg {{ $accentClasses['icon'] }}">
            @switch($icon)
                @case('verified')
                    <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg>
                    @break

                @case('clock')
                    <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    @break

                @case('scanner')
                    <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h3m10 0h3v3M4 17v3h3m10 0h3v-3M7 12h10"/></svg>
                    @break

                @default
                    <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM18 18h3v3h-3z"/></svg>
            @endswitch
        </span>
    </div>
</article>
