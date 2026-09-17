@php
    $checkInPercentage = $this->checkInPercentage;
    $formattedTotalTickets = number_format($totalTickets);
    $formattedVerifiedTickets = number_format($verifiedTickets);
    $formattedUnverifiedTickets = number_format($unverifiedTickets);
    $offlineScanners = $maxScanners - $activeScanners;
    $onlineScannerCount = collect($scannerStatuses)->where('status', 'Online')->count();
    $categoryStyles = [
        'blue' => [
            'badge' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
            'bar' => 'bg-blue-500',
        ],
        'emerald' => [
            'badge' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
            'bar' => 'bg-emerald-500',
        ],
        'amber' => [
            'badge' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
            'bar' => 'bg-amber-500',
        ],
    ];
@endphp

<div class="mx-auto max-w-[1480px] space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p data-testid="dashboard-date" class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ $dashboardDate }}</p>
            <h2 data-testid="welcome-heading" class="mt-1 text-2xl font-extrabold text-slate-950 dark:text-white">Selamat malam, Admin.</h2>
        </div>
        <div data-testid="last-updated-status" class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400"><span class="size-1.5 rounded-full bg-emerald-500"></span>Data diperbarui {{ $lastUpdated }}</div>
    </div>

    <section aria-labelledby="stats-heading">
        <h2 id="stats-heading" class="sr-only">Statistik Event</h2>
        <div class="grid grid-cols-1 gap-3 min-[390px]:grid-cols-2 xl:grid-cols-4">
            <x-admin.stat-card
                title="Total QR Terdaftar"
                :value="$formattedTotalTickets"
                subtitle="Kapasitas penuh"
                icon="qr"
                accent="blue"
                test-id="total-qr-stat-card"
            />
            <x-admin.stat-card
                title="Sudah Diverifikasi"
                :value="$formattedVerifiedTickets"
                subtitle="{{ $checkInPercentage }}% check-in"
                icon="verified"
                accent="emerald"
                test-id="verified-stat-card"
            />
            <x-admin.stat-card
                title="Belum Diverifikasi"
                :value="$formattedUnverifiedTickets"
                subtitle="{{ number_format(($unverifiedTickets / $totalTickets) * 100, 1) }}% tersisa"
                icon="clock"
                accent="amber"
                test-id="unverified-stat-card"
            />
            <x-admin.stat-card
                title="Scanner Aktif"
                value="{{ $activeScanners }} <span class=&quot;text-base text-slate-400&quot;>/ {{ $maxScanners }}</span>"
                subtitle="{{ $offlineScanners }} perangkat offline"
                icon="scanner"
                accent="indigo"
                test-id="active-scanner-stat-card"
            />
        </div>
    </section>

    <section data-testid="checkin-progress-section" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm shadow-slate-200/40 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none" aria-labelledby="progress-title">
        <div class="grid lg:grid-cols-[1fr_auto]">
            <div class="p-5 sm:p-6">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-wider text-blue-600 dark:text-blue-400">Live Overview</p>
                        <h2 id="progress-title" class="mt-1 text-lg font-extrabold">Progress Check-in</h2>
                        <p data-testid="progress-description" class="mt-1 text-sm text-slate-500 dark:text-slate-400"><strong class="text-slate-700 dark:text-slate-200">{{ $formattedVerifiedTickets }}</strong> dari <strong class="text-slate-700 dark:text-slate-200">{{ $formattedTotalTickets }}</strong> tiket telah diverifikasi</p>
                    </div>
                    <p data-testid="progress-percentage" class="text-4xl font-extrabold text-blue-600 dark:text-blue-400">{{ $checkInPercentage }}<span class="text-xl">%</span></p>
                </div>
                <div class="mt-6 h-3 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800" role="progressbar" aria-label="Progress check-in" aria-valuenow="{{ $checkInPercentage }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="h-full rounded-full bg-blue-600" style="width: {{ $checkInPercentage }}%"></div>
                </div>
            </div>
            <div class="grid grid-cols-3 border-t border-slate-100 bg-slate-50/70 lg:w-[390px] lg:border-l lg:border-t-0 dark:border-slate-800 dark:bg-slate-800/35">
                <div class="p-4 text-center lg:flex lg:flex-col lg:justify-center"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Target Tiket</p><p data-testid="target-ticket-value" class="mt-1 text-base font-extrabold">{{ $formattedTotalTickets }}</p></div>
                <div class="border-x border-slate-200 p-4 text-center lg:flex lg:flex-col lg:justify-center dark:border-slate-700"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Sudah Masuk</p><p data-testid="checkedin-ticket-value" class="mt-1 text-base font-extrabold text-emerald-600 dark:text-emerald-400">{{ $formattedVerifiedTickets }}</p></div>
                <div class="p-4 text-center lg:flex lg:flex-col lg:justify-center"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Sisa</p><p data-testid="remaining-ticket-value" class="mt-1 text-base font-extrabold text-amber-600 dark:text-amber-400">{{ $formattedUnverifiedTickets }}</p></div>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(320px,0.75fr)]">
        <div class="space-y-6">
            <section data-testid="ticket-category-section" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/40 sm:p-6 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none" aria-labelledby="category-title">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 id="category-title" class="text-lg font-extrabold">Kategori Tiket</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Perbandingan check-in tiap kategori</p>
                    </div>
                    <span class="grid size-10 place-items-center rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                        <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 2 4 4-4 4-4-4 4-4ZM6 14l4 4-4 4-4-4 4-4Zm12 0 4 4-4 4-4-4 4-4Z"/></svg>
                    </span>
                </div>
                <div class="mt-6 grid gap-5 md:grid-cols-3 md:gap-0 md:divide-x md:divide-slate-200 dark:md:divide-slate-800">
                    @foreach ($ticketCategories as $category)
                        @php
                            $style = $categoryStyles[$category['color']];
                            $positionClass = $loop->first ? 'md:pr-5' : ($loop->last ? 'md:pl-5' : 'md:px-5');
                        @endphp
                        <div data-testid="{{ $category['testId'] }}" class="{{ $positionClass }}">
                            <div class="flex items-end justify-between">
                                <div>
                                    <span class="inline-flex rounded px-2 py-1 text-[10px] font-extrabold {{ $style['badge'] }}">{{ $category['label'] }}</span>
                                    <p class="mt-2 text-sm font-bold">{{ number_format($category['verified']) }} <span class="font-medium text-slate-400">/ {{ number_format($category['total']) }}</span></p>
                                </div>
                                <p class="text-lg font-extrabold">{{ $category['percentage'] }}%</p>
                            </div>
                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div class="h-full rounded-full {{ $style['bar'] }}" style="width: {{ $category['percentage'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section data-testid="recent-verification-section" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm shadow-slate-200/40 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none" aria-labelledby="recent-title">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6 dark:border-slate-800">
                    <div>
                        <h2 id="recent-title" class="text-lg font-extrabold">Verifikasi Terbaru</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Aktivitas masuk secara real-time</p>
                    </div>
                    <a href="#riwayat" data-testid="view-all-verifications-link" class="inline-flex min-h-10 items-center gap-1.5 text-sm font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400">Lihat Semua<svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg></a>
                </div>
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                            <tr><th class="px-6 py-3">QR Code</th><th class="px-4 py-3">Kategori</th><th class="px-4 py-3">Waktu</th><th class="px-4 py-3">Scanner</th><th class="px-6 py-3 text-right">Status</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($recentVerifications as $verification)
                                <tr data-testid="verification-row-{{ strtolower($verification['qrCode']) }}" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                    <td class="px-6 py-3.5 font-extrabold">{{ $verification['qrCode'] }}</td>
                                    <td class="px-4 py-3.5"><span class="font-semibold">{{ $verification['category'] }}</span></td>
                                    <td class="px-4 py-3.5 tabular-nums text-slate-500 dark:text-slate-400">{{ $verification['time'] }}</td>
                                    <td class="px-4 py-3.5 font-semibold">{{ $verification['scanner'] }}</td>
                                    <td class="px-6 py-3.5 text-right"><span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"><span class="size-1.5 rounded-full bg-emerald-500"></span>{{ $verification['status'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="divide-y divide-slate-100 md:hidden dark:divide-slate-800">
                    @foreach ($recentVerifications as $verification)
                        <article data-testid="mobile-verification-card-{{ strtolower($verification['qrCode']) }}" class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-extrabold">{{ $verification['qrCode'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $verification['category'] }} &middot; {{ $verification['scanner'] }} &middot; {{ $verification['time'] }}</p>
                                </div>
                                <span class="rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">{{ $verification['status'] }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>

        <aside class="space-y-6" aria-label="Informasi operasional">
            <section data-testid="quick-actions-section" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/40 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none" aria-labelledby="quick-actions-title">
                <h2 id="quick-actions-title" class="text-lg font-extrabold">Aksi Cepat</h2>
                <div class="mt-4 grid grid-cols-2 gap-2.5">
                    <button data-testid="register-qr-action-button" type="button" class="flex min-h-12 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-bold hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:bg-slate-800 dark:hover:border-blue-500 dark:hover:bg-blue-500/10"><svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>Registrasi QR</button>
                    <button data-testid="add-category-action-button" type="button" class="flex min-h-12 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-bold hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:bg-slate-800 dark:hover:border-blue-500 dark:hover:bg-blue-500/10"><svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>Tambah Kategori</button>
                    <button data-testid="add-scanner-action-button" type="button" class="flex min-h-12 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-bold hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:bg-slate-800 dark:hover:border-blue-500 dark:hover:bg-blue-500/10"><svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>Tambah Scanner</button>
                    <a href="{{ route('scanner.index') }}" data-testid="open-scanner-action-button" class="flex min-h-12 items-center justify-center gap-2 rounded-lg bg-blue-600 px-3 text-sm font-bold text-white shadow-sm shadow-blue-600/20 hover:bg-blue-700"><svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h3m10 0h3v3M4 17v3h3m10 0h3v-3M7 12h10"/></svg>Buka Scanner</a>
                </div>
            </section>

            <section data-testid="scanner-status-section" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/40 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none" aria-labelledby="scanner-status-title">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 id="scanner-status-title" class="text-lg font-extrabold">Status Scanner</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">4 gate utama</p>
                    </div>
                    <span data-testid="scanner-online-count" class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">{{ $onlineScannerCount }} Online</span>
                </div>
                <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($scannerStatuses as $scanner)
                        @php($isOnline = $scanner['status'] === 'Online')
                        <div data-testid="{{ $scanner['testId'] }}" class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                            @if ($loop->first && $isOnline)
                                <span class="relative flex size-2.5"><span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-50"></span><span class="relative size-2.5 rounded-full bg-emerald-500"></span></span>
                            @else
                                <span class="size-2.5 rounded-full {{ $isOnline ? 'bg-emerald-500' : 'bg-red-400' }}"></span>
                            @endif
                            <div class="flex-1">
                                <p class="text-sm font-bold">{{ $scanner['gate'] }}</p>
                                <p class="text-xs {{ $isOnline ? 'text-slate-400' : 'text-red-500' }}">{{ $scanner['status'] }}</p>
                            </div>
                            <p class="text-sm font-extrabold">{{ $scanner['scans'] }} <span class="text-xs font-medium text-slate-400">scan</span></p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section data-testid="active-event-information" class="relative overflow-hidden rounded-lg bg-slate-900 p-5 text-white shadow-sm dark:border dark:border-slate-700" aria-labelledby="event-info-title">
                <div class="absolute right-2 top-2 size-20 rounded-full border-[14px] border-blue-500/20"></div>
                <p class="text-[10px] font-extrabold uppercase tracking-widest text-blue-300">Event Aktif</p>
                <h2 id="event-info-title" class="mt-2 text-xl font-extrabold">{{ $activeEvent }}</h2>
                <dl class="mt-5 grid grid-cols-2 gap-x-4 gap-y-4 text-sm">
                    <div><dt class="text-xs text-slate-400">Tanggal</dt><dd data-testid="event-date" class="mt-1 font-bold">{{ $eventDate }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Lokasi</dt><dd data-testid="event-location" class="mt-1 font-bold">{{ $eventLocation }}</dd></div>
                    <div class="col-span-2 flex items-center justify-between border-t border-slate-700 pt-4"><dt class="text-xs text-slate-400">Status Event</dt><dd data-testid="event-status" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/15 px-2.5 py-1 text-xs font-extrabold text-emerald-300"><span class="size-1.5 rounded-full bg-emerald-400"></span>AKTIF</dd></div>
                </dl>
            </section>
        </aside>
    </div>

    <footer data-testid="dashboard-footer" class="flex flex-col gap-2 border-t border-slate-200 pt-5 text-xs text-slate-400 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800"><p>&copy; 2026 Gateflow Ticket System</p><p>Operational dashboard &middot; Waktu server 19:42 WIB</p></footer>
</div>
