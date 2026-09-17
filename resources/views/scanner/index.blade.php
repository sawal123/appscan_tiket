<x-scanner.layout
    title="Scan Tiket — Ticket Scanner"
    description="Scanner QR Ticket untuk petugas gate event."
    page="scanner"
    body-class="app-body"
>
    <div
        x-data="scannerApp({
            validateUrl: '{{ route('scanner.validate') }}',
            checkInUrl: '{{ route('scanner.check-in') }}',
        })"
        x-on:keydown.escape.window="manualOpen ? closeManual() : (sheet ? closeSheet() : null)"
    >
        <x-scanner.header
            :subtitle="$activeEvent?->name ?? 'Belum ada event aktif'"
            :scanner-name="$scannerName"
            :scanner-initials="$scannerInitials"
        />

        <main class="scanner-page">
            <div class="scanner-workspace">
                <x-scanner.event-context
                    :name="$activeEvent?->name ?? 'Belum ada event aktif'"
                    :date="$activeEvent?->event_date?->translatedFormat('d F Y') ?? '—'"
                    :location="$activeEvent?->location ?? '—'"
                />

                <x-scanner.scan-method>
                    <x-scanner.camera-panel />
                    <x-scanner.device-panel />
                </x-scanner.scan-method>
            </div>
        </main>

        <x-scanner.bottom-nav active="scan" />
        <x-scanner.ticket-result />
        <x-scanner.manual-input />
        <x-scanner.toast />
    </div>
</x-scanner.layout>
