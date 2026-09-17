@props([
    'as' => 'section',
    'padded' => false,
    'overflow' => false,
])

@php
    $classes = 'rounded-lg border border-slate-200 bg-white shadow-sm shadow-slate-200/40 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none';

    if ($overflow) {
        $classes .= ' overflow-hidden';
    }

    if ($padded) {
        $classes .= ' p-5 sm:p-6';
    }
@endphp

<{{ $as }} {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</{{ $as }}>
