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
                    <h2 id="ticket-import-title" class="text-lg font-extrabold">Import QR</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Terima file CSV / Excel (.xlsx). Format: qr_code,ticket_category. Pilih event sebelum upload.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <x-admin.ui.button variant="outline" :href="route('admin.tickets.import-template')" data-testid="ticket-template-download-button">
                        <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                        Download Template Excel
                    </x-admin.ui.button>
                    @if ($importResult)
                    <div class="flex flex-wrap gap-2" data-testid="ticket-import-summary">
                        <x-admin.ui.badge variant="emerald" dot>{{ $importResult['success'] }} sukses</x-admin.ui.badge>
                        <x-admin.ui.badge variant="{{ $importResult['failed'] > 0 ? 'red' : 'slate' }}" dot>{{ $importResult['failed'] }} gagal</x-admin.ui.badge>
                    </div>
                    @endif
                </div>
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
                    label="File CSV / Excel"
                    type="file"
                    id="ticket-import-file"
                    wire:model="importFile"
                    accept=".csv,.xlsx"
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
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $tickets->total() }} tiket total · Halaman {{ $tickets->currentPage() }} dari {{ $tickets->lastPage() }}</p>
                    </div>
                </div>

                @if ($ticketActionMessage)
                    <x-admin.ui.alert variant="success" data-testid="ticket-action-message">{{ $ticketActionMessage }}</x-admin.ui.alert>
                @endif

                @if (count($selectedTicketIds) > 0)
                    <div class="flex flex-col gap-3 rounded-lg border border-blue-200 bg-blue-50 p-3 sm:flex-row sm:items-center sm:justify-between dark:border-blue-500/30 dark:bg-blue-500/10" data-testid="ticket-bulk-toolbar">
                        <p class="text-sm font-bold text-blue-700 dark:text-blue-300"><span data-testid="ticket-selected-count">{{ count($selectedTicketIds) }}</span> tiket terpilih</p>
                        <x-admin.ui.button
                            variant="danger"
                            wire:click="confirmBulkDelete"
                            target="confirmBulkDelete"
                            data-testid="ticket-bulk-delete-button"
                        >
                            Hapus Terpilih
                        </x-admin.ui.button>
                    </div>
                @endif

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
                            <th class="w-12 px-4 py-3">
                                <input
                                    type="checkbox"
                                    wire:model.live="selectAllDisplayed"
                                    data-testid="ticket-select-all-input"
                                    aria-label="Pilih Semua"
                                    class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900"
                                >
                            </th>
                            <th class="px-6 py-3">QR Code</th>
                            <th class="px-4 py-3">Event</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Registered</th>
                            <th class="px-6 py-3">Admin</th>
                            <th class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($tickets as $ticket)
                            @php($canManage = $ticket->checked_in_at === null)
                            <tr data-testid="ticket-row-{{ $ticket->id }}" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                <td class="px-4 py-3.5">
                                    @if ($canManage)
                                        <input
                                            type="checkbox"
                                            wire:model.live="selectedTicketIds"
                                            value="{{ $ticket->id }}"
                                            data-testid="ticket-select-{{ $ticket->id }}"
                                            aria-label="Pilih tiket {{ $ticket->qr_code }}"
                                            class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900"
                                        >
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 font-extrabold text-slate-950 dark:text-white">{{ $ticket->qr_code }}</td>
                                <td class="px-4 py-3.5 text-slate-600 dark:text-slate-300">{{ $ticket->event?->name ?? $ticket->ticketCategory?->event?->name }}</td>
                                <td class="px-4 py-3.5 text-slate-600 dark:text-slate-300">{{ $ticket->ticketCategory?->name }}</td>
                                <td class="px-4 py-3.5">
                                    <x-admin.ui.badge :variant="$statusVariants[$ticket->status] ?? 'slate'" dot data-testid="ticket-status-{{ $ticket->id }}">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</x-admin.ui.badge>
                                </td>
                                <td class="px-4 py-3.5 tabular-nums text-slate-600 dark:text-slate-300">{{ $ticket->registered_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                                <td class="px-6 py-3.5 text-slate-600 dark:text-slate-300">{{ $ticket->registeredBy?->name ?? '-' }}</td>
                                <td class="px-6 py-3.5">
                                    @if ($canManage)
                                        <div class="flex justify-end gap-2">
                                            <x-admin.ui.button variant="outline" size="icon" wire:click="edit({{ $ticket->id }})" target="edit({{ $ticket->id }})" data-testid="ticket-edit-{{ $ticket->id }}" aria-label="Edit" title="Edit">
                                                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="m16.5 3.5 4 4L7 21H3v-4L16.5 3.5Z"/></svg>
                                            </x-admin.ui.button>
                                            <x-admin.ui.button variant="outline" size="icon" wire:click="confirmDeleteTicket({{ $ticket->id }})" target="confirmDeleteTicket({{ $ticket->id }})" data-testid="ticket-delete-{{ $ticket->id }}" aria-label="Hapus" title="Hapus">
                                                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6m4-6v6"/></svg>
                                            </x-admin.ui.button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada tiket.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 md:hidden dark:divide-slate-800">
                @forelse ($tickets as $ticket)
                    @php($canManage = $ticket->checked_in_at === null)
                    <article data-testid="ticket-card-{{ $ticket->id }}" class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            @if ($canManage)
                                <input
                                    type="checkbox"
                                    wire:model.live="selectedTicketIds"
                                    value="{{ $ticket->id }}"
                                    aria-label="Pilih tiket {{ $ticket->qr_code }}"
                                    class="mt-1 size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900"
                                >
                            @endif
                            <div class="min-w-0 flex-1">
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
                        @if ($canManage)
                            <div class="flex gap-2">
                                <x-admin.ui.button variant="outline" wire:click="edit({{ $ticket->id }})" target="edit({{ $ticket->id }})">Edit</x-admin.ui.button>
                                <x-admin.ui.button variant="outline" wire:click="confirmDeleteTicket({{ $ticket->id }})" target="confirmDeleteTicket({{ $ticket->id }})">Hapus</x-admin.ui.button>
                            </div>
                        @endif
                    </article>
                @empty
                    <p class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada tiket.</p>
                @endforelse
            </div>
        @if ($tickets->hasPages())
            <div class="flex flex-col gap-3 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800" data-testid="ticket-pagination">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Menampilkan {{ $tickets->firstItem() }}–{{ $tickets->lastItem() }} dari {{ $tickets->total() }} tiket
                </p>
                <div>
                    {{ $tickets->links(data: ['scrollTo' => false]) }}
                </div>
            </div>
        @endif
        </x-admin.ui.card>
    </div>

    <x-admin.ui.modal
        id="ticket-edit-modal"
        title="Edit Tiket"
        subtitle="Perbarui QR dan kategori tiket yang belum check-in."
        show="showEditModal"
        close-action="closeEditModal"
        title-test-id="ticket-edit-modal-title"
    >
        <form wire:submit="saveEdit" class="mt-5 space-y-4">
            <x-admin.form.input
                label="QR Code"
                id="ticket-edit-qr"
                wire:model="editingQrCode"
                data-testid="ticket-edit-qr-input"
            />

            <x-admin.form.select
                label="Kategori Tiket"
                id="ticket-edit-category"
                wire:model="editingCategoryId"
                data-testid="ticket-edit-category-input"
            >
                <option value="">Pilih Kategori</option>
                @foreach ($editingCategoryOptions as $categoryOption)
                    <option value="{{ $categoryOption['id'] }}">{{ $categoryOption['name'] }}</option>
                @endforeach
            </x-admin.form.select>

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                <x-admin.ui.button variant="outline" type="button" wire:click="closeEditModal">Batal</x-admin.ui.button>
                <x-admin.ui.button type="submit" target="saveEdit" data-testid="ticket-edit-save-button">Simpan</x-admin.ui.button>
            </div>
        </form>
    </x-admin.ui.modal>

    <x-admin.ui.modal
        id="ticket-delete-modal"
        title="Hapus Tiket?"
        subtitle="Data yang sudah dihapus tidak dapat dikembalikan."
        show="showDeleteModal"
        close-action="cancelDeleteTicket"
        title-test-id="ticket-delete-modal-title"
    >
        <p class="mt-4 text-sm text-slate-600 dark:text-slate-300">
            Apakah Anda yakin ingin menghapus tiket
            <span class="font-bold text-slate-900 dark:text-white" data-testid="ticket-delete-name">&ldquo;{{ $deletingTicketLabel }}&rdquo;</span>?
            Data yang sudah dihapus tidak dapat dikembalikan.
        </p>
        <div class="mt-5 flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
            <x-admin.ui.button variant="outline" wire:click="cancelDeleteTicket" data-testid="ticket-delete-cancel">Batal</x-admin.ui.button>
            <x-admin.ui.button variant="danger" wire:click="deleteTicket({{ $deletingTicketId }})" target="deleteTicket({{ $deletingTicketId }})" data-testid="ticket-delete-confirm">Hapus</x-admin.ui.button>
        </div>
    </x-admin.ui.modal>

    <x-admin.ui.modal
        id="ticket-bulk-delete-modal"
        title="Hapus Tiket Terpilih?"
        subtitle="Data yang sudah dihapus tidak dapat dikembalikan."
        show="showBulkDeleteModal"
        close-action="cancelBulkDelete"
        title-test-id="ticket-bulk-delete-modal-title"
    >
        <p class="mt-4 text-sm text-slate-600 dark:text-slate-300">
            Apakah Anda yakin ingin menghapus
            <span class="font-bold text-slate-900 dark:text-white" data-testid="ticket-bulk-delete-count">{{ count($selectedTicketIds) }}</span>
            tiket terpilih? Data yang sudah dihapus tidak dapat dikembalikan.
        </p>
        <div class="mt-5 flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
            <x-admin.ui.button variant="outline" wire:click="cancelBulkDelete" data-testid="ticket-bulk-delete-cancel">Batal</x-admin.ui.button>
            <x-admin.ui.button variant="danger" wire:click="bulkDelete" target="bulkDelete" data-testid="ticket-bulk-delete-confirm">Hapus</x-admin.ui.button>
        </div>
    </x-admin.ui.modal>
</div>
