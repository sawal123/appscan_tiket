<div class="mx-auto max-w-[1480px] space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-bold text-blue-600 dark:text-blue-400">Manajemen Scanner</p>
            <h2 data-testid="scanner-create-heading" class="mt-1 text-2xl font-extrabold text-slate-950 dark:text-white">Tambah Scanner</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Akun ini otomatis mendapat role scanner dan hanya dapat mengakses halaman scanner.</p>
        </div>
        <x-admin.ui.button variant="outline" :href="route('admin.scanners')" data-testid="back-to-scanners-link">
            <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            Daftar Scanner
        </x-admin.ui.button>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.7fr)]">
        <x-admin.ui.card :padded="true" aria-labelledby="scanner-form-title">
            <h2 id="scanner-form-title" class="text-lg font-extrabold">Data Akun</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Semua field wajib diisi.</p>

            <form wire:submit="save" class="mt-5 space-y-4">
                <x-admin.form.input
                    label="Nama Lengkap"
                    id="scanner-create-name"
                    wire:model="name"
                    data-testid="scanner-name-input"
                />

                <x-admin.form.input
                    label="Email"
                    type="email"
                    id="scanner-create-email"
                    wire:model="email"
                    autocomplete="off"
                    data-testid="scanner-email-input"
                />

                <x-admin.form.input
                    label="Password"
                    type="password"
                    id="scanner-create-password"
                    wire:model="password"
                    hint="(minimal 8 karakter)"
                    autocomplete="new-password"
                    data-testid="scanner-password-input"
                />

                <x-admin.form.input
                    label="Konfirmasi Password"
                    type="password"
                    id="scanner-create-password-confirmation"
                    wire:model="password_confirmation"
                    autocomplete="new-password"
                    data-testid="scanner-password-confirmation-input"
                />

                <x-admin.form.select
                    label="Event Scanner"
                    id="scanner-create-event"
                    wire:model="event_id"
                    data-testid="scanner-event-input"
                >
                    <option value="">Pilih Event</option>
                    @foreach ($events as $eventOption)
                        <option value="{{ $eventOption->id }}">{{ $eventOption->name }} ({{ $eventOption->status->label() }})</option>
                    @endforeach
                </x-admin.form.select>

                <div class="flex flex-col gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end dark:border-slate-800">
                    <x-admin.ui.button variant="outline" :href="route('admin.scanners')" type="button">Batal</x-admin.ui.button>
                    <x-admin.ui.button type="submit" target="save" data-testid="scanner-save-button">
                        <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                        Simpan Scanner
                    </x-admin.ui.button>
                </div>
            </form>
        </x-admin.ui.card>

        <x-admin.ui.card :padded="true" aria-labelledby="scanner-info-title">
            <h2 id="scanner-info-title" class="text-lg font-extrabold">Akses Scanner</h2>
            <ul class="mt-4 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                <li class="flex gap-2">
                    <svg aria-hidden="true" class="mt-0.5 size-4 shrink-0 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg>
                    Dapat login ke aplikasi dengan email dan password di atas.
                </li>
                <li class="flex gap-2">
                    <svg aria-hidden="true" class="mt-0.5 size-4 shrink-0 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg>
                    Dapat membuka halaman scanner sesuai event yang ditetapkan.
                </li>
                <li class="flex gap-2">
                    <svg aria-hidden="true" class="mt-0.5 size-4 shrink-0 text-red-500 dark:text-red-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="m15 9-6 6M9 9l6 6"/></svg>
                    Tidak dapat mengakses dashboard, event, maupun tiket admin.
                </li>
            </ul>
        </x-admin.ui.card>
    </div>
</div>
