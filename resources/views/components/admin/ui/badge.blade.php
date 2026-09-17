@props([
    'variant' => 'slate',
    'size' => 'md',
    'weight' => 'bold',
    'shape' => 'pill',
    'dot' => false,
    'dotClass' => 'bg-current',
])

@php
    $sizes = [
        'xs' => 'px-2 py-1 text-[10px]',
        'sm' => 'px-2 py-1 text-[11px]',
        'md' => 'px-2.5 py-1 text-xs',
    ];

    $weights = [
        'bold' => 'font-bold',
        'extrabold' => 'font-extrabold',
    ];

    $shapes = [
        'pill' => 'rounded-full',
        'tag' => 'rounded',
    ];

    $variants = [
        'slate' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        'slate-soft' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
        'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        'blue' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
        'amber' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        'red' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300',
        'emerald-solid' => 'bg-emerald-500/15 text-emerald-300',
    ];

    $classes = trim('inline-flex items-center gap-1.5 '.($shapes[$shape] ?? $shapes['pill']).' '.($sizes[$size] ?? $sizes['md']).' '.($weights[$weight] ?? $weights['bold']).' '.($variants[$variant] ?? $variants['slate']));
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if ($dot)
        <span class="size-1.5 rounded-full {{ $dotClass }}"></span>
    @endif
    {{ $slot }}
</span>
