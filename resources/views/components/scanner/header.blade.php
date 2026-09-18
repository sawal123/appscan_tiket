@props([
    'subtitle' => null,
    'scannerName' => 'Scanner',
    'scannerInitials' => 'S',
    'scannerRole' => 'Scanner',
])

<header class="app-header" data-testid="app-header">
    <div class="app-header__inner">
        <a class="brand-lockup brand-lockup--compact" href="{{ route('scanner.index') }}" data-testid="header-brand-link" aria-label="Ticket Scanner">
            <span class="brand-mark" aria-hidden="true"><i data-lucide="scan-line"></i></span>
            <span><strong>TICKET SCANNER</strong><small data-testid="header-active-event">{{ $subtitle }}</small></span>
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

            <span class="scanner-identity" data-testid="scanner-identity" aria-label="Operator {{ $scannerName }} · {{ $scannerRole }}">
                <span class="avatar-button" data-testid="profile-button" aria-hidden="true">{{ $scannerInitials }}</span>
                <span class="scanner-identity__meta">
                    <strong data-testid="scanner-name" title="{{ $scannerName }}">{{ $scannerName }}</strong>
                    <small data-testid="scanner-role">{{ $scannerRole }}</small>
                </span>
            </span>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="icon-button" type="submit" data-testid="scanner-logout-button" aria-label="Keluar">
                    <i data-lucide="log-out"></i>
                </button>
            </form>
        </div>
    </div>
</header>
