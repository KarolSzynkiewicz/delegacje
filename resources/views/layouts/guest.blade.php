<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ChronoLogic') }}</title>

        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="alternate icon" href="{{ asset('favicon.svg') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@300..700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body style="background-color: var(--bg-body); color: var(--text-main);">
        {{ $slot }}
        <script>
            (function () {
                const nodes = document.querySelectorAll('[data-cl-clock]');
                if (!nodes.length) return;
                const tick = () => {
                    const t = new Date().toLocaleTimeString('pl-PL', { hour12: false });
                    nodes.forEach((el) => { el.textContent = t; });
                };
                tick();
                setInterval(tick, 1000);
            })();
        </script>
    </body>
</html>
