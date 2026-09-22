<div>
    @if ($isScanner)
        <x-scanner.header
            subtitle="Profil Saya"
            :scanner-name="$scannerName"
            :scanner-initials="$scannerInitials"
            :scanner-role="$scannerRole"
        />

        <main class="scanner-page">
            <div class="scanner-workspace">
                @if ($status)
                    <div class="empty-state" data-testid="profile-success-alert">{{ $status }}</div>
                @endif

                <form wire:submit="saveProfile" class="modal-card" data-testid="profile-information-section">
                    <div>
                        <h2>Informasi Akun</h2>
                        <p>Nama dan alamat email Anda.</p>
                    </div>

                    <label class="form-field" for="profile-name">
                        <span>Nama</span>
                        <span class="input-shell">
                            <input id="profile-name" wire:model="name" autocomplete="name" data-testid="profile-name-input">
                        </span>
                        @error('name') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="form-field" for="profile-email">
                        <span>Email</span>
                        <span class="input-shell">
                            <input type="email" id="profile-email" wire:model="email" autocomplete="email" data-testid="profile-email-input">
                        </span>
                        @error('email') <small>{{ $message }}</small> @enderror
                    </label>

                    <button class="button button--primary" type="submit" data-testid="profile-save-button">Simpan Perubahan</button>
                </form>

                <form wire:submit="updatePassword" class="modal-card" data-testid="profile-password-section">
                    <div>
                        <h2>Ubah Password</h2>
                        <p>Biarkan kosong jika tidak ingin mengubah password.</p>
                    </div>

                    <label class="form-field" for="profile-current-password">
                        <span>Password Saat Ini</span>
                        <span class="input-shell">
                            <input type="password" id="profile-current-password" wire:model="current_password" autocomplete="current-password" data-testid="profile-current-password-input">
                        </span>
                        @error('current_password') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="form-field" for="profile-new-password">
                        <span>Password Baru</span>
                        <span class="input-shell">
                            <input type="password" id="profile-new-password" wire:model="password" autocomplete="new-password" data-testid="profile-new-password-input">
                        </span>
                        @error('password') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="form-field" for="profile-confirm-password">
                        <span>Konfirmasi Password Baru</span>
                        <span class="input-shell">
                            <input type="password" id="profile-confirm-password" wire:model="password_confirmation" autocomplete="new-password" data-testid="profile-confirm-password-input">
                        </span>
                        @error('password_confirmation') <small>{{ $message }}</small> @enderror
                    </label>

                    <button class="button button--primary" type="submit" data-testid="profile-password-button">Perbarui Password</button>
                </form>
            </div>
        </main>
    @else
        <div class="mx-auto max-w-[960px] space-y-6">
            @if ($status)
                <x-admin.ui.alert variant="success" data-testid="profile-success-alert">{{ $status }}</x-admin.ui.alert>
            @endif

            <form wire:submit="saveProfile" class="space-y-5">
                <x-admin.ui.card :padded="true" data-testid="profile-information-section">
                    <div class="grid gap-5 md:grid-cols-[220px_1fr]">
                        <div>
                            <h3 class="text-base font-extrabold text-slate-950 dark:text-white">Informasi Akun</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Nama dan alamat email Anda.</p>
                        </div>
                        <div class="space-y-4">
                            <x-admin.form.input
                                label="Nama"
                                id="profile-name"
                                wire:model="name"
                                autocomplete="name"
                                data-testid="profile-name-input"
                            />
                            <x-admin.form.input
                                label="Email"
                                type="email"
                                id="profile-email"
                                wire:model="email"
                                autocomplete="email"
                                data-testid="profile-email-input"
                            />
                        </div>
                    </div>
                </x-admin.ui.card>

                <div class="flex justify-end">
                    <x-admin.ui.button type="submit" target="saveProfile" data-testid="profile-save-button">Simpan Perubahan</x-admin.ui.button>
                </div>
            </form>

            <form wire:submit="updatePassword" class="space-y-5">
                <x-admin.ui.card :padded="true" data-testid="profile-password-section">
                    <div class="grid gap-5 md:grid-cols-[220px_1fr]">
                        <div>
                            <h3 class="text-base font-extrabold text-slate-950 dark:text-white">Ubah Password</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Biarkan kosong jika tidak ingin mengubah password.</p>
                        </div>
                        <div class="space-y-4">
                            <x-admin.form.input
                                label="Password Saat Ini"
                                type="password"
                                id="profile-current-password"
                                wire:model="current_password"
                                autocomplete="current-password"
                                hint="(wajib jika mengubah password)"
                                data-testid="profile-current-password-input"
                            />
                            <x-admin.form.input
                                label="Password Baru"
                                type="password"
                                id="profile-new-password"
                                wire:model="password"
                                autocomplete="new-password"
                                hint="(minimal 8 karakter)"
                                data-testid="profile-new-password-input"
                            />
                            <x-admin.form.input
                                label="Konfirmasi Password Baru"
                                type="password"
                                id="profile-confirm-password"
                                wire:model="password_confirmation"
                                autocomplete="new-password"
                                data-testid="profile-confirm-password-input"
                            />
                        </div>
                    </div>
                </x-admin.ui.card>

                <div class="flex justify-end">
                    <x-admin.ui.button type="submit" target="updatePassword" data-testid="profile-password-button">Perbarui Password</x-admin.ui.button>
                </div>
            </form>
        </div>
    @endif
</div>
