<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-vh-100">
        <div class="min-vh-100 d-flex flex-column position-relative">
            @include('layouts.navigation')

            <div class="flex-grow-1 position-relative app-content-wrapper">
                <!-- Page Heading -->
                @if (isset($header))
                    <header class="app-header">
                        <div class="container-xxl py-3 px-3 px-md-4 px-lg-5">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <!-- Page Content -->
                <main class="flex-grow-1 container-xxl py-4 px-3 px-md-4 px-lg-5">
                    @yield('content')
                    @isset($slot)
                        {{ $slot }}
                    @endisset
                </main>
            </div>
        </div>
        @livewireScripts
    </body>
</html>
