@props([
    'label' => null,
    'hint' => null,
    'field' => null,
    'labelClass' => 'block text-sm font-bold',
    'errorTestId' => null,
    'flush' => false,
    'wrapper' => null,
])

@php
    $id = $attributes->get('id');
    $model = $attributes->whereStartsWith('wire:model')->first();
    $errorKey = $field ?? ($model ? str($model)->before('.')->toString() : null);
    $selectTestId = $attributes->get('data-testid');
    $resolvedErrorTestId = $errorTestId ?? ($selectTestId ? str_replace('-input', '-error', $selectTestId) : null);

    $classes = 'w-full min-h-11 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-950 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/15 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:bg-slate-800/70 dark:text-white dark:focus:border-blue-500 dark:focus:bg-slate-900 dark:focus:ring-blue-500/20';

    if (! $flush) {
        $classes = 'mt-1.5 '.$classes;
    }
@endphp

<div @class([$wrapper])>
    @if ($label)
        <label @if ($id) for="{{ $id }}" @endif class="{{ $labelClass }}">{{ $label }}@if ($hint) <span class="font-medium text-slate-400">{{ $hint }}</span>@endif</label>
    @endif

    <select {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </select>

    @if ($errorKey && $errors->has($errorKey))
        <p @if ($resolvedErrorTestId) data-testid="{{ $resolvedErrorTestId }}" @endif class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $errors->first($errorKey) }}</p>
    @endif
</div>
