@php
    $statusVariants = [
        'registered' => 'blue',
        'checked_in' => 'emerald',
    ];

    $statusLabels = [
        'registered' => 'Registered',
        'checked_in' => 'Checked In',
    ];
@endphp

<div>
    <div class="mx-auto max-w-[1480px] space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-bold text-blue-600 dark:text-blue-400">Registrasi QR</p>
                <h2 data-testid="tickets-heading" class="mt-1 text-2xl font-extrabold text-slate-950 dark:text-white">Daftar Tiket</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Kelola QR ticket per event dan kategori.</p>
            </div>
            <x-admin.ui.button :href="route('admin.tickets.create')" data-testid="create-ticket-button">
                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Registrasi QR
            </x-admin.ui.button>
        </div>

        <x-admin.ui.card :padded="true" aria-labelledby="ticket-import-title">
            <div class="grid gap-5 lg:grid-cols-[1fr_auto] lg:items-end">
                <div>
                    <h2 id="ticket-import-title" class="text-lg font-extrabold">Import CSV</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Format: qr_code,ticket_category. Pilih event sebelum upload.</p>
                </div>

                @if ($importResult)
                    <div class="flex flex-wrap gap-2" data-testid="ticket-import-summary">
                        <x-admin.ui.badge variant="emerald" dot>{{ $importResult['success'] }} sukses</x-admin.ui.badge>
                        <x-admin.ui.badge variant="{{ $importResult['failed'] > 0 ? 'red' : 'slate' }}" dot>{{ $importResult['failed'] }} gagal</x-admin.ui.badge>
                    </div>
                @endif
            </div>

            <form wire:submit="import" class="mt-5 grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-end">
                <x-admin.form.select
                    label="Event Import"
                    id="ticket-import-event"
                    wire:model="import_event_id"
                    data-testid="ticket-import-event-input"
                >
                    <option value="">Pilih Event</option>
                    @foreach ($events as $eventOption)
                        <option value="{{ $eventOption->id }}">{{ $eventOption->name }}</option>
                    @endforeach
                </x-admin.form.select>

                <x-admin.form.input
                    label="File CSV"
                    type="file"
                    id="ticket-import-file"
                    wire:model="importFile"
                    data-testid="ticket-import-file-input"
                />

                <x-admin.ui.button type="submit" target="import" class="w-full lg:w-auto" data-testid="ticket-import-submit-button">
                    <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                    Import
                </x-admin.ui.button>
            </form>

            @if ($importResult && count($importResult['errors']) > 0)
                <div x-data="{ open: true }" class="mt-4 rounded-lg border border-red-200 bg-red-50 dark:border-red-500/30 dark:bg-red-500/10" data-testid="ticket-import-errors">
                    <button type="button" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left text-sm font-extrabold text-red-700 dark:text-red-300" x-on:click="open = ! open">
                        <span>Detail gagal import</span>
                        <svg aria-hidden="true" class="size-4 transition" x-bind:class="{ 'rotate-180': open }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <ul x-show="open" class="space-y-1 border-t border-red-200 px-4 py-3 text-sm font-semibold text-red-700 dark:border-red-500/30 dark:text-red-300">
                        @foreach ($importResult['errors'] as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-admin.ui.card>

        <x-admin.ui.card :overflow="true" aria-labelledby="ticket-list-title">
            <div class="space-y-4 border-b border-slate-100 px-5 py-4 sm:px-6 dark:border-slate-800">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 id="ticket-list-title" class="text-lg font-extrabold">Semua Tiket</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $tickets->count() }} tiket ditampilkan</p>
                    </div>
                </div>

                <div class="grid gap-3 lg:grid-cols-3">
                    <x-admin.form.input
                        label="Cari QR"
                        label-class="sr-only"
                        :flush="true"
                        id="ticket-search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari QR"
                        data-testid="ticket-search-input"
                    />

                    <x-admin.form.select
                        label="Filter Event"
                        label-class="sr-only"
                        :flush="true"
                        id="ticket-event-filter"
                        wire:model.live="eventFilter"
                        data-testid="ticket-event-filter-input"
                    >
                        <option value="">Semua Event</option>
                        @foreach ($events as $eventOption)
                            <option value="{{ $eventOption->id }}">{{ $eventOption->name }}</option>
                        @endforeach
                    </x-admin.form.select>

                    <x-admin.form.select
                        label="Filter Kategori"
                        label-class="sr-only"
                        :flush="true"
                        id="ticket-category-filter"
                        wire:model.live="categoryFilter"
                        data-testid="ticket-category-filter-input"
                    >
                        <option value="">Semua Kategori</option>
                        @foreach ($filterCategories as $categoryOption)
                            <option value="{{ $categoryOption->id }}">{{ $categoryOption->name }}</option>
                        @endforeach
                    </x-admin.form.select>
                </div>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                        <tr>
                            <th class="px-6 py-3">QR Code</th>
                            <th class="px-4 py-3">Event</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Registered</th>
                            <th class="px-6 py-3">Admin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($tickets as $ticket)
                            <tr data-testid="ticket-row-{{ $ticket->id }}" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                <td class="px-6 py-3.5 font-extrabold text-slate-950 dark:text-white">{{ $ticket->qr_code }}</td>
                                <td class="px-4 py-3.5 text-slate-600 dark:text-slate-300">{{ $ticket->event?->name ?? $ticket->ticketCategory?->event?->name }}</td>
                                <td class="px-4 py-3.5 text-slate-600 dark:text-slate-300">{{ $ticket->ticketCategory?->name }}</td>
                                <td class="px-4 py-3.5">
                                    <x-admin.ui.badge :variant="$statusVariants[$ticket->status] ?? 'slate'" dot data-testid="ticket-status-{{ $ticket->id }}">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</x-admin.ui.badge>
                                </td>
                                <td class="px-4 py-3.5 tabular-nums text-slate-600 dark:text-slate-300">{{ $ticket->registered_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                                <td class="px-6 py-3.5 text-slate-600 dark:text-slate-300">{{ $ticket->registeredBy?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada tiket.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 md:hidden dark:divide-slate-800">
                @forelse ($tickets as $ticket)
                    <article data-testid="ticket-card-{{ $ticket->id }}" class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="break-all font-extrabold text-slate-950 dark:text-white">{{ $ticket->qr_code }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $ticket->event?->name ?? $ticket->ticketCategory?->event?->name }} &middot; {{ $ticket->ticketCategory?->name }}</p>
                            </div>
                            <x-admin.ui.badge :variant="$statusVariants[$ticket->status] ?? 'slate'" size="sm" class="shrink-0">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</x-admin.ui.badge>
                        </div>
                        <dl class="grid grid-cols-2 gap-3 text-xs">
                            <div>
                                <dt class="font-bold text-slate-400">Registered</dt>
                                <dd class="mt-1 font-semibold text-slate-700 dark:text-slate-300">{{ $ticket->registered_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="font-bold text-slate-400">Admin</dt>
                                <dd class="mt-1 font-semibold text-slate-700 dark:text-slate-300">{{ $ticket->registeredBy?->name ?? '-' }}</dd>
                            </div>
                        </dl>
                    </article>
                @empty
                    <p class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada tiket.</p>
                @endforelse
            </div>
        </x-admin.ui.card>
    </div>
</div>
