<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="scroll-smooth"
    x-data="{
        darkMode: localStorage.getItem('gateflow-theme') === 'dark' || (!localStorage.getItem('gateflow-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),
        sidebarOpen: false,
        toggleTheme() {
            this.darkMode = ! this.darkMode;
            localStorage.setItem('gateflow-theme', this.darkMode ? 'dark' : 'light');
        },
    }"
    x-bind:class="{ 'dark': darkMode }"
>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="description" content="Admin Dashboard QR Ticket Check-in System" />

        <title>{{ filled($title ?? null) ? $title.' - Gateflow' : 'Gateflow' }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles

        <script>
            if (localStorage.getItem('gateflow-theme') === 'dark' || (!localStorage.getItem('gateflow-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        </script>
    </head>
    <body
        class="gateflow-admin min-h-screen bg-slate-50 font-sans text-slate-900 antialiased selection:bg-blue-200 selection:text-blue-950 dark:bg-slate-950 dark:text-slate-100"
        x-bind:class="{ 'overflow-hidden': sidebarOpen }"
        x-on:keydown.escape.window="sidebarOpen = false"
        x-on:resize.window="if (window.innerWidth >= 1024) sidebarOpen = false"
    >
        <div
            data-testid="mobile-sidebar-overlay"
            class="fixed inset-0 z-40 bg-slate-950/45 backdrop-blur-[2px] lg:hidden"
            x-cloak
            x-show="sidebarOpen"
            x-transition.opacity
            x-on:click="sidebarOpen = false"
            aria-hidden="true"
        ></div>

        <x-admin.sidebar />

        <div class="min-h-screen lg:pl-[264px]">
            <x-admin.topbar title="Dashboard" subtitle="Ringkasan aktivitas event hari ini" />

            <main id="dashboard" class="soft-grid px-4 py-6 sm:px-6 sm:py-8 xl:px-8">
                {{ $slot }}
            </main>
        </div>

        @fluxScripts
    </body>
</html>
