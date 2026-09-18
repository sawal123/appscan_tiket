@php
    $user = auth()->user();
    $adminName = $user?->name ?: 'Administrator';
    $initials = $user && method_exists($user, 'initials') ? $user->initials() : 'AD';

    $navigation = [
        [
            'label' => 'Dashboard',
            'href' => route('admin.dashboard'),
            'active' => request()->routeIs('admin.dashboard'),
            'testId' => 'nav-dashboard-link',
            'icon' => 'dashboard',
        ],
        [
            'label' => 'Event',
            'href' => route('admin.events'),
            'active' => request()->routeIs('admin.events'),
            'testId' => 'nav-event-link',
            'icon' => 'event',
        ],
        [
            'label' => 'Registrasi QR',
            'href' => route('admin.tickets'),
            'active' => request()->routeIs('admin.tickets'),
            'testId' => 'nav-qr-registration-link',
            'icon' => 'registration',
        ],
        [
            'label' => 'Tiket',
            'href' => route('admin.tickets'),
            'active' => request()->routeIs('admin.tickets'),
            'testId' => 'nav-tickets-link',
            'icon' => 'tickets',
        ],
        [
            'label' => 'Kategori Tiket',
            'href' => route('admin.ticket-categories'),
            'active' => request()->routeIs('admin.ticket-categories'),
            'testId' => 'nav-ticket-categories-link',
            'icon' => 'categories',
        ],
        ['label' => 'User Scanner', 'href' => '#scanner', 'testId' => 'nav-scanner-users-link', 'icon' => 'scanner'],
        ['label' => 'Riwayat Verifikasi', 'href' => '#riwayat', 'testId' => 'nav-verification-history-link', 'icon' => 'history'],
        ['label' => 'Pengaturan', 'href' => '#pengaturan', 'testId' => 'nav-settings-link', 'icon' => 'settings'],
    ];
@endphp

<aside
    data-sidebar
    data-testid="admin-sidebar"
    class="fixed inset-y-0 left-0 z-50 flex w-[264px] -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-[220ms] ease-out lg:translate-x-0 dark:border-slate-800 dark:bg-slate-900"
    x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    aria-label="Navigasi utama"
>
    <div class="flex h-[88px] items-center justify-between border-b border-slate-100 px-5 dark:border-slate-800">
        <a href="{{ route('admin.dashboard') }}" data-testid="brand-home-link" class="flex min-w-0 items-center gap-3" aria-label="Gateflow Dashboard">
            <span class="grid size-11 shrink-0 place-items-center rounded-lg bg-blue-600 text-white shadow-sm shadow-blue-600/20">
                <svg aria-hidden="true" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM18 18h3v3h-3zM18 14h3M14 18v3"/></svg>
            </span>
            <span class="min-w-0">
                <span data-testid="application-name" class="block text-base font-extrabold text-slate-950 dark:text-white">Gateflow</span>
                <span data-testid="active-event-sidebar" class="block truncate text-xs font-medium text-slate-500 dark:text-slate-400">Festival ABC 2026</span>
            </span>
        </a>

        <button data-testid="mobile-sidebar-close-button" type="button" class="grid size-10 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900 lg:hidden dark:hover:bg-slate-800 dark:hover:text-white" aria-label="Tutup menu" x-on:click="sidebarOpen = false">
            <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18 6-12 12M6 6l12 12"/></svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-5" data-testid="primary-navigation">
        <p class="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Workspace</p>
        <ul class="space-y-1">
            @foreach ($navigation as $item)
                @php($active = $item['active'] ?? false)
                <li>
                    <a
                        href="{{ $item['href'] }}"
                        data-testid="{{ $item['testId'] }}"
                        @if ($active) aria-current="page" @endif
                        class="{{ $active ? 'flex min-h-11 items-center gap-3 rounded-lg bg-blue-50 px-3 text-sm font-bold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300' : 'flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-semibold text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' }}"
                    >
                        @switch($item['icon'])
                            @case('dashboard')
                                <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                                @break

                            @case('event')
                                <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Z"/></svg>
                                @break

                            @case('registration')
                                <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 5a2 2 0 0 1 2-2h4v6H3V5Zm12-2h4a2 2 0 0 1 2 2v4h-6V3ZM3 15h6v6H5a2 2 0 0 1-2-2v-4Zm12 0h2v2h-2zm4 0h2v6h-6v-2h4zM15 3v6h6"/><path d="M12 5v14M5 12h14"/></svg>
                                @break

                            @case('tickets')
                                <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 9a3 3 0 0 0 0 6v4h20v-4a3 3 0 0 0 0-6V5H2v4Z"/><path d="M13 5v2m0 4v2m0 4v2"/></svg>
                                @break

                            @case('categories')
                                <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 2 4 4-4 4-4-4 4-4ZM6 14l4 4-4 4-4-4 4-4Zm12 0 4 4-4 4-4-4 4-4Z"/></svg>
                                @break

                            @case('scanner')
                                <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V3h4M17 3h4v4M21 17v4h-4M7 21H3v-4M7 12h10"/></svg>
                                @break

                            @case('history')
                                <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5m4-2v6l4 2"/></svg>
                                @break

                            @case('settings')
                                <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1V21h-4v-.09A1.7 1.7 0 0 0 8.5 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1-.4H3v-4h.09A1.7 1.7 0 0 0 4.6 8.5a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1V3h4v.09A1.7 1.7 0 0 0 15.5 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.12.38.34.72.6 1 .28.27.64.4 1 .4h.09v4H21a1.7 1.7 0 0 0-1.6.6Z"/></svg>
                                @break
                        @endswitch

                        {{ $item['label'] }}

                        @if ($active)
                            <span class="ml-auto size-1.5 rounded-full bg-blue-600"></span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="border-t border-slate-100 p-3 dark:border-slate-800">
        <div data-testid="sidebar-admin-profile" class="flex items-center gap-3 rounded-lg bg-slate-50 p-3 dark:bg-slate-800/70">
            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-slate-900 text-sm font-bold text-white dark:bg-blue-600">{{ $initials }}</span>
            <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-bold">{{ $adminName }}</span>
                <span class="block text-xs text-slate-500 dark:text-slate-400">Admin Event</span>
            </span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button data-testid="logout-button" type="submit" class="grid size-9 place-items-center rounded-lg text-slate-400 hover:bg-white hover:text-red-600 dark:hover:bg-slate-700" aria-label="Keluar">
                    <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 17l5-5-5-5m5 5H3m12-9h6v18h-6"/></svg>
                </button>
            </form>
        </div>
    </div>
</aside>
