<div>
    <div class="mx-auto max-w-[1480px] space-y-6">
        <div>
            <p class="text-sm font-bold text-blue-600 dark:text-blue-400">Scanner Device</p>
            <h2 data-testid="scanner-monitoring-heading" class="mt-1 text-2xl font-extrabold text-slate-950 dark:text-white">Monitoring Scanner</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Pantau heartbeat dan aktivitas scanner.</p>
        </div>

        <x-admin.ui.card :overflow="true" aria-labelledby="scanner-monitoring-list-title">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6 dark:border-slate-800">
                <h2 id="scanner-monitoring-list-title" class="text-lg font-extrabold">Perangkat Scanner</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $sessions->count() }} sesi ditampilkan</p>
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
                            <th class="px-6 py-3 text-right">Scan Event Aktif</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($sessions as $session)
                            @php($online = $session->isOnline())
                            <tr data-testid="scanner-session-row-{{ $session->id }}" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                <td class="px-6 py-3.5">
                                    <p class="font-extrabold text-slate-950 dark:text-white">{{ $session->user?->name ?? 'Scanner' }}</p>
                                    <p class="mt-0.5 text-xs text-slate-400">{{ $session->user?->email ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-3.5 font-semibold text-slate-700 dark:text-slate-300">{{ $session->user?->scannerEventAssignment?->event?->name ?? '-' }}</td>
                                <td class="px-4 py-3.5">
                                    <p class="font-semibold text-slate-700 dark:text-slate-300">{{ $session->device_name ?? '-' }}</p>
                                    <p class="mt-0.5 max-w-[220px] truncate text-xs text-slate-400">{{ $session->device_id ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-3.5">
                                    <x-admin.ui.badge :variant="$online ? 'emerald' : 'slate'" dot data-testid="scanner-session-status-{{ $session->id }}">{{ $online ? 'Online' : 'Offline' }}</x-admin.ui.badge>
                                </td>
                                <td class="px-4 py-3.5 tabular-nums text-slate-600 dark:text-slate-300">{{ $session->last_seen_at->translatedFormat('d M Y H:i') }}</td>
                                <td class="px-4 py-3.5 tabular-nums text-slate-600 dark:text-slate-300">{{ $session->last_scan_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                                <td class="px-6 py-3.5 text-right font-extrabold">{{ number_format($scanTotals->get($session->user_id, 0)) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada scanner yang mengirim heartbeat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 md:hidden dark:divide-slate-800">
                @forelse ($sessions as $session)
                    @php($online = $session->isOnline())
                    <article data-testid="scanner-session-card-{{ $session->id }}" class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-extrabold text-slate-950 dark:text-white">{{ $session->user?->name ?? 'Scanner' }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $session->device_name ?? '-' }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">Event: {{ $session->user?->scannerEventAssignment?->event?->name ?? '-' }}</p>
                            </div>
                            <x-admin.ui.badge :variant="$online ? 'emerald' : 'slate'" size="sm">{{ $online ? 'Online' : 'Offline' }}</x-admin.ui.badge>
                        </div>
                        <dl class="grid grid-cols-2 gap-3 text-xs">
                            <div>
                                <dt class="font-bold text-slate-400">Last Seen</dt>
                                <dd class="mt-1 font-semibold text-slate-700 dark:text-slate-300">{{ $session->last_seen_at->format('d/m/Y H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="font-bold text-slate-400">Last Scan</dt>
                                <dd class="mt-1 font-semibold text-slate-700 dark:text-slate-300">{{ $session->last_scan_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="font-bold text-slate-400">Scan Event Aktif</dt>
                                <dd class="mt-1 font-semibold text-slate-700 dark:text-slate-300">{{ number_format($scanTotals->get($session->user_id, 0)) }}</dd>
                            </div>
                        </dl>
                    </article>
                @empty
                    <p class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada scanner yang mengirim heartbeat.</p>
                @endforelse
            </div>
        </x-admin.ui.card>
    </div>
</div>
