@php
    $statusVariants = [
        true => 'emerald',
        false => 'red',
    ];
@endphp

<div>
    <div class="mx-auto max-w-[1480px] space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-bold text-blue-600 dark:text-blue-400">Manajemen Scanner</p>
                <h2 data-testid="scanners-heading" class="mt-1 text-2xl font-extrabold text-slate-950 dark:text-white">User Scanner</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Akun petugas untuk login ke halaman scanner.</p>
            </div>
            <x-admin.ui.button :href="route('admin.scanners.create')" data-testid="create-scanner-button">
                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Tambah Scanner
            </x-admin.ui.button>
        </div>

        @if ($errors->has('delete'))
            <x-admin.ui.alert variant="danger" data-testid="scanner-delete-error">{{ $errors->first('delete') }}</x-admin.ui.alert>
        @endif

        <x-admin.ui.card :overflow="true" aria-labelledby="scanner-list-title">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6 dark:border-slate-800">
                <h2 id="scanner-list-title" class="text-lg font-extrabold">Semua Scanner</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $scanners->count() }} akun scanner terdaftar</p>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                        <tr>
                            <th class="px-6 py-3">Nama</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Event Scanner</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Jumlah Scan</th>
                            <th class="px-4 py-3">Dibuat</th>
                            <th class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($scanners as $scanner)
                            <tr data-testid="scanner-row-{{ $scanner->id }}" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                <td class="px-6 py-3.5 font-extrabold text-slate-950 dark:text-white">{{ $scanner->name }}</td>
                                <td class="px-4 py-3.5 text-slate-600 dark:text-slate-300">{{ $scanner->email }}</td>
                                <td class="px-4 py-3.5 font-semibold text-slate-700 dark:text-slate-300" data-testid="scanner-event-{{ $scanner->id }}">{{ $scanner->scannerEventAssignment?->event?->name ?? '-' }}</td>
                                <td class="px-4 py-3.5">
                                    <x-admin.ui.badge :variant="$statusVariants[$scanner->is_active]" dot data-testid="scanner-status-{{ $scanner->id }}">{{ $scanner->is_active ? 'Aktif' : 'Nonaktif' }}</x-admin.ui.badge>
                                </td>
                                <td class="px-4 py-3.5 tabular-nums text-slate-600 dark:text-slate-300" data-testid="scanner-scans-{{ $scanner->id }}">{{ $scanner->check_in_logs_count }} scan</td>
                                <td class="px-4 py-3.5 tabular-nums text-slate-600 dark:text-slate-300">{{ $scanner->created_at?->translatedFormat('d M Y') ?? '-' }}</td>
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-admin.ui.button variant="outline" size="icon" wire:click="toggleActive({{ $scanner->id }})" target="toggleActive({{ $scanner->id }})" data-testid="scanner-toggle-{{ $scanner->id }}" :aria-label="$scanner->is_active ? 'Nonaktifkan scanner' : 'Aktifkan scanner'" :title="$scanner->is_active ? 'Nonaktifkan' : 'Aktifkan'">
                                            @if ($scanner->is_active)
                                                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><path d="M12 2v10"/></svg>
                                                <span class="sr-only">Nonaktifkan</span>
                                            @else
                                                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                                                <span class="sr-only">Aktifkan</span>
                                            @endif
                                        </x-admin.ui.button>
                                        <x-admin.ui.button variant="outline" size="icon" wire:click="edit({{ $scanner->id }})" target="edit({{ $scanner->id }})" data-testid="scanner-edit-{{ $scanner->id }}" aria-label="Edit scanner" title="Edit">
                                            <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="m16.5 3.5 4 4L7 21H3v-4L16.5 3.5Z"/></svg>
                                            <span class="sr-only">Edit</span>
                                        </x-admin.ui.button>
                                        <x-admin.ui.button variant="outline" size="icon" wire:click="assignEvent({{ $scanner->id }})" target="assignEvent({{ $scanner->id }})" data-testid="scanner-assign-event-{{ $scanner->id }}" aria-label="Atur event scanner" title="Atur Event">
                                            <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Z"/><path d="m9 16 2 2 4-4"/></svg>
                                            <span class="sr-only">Atur Event</span>
                                        </x-admin.ui.button>
                                        @if ($scanner->check_in_logs_count === 0)
                                            <x-admin.ui.button variant="danger" size="icon" wire:click="delete({{ $scanner->id }})" target="delete({{ $scanner->id }})" wire:confirm="Hapus scanner ini?" data-testid="delete-scanner-{{ $scanner->id }}" aria-label="Hapus scanner" title="Hapus">
                                                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                                <span class="sr-only">Hapus</span>
                                            </x-admin.ui.button>
                                        @else
                                            <x-admin.ui.button variant="danger" size="icon" disabled title="Tidak dapat dihapus karena sudah memiliki histori scan. Nonaktifkan scanner ini jika tidak ingin digunakan lagi." data-testid="scanner-delete-blocked-{{ $scanner->id }}" aria-label="Tidak dapat dihapus">
                                                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                                <span class="sr-only">Tidak dapat dihapus</span>
                                            </x-admin.ui.button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada akun scanner.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 md:hidden dark:divide-slate-800">
                @forelse ($scanners as $scanner)
                    <article data-testid="scanner-card-{{ $scanner->id }}" class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-extrabold text-slate-950 dark:text-white">{{ $scanner->name }}</p>
                                <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ $scanner->email }}</p>
                            </div>
                            <x-admin.ui.badge :variant="$statusVariants[$scanner->is_active]" size="sm" class="shrink-0">{{ $scanner->is_active ? 'Aktif' : 'Nonaktif' }}</x-admin.ui.badge>
                        </div>
                        <dl class="grid grid-cols-2 gap-3 text-xs">
                            <div class="col-span-2">
                                <dt class="font-bold text-slate-400">Event Scanner</dt>
                                <dd class="mt-1 font-semibold text-slate-700 dark:text-slate-300">{{ $scanner->scannerEventAssignment?->event?->name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="font-bold text-slate-400">Jumlah Scan</dt>
                                <dd class="mt-1 font-semibold text-slate-700 dark:text-slate-300">{{ $scanner->check_in_logs_count }} scan</dd>
                            </div>
                            <div>
                                <dt class="font-bold text-slate-400">Dibuat</dt>
                                <dd class="mt-1 font-semibold text-slate-700 dark:text-slate-300">{{ $scanner->created_at?->translatedFormat('d M Y') ?? '-' }}</dd>
                            </div>
                        </dl>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-admin.ui.button variant="outline" size="icon" wire:click="toggleActive({{ $scanner->id }})" target="toggleActive({{ $scanner->id }})" :aria-label="$scanner->is_active ? 'Nonaktifkan scanner' : 'Aktifkan scanner'" :title="$scanner->is_active ? 'Nonaktifkan' : 'Aktifkan'">
                                @if ($scanner->is_active)
                                    <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><path d="M12 2v10"/></svg>
                                    <span class="sr-only">Nonaktifkan</span>
                                @else
                                    <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                                    <span class="sr-only">Aktifkan</span>
                                @endif
                            </x-admin.ui.button>
                            <x-admin.ui.button variant="outline" size="icon" wire:click="edit({{ $scanner->id }})" target="edit({{ $scanner->id }})" aria-label="Edit scanner" title="Edit">
                                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="m16.5 3.5 4 4L7 21H3v-4L16.5 3.5Z"/></svg>
                                <span class="sr-only">Edit</span>
                            </x-admin.ui.button>
                            <x-admin.ui.button variant="outline" size="icon" wire:click="assignEvent({{ $scanner->id }})" target="assignEvent({{ $scanner->id }})" aria-label="Atur event scanner" title="Atur Event">
                                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Z"/><path d="m9 16 2 2 4-4"/></svg>
                                <span class="sr-only">Atur Event</span>
                            </x-admin.ui.button>
                            @if ($scanner->check_in_logs_count === 0)
                                <x-admin.ui.button variant="danger" size="icon" wire:click="delete({{ $scanner->id }})" target="delete({{ $scanner->id }})" wire:confirm="Hapus scanner ini?" aria-label="Hapus scanner" title="Hapus">
                                    <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                    <span class="sr-only">Hapus</span>
                                </x-admin.ui.button>
                            @else
                                <x-admin.ui.button variant="danger" size="icon" disabled title="Tidak dapat dihapus karena sudah memiliki histori scan. Nonaktifkan scanner ini jika tidak ingin digunakan lagi." data-testid="scanner-delete-blocked-{{ $scanner->id }}" aria-label="Tidak dapat dihapus">
                                    <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                    <span class="sr-only">Tidak dapat dihapus</span>
                                </x-admin.ui.button>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada akun scanner.</p>
                @endforelse
            </div>
        </x-admin.ui.card>
    </div>

    <x-admin.ui.modal
        id="scanner-modal"
        title="Edit Scanner"
        subtitle="Perbarui data akun petugas scanner."
        title-test-id="scanner-modal-title"
    >
        <form wire:submit="save" class="mt-5 space-y-4">
            <x-admin.form.input
                label="Nama Lengkap"
                id="scanner-name"
                wire:model="name"
                data-testid="scanner-name-input"
            />

            <x-admin.form.input
                label="Email"
                type="email"
                id="scanner-email"
                wire:model="email"
                data-testid="scanner-email-input"
            />

            <x-admin.form.input
                label="Password"
                hint="(kosongkan jika tidak diubah)"
                type="password"
                id="scanner-password"
                wire:model="password"
                autocomplete="new-password"
                data-testid="scanner-password-input"
            />

            <x-admin.form.input
                label="Konfirmasi Password"
                type="password"
                id="scanner-password-confirmation"
                wire:model="password_confirmation"
                autocomplete="new-password"
                data-testid="scanner-password-confirmation-input"
            />

            <x-admin.form.select
                label="Status"
                id="scanner-status"
                wire:model="is_active"
                data-testid="scanner-status-input"
            >
                <option value="1">Aktif</option>
                <option value="0">Nonaktif</option>
            </x-admin.form.select>

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                <x-admin.ui.button variant="outline" wire:click="closeModal">Batal</x-admin.ui.button>
                <x-admin.ui.button type="submit" target="save" data-testid="scanner-submit-button">Simpan</x-admin.ui.button>
            </div>
        </form>
    </x-admin.ui.modal>

    <x-admin.ui.modal
        id="scanner-event-modal"
        title="Atur Event Scanner"
        subtitle="Tetapkan satu event aktif untuk scanner ini."
        show="showEventModal"
        close-action="closeEventModal"
        title-test-id="scanner-event-modal-title"
    >
        <form wire:submit="saveEventAssignment" class="mt-5 space-y-4">
            <x-admin.form.input
                label="Scanner"
                id="scanner-assignment-name"
                wire:model="assigningScannerName"
                readonly
                data-testid="scanner-assignment-name-input"
            />

            <x-admin.form.select
                label="Event"
                id="scanner-assignment-event"
                wire:model="assignmentEventId"
                data-testid="scanner-assignment-event-input"
            >
                <option value="">Pilih Event</option>
                @foreach ($events as $eventOption)
                    <option value="{{ $eventOption->id }}">{{ $eventOption->name }}</option>
                @endforeach
            </x-admin.form.select>

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                <x-admin.ui.button variant="outline" wire:click="closeEventModal">Batal</x-admin.ui.button>
                <x-admin.ui.button type="submit" target="saveEventAssignment" data-testid="scanner-assignment-submit-button">Simpan Event</x-admin.ui.button>
            </div>
        </form>
    </x-admin.ui.modal>
</div>
