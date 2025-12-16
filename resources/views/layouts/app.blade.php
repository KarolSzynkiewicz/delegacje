<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stocznia - Zarządzanie Logistyką</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        nav {
            background-color: #2c3e50;
        }
        nav a {
            color: #ecf0f1 !important;
        }
        nav a:hover {
            color: #3498db !important;
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
            color: #3498db !important;
        }
        main {
            padding: 2rem 0;
            min-height: calc(100vh - 200px);
        }
        footer {
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 2rem 0;
            margin-top: 3rem;
        }
        .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
        }
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        .table-dark {
            background-color: #2c3e50;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/">Stocznia</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('locations.index') }}">Lokalizacje</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('projects.index') }}">Projekty</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('delegations.index') }}">Delegacje</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('time_logs.index') }}">Zapisy Czasu</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container">
        @yield('content')
    </main>

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Stocznia</h5>
                    <p>System zarządzania logistyką i delegowaniem pracowników</p>
                </div>
                <div class="col-md-6 text-end">
                    <p>&copy; 2025 Stocznia. Wszystkie prawa zastrzeżone.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
