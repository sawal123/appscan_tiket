<x-scanner.layout
    title="Tiket Terverifikasi — Ticket Scanner"
    description="Daftar tiket yang telah terverifikasi di event aktif."
    page="verified"
    body-class="app-body"
>
    <div
        x-data="verifiedApp(@js($tickets))"
        x-on:keydown.meta.k.window.prevent="$refs.search?.focus()"
        x-on:keydown.ctrl.k.window.prevent="$refs.search?.focus()"
    >
        <x-scanner.header
            :subtitle="$activeEvent?->name ?? 'Belum ada event aktif'"
            :scanner-name="$scannerName"
            :scanner-initials="$scannerInitials"
        />

        <main class="verified-page">
            <section class="verified-hero" aria-labelledby="verified-title">
                <div>
                    <p class="eyebrow" data-testid="verified-event-label">{{ $activeEvent?->name ?? 'Belum ada event aktif' }} &middot; {{ $activeEvent?->location ?? '—' }}</p>
                    <h1 id="verified-title" data-testid="verified-page-title">Tiket Terverifikasi</h1>
                    <p class="verified-count" id="verifiedCount" data-testid="verified-ticket-count" x-text="countLabel">0 tiket telah masuk</p>
                </div>
                <a class="button button--primary verified-scan-button" href="{{ route('scanner.index') }}" data-testid="verified-scan-ticket-link"><i data-lucide="scan-line"></i>Scan tiket</a>
            </section>

            <section class="verified-tools" aria-label="Pencarian dan filter tiket">
                <label class="search-shell" for="ticketSearch">
                    <i data-lucide="search"></i>
                    <input id="ticketSearch" type="search" autocomplete="off" placeholder="Cari kode QR..." data-testid="verified-search-input" x-ref="search" x-model="search">
                    <kbd>⌘ K</kbd>
                </label>

                <div class="filter-row" role="group" aria-label="Filter kategori" data-testid="verified-filter-group">
                    <button class="filter-chip" type="button" data-filter="Semua" data-testid="filter-all-button" x-bind:class="{ 'is-active': filter === 'Semua' }" x-on:click="filter = 'Semua'">Semua</button>
                    <button class="filter-chip" type="button" data-filter="VIP" data-testid="filter-vip-button" x-bind:class="{ 'is-active': filter === 'VIP' }" x-on:click="filter = 'VIP'">VIP</button>
                    <button class="filter-chip" type="button" data-filter="Regular" data-testid="filter-regular-button" x-bind:class="{ 'is-active': filter === 'Regular' }" x-on:click="filter = 'Regular'">Regular</button>
                    <button class="filter-chip" type="button" data-filter="VVIP" data-testid="filter-vvip-button" x-bind:class="{ 'is-active': filter === 'VVIP' }" x-on:click="filter = 'VVIP'">VVIP</button>
                </div>
            </section>

            <section class="ticket-list-section" aria-labelledby="list-title">
                <div class="list-header">
                    <h2 id="list-title" data-testid="verified-list-title">Check-in terbaru</h2>
                </div>

                <div class="ticket-table-head" aria-hidden="true" x-show="tickets.length > 0">
                    <span>Tiket</span><span>Waktu</span><span>Gate</span><span>Status</span>
                </div>

                <div class="verified-list" id="verifiedList" data-testid="verified-ticket-list" x-show="tickets.length > 0">
                    <template x-for="(ticket, index) in filtered" :key="ticket.code">
                        <article
                            class="ticket-row"
                            x-init="$nextTick(() => window.renderIcons())"
                            x-bind:data-testid="'verified-ticket-row-' + index"
                            x-bind:style="'animation-delay:' + Math.min(index * 35, 210) + 'ms'"
                        >
                            <div class="ticket-identity">
                                <span class="ticket-check" aria-hidden="true"><i data-lucide="check"></i></span>
                                <span class="ticket-code">
                                    <span class="ticket-badge" x-bind:data-testid="'ticket-category-' + index" x-text="ticket.category"></span>
                                    <strong x-bind:data-testid="'ticket-code-' + index" x-text="ticket.code"></strong>
                                </span>
                            </div>
                            <span class="ticket-cell" x-bind:data-testid="'ticket-time-' + index" x-text="ticket.time"></span>
                            <span class="ticket-cell" x-bind:data-testid="'ticket-gate-' + index" x-text="ticket.gate"></span>
                            <span class="ticket-status" x-bind:data-testid="'ticket-status-' + index">Masuk</span>
                        </article>
                    </template>
                </div>

                <div class="empty-state" id="emptyState" data-testid="verified-empty-state" x-show="tickets.length === 0" x-cloak>
                    <span class="empty-state__icon"><i data-lucide="ticket-check"></i></span>
                    <h2 data-testid="empty-state-title">Belum Ada Tiket Terverifikasi</h2>
                    <p data-testid="empty-state-description">Tiket yang berhasil check-in akan muncul di sini.</p>
                    <a class="button button--primary" href="{{ route('scanner.index') }}" data-testid="empty-start-scan-link"><i data-lucide="scan-line"></i>Mulai Scan</a>
                </div>

                <div class="empty-state" data-testid="no-search-results" x-show="tickets.length > 0 && filtered.length === 0" x-cloak>
                    <span class="empty-state__icon"><i data-lucide="search-x"></i></span>
                    <h2>Tiket tidak ditemukan</h2>
                    <p>Coba kata kunci atau kategori lain.</p>
                </div>
            </section>
        </main>

        <x-scanner.bottom-nav active="verified" />
        <x-scanner.toast />
    </div>
</x-scanner.layout>
