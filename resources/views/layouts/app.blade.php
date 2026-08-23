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
        @livewireStyles
    </head>
    <body class="min-vh-100">
        @php
            $edgeToEdge = $edgeToEdge ?? false;
            $fullWidth = $fullWidth ?? false;
        @endphp
        <div class="min-vh-100 d-flex flex-column position-relative">
            @include('layouts.navigation')

            <div class="flex-grow-1 position-relative app-content-wrapper">
                @if (isset($header))
                    <header class="app-header">
                        <div @class([
                            'py-3',
                            'w-100 px-2 px-md-3' => $edgeToEdge,
                            'container-fluid px-2 px-md-3' => ! $edgeToEdge && $fullWidth,
                            'container-xxl px-3 px-md-4 px-lg-5' => ! $edgeToEdge && ! $fullWidth,
                        ])>
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <!-- Page Content -->
                <main @class([
                    'flex-grow-1 py-4',
                    'w-100 px-2 px-md-3' => $edgeToEdge,
                    'container-fluid px-2 px-md-3' => ! $edgeToEdge && $fullWidth,
                    'container-xxl px-3 px-md-4 px-lg-5' => ! $edgeToEdge && ! $fullWidth,
                ])>
                    <div class="app-page-shell">
                        @yield('content')
                        @isset($slot)
                            {{ $slot }}
                        @endisset
                    </div>
                </main>
            </div>
        </div>
        @livewireScripts
        @stack('scripts')
    </body>
</html>
