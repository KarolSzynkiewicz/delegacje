<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-vh-100">
        <div class="min-vh-100 d-flex flex-column position-relative">
            @include('layouts.navigation')

            <div class="flex-grow-1 position-relative" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border-radius: 20px 20px 0 0; margin-top: 0;">
                <!-- Page Heading -->
                @if (isset($header))
                    <header class="bg-white border-bottom shadow-sm" style="background: rgba(255, 255, 255, 0.95) !important; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);">
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
        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        <style>
            /* Radial gradient background - spójny z landing page i logowaniem */
            body {
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
                background-attachment: fixed;
                min-height: 100vh;
            }
            
            /* Subtelny efekt glassmorphism dla headerów */
            header.bg-white {
                background: rgba(255, 255, 255, 0.95) !important;
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
            }
            
            /* Karty z lekkim efektem glass */
            .card {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(5px);
                -webkit-backdrop-filter: blur(5px);
                border: 1px solid rgba(255, 255, 255, 0.3);
            }
            
            /* Navigation header z glass effect */
            .navbar {
                background: rgba(255, 255, 255, 0.95) !important;
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            }
            
            /* Dropdowny muszą być nad headerem strony */
            .dropdown-menu {
                z-index: 1050 !important;
            }
            
            /* Header strony (z tytułem) ma niższy z-index */
            header.bg-white.border-bottom {
                position: relative;
                z-index: 100;
            }
            
            /* Spójny max-width dla lepszej czytelności na dużych ekranach */
            .container-xxl {
                max-width: 1400px;
            }
            
            /* Dark mode support */
            [data-bs-theme="dark"] {
                --bs-body-bg: #1a1d23;
                --bs-body-color: #fff;
                --bs-body-tertiary-bg: #252932;
                --bs-border-color: #3a3f4a;
            }
            
            [data-bs-theme="dark"] body {
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
                background-attachment: fixed;
            }
            
            [data-bs-theme="dark"] header.bg-white {
                background: rgba(37, 41, 50, 0.95) !important;
            }
            
            [data-bs-theme="dark"] .navbar {
                background: rgba(37, 41, 50, 0.95) !important;
            }
            
            [data-bs-theme="dark"] .bg-white {
                background-color: var(--bs-body-tertiary-bg) !important;
            }
            
            [data-bs-theme="dark"] .bg-light {
                background-color: var(--bs-body-bg) !important;
            }
            
            [data-bs-theme="dark"] .text-secondary {
                color: #adb5bd !important;
            }
            
            [data-bs-theme="dark"] .border-bottom {
                border-color: var(--bs-border-color) !important;
            }
            
            [data-bs-theme="dark"] .dropdown-menu {
                background-color: var(--bs-body-tertiary-bg);
                border-color: var(--bs-border-color);
            }
            
            [data-bs-theme="dark"] .dropdown-item {
                color: var(--bs-body-color);
            }
            
            [data-bs-theme="dark"] .dropdown-item:hover,
            [data-bs-theme="dark"] .dropdown-item.active {
                background-color: var(--bs-primary);
                color: white;
            }
            
            [data-bs-theme="dark"] .card {
                background: rgba(37, 41, 50, 0.9);
                border-color: var(--bs-border-color);
            }
            
            [data-bs-theme="dark"] .text-dark {
                color: var(--bs-body-color) !important;
            }
            
            [data-bs-theme="dark"] .text-muted {
                color: #adb5bd !important;
            }
            
            /* Spójny system kolorów dla statusów */
            .badge-status-active {
                background-color: #198754;
                color: white;
            }
            
            .badge-status-completed {
                background-color: #0d6efd;
                color: white;
            }
            
            .badge-status-cancelled {
                background-color: #dc3545;
                color: white;
            }
            
            .badge-status-scheduled {
                background-color: #0dcaf0;
                color: #000;
            }
            
            .badge-status-pending {
                background-color: #ffc107;
                color: #000;
            }
            
            /* Spójne style dla kart */
            .card {
                transition: transform 0.2s, box-shadow 0.2s;
            }
            
            .card:hover {
                transform: translateY(-2px);
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
            }
            
            /* Spójne style dla przycisków */
            .btn {
                transition: all 0.2s;
            }
            
            .btn:hover {
                transform: translateY(-1px);
            }
            
            /* Spójne style dla tabel */
            .table {
                margin-bottom: 0;
            }
            
            .table thead th {
                border-bottom: 2px solid #dee2e6;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.75rem;
                letter-spacing: 0.05em;
            }
            
            .table tbody tr {
                transition: background-color 0.15s;
            }
            
            .table tbody tr:hover {
                background-color: rgba(0, 0, 0, 0.02);
            }
            
            /* Spójne style dla formularzy */
            .form-label {
                font-weight: 500;
                margin-bottom: 0.5rem;
                color: #495057;
            }
            
            .form-control:focus,
            .form-select:focus {
                border-color: #86b7fe;
                box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            }
            
            /* Spójne style dla alertów */
            .alert {
                border-left: 4px solid;
            }
            
            .alert-success {
                border-left-color: #198754;
            }
            
            .alert-danger {
                border-left-color: #dc3545;
            }
            
            .alert-warning {
                border-left-color: #ffc107;
            }
            
            .alert-info {
                border-left-color: #0dcaf0;
            }
            
            /* Spójne odstępy */
            .section-spacing {
                margin-bottom: 2rem;
            }
            
            /* Spójne style dla pustych stanów */
            .empty-state {
                text-align: center;
                padding: 3rem 1rem;
            }
            
            .empty-state i {
                font-size: 4rem;
                color: #6c757d;
                margin-bottom: 1rem;
            }
            
            /* Dark mode adjustments */
            [data-bs-theme="dark"] .table tbody tr:hover {
                background-color: rgba(255, 255, 255, 0.05);
            }
            
            [data-bs-theme="dark"] .form-label {
                color: #adb5bd;
            }
            
            [data-bs-theme="dark"] .empty-state i {
                color: #6c757d;
            }
        </style>
    </body>
</html>
