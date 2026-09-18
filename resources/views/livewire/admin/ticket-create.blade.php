<div
    x-data="ticketRegistrationScanner"
    x-on:ticket-registered.window="rearm()"
    x-on:keydown.escape.window="stopCamera()"
    class="mx-auto max-w-[1480px] space-y-6"
>
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-bold text-blue-600 dark:text-blue-400">Registrasi QR</p>
            <h2 data-testid="ticket-create-heading" class="mt-1 text-2xl font-extrabold text-slate-950 dark:text-white">Scan &amp; Daftarkan Tiket</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Pindai QR dari kamera atau hardware scanner 2D, lalu simpan tiket.</p>
        </div>
        <x-admin.ui.button variant="outline" :href="route('admin.tickets')" data-testid="back-to-tickets-link">
            <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            Daftar Tiket
        </x-admin.ui.button>
    </div>

    @if ($registeredCode)
        <div class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300" data-testid="ticket-registered-banner" role="status">
            <svg aria-hidden="true" class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg>
            QR {{ $registeredCode }} berhasil didaftarkan
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.9fr)]">
        <x-admin.ui.card :padded="true" data-testid="scan-method-section" aria-labelledby="scan-method-title">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-wider text-blue-600 dark:text-blue-400">Metode Pemindaian</p>
                    <h2 id="scan-method-title" class="mt-1 text-lg font-extrabold">Pilih perangkat</h2>
                </div>
                <x-admin.ui.badge variant="blue" data-testid="scan-method-active-badge">{{ $scanMethod === 'device' ? 'Hardware Scanner' : 'Camera QR' }}</x-admin.ui.badge>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-2 rounded-lg bg-slate-100 p-1 dark:bg-slate-800" role="tablist" aria-label="Metode scan" data-testid="scan-method-tabs">
                <button
                    type="button"
                    role="tab"
                    data-testid="camera-method-tab"
                    wire:click="setScanMethod('camera')"
                    x-bind:aria-selected="($wire.scanMethod === 'camera').toString()"
                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md text-sm font-bold transition"
                    x-bind:class="$wire.scanMethod === 'camera' ? 'bg-white text-blue-700 shadow-sm dark:bg-slate-900 dark:text-blue-300' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                >
                    <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2Z"/><circle cx="12" cy="13" r="4"/></svg>
                    Camera QR
                </button>
                <button
                    type="button"
                    role="tab"
                    data-testid="device-method-tab"
                    wire:click="setScanMethod('device')"
                    x-bind:aria-selected="($wire.scanMethod === 'device').toString()"
                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md text-sm font-bold transition"
                    x-bind:class="$wire.scanMethod === 'device' ? 'bg-white text-blue-700 shadow-sm dark:bg-slate-900 dark:text-blue-300' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                >
                    <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V3h4M17 3h4v4M21 17v4h-4M7 21H3v-4M7 12h10"/></svg>
                    Hardware Scanner 2D
                </button>
            </div>

            <div class="mt-5" data-testid="camera-method-panel" x-show="$wire.scanMethod === 'camera'">
                <div class="relative aspect-[16/10] w-full overflow-hidden rounded-lg border border-slate-800 bg-slate-950" data-testid="camera-viewport" x-bind:class="{ 'ring-2 ring-blue-500/40': cameraActive }">
                    <video class="size-full object-cover" data-testid="camera-video" x-ref="cameraVideo" playsinline muted x-bind:class="cameraActive ? 'opacity-100' : 'opacity-0'"></video>

                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-2 px-6 text-center text-slate-300" data-testid="camera-placeholder" x-show="! cameraActive">
                        <span class="grid size-14 place-items-center rounded-full border border-white/15 bg-white/5">
                            <svg aria-hidden="true" class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2Z"/><circle cx="12" cy="13" r="4"/></svg>
                        </span>
                        <strong class="text-sm text-white" x-text="cameraMessage">Kamera belum aktif</strong>
                        <span class="text-xs">Izinkan akses untuk mulai memindai</span>
                    </div>

                    <div class="pointer-events-none absolute inset-[18%]" aria-hidden="true">
                        <span class="absolute left-0 top-0 size-8 rounded-tl-lg border-l-2 border-t-2 border-white/90"></span>
                        <span class="absolute right-0 top-0 size-8 rounded-tr-lg border-r-2 border-t-2 border-white/90"></span>
                        <span class="absolute bottom-0 left-0 size-8 rounded-bl-lg border-b-2 border-l-2 border-white/90"></span>
                        <span class="absolute bottom-0 right-0 size-8 rounded-br-lg border-b-2 border-r-2 border-white/90"></span>
                    </div>

                    <button
                        type="button"
                        data-testid="activate-camera-button"
                        x-show="! cameraActive"
                        x-bind:disabled="cameraLoading"
                        x-on:click="startCamera()"
                        class="absolute bottom-4 left-1/2 inline-flex min-h-11 -translate-x-1/2 items-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-bold text-white shadow-sm shadow-blue-600/20 hover:bg-blue-700 disabled:opacity-60"
                    >
                        <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2Z"/><circle cx="12" cy="13" r="4"/></svg>
                        <span x-text="cameraLoading ? 'Mengaktifkan...' : 'Aktifkan Kamera'">Aktifkan Kamera</span>
                    </button>

                    <button
                        type="button"
                        data-testid="stop-camera-button"
                        x-cloak
                        x-show="cameraActive"
                        x-on:click="stopCamera()"
                        class="absolute bottom-4 left-1/2 inline-flex min-h-11 -translate-x-1/2 items-center gap-2 rounded-lg border border-white/20 bg-slate-950/60 px-4 text-sm font-bold text-white backdrop-blur hover:bg-slate-950/80"
                    >
                        <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>
                        Hentikan Kamera
                    </button>
                </div>

                <p class="mt-3 text-center text-sm text-slate-500 dark:text-slate-400" data-testid="camera-instruction">Arahkan kamera ke QR tiket, QR akan terbaca otomatis.</p>
            </div>

            <div class="mt-5" data-testid="device-method-panel" x-cloak x-show="$wire.scanMethod === 'device'">
                <div class="flex min-h-[280px] flex-col items-center justify-center rounded-lg border border-slate-200 bg-slate-50 px-6 py-10 text-center dark:border-slate-800 dark:bg-slate-800/40">
                    <span class="grid size-16 place-items-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300">
                        <svg aria-hidden="true" class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V3h4M17 3h4v4M21 17v4h-4M7 21H3v-4M7 12h10"/></svg>
                    </span>
                    <p class="mt-4 text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Perangkat Terhubung</p>
                    <h3 class="mt-1 text-lg font-extrabold" data-testid="device-ready-title">Scanner siap</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Scan QR menggunakan perangkat scanner</p>

                    <label class="mt-5 flex w-full max-w-md items-center gap-3 rounded-lg border border-slate-300 bg-white px-3 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/15 dark:border-slate-700 dark:bg-slate-900" for="hardware-scanner-input">
                        <svg aria-hidden="true" class="size-5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V3h4M17 3h4v4M21 17v4h-4M7 21H3v-4M7 12h10"/></svg>
                        <input
                            id="hardware-scanner-input"
                            type="text"
                            inputmode="text"
                            autocomplete="off"
                            placeholder="Menunggu QR..."
                            data-testid="hardware-scanner-input"
                            x-ref="hardwareInput"
                            x-model="hardwareCode"
                            x-on:keydown.enter.prevent="submitHardwareCode()"
                            class="w-full min-h-11 border-0 bg-transparent text-sm font-semibold text-slate-950 outline-none placeholder:text-slate-400 dark:text-white"
                        >
                    </label>

                    <small class="mt-3 text-xs text-slate-400" data-testid="device-input-hint">Input otomatis diproses setelah scanner mengirim Enter</small>
                </div>
            </div>
        </x-admin.ui.card>

        <x-admin.ui.card :padded="true" data-testid="ticket-target-section" aria-labelledby="ticket-target-title">
            <h2 id="ticket-target-title" class="text-lg font-extrabold">Target Tiket</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Event dan kategori wajib dipilih sebelum menyimpan.</p>

            <form wire:submit="save" class="mt-5 space-y-4">
                <x-admin.form.select
                    label="Event"
                    id="ticket-create-event"
                    wire:model.live="event_id"
                    data-testid="ticket-event-input"
                >
                    <option value="">Pilih Event</option>
                    @foreach ($events as $eventOption)
                        <option value="{{ $eventOption->id }}">{{ $eventOption->name }}</option>
                    @endforeach
                </x-admin.form.select>

                <x-admin.form.select
                    label="Kategori"
                    id="ticket-create-category"
                    wire:model="ticket_category_id"
                    data-testid="ticket-category-input"
                >
                    <option value="">Pilih Kategori</option>
                    @foreach ($categories as $categoryOption)
                        <option value="{{ $categoryOption->id }}">{{ $categoryOption->name }}</option>
                    @endforeach
                </x-admin.form.select>

                <x-admin.form.input
                    label="QR Code"
                    id="ticket-create-qr-code"
                    wire:model="qr_code"
                    placeholder="Menunggu hasil scan..."
                    autocomplete="off"
                    data-testid="ticket-qr-code-input"
                />

                <div data-testid="ticket-qr-preview" x-cloak x-show="detectedCode !== ''" class="flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-bold text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300">
                    <svg aria-hidden="true" class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM18 18h3v3h-3z"/></svg>
                    <span class="truncate">QR terdeteksi: <span x-text="detectedCode"></span></span>
                </div>

                <div class="flex flex-col gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end dark:border-slate-800">
                    <x-admin.ui.button variant="outline" type="button" x-on:click="rearm()" data-testid="reset-scan-button">Scan Ulang</x-admin.ui.button>
                    <x-admin.ui.button type="submit" data-testid="ticket-save-button">
                        <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                        Simpan Tiket
                    </x-admin.ui.button>
                </div>
            </form>
        </x-admin.ui.card>
    </div>
</div>
