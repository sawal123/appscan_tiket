@php
    $summary = $this->summary;
    $scannerRows = $this->scannerRows;
    $lastActivities = $this->lastActivities;
@endphp

<div>
    <div class="mx-auto max-w-[1480px] space-y-6">
        <div>
            <p class="text-sm font-bold text-blue-600 dark:text-blue-400">Operasional Event</p>
            <h2 data-testid="scanner-operations-heading" class="mt-1 text-2xl font-extrabold text-slate-950 dark:text-white">Operasional Scanner</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ringkasan perangkat dan aktivitas scan pada event aktif.</p>
        </div>

        <section aria-labelledby="scanner-operations-summary-title">
            <h2 id="scanner-operations-summary-title" class="sr-only">Ringkasan Operasional Scanner</h2>
            <div class="grid grid-cols-1 gap-3 min-[390px]:grid-cols-2 xl:grid-cols-4">
                <x-admin.stat-card title="Scanner Online" :value="$summary['online']" subtitle="Heartbeat < 2 menit" icon="scanner" accent="emerald" test-id="operations-online-card" />
                <x-admin.stat-card title="Total Scan Hari Ini" :value="$summary['totalToday']" subtitle="Event aktif" icon="verified" accent="blue" test-id="operations-today-card" />
                <x-admin.stat-card title="Scan 10 Menit" :value="$summary['recent']" subtitle="Velocity terbaru" icon="clock" accent="indigo" test-id="operations-velocity-card" />
                <x-admin.stat-card title="Scanner Idle" :value="$summary['idle']" subtitle="Online tanpa scan >10 menit" icon="clock" accent="amber" test-id="operations-idle-card" />
            </div>
        </section>

        <x-admin.ui.card :overflow="true" aria-labelledby="scanner-operations-table-title">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6 dark:border-slate-800">
                <h2 id="scanner-operations-table-title" class="text-lg font-extrabold">Scanner</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ count($scannerRows) }} sesi scanner ditampilkan</p>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                        <tr>
                            <th class="px-6 py-3">Scanner</th>
                            <th class="px-4 py-3">Event Assigned</th>
                            <th class="px-4 py-3">Device</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Last Seen</th>
                            <th class="px-4 py-3">Last Scan</th>
                            <th class="px-6 py-3 text-right">Jumlah Scan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($scannerRows as $row)
                            <tr data-testid="operations-scanner-row-{{ $row['id'] }}" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                <td class="px-6 py-3.5 font-extrabold text-slate-950 dark:text-white">{{ $row['scanner'] }}</td>
                                <td class="px-4 py-3.5 font-semibold text-slate-700 dark:text-slate-300">{{ $row['event'] }}</td>
                                <td class="px-4 py-3.5 text-slate-600 dark:text-slate-300">{{ $row['device'] }}</td>
                                <td class="px-4 py-3.5"><x-admin.ui.badge :variant="$row['statusVariant']" dot data-testid="operations-scanner-status-{{ $row['id'] }}">{{ $row['status'] }}</x-admin.ui.badge></td>
                                <td class="px-4 py-3.5 tabular-nums text-slate-600 dark:text-slate-300">{{ $row['lastSeen'] }}</td>
                                <td class="px-4 py-3.5 tabular-nums text-slate-600 dark:text-slate-300">{{ $row['lastScan'] }}</td>
                                <td class="px-6 py-3.5 text-right font-extrabold">{{ number_format($row['scans']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada sesi scanner.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 md:hidden dark:divide-slate-800">
                @forelse ($scannerRows as $row)
                    <article data-testid="operations-scanner-card-{{ $row['id'] }}" class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-extrabold text-slate-950 dark:text-white">{{ $row['scanner'] }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $row['device'] }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">Event: {{ $row['event'] }}</p>
                            </div>
                            <x-admin.ui.badge :variant="$row['statusVariant']" size="sm">{{ $row['status'] }}</x-admin.ui.badge>
                        </div>
                        <dl class="grid grid-cols-2 gap-3 text-xs">
                            <div><dt class="font-bold text-slate-400">Last Seen</dt><dd class="mt-1 font-semibold text-slate-700 dark:text-slate-300">{{ $row['lastSeen'] }}</dd></div>
                            <div><dt class="font-bold text-slate-400">Last Scan</dt><dd class="mt-1 font-semibold text-slate-700 dark:text-slate-300">{{ $row['lastScan'] }}</dd></div>
                            <div><dt class="font-bold text-slate-400">Jumlah Scan</dt><dd class="mt-1 font-semibold text-slate-700 dark:text-slate-300">{{ number_format($row['scans']) }}</dd></div>
                        </dl>
                    </article>
                @empty
                    <p class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada sesi scanner.</p>
                @endforelse
            </div>
        </x-admin.ui.card>

        <x-admin.ui.card :overflow="true" aria-labelledby="scanner-last-activity-title">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6 dark:border-slate-800">
                <h2 id="scanner-last-activity-title" class="text-lg font-extrabold">Last Activity</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">10 scan terakhir pada event aktif</p>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                        <tr><th class="px-6 py-3">Waktu</th><th class="px-4 py-3">Scanner</th><th class="px-4 py-3">QR</th><th class="px-6 py-3">Kategori</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($lastActivities as $activity)
                            <tr data-testid="operations-activity-row-{{ $activity['id'] }}" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                <td class="px-6 py-3.5 tabular-nums text-slate-600 dark:text-slate-300">{{ $activity['time'] }}</td>
                                <td class="px-4 py-3.5 font-semibold">{{ $activity['scanner'] }}</td>
                                <td class="px-4 py-3.5 font-extrabold">{{ $activity['qr'] }}</td>
                                <td class="px-6 py-3.5 text-slate-600 dark:text-slate-300">{{ $activity['category'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada aktivitas scan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 md:hidden dark:divide-slate-800">
                @forelse ($lastActivities as $activity)
                    <article data-testid="operations-activity-card-{{ $activity['id'] }}" class="p-4">
                        <p class="font-extrabold text-slate-950 dark:text-white">{{ $activity['qr'] }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $activity['category'] }} &middot; {{ $activity['scanner'] }} &middot; {{ $activity['time'] }}</p>
                    </article>
                @empty
                    <p class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada aktivitas scan.</p>
                @endforelse
            </div>
        </x-admin.ui.card>
    </div>
</div>
