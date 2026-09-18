<div>
    <div class="mx-auto max-w-[960px] space-y-6">
        <div>
            <p class="text-sm font-bold text-blue-600 dark:text-blue-400">Pengaturan</p>
            <h2 data-testid="settings-heading" class="mt-1 text-2xl font-extrabold text-slate-950 dark:text-white">Pengaturan Aplikasi</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Kelola nama aplikasi dan perilaku dasar scanner.</p>
        </div>

        @if (session('settingsSaved'))
            <x-admin.ui.alert variant="success" data-testid="settings-success-alert">{{ session('settingsSaved') }}</x-admin.ui.alert>
        @endif

        <form wire:submit="save" class="space-y-5">
            <x-admin.ui.card :padded="true" data-testid="settings-application-section">
                <div class="grid gap-5 md:grid-cols-[220px_1fr]">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-950 dark:text-white">Application</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Identitas aplikasi.</p>
                    </div>
                    <x-admin.form.input
                        label="Nama aplikasi"
                        id="app-name"
                        wire:model="appName"
                        data-testid="app-name-input"
                    />
                </div>
            </x-admin.ui.card>

            <x-admin.ui.card :padded="true" data-testid="settings-scanner-section">
                <div class="grid gap-5 md:grid-cols-[220px_1fr]">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-950 dark:text-white">Scanner</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Perilaku validasi scanner.</p>
                    </div>
                    <x-admin.form.input
                        label="Success auto close timeout"
                        type="number"
                        min="500"
                        max="10000"
                        id="scanner-success-timeout"
                        wire:model="scannerSuccessTimeout"
                        data-testid="scanner-success-timeout-input"
                    />
                </div>
            </x-admin.ui.card>

            <x-admin.ui.card :padded="true" data-testid="settings-system-section">
                <div class="grid gap-5 md:grid-cols-[220px_1fr]">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-950 dark:text-white">System</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Zona waktu sistem.</p>
                    </div>
                    <x-admin.form.select
                        label="Timezone"
                        id="system-timezone"
                        wire:model="systemTimezone"
                        data-testid="system-timezone-input"
                    >
                        @foreach ($this->timezoneOptions() as $timezone)
                            <option value="{{ $timezone }}">{{ $timezone }}</option>
                        @endforeach
                    </x-admin.form.select>
                </div>
            </x-admin.ui.card>

            <div class="flex justify-end">
                <x-admin.ui.button type="submit" target="save" data-testid="settings-submit-button">Simpan Pengaturan</x-admin.ui.button>
            </div>
        </form>
    </div>
</div>
