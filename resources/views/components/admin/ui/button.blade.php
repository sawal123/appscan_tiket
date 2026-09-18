@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'target' => null,
])

@php
    $sizes = [
        'sm' => 'min-h-9 px-3 text-xs',
        'md' => 'min-h-11 px-4 text-sm',
        'lg' => 'min-h-12 px-3 text-sm',
        'icon' => 'size-9 min-h-9 p-0 text-xs',
    ];

    $variants = [
        'primary' => 'bg-blue-600 text-white shadow-sm shadow-blue-600/20 hover:bg-blue-700',
        'outline' => 'border border-slate-200 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800',
        'success' => 'border border-emerald-200 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-500/30 dark:text-emerald-300 dark:hover:bg-emerald-500/10',
        'danger' => 'border border-red-200 text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:text-red-400 dark:hover:bg-red-500/10',
        'tile' => 'border border-slate-200 bg-white hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:bg-slate-800 dark:hover:border-blue-500 dark:hover:bg-blue-500/10',
    ];

    // Livewire only reads wire:target from the element itself, so the loading
    // attributes live on the button: wire:click buttons scope themselves, while
    // form submits need an explicit target.
    $triggersRequest = $attributes->has('wire:click') || $target !== null || $type === 'submit';

    $loadingAttributes = [];

    if ($triggersRequest) {
        $loadingAttributes = [
            'wire:loading.attr' => 'disabled',
            'wire:loading.class' => 'is-loading cursor-wait opacity-70',
        ];
    }

    if ($target !== null) {
        $loadingAttributes['wire:target'] = $target;
    }

    $classes = trim('admin-button inline-flex items-center justify-center gap-2 rounded-lg font-bold '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']).($size === 'icon' ? ' is-icon' : ''));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge($loadingAttributes)->merge(['type' => $type, 'class' => $classes]) }}>
        @if ($triggersRequest)
            <span class="admin-button-spinner shrink-0" aria-hidden="true">
                <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z" />
                </svg>
            </span>
        @endif

        <span class="admin-button-content contents">{{ $slot }}</span>
    </button>
@endif
