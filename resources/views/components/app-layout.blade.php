<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <style>
            .app-background {
                background-color: hsl(218, 41%, 15%);
                background-image: radial-gradient(650px circle at 0% 0%,
                        hsl(218, 41%, 35%) 15%,
                        hsl(218, 41%, 30%) 35%,
                        hsl(218, 41%, 20%) 75%,
                        hsl(218, 41%, 19%) 80%,
                        transparent 100%),
                    radial-gradient(1250px circle at 100% 100%,
                        hsl(218, 41%, 45%) 15%,
                        hsl(218, 41%, 30%) 35%,
                        hsl(218, 41%, 20%) 75%,
                        hsl(218, 41%, 19%) 80%,
                        transparent 100%);
                min-height: 100vh;
                position: relative;
            }

            .app-content-wrapper {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
                border-radius: 20px 20px 0 0;
                margin-top: 0;
                min-height: calc(100vh - 60px);
            }

            .navbar {
                background: rgba(255, 255, 255, 0.95) !important;
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }

            header.bg-white {
                background: rgba(255, 255, 255, 0.95) !important;
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
            }

            .card {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(5px);
                -webkit-backdrop-filter: blur(5px);
            }

            [data-bs-theme="dark"] .app-background {
                background-color: hsl(218, 41%, 8%);
                background-image: radial-gradient(650px circle at 0% 0%,
                        hsl(218, 41%, 20%) 15%,
                        hsl(218, 41%, 15%) 35%,
                        hsl(218, 41%, 10%) 75%,
                        hsl(218, 41%, 9%) 80%,
                        transparent 100%),
                    radial-gradient(1250px circle at 100% 100%,
                        hsl(218, 41%, 25%) 15%,
                        hsl(218, 41%, 15%) 35%,
                        hsl(218, 41%, 10%) 75%,
                        hsl(218, 41%, 9%) 80%,
                        transparent 100%);
            }

            [data-bs-theme="dark"] .app-content-wrapper {
                background: rgba(37, 41, 50, 0.95);
            }

            [data-bs-theme="dark"] .navbar {
                background: rgba(37, 41, 50, 0.95) !important;
            }

            [data-bs-theme="dark"] header.bg-white {
                background: rgba(37, 41, 50, 0.95) !important;
            }

            [data-bs-theme="dark"] .card {
                background: rgba(37, 41, 50, 0.95);
            }
        </style>

        <div class="app-background">
            @include('layouts.navigation')

            <div class="app-content-wrapper">
                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white border-bottom shadow-sm">
                        <div class="container-xxl py-3 px-3 px-md-4 px-lg-5">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="container-xxl py-4 px-3 px-md-4 px-lg-5">
                    {{ $slot }}
                </main>
            </div>
        </div>
        
        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        @livewireScripts
    </body>
</html>
