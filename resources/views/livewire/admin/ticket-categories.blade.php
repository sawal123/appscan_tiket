@php
    $statusVariants = [
        'active' => 'emerald',
        'inactive' => 'slate-soft',
    ];
@endphp

<div>
    <div class="mx-auto max-w-[1480px] space-y-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-bold text-blue-600 dark:text-blue-400">Manajemen Tiket</p>
                <h2 data-testid="ticket-categories-heading" class="mt-1 text-2xl font-extrabold text-slate-950 dark:text-white">Kategori Tiket</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Kelola kategori tiket untuk setiap event.</p>
            </div>
            <x-admin.ui.button wire:click="create" data-testid="create-ticket-category-button">
                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Tambah Kategori
            </x-admin.ui.button>
        </div>

        @if ($errors->has('delete'))
            <x-admin.ui.alert variant="danger" data-testid="ticket-category-delete-error">{{ $errors->first('delete') }}</x-admin.ui.alert>
        @endif

        <x-admin.ui.card :overflow="true" aria-labelledby="ticket-category-list-title">
            <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:border-slate-800">
                <div>
                    <h2 id="ticket-category-list-title" class="text-lg font-extrabold">Daftar Kategori</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $categories->count() }} kategori ditampilkan</p>
                </div>
                <x-admin.form.select
                    label="Filter Event"
                    label-class="sr-only"
                    wrapper="sm:w-72"
                    :flush="true"
                    id="event-filter"
                    wire:model.live="eventFilter"
                    data-testid="event-filter-select"
                >
                    <option value="">Semua Event</option>
                    @foreach ($events as $eventOption)
                        <option value="{{ $eventOption->id }}">{{ $eventOption->name }}</option>
                    @endforeach
                </x-admin.form.select>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                        <tr>
                            <th class="px-6 py-3">Nama Kategori</th>
                            <th class="px-4 py-3">Event</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($categories as $category)
                            <tr data-testid="ticket-category-row-{{ $category->id }}" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                <td class="px-6 py-3.5 font-extrabold text-slate-950 dark:text-white">{{ $category->name }}</td>
                                <td class="px-4 py-3.5 text-slate-600 dark:text-slate-300">{{ $category->event->name }}</td>
                                <td class="px-4 py-3.5">
                                    <x-admin.ui.badge :variant="$category->is_active ? $statusVariants['active'] : $statusVariants['inactive']" dot data-testid="ticket-category-status-{{ $category->id }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</x-admin.ui.badge>
                                </td>
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-admin.ui.button variant="outline" size="icon" wire:click="toggleActive({{ $category->id }})" target="toggleActive({{ $category->id }})" data-testid="toggle-ticket-category-{{ $category->id }}" :aria-label="$category->is_active ? 'Nonaktifkan kategori' : 'Aktifkan kategori'" :title="$category->is_active ? 'Nonaktifkan' : 'Aktifkan'">
                                            @if ($category->is_active)
                                                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><path d="M12 2v10"/></svg>
                                                <span class="sr-only">Nonaktifkan</span>
                                            @else
                                                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                                                <span class="sr-only">Aktifkan</span>
                                            @endif
                                        </x-admin.ui.button>
                                        <x-admin.ui.button variant="outline" size="icon" wire:click="edit({{ $category->id }})" target="edit({{ $category->id }})" data-testid="edit-ticket-category-{{ $category->id }}" aria-label="Edit kategori" title="Edit">
                                            <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="m16.5 3.5 4 4L7 21H3v-4L16.5 3.5Z"/></svg>
                                            <span class="sr-only">Edit</span>
                                        </x-admin.ui.button>
                                        @if ($category->tickets_count === 0)
                                            <x-admin.ui.button variant="danger" size="icon" wire:click="confirmDelete({{ $category->id }})" target="confirmDelete({{ $category->id }})" data-testid="delete-ticket-category-{{ $category->id }}" aria-label="Hapus kategori" title="Hapus">
                                                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                                <span class="sr-only">Hapus</span>
                                            </x-admin.ui.button>
                                        @else
                                            <x-admin.ui.button variant="danger" size="icon" wire:click="showDeleteBlocked({{ $category->id }})" target="showDeleteBlocked({{ $category->id }})" data-testid="ticket-category-delete-blocked-{{ $category->id }}" aria-label="Tidak dapat dihapus" title="Tidak dapat dihapus">
                                                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                                <span class="sr-only">Tidak dapat dihapus</span>
                                            </x-admin.ui.button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada kategori untuk event ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 md:hidden dark:divide-slate-800">
                @forelse ($categories as $category)
                    <article data-testid="ticket-category-card-{{ $category->id }}" class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-extrabold text-slate-950 dark:text-white">{{ $category->name }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $category->event->name }}</p>
                            </div>
                            <x-admin.ui.badge :variant="$category->is_active ? $statusVariants['active'] : $statusVariants['inactive']" size="sm" class="shrink-0">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</x-admin.ui.badge>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-admin.ui.button variant="outline" size="icon" wire:click="toggleActive({{ $category->id }})" target="toggleActive({{ $category->id }})" :aria-label="$category->is_active ? 'Nonaktifkan kategori' : 'Aktifkan kategori'" :title="$category->is_active ? 'Nonaktifkan' : 'Aktifkan'">
                                @if ($category->is_active)
                                    <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><path d="M12 2v10"/></svg>
                                    <span class="sr-only">Nonaktifkan</span>
                                @else
                                    <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                                    <span class="sr-only">Aktifkan</span>
                                @endif
                            </x-admin.ui.button>
                            <x-admin.ui.button variant="outline" size="icon" wire:click="edit({{ $category->id }})" target="edit({{ $category->id }})" aria-label="Edit kategori" title="Edit">
                                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="m16.5 3.5 4 4L7 21H3v-4L16.5 3.5Z"/></svg>
                                <span class="sr-only">Edit</span>
                            </x-admin.ui.button>
                            @if ($category->tickets_count === 0)
                                <x-admin.ui.button variant="danger" size="icon" wire:click="confirmDelete({{ $category->id }})" target="confirmDelete({{ $category->id }})" aria-label="Hapus kategori" title="Hapus">
                                    <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                    <span class="sr-only">Hapus</span>
                                </x-admin.ui.button>
                            @else
                                <x-admin.ui.button variant="danger" size="icon" wire:click="showDeleteBlocked({{ $category->id }})" target="showDeleteBlocked({{ $category->id }})" data-testid="ticket-category-delete-blocked-{{ $category->id }}" aria-label="Tidak dapat dihapus" title="Tidak dapat dihapus">
                                    <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                    <span class="sr-only">Tidak dapat dihapus</span>
                                </x-admin.ui.button>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada kategori untuk event ini.</p>
                @endforelse
            </div>
        </x-admin.ui.card>
    </div>

    <x-admin.ui.modal
        id="ticket-category-modal"
        :title="$editingId ? 'Edit Kategori' : 'Tambah Kategori'"
        subtitle="Kategori tiket wajib terkait dengan sebuah event."
        title-test-id="ticket-category-modal-title"
    >
        <form wire:submit="save" class="mt-5 space-y-4">
            <x-admin.form.select
                label="Event"
                id="ticket-category-event"
                wire:model="event_id"
                data-testid="ticket-category-event-input"
            >
                <option value="">Pilih Event</option>
                @foreach ($events as $eventOption)
                    <option value="{{ $eventOption->id }}">{{ $eventOption->name }}</option>
                @endforeach
            </x-admin.form.select>

            <x-admin.form.input
                label="Nama Kategori"
                id="ticket-category-name"
                wire:model="name"
                data-testid="ticket-category-name-input"
            />

            <label for="ticket-category-active" class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-3 dark:border-slate-700">
                <input id="ticket-category-active" type="checkbox" wire:model="is_active" data-testid="ticket-category-active-input" class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-200 dark:border-slate-600" />
                <span class="text-sm font-bold">Kategori aktif</span>
            </label>

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                <x-admin.ui.button variant="outline" wire:click="closeModal">Batal</x-admin.ui.button>
                <x-admin.ui.button type="submit" target="save" data-testid="ticket-category-submit-button">Simpan</x-admin.ui.button>
            </div>
        </form>
    </x-admin.ui.modal>

    <x-admin.ui.modal
        id="ticket-category-delete-modal"
        title="Hapus Kategori?"
        subtitle="Data yang sudah dihapus tidak dapat dikembalikan."
        show="showDeleteModal"
        close-action="cancelDelete"
        title-test-id="ticket-category-delete-modal-title"
    >
        <p class="mt-4 text-sm text-slate-600 dark:text-slate-300">
            Apakah Anda yakin ingin menghapus kategori
            <span class="font-bold text-slate-900 dark:text-white" data-testid="ticket-category-delete-name">&ldquo;{{ $deletingName }}&rdquo;</span>?
            Data yang sudah dihapus tidak dapat dikembalikan.
        </p>
        <div class="mt-5 flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
            <x-admin.ui.button variant="outline" wire:click="cancelDelete" data-testid="ticket-category-delete-cancel">Batal</x-admin.ui.button>
            <x-admin.ui.button variant="danger" wire:click="delete({{ $deletingId }})" target="delete({{ $deletingId }})" data-testid="ticket-category-delete-confirm">Hapus</x-admin.ui.button>
        </div>
    </x-admin.ui.modal>

    <x-admin.ui.modal
        id="ticket-category-delete-blocked-modal"
        title="Kategori Tidak Dapat Dihapus"
        show="showDeleteBlockedModal"
        close-action="closeDeleteBlocked"
        title-test-id="ticket-category-delete-blocked-modal-title"
    >
        <div class="mt-4 space-y-3 text-sm text-slate-600 dark:text-slate-300">
            <p>Kategori ini sudah memiliki tiket yang terhubung.</p>
            <p>Kategori tidak dapat dihapus agar data tiket tetap valid.</p>
            <p>Gunakan opsi "Nonaktifkan".</p>
        </div>
        <div class="mt-5 flex justify-end border-t border-slate-100 pt-4 dark:border-slate-800">
            <x-admin.ui.button variant="outline" wire:click="closeDeleteBlocked" data-testid="ticket-category-delete-blocked-close">Mengerti</x-admin.ui.button>
        </div>
    </x-admin.ui.modal>
</div>
