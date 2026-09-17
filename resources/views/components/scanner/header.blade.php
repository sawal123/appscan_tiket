@props([
    'subtitle' => null,
    'scannerName' => 'Scanner',
    'scannerInitials' => 'S',
])

<header class="app-header" data-testid="app-header">
    <div class="app-header__inner">
        <a class="brand-lockup brand-lockup--compact" href="{{ route('scanner.index') }}" data-testid="header-brand-link" aria-label="Ticket Scanner">
            <span class="brand-mark" aria-hidden="true"><i data-lucide="scan-line"></i></span>
            <span><strong>TICKET SCANNER</strong><small>{{ $subtitle }}</small></span>
        </a>

        <div class="header-actions">
            <span class="connection-pill" data-testid="connection-status" x-bind:class="{ 'is-offline': ! $store.connection.online }">
                <span class="connection-dot"></span>
                <span data-testid="connection-status-text" x-text="$store.connection.online ? 'Online' : 'Offline'">Online</span>
            </span>

            <button class="icon-button" type="button" data-theme-toggle data-testid="scanner-theme-toggle" aria-label="Ganti tema" x-on:click="$store.theme.toggle()">
                <span data-theme-icon="light"><i data-lucide="moon"></i></span>
                <span data-theme-icon="dark"><i data-lucide="sun"></i></span>
            </button>

            <button class="avatar-button" type="button" data-testid="profile-button" aria-label="Profil {{ $scannerName }}">{{ $scannerInitials }}</button>
        </div>
    </div>
</header>
