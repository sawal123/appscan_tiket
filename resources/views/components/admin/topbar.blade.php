@props([
    'title' => 'Dashboard',
    'subtitle' => 'Ringkasan aktivitas event hari ini',
])

@php
    $user = auth()->user();
    $adminName = $user?->name ?: 'Administrator';
    $initials = $user && method_exists($user, 'initials') ? $user->initials() : 'AD';
@endphp

<header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/95 dark:border-slate-800 dark:bg-slate-900/95">
    <div class="flex min-h-[88px] items-center gap-4 px-4 sm:px-6 xl:px-8">
        <button data-testid="mobile-sidebar-open-button" type="button" class="grid size-11 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-slate-700 lg:hidden dark:border-slate-700 dark:bg-slate-800 dark:text-white" aria-label="Buka menu" x-bind:aria-expanded="sidebarOpen.toString()" x-on:click="sidebarOpen = true">
            <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <div class="min-w-0 flex-1">
            <h1 data-testid="dashboard-title" class="truncate text-xl font-extrabold text-slate-950 sm:text-2xl dark:text-white">{{ $title }}</h1>
            <p data-testid="dashboard-subtitle" class="hidden text-sm text-slate-500 sm:block dark:text-slate-400">{{ $subtitle }}</p>
        </div>

        <div class="flex items-center gap-2 sm:gap-4">
            <div data-testid="active-event-header" class="hidden items-center gap-3 border-r border-slate-200 pr-4 md:flex dark:border-slate-700">
                <span class="relative flex size-2.5"><span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-60"></span><span class="relative inline-flex size-2.5 rounded-full bg-emerald-500"></span></span>
                <span>
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Event Aktif</span>
                    <span class="block text-sm font-bold">Festival ABC 2026</span>
                </span>
            </div>

            <button data-testid="theme-toggle-button" type="button" class="grid size-11 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:border-blue-300 hover:text-blue-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300" aria-label="Ubah tema" title="Ubah tema" x-on:click="toggleTheme()">
                <svg aria-hidden="true" class="hidden size-5 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                <svg aria-hidden="true" class="size-5 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
            </button>

            <div data-testid="header-admin-profile" class="flex items-center gap-3">
                <span class="hidden text-right sm:block">
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Admin</span>
                    <span class="block text-sm font-bold">{{ $adminName }}</span>
                </span>
                <span class="grid size-11 place-items-center rounded-full bg-blue-100 text-sm font-extrabold text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">{{ $initials }}</span>
            </div>
        </div>
    </div>
</header>
