@props([
    'title' => 'Ticket Scanner',
    'description' => 'Scanner QR Ticket untuk petugas gate event.',
    'page' => 'scanner',
    'bodyClass' => 'app-body',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="description" content="{{ $description }}">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title }}</title>

        <script>
            (function () {
                try {
                    var stored = localStorage.getItem('ticket-scanner-theme');
                    var dark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                    document.documentElement.dataset.theme = dark ? 'dark' : 'light';
                } catch (error) {
                    // Storage can be unavailable; fall back to the light theme.
                }
            })();
        </script>

        @vite(['resources/css/scanner.css', 'resources/js/scanner.js'])
    </head>
    <body class="{{ $bodyClass }}" data-page="{{ $page }}" x-data="{}">
        {{ $slot }}
    </body>
</html>
