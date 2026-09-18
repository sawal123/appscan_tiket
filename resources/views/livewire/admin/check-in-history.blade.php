@php
    $statusVariants = [
        'success' => 'emerald',
        'already_checked_in' => 'amber',
        'invalid' => 'red',
    ];

    $statusLabels = [
        'success' => 'Success',
        'already_checked_in' => 'Already Checked In',
        'invalid' => 'Invalid',
    ];
@endphp

<div>
    <div class="mx-auto max-w-[1480px] space-y-6">
        <div>
            <p class="text-sm font-bold text-blue-600 dark:text-blue-400">Audit Log</p>
            <h2 data-testid="check-in-history-heading" class="mt-1 text-2xl font-extrabold text-slate-950 dark:text-white">Riwayat Check-in</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Catatan percobaan scan tiket.</p>
        </div>

        <x-admin.ui.card :overflow="true" aria-labelledby="check-in-history-list-title">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6 dark:border-slate-800">
                <h2 id="check-in-history-list-title" class="text-lg font-extrabold">Semua Scan</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $logs->count() }} log ditampilkan</p>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                        <tr>
                            <th class="px-6 py-3">QR</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3">Waktu Scan</th>
                            <th class="px-4 py-3">Scanner</th>
                            <th class="px-6 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($logs as $log)
                            <tr data-testid="check-in-log-row-{{ $log->id }}" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                <td class="px-6 py-3.5 font-extrabold text-slate-950 dark:text-white">{{ $log->ticket?->qr_code ?? $log->qr_code ?? '-' }}</td>
                                <td class="px-4 py-3.5 text-slate-600 dark:text-slate-300">{{ $log->ticket?->ticketCategory?->name ?? '-' }}</td>
                                <td class="px-4 py-3.5 tabular-nums text-slate-600 dark:text-slate-300">{{ $log->scanned_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                                <td class="px-4 py-3.5 text-slate-600 dark:text-slate-300">{{ $log->scanner?->name ?? '-' }}</td>
                                <td class="px-6 py-3.5">
                                    <x-admin.ui.badge :variant="$statusVariants[$log->status] ?? 'slate'" dot data-testid="check-in-log-status-{{ $log->id }}">{{ $statusLabels[$log->status] ?? $log->status }}</x-admin.ui.badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada riwayat check-in.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 md:hidden dark:divide-slate-800">
                @forelse ($logs as $log)
                    <article data-testid="check-in-log-card-{{ $log->id }}" class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="break-all font-extrabold text-slate-950 dark:text-white">{{ $log->ticket?->qr_code ?? $log->qr_code ?? '-' }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $log->ticket?->ticketCategory?->name ?? '-' }} &middot; {{ $log->scanner?->name ?? '-' }}</p>
                            </div>
                            <x-admin.ui.badge :variant="$statusVariants[$log->status] ?? 'slate'" size="sm" class="shrink-0">{{ $statusLabels[$log->status] ?? $log->status }}</x-admin.ui.badge>
                        </div>
                        <p class="text-xs font-semibold tabular-nums text-slate-700 dark:text-slate-300">{{ $log->scanned_at?->format('d/m/Y H:i') ?? '-' }}</p>
                    </article>
                @empty
                    <p class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada riwayat check-in.</p>
                @endforelse
            </div>
        </x-admin.ui.card>
    </div>
</div>
