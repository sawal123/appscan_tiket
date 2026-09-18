@php
    $summary = $this->summary;
    $categoryBreakdown = $this->categoryBreakdown;
    $scannerBreakdown = $this->scannerBreakdown;
@endphp

<div class="mx-auto max-w-[1480px] space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-bold text-blue-600 dark:text-blue-400">Laporan</p>
            <h2 data-testid="report-heading" class="mt-1 text-2xl font-extrabold text-slate-950 dark:text-white">Laporan Check-in</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Rekap aktivitas scan tiket event aktif.</p>
        </div>
        <x-admin.form.select
            label="Periode"
            label-class="sr-only"
            :flush="true"
            id="report-period"
            wire:model.live="period"
            data-testid="report-period-filter"
            wrapper="shrink-0 sm:w-56"
        >
            @foreach ($this->periodOptions() as $option)
                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
            @endforeach
        </x-admin.form.select>
    </div>

    <section aria-labelledby="report-summary-heading">
        <h2 id="report-summary-heading" class="sr-only">Ringkasan</h2>
        <div class="grid grid-cols-1 gap-3 min-[390px]:grid-cols-2 xl:grid-cols-4">
            <x-admin.stat-card
                title="Total Tiket"
                :value="number_format($summary['total'])"
                subtitle="Event aktif"
                icon="qr"
                accent="blue"
                test-id="report-summary-total"
            />
            <x-admin.stat-card
                title="Success"
                :value="number_format($summary['success'])"
                subtitle="Check-in berhasil"
                icon="verified"
                accent="emerald"
                test-id="report-summary-success"
            />
            <x-admin.stat-card
                title="Already Checked In"
                :value="number_format($summary['alreadyCheckedIn'])"
                subtitle="Scan ulang"
                icon="clock"
                accent="amber"
                test-id="report-summary-already"
            />
            <x-admin.stat-card
                title="Invalid"
                :value="number_format($summary['invalid'])"
                subtitle="QR tidak dikenal"
                icon="scanner"
                accent="red"
                test-id="report-summary-invalid"
            />
        </div>
    </section>

    <x-admin.ui.card :overflow="true" data-testid="report-category-section" aria-labelledby="report-category-title">
        <div class="border-b border-slate-100 px-5 py-4 sm:px-6 dark:border-slate-800">
            <h2 id="report-category-title" class="text-lg font-extrabold">Rincian Kategori</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Check-in per kategori tiket event aktif</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                    <tr>
                        <th class="px-6 py-3">Kategori</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Checked-in</th>
                        <th class="px-6 py-3">Progress</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($categoryBreakdown as $category)
                        <tr data-testid="{{ $category['testId'] }}">
                            <td class="px-6 py-3.5 font-extrabold text-slate-950 dark:text-white">{{ $category['name'] }}</td>
                            <td class="px-4 py-3.5 tabular-nums text-slate-600 dark:text-slate-300">{{ number_format($category['total']) }}</td>
                            <td class="px-4 py-3.5 tabular-nums text-slate-600 dark:text-slate-300">{{ number_format($category['checkedIn']) }}</td>
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="h-2 w-32 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                        <div class="h-full rounded-full bg-blue-500" style="width: {{ $category['percentage'] }}%"></div>
                                    </div>
                                    <span data-testid="{{ $category['testId'] }}-value" class="tabular-nums text-xs font-bold text-slate-600 dark:text-slate-300">{{ $category['percentage'] }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" data-testid="report-category-empty" class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada kategori pada event aktif.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.ui.card>

    <x-admin.ui.card :overflow="true" data-testid="report-scanner-section" aria-labelledby="report-scanner-title">
        <div class="border-b border-slate-100 px-5 py-4 sm:px-6 dark:border-slate-800">
            <h2 id="report-scanner-title" class="text-lg font-extrabold">Aktivitas Scanner</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Jumlah scan per petugas pada periode terpilih</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                    <tr>
                        <th class="px-6 py-3 w-16">#</th>
                        <th class="px-4 py-3">Scanner</th>
                        <th class="px-6 py-3 text-right">Jumlah Scan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($scannerBreakdown as $index => $scanner)
                        <tr data-testid="report-scanner-row-{{ $scanner['id'] }}">
                            <td class="px-6 py-3.5 tabular-nums text-slate-400">{{ $index + 1 }}</td>
                            <td class="px-4 py-3.5 font-bold text-slate-950 dark:text-white">{{ $scanner['name'] }}</td>
                            <td data-testid="report-scanner-scans-{{ $scanner['id'] }}" class="px-6 py-3.5 text-right font-extrabold tabular-nums">{{ number_format($scanner['scans']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" data-testid="report-scanner-empty" class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada aktivitas scan pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.ui.card>
</div>
