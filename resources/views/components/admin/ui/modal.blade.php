@props([
    'title' => null,
    'subtitle' => null,
    'id' => 'admin-modal',
    'show' => 'showModal',
    'closeAction' => 'closeModal',
    'titleTestId' => null,
])

@php
    $overlayClasses = 'fixed inset-0 z-50 flex items-end justify-center bg-slate-950/45 backdrop-blur-[2px] sm:items-center sm:p-4';
    $panelClasses = 'w-full max-w-lg rounded-t-xl border border-slate-200 bg-white p-5 shadow-xl sm:rounded-lg sm:p-6 dark:border-slate-800 dark:bg-slate-900';
    $closeClasses = 'grid size-9 shrink-0 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800';
@endphp

<div
    x-data
    x-cloak
    x-show="$wire.{{ $show }}"
    x-transition.opacity
    class="{{ $overlayClasses }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $id }}-title"
    {{ $attributes }}
>
    <div class="{{ $panelClasses }}" x-on:click.outside="$wire.{{ $closeAction }}()">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 id="{{ $id }}-title" @if ($titleTestId) data-testid="{{ $titleTestId }}" @endif class="text-lg font-extrabold">{{ $title }}</h2>
                @if ($subtitle)
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
                @endif
            </div>
            <button type="button" wire:click="{{ $closeAction }}" class="{{ $closeClasses }}" aria-label="Tutup">
                <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18 6-12 12M6 6l12 12"/></svg>
            </button>
        </div>

        {{ $slot }}
    </div>
</div>
