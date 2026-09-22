@php
    $statusVariants = [
        'draft' => 'slate',
        'active' => 'emerald',
        'completed' => 'blue',
    ];
@endphp

<div>
    <div class="mx-auto max-w-[1480px] space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-bold text-blue-600 dark:text-blue-400">Manajemen Event</p>
                <h2 data-testid="events-heading" class="mt-1 text-2xl font-extrabold text-slate-950 dark:text-white">Daftar Event</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Hanya satu event yang dapat berstatus aktif pada satu waktu.</p>
            </div>
            <x-admin.ui.button wire:click="create" data-testid="create-event-button">
                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Tambah Event
            </x-admin.ui.button>
        </div>

        @if ($errors->has('delete'))
            <x-admin.ui.alert variant="danger" data-testid="event-delete-error">{{ $errors->first('delete') }}</x-admin.ui.alert>
        @endif

        <x-admin.ui.card :overflow="true" aria-labelledby="event-list-title">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6 dark:border-slate-800">
                <div>
                    <h2 id="event-list-title" class="text-lg font-extrabold">Semua Event</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $events->count() }} event terdaftar</p>
                </div>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                        <tr>
                            <th class="px-6 py-3">Nama Event</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Lokasi</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($events as $event)
                            <tr data-testid="event-row-{{ $event->id }}" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                <td class="px-6 py-3.5 font-extrabold text-slate-950 dark:text-white">{{ $event->name }}</td>
                                <td class="px-4 py-3.5 tabular-nums text-slate-600 dark:text-slate-300">{{ $event->event_date->translatedFormat('d F Y') }}</td>
                                <td class="px-4 py-3.5 text-slate-600 dark:text-slate-300">{{ $event->location ?: '-' }}</td>
                                <td class="px-4 py-3.5">
                                    <x-admin.ui.badge :variant="$statusVariants[$event->status->value]" dot data-testid="event-status-{{ $event->id }}">{{ $event->status->label() }}</x-admin.ui.badge>
                                </td>
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center justify-end gap-2">
                                        @if ($event->status !== \App\Enums\EventStatus::Active)
                                            <x-admin.ui.button variant="success" size="icon" wire:click="activate({{ $event->id }})" target="activate({{ $event->id }})" data-testid="activate-event-{{ $event->id }}" aria-label="Aktifkan event" title="Aktifkan">
                                                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                                                <span class="sr-only">Aktifkan</span>
                                            </x-admin.ui.button>
                                        @endif
                                        <x-admin.ui.button variant="outline" size="icon" wire:click="edit({{ $event->id }})" target="edit({{ $event->id }})" data-testid="edit-event-{{ $event->id }}" aria-label="Edit event" title="Edit">
                                            <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="m16.5 3.5 4 4L7 21H3v-4L16.5 3.5Z"/></svg>
                                            <span class="sr-only">Edit</span>
                                        </x-admin.ui.button>
                                        @if ($event->tickets_count === 0 && $event->ticket_categories_count === 0)
                                            <x-admin.ui.button variant="danger" size="icon" wire:click="delete({{ $event->id }})" target="delete({{ $event->id }})" wire:confirm="Hapus event ini?" data-testid="delete-event-{{ $event->id }}" aria-label="Hapus event" title="Hapus">
                                                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                                <span class="sr-only">Hapus</span>
                                            </x-admin.ui.button>
                                        @else
                                            <x-admin.ui.button variant="danger" size="icon" disabled title="{{ $event->tickets_count > 0 ? 'Tidak dapat dihapus karena sudah memiliki tiket.' : 'Tidak dapat dihapus karena masih memiliki kategori tiket.' }}" data-testid="event-delete-blocked-{{ $event->id }}" aria-label="Tidak dapat dihapus">
                                                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                                <span class="sr-only">Tidak dapat dihapus</span>
                                            </x-admin.ui.button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada event.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 md:hidden dark:divide-slate-800">
                @forelse ($events as $event)
                    <article data-testid="event-card-{{ $event->id }}" class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-extrabold text-slate-950 dark:text-white">{{ $event->name }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $event->event_date->translatedFormat('d F Y') }} &middot; {{ $event->location ?: 'Lokasi belum diisi' }}</p>
                            </div>
                            <x-admin.ui.badge :variant="$statusVariants[$event->status->value]" size="sm" class="shrink-0">{{ $event->status->label() }}</x-admin.ui.badge>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($event->status !== \App\Enums\EventStatus::Active)
                                <x-admin.ui.button variant="success" size="icon" wire:click="activate({{ $event->id }})" target="activate({{ $event->id }})" aria-label="Aktifkan event" title="Aktifkan">
                                    <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                                    <span class="sr-only">Aktifkan</span>
                                </x-admin.ui.button>
                            @endif
                            <x-admin.ui.button variant="outline" size="icon" wire:click="edit({{ $event->id }})" target="edit({{ $event->id }})" aria-label="Edit event" title="Edit">
                                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="m16.5 3.5 4 4L7 21H3v-4L16.5 3.5Z"/></svg>
                                <span class="sr-only">Edit</span>
                            </x-admin.ui.button>
                            @if ($event->tickets_count === 0 && $event->ticket_categories_count === 0)
                                <x-admin.ui.button variant="danger" size="icon" wire:click="delete({{ $event->id }})" target="delete({{ $event->id }})" wire:confirm="Hapus event ini?" aria-label="Hapus event" title="Hapus">
                                    <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                    <span class="sr-only">Hapus</span>
                                </x-admin.ui.button>
                            @else
                                <x-admin.ui.button variant="danger" size="icon" disabled title="{{ $event->tickets_count > 0 ? 'Tidak dapat dihapus karena sudah memiliki tiket.' : 'Tidak dapat dihapus karena masih memiliki kategori tiket.' }}" data-testid="event-delete-blocked-{{ $event->id }}" aria-label="Tidak dapat dihapus">
                                    <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                    <span class="sr-only">Tidak dapat dihapus</span>
                                </x-admin.ui.button>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada event.</p>
                @endforelse
            </div>
        </x-admin.ui.card>
    </div>

    <x-admin.ui.modal
        id="event-modal"
        :title="$editingId ? 'Edit Event' : 'Tambah Event'"
        subtitle="Lengkapi informasi event di bawah ini."
        title-test-id="event-modal-title"
    >
        <form wire:submit="save" class="mt-5 space-y-4">
            <x-admin.form.input
                label="Nama Event"
                id="event-name"
                wire:model="name"
                data-testid="event-name-input"
            />

            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin.form.input
                    label="Tanggal"
                    type="date"
                    id="event-date"
                    wire:model="event_date"
                    data-testid="event-date-input"
                />

                <x-admin.form.select
                    label="Status"
                    id="event-status"
                    wire:model="status"
                    data-testid="event-status-input"
                >
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                    @endforeach
                </x-admin.form.select>
            </div>

            <x-admin.form.input
                label="Lokasi"
                hint="(opsional)"
                id="event-location"
                wire:model="location"
                data-testid="event-location-input"
            />

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                <x-admin.ui.button variant="outline" wire:click="closeModal">Batal</x-admin.ui.button>
                <x-admin.ui.button type="submit" target="save" data-testid="event-submit-button">Simpan</x-admin.ui.button>
            </div>
        </form>
    </x-admin.ui.modal>
</div>
