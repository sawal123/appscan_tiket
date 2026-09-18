<x-scanner.layout
    title="Tiket Terverifikasi - Ticket Scanner"
    description="Daftar tiket yang telah terverifikasi di event aktif."
    page="verified"
    body-class="app-body"
>
    <div x-data x-on:keydown.meta.k.window.prevent="$refs.search?.focus()" x-on:keydown.ctrl.k.window.prevent="$refs.search?.focus()">
        <x-scanner.header
            :subtitle="$activeEvent?->name ?? 'Belum ada event aktif'"
            :scanner-name="$scannerName"
            :scanner-initials="$scannerInitials"
        />

        <main class="verified-page">
            <section class="verified-hero" aria-labelledby="verified-title">
                <div>
                    <p class="eyebrow" data-testid="verified-event-label">{{ $activeEvent?->name ?? 'Belum ada event aktif' }} &middot; {{ $activeEvent?->location ?? '-' }}</p>
                    <h1 id="verified-title" data-testid="verified-page-title">Tiket Terverifikasi</h1>
                    <p class="verified-count" id="verifiedCount" data-testid="verified-ticket-count">{{ $tickets->total() }} tiket telah masuk</p>
                </div>
                <a class="button button--primary verified-scan-button" href="{{ route('scanner.index') }}" data-testid="verified-scan-ticket-link"><i data-lucide="scan-line"></i>Scan tiket</a>
            </section>

            <section class="verified-tools" aria-label="Pencarian dan filter tiket">
                <form method="GET" action="{{ route('scanner.verified') }}" class="search-shell">
                    <label class="sr-only" for="ticketSearch">Cari kode QR</label>
                    <i data-lucide="search"></i>
                    <input id="ticketSearch" name="search" value="{{ $search }}" type="search" autocomplete="off" placeholder="Cari kode QR..." data-testid="verified-search-input" x-ref="search">
                    @if ($categoryFilter)
                        <input type="hidden" name="category" value="{{ $categoryFilter }}">
                    @endif
                    <button class="text-button" type="submit" data-testid="verified-search-button">Cari</button>
                </form>

                <div class="filter-row" role="group" aria-label="Filter kategori" data-testid="verified-filter-group">
                    @php($baseQuery = filled($search) ? ['search' => $search] : [])
                    <a class="filter-chip {{ $categoryFilter ? '' : 'is-active' }}" href="{{ route('scanner.verified', $baseQuery) }}" data-testid="filter-all-button">Semua</a>
                    @foreach ($categories as $category)
                        <a
                            class="filter-chip {{ $categoryFilter === $category->id ? 'is-active' : '' }}"
                            href="{{ route('scanner.verified', [...$baseQuery, 'category' => $category->id]) }}"
                            data-testid="filter-category-{{ $category->id }}"
                        >{{ $category->name }}</a>
                    @endforeach
                </div>
            </section>

            <section class="ticket-list-section" aria-labelledby="list-title">
                <div class="list-header">
                    <h2 id="list-title" data-testid="verified-list-title">Check-in terbaru</h2>
                </div>

                @if ($tickets->count() > 0)
                    <div class="ticket-table-head" aria-hidden="true">
                        <span>QR Code</span><span>Kategori</span><span>Waktu Check-in</span><span>Scanner</span><span>Status</span>
                    </div>

                    <div class="verified-list" id="verifiedList" data-testid="verified-ticket-list">
                        @foreach ($tickets as $ticket)
                            <article class="ticket-row" data-testid="verified-ticket-row-{{ $ticket->id }}">
                                <div class="ticket-identity">
                                    <span class="ticket-check" aria-hidden="true"><i data-lucide="check"></i></span>
                                    <span class="ticket-code">
                                        <span class="ticket-badge" data-testid="ticket-category-{{ $ticket->id }}">{{ $ticket->ticketCategory?->name ?? '-' }}</span>
                                        <strong data-testid="ticket-code-{{ $ticket->id }}">{{ $ticket->qr_code }}</strong>
                                    </span>
                                </div>
                                <span class="ticket-cell ticket-cell--category">{{ $ticket->ticketCategory?->name ?? '-' }}</span>
                                <span class="ticket-cell ticket-cell--time" data-testid="ticket-time-{{ $ticket->id }}">{{ $ticket->checked_in_at?->translatedFormat('d M Y H:i') ?? '-' }}</span>
                                <span class="ticket-cell ticket-cell--scanner" data-testid="ticket-scanner-{{ $ticket->id }}">{{ $ticket->checkedInBy?->name ?? '-' }}</span>
                                <span class="ticket-status" data-testid="ticket-status-{{ $ticket->id }}">SUCCESS</span>
                            </article>
                        @endforeach
                    </div>

                    <nav class="verified-pagination" aria-label="Pagination riwayat check-in">
                        @if ($tickets->onFirstPage())
                            <span class="pagination-link is-disabled">Sebelumnya</span>
                        @else
                            <a class="pagination-link" href="{{ $tickets->previousPageUrl() }}">Sebelumnya</a>
                        @endif

                        <span class="pagination-summary" data-testid="verified-pagination-summary">Halaman {{ $tickets->currentPage() }} dari {{ $tickets->lastPage() }}</span>

                        @if ($tickets->hasMorePages())
                            <a class="pagination-link" href="{{ $tickets->nextPageUrl() }}" data-testid="verified-next-page-link">Berikutnya</a>
                        @else
                            <span class="pagination-link is-disabled">Berikutnya</span>
                        @endif
                    </nav>
                @else
                    <div class="empty-state" id="emptyState" data-testid="verified-empty-state">
                        <span class="empty-state__icon"><i data-lucide="ticket-check"></i></span>
                        <h2 data-testid="empty-state-title">Belum Ada Tiket Terverifikasi</h2>
                        <p data-testid="empty-state-description">Tiket yang berhasil check-in akan muncul di sini.</p>
                        <a class="button button--primary" href="{{ route('scanner.index') }}" data-testid="empty-start-scan-link"><i data-lucide="scan-line"></i>Mulai Scan</a>
                    </div>
                @endif
            </section>
        </main>

        <x-scanner.bottom-nav active="verified" />
        <x-scanner.toast />
    </div>
</x-scanner.layout>
