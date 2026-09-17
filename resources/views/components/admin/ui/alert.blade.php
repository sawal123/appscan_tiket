@props([
    'variant' => 'danger',
])

@php
    $variants = [
        'danger' => 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300',
        'info' => 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300',
    ];

    $classes = 'rounded-lg border px-4 py-3 text-sm font-semibold '.($variants[$variant] ?? $variants['danger']);
@endphp

<div role="alert" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</div>
