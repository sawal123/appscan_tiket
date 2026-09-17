<x-scanner.layout
    title="Masuk — Ticket Scanner"
    description="Login petugas Ticket Scanner untuk proses check-in event."
    page="login"
    body-class="login-body"
>
    <button class="icon-button login-theme-toggle" type="button" data-theme-toggle data-testid="login-theme-toggle" aria-label="Ganti tema" x-on:click="$store.theme.toggle()">
        <span data-theme-icon="light"><i data-lucide="moon"></i></span>
        <span data-theme-icon="dark"><i data-lucide="sun"></i></span>
    </button>

    <main class="login-shell">
        <section class="login-panel" aria-labelledby="login-title" data-testid="login-panel">
            <div class="brand-lockup brand-lockup--login" data-testid="login-brand">
                <span class="brand-mark" aria-hidden="true"><i data-lucide="scan-line"></i></span>
                <span>
                    <strong>TICKET SCANNER</strong>
                    <small>Event Check-in System</small>
                </span>
            </div>

            <div class="login-heading">
                <p class="eyebrow" data-testid="login-eyebrow">Akses Petugas</p>
                <h1 id="login-title" data-testid="login-title">Siap menjaga alur masuk tetap lancar.</h1>
                <p data-testid="login-description">Masuk menggunakan akun operator yang telah diberikan oleh koordinator event.</p>
            </div>

            <form class="auth-form" method="POST" action="{{ route('login.store') }}" novalidate data-testid="login-form" x-data="{ showPassword: false }">
                @csrf

                <div class="form-field @error('email') has-error @enderror">
                    <label for="email">Email / Username</label>
                    <div class="input-shell">
                        <i data-lucide="user-round" aria-hidden="true"></i>
                        <input id="email" name="email" type="text" value="{{ old('email') }}" autocomplete="username" placeholder="operator@gate.id" data-testid="login-identity-input" required autofocus>
                    </div>
                    <span class="field-message" id="emailError" data-testid="login-identity-error">@error('email'){{ $message }}@enderror</span>
                </div>

                <div class="form-field @error('password') has-error @enderror">
                    <label for="password">Password</label>
                    <div class="input-shell">
                        <i data-lucide="lock-keyhole" aria-hidden="true"></i>
                        <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Masukkan password" data-testid="login-password-input" required x-ref="password">
                        <button
                            class="input-action"
                            type="button"
                            aria-label="Tampilkan password"
                            data-testid="login-password-toggle"
                            x-on:click="showPassword = ! showPassword; $refs.password.type = showPassword ? 'text' : 'password'"
                        >
                            <span x-show="! showPassword" x-cloak><i data-lucide="eye"></i></span>
                            <span x-show="showPassword" x-cloak><i data-lucide="eye-off"></i></span>
                        </button>
                    </div>
                    <span class="field-message" id="passwordError" data-testid="login-password-error">@error('password'){{ $message }}@enderror</span>
                </div>

                <div class="form-alert" id="loginError" role="alert" aria-live="polite" data-testid="login-error-message" @if (! $errors->any() && ! session('status')) hidden @endif>
                    <i data-lucide="{{ $errors->any() ? 'circle-alert' : 'circle-check' }}" aria-hidden="true"></i>
                    <span>{{ $errors->any() ? 'Email atau password tidak sesuai.' : session('status') }}</span>
                </div>

                <button class="button button--primary button--large" type="submit" id="loginButton" data-testid="login-submit-button">
                    <span>Masuk</span>
                    <i data-lucide="arrow-right" aria-hidden="true"></i>
                </button>
            </form>

            <p class="login-footnote" data-testid="login-security-note">
                <i data-lucide="shield-check" aria-hidden="true"></i>
                Sesi operator dilindungi untuk perangkat ini.
            </p>
        </section>

        <aside class="login-visual" aria-hidden="true">
            <div class="gate-lines"></div>
            <div class="visual-status">
                <span class="status-signal"></span>
                <span>Gate A aktif</span>
            </div>
            <div class="visual-copy">
                <span>{{ $activeEvent?->name ?? 'Event Check-in System' }}</span>
                <strong>Check-in cepat.<br>Keputusan jelas.</strong>
            </div>
        </aside>
    </main>

    <x-scanner.toast />
</x-scanner.layout>
