@props([
    'fullWidth' => false,
    /** Pełna szerokość viewportu (bez container-xxl / zbędnych paddingów w main) — np. szerokie tabele */
    'edgeToEdge' => false,
])

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
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased">

        <div class="app-background">
            @include('layouts.navigation')

            <div class="app-content-wrapper">
                <!-- Page Heading -->
                @isset($header)
                    <header>
                        <div class="{{ $edgeToEdge ? 'w-100 py-3 px-2 px-md-3' : ($fullWidth ? 'container-fluid py-3 px-2 px-md-3' : 'container-xxl py-3 px-3 px-md-4 px-lg-5') }}">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="{{ $edgeToEdge ? 'w-100 py-3 px-0' : ($fullWidth ? 'container-fluid py-3 px-2 px-md-3' : 'container-xxl py-4 px-3 px-md-4 px-lg-5') }}">
                    @if($edgeToEdge)
                        {{ $slot }}
                    @else
                        <div class="app-page-shell">
                            {{ $slot }}
                        </div>
                    @endif
                </main>
            </div>
        </div>
        @livewireScripts
        @stack('scripts')
    </body>
</html>
