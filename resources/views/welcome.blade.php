<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stocznia - Inteligentny System Zarządzania Projektami i Logistyką</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-color: #198754;
            --dark-blue: #0d6efd;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Hero Section */
        .hero-section {
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
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            color: white;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 4rem 0;
        }

        .hero-logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 20px;
            backdrop-filter: blur(10px);
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.3);
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            opacity: 0.95;
            font-weight: 300;
        }

        .hero-description {
            font-size: 1.2rem;
            margin-bottom: 2.5rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-hero {
            padding: 1rem 2.5rem;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            margin: 0.5rem;
            border: none;
        }

        .btn-hero-primary {
            background: white;
            color: #667eea;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .btn-hero-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
            background: #f8f9fa;
        }

        .btn-hero-secondary {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 2px solid white;
            backdrop-filter: blur(10px);
        }

        .btn-hero-secondary:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-3px);
        }

        /* Stats Section */
        .stats-section {
            background: white;
            padding: 4rem 0;
            margin-top: -50px;
            position: relative;
            z-index: 10;
            border-radius: 30px 30px 0 0;
            box-shadow: 0 -10px 40px rgba(0,0,0,0.1);
        }

        .stat-card {
            text-align: center;
            padding: 2rem;
            border-radius: 15px;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: var(--dark-blue);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1.1rem;
            color: #666;
            font-weight: 500;
        }

        /* Problems Section */
        .problems-section {
            background: #f8f9fa;
            padding: 5rem 0;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 1rem;
            color: #333;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 3rem;
        }

        .problem-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-left: 4px solid var(--dark-blue);
            height: 100%;
        }

        .problem-card:hover {
            transform: translateX(10px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .problem-icon {
            font-size: 2.5rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }

        .solution-icon {
            font-size: 2.5rem;
            color: var(--success-color);
            margin-bottom: 1rem;
        }

        /* Features Section */
        .features-section {
            background: white;
            padding: 5rem 0;
        }

        .feature-card {
            background: #f8f9fa;
            padding: 2.5rem;
            border-radius: 20px;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
            border: 2px solid transparent;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
            background: white;
            border-color: var(--dark-blue);
        }

        .feature-icon {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .feature-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 1rem;
        }

        .feature-description {
            color: #666;
            line-height: 1.6;
        }

        /* Comparison Section */
        .comparison-section {
            background: white;
            padding: 5rem 0;
        }

        .comparison-card {
            background: #f8f9fa;
            padding: 2.5rem;
            border-radius: 20px;
            height: 100%;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .comparison-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background: white;
        }

        .comparison-card.traditional {
            border-left: 4px solid #dc3545;
        }

        .comparison-card.our-system {
            border-left: 4px solid var(--success-color);
            background: linear-gradient(135deg, #f8f9fa 0%, #e8f5e9 100%);
        }

        .comparison-card.traditional:hover {
            border-color: #dc3545;
        }

        .comparison-card.our-system:hover {
            border-color: var(--success-color);
        }

        .comparison-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
        }

        .comparison-icon.traditional {
            color: #dc3545;
        }

        .comparison-icon.our-system {
            color: var(--success-color);
        }

        .comparison-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #333;
        }

        .comparison-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .comparison-list li {
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            color: #666;
        }

        .comparison-list li:last-child {
            border-bottom: none;
        }

        .comparison-list li i {
            margin-right: 0.5rem;
        }

        .comparison-list.traditional li i {
            color: #dc3545;
        }

        .comparison-list.our-system li i {
            color: var(--success-color);
        }

        /* Security Section */
        .security-section {
            background: white;
            padding: 5rem 0;
        }

        .security-card {
            background: #f8f9fa;
            padding: 2.5rem;
            border-radius: 20px;
            height: 100%;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            border-top: 4px solid var(--dark-blue);
        }

        .security-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background: white;
            border-color: var(--dark-blue);
        }

        .security-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            color: var(--dark-blue);
        }

        .security-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 1rem;
        }

        .security-description {
            color: #666;
            line-height: 1.6;
        }

        .security-feature {
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .security-feature:last-child {
            border-bottom: none;
        }

        .security-feature i {
            color: var(--success-color);
            margin-right: 0.75rem;
        }

        /* Benefits Section */
        .benefits-section {
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
            color: white;
            padding: 5rem 0;
        }

        .benefit-item {
            padding: 1.5rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .benefit-item:last-child {
            border-bottom: none;
        }

        .benefit-icon {
            font-size: 2rem;
            margin-right: 1rem;
            color: #ffc107;
        }

        /* CTA Section */
        .cta-section {
            background: white;
            padding: 5rem 0;
            text-align: center;
        }

        .cta-box {
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
            color: white;
            padding: 4rem;
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .cta-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .cta-description {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.2rem;
            }

            .hero-description {
                font-size: 1rem;
            }

            .stat-number {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <div class="hero-logo">
                    <x-application-logo class="d-block" style="width: 100%; height: 100%;" />
                </div>
                <h1 class="hero-title">Stocznia</h1>
                <p class="hero-subtitle">Inteligentny System Zarządzania Projektami i Logistyką</p>
                <p class="hero-description">
                    Eliminuj chaos w planowaniu. Automatyzuj przypisania. Kontroluj dokumentację. 
                    Oszczędzaj czas i zwiększaj efektywność o 70%.
                </p>
                
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-hero btn-hero-primary">
                            <i class="bi bi-speedometer2 me-2"></i>Przejdź do Panelu
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-hero btn-hero-secondary">
                                <i class="bi bi-box-arrow-right me-2"></i>Wyloguj się
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-hero btn-hero-primary">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Zaloguj się
                        </a>
                        <a href="{{ route('register') }}" class="btn btn-hero btn-hero-secondary">
                            <i class="bi bi-person-plus me-2"></i>Zarejestruj się
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </section>

    @if (!Auth::check())
        <!-- Stats Section -->
        <section class="stats-section">
            <div class="container">
                <div class="row g-4">
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card">
                            <div class="stat-number">70%</div>
                            <div class="stat-label">Oszczędność czasu</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card">
                            <div class="stat-number">95%</div>
                            <div class="stat-label">Mniej błędów</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card">
                            <div class="stat-number">100%</div>
                            <div class="stat-label">Kontrola dokumentów</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card">
                            <div class="stat-number">24/7</div>
                            <div class="stat-label">Dostępność systemu</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Problems Section -->
        <section class="problems-section">
            <div class="container">
                <h2 class="section-title">Jakie problemy rozwiązujemy?</h2>
                <p class="section-subtitle">Zamiast godzin spędzonych na ręcznym planowaniu, otrzymujesz inteligentny system</p>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="problem-card">
                            <div class="problem-icon">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Chaos w planowaniu zasobów</h4>
                            <p class="text-muted mb-3">
                                Ręczne planowanie, konflikty terminów, brak widoczności dostępności pracowników
                            </p>
                            <div class="solution-icon">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <h5 class="fw-bold text-success">Rozwiązanie:</h5>
                            <p class="mb-0">
                                Automatyczna walidacja dostępności, przegląd tygodniowy, wykrywanie konfliktów w czasie rzeczywistym
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="problem-card">
                            <div class="problem-icon">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Ryzyko prawno-dokumentacyjne</h4>
                            <p class="text-muted mb-3">
                                Przypisania bez ważnych dokumentów, kary, opóźnienia projektów
                            </p>
                            <div class="solution-icon">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <h5 class="fw-bold text-success">Rozwiązanie:</h5>
                            <p class="mb-0">
                                Automatyczna walidacja dokumentów przed przypisaniem, alerty o wygasających dokumentach
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="problem-card">
                            <div class="problem-icon">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Niewłaściwe dopasowanie pracowników</h4>
                            <p class="text-muted mb-3">
                                Brak właściwych kompetencji, przekroczenia budżetu, opóźnienia
                            </p>
                            <div class="solution-icon">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <h5 class="fw-bold text-success">Rozwiązanie:</h5>
                            <p class="mb-0">
                                System ról, dopasowanie do zapotrzebowania, kontrola realizacji w czasie rzeczywistym
                            </p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="problem-card">
                            <div class="problem-icon">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </div>
                            <h4 class="fw-bold mb-3">Brak widoczności w czasie rzeczywistym</h4>
                            <p class="text-muted mb-3">
                                Brak aktualnego obrazu zasobów, trudne podejmowanie decyzji
                            </p>
                            <div class="solution-icon">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <h5 class="fw-bold text-success">Rozwiązanie:</h5>
                            <p class="mb-0">
                                Przegląd tygodniowy, dashboard, raporty na żywo, alerty o problemach
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features-section">
            <div class="container">
                <h2 class="section-title">Kluczowe funkcjonalności</h2>
                <p class="section-subtitle">Wszystko, czego potrzebujesz do efektywnego zarządzania projektami</p>
                
                <div class="row g-4">
                    <div class="col-lg-4 col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <h5 class="feature-title">Zarządzanie Pracownikami</h5>
                            <p class="feature-description">
                                Centralna baza pracowników z rolami, dokumentami, uprawnieniami i historią rotacji
                            </p>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <h5 class="feature-title">Walidacja Dokumentów</h5>
                            <p class="feature-description">
                                Automatyczne sprawdzanie ważności dokumentów przed przypisaniem do projektu
                            </p>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-calendar-week"></i>
                            </div>
                            <h5 class="feature-title">Przegląd Tygodniowy</h5>
                            <p class="feature-description">
                                Wizualny przegląd wszystkich projektów, zapotrzebowania i realizacji w jednym miejscu
                            </p>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-car-front"></i>
                            </div>
                            <h5 class="feature-title">Zarządzanie Flotą</h5>
                            <p class="feature-description">
                                Kompleksowe zarządzanie pojazdami, stanem technicznym, przeglądami i przypisaniami
                            </p>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-house-door"></i>
                            </div>
                            <h5 class="feature-title">Akomodacje</h5>
                            <p class="feature-description">
                                Zarządzanie mieszkaniami, kontrolą pojemności i przypisaniami pracowników
                            </p>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-diagram-3"></i>
                            </div>
                            <h5 class="feature-title">Inteligentne Planowanie</h5>
                            <p class="feature-description">
                                Automatyczne wykrywanie konfliktów, dopasowanie ról i kontrola zapotrzebowania
                            </p>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-arrow-repeat"></i>
                            </div>
                            <h5 class="feature-title">Rotacje Pracowników</h5>
                            <p class="feature-description">
                                Definiowanie okresów dostępności z automatycznym statusem i śledzeniem
                            </p>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-clipboard-check"></i>
                            </div>
                            <h5 class="feature-title">Walidacja Przypisań</h5>
                            <p class="feature-description">
                                Sprawdzanie dostępności, dokumentów, konfliktów czasowych i zapotrzebowania
                            </p>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-graph-up"></i>
                            </div>
                            <h5 class="feature-title">Raporty i Statystyki</h5>
                            <p class="feature-description">
                                Realizacja zapotrzebowania, statystyki projektów i analityka w czasie rzeczywistym
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Comparison Section -->
        <section class="comparison-section">
            <div class="container">
                <h2 class="section-title">Stocznia vs Tradycyjne metody</h2>
                <p class="section-subtitle">Porównaj nasz system z kanbanami, tablicami i Excelami</p>
                
                <div class="row g-4 mb-5">
                    <div class="col-lg-6">
                        <div class="comparison-card traditional">
                            <div class="comparison-icon traditional">
                                <i class="bi bi-file-earmark-spreadsheet"></i>
                            </div>
                            <h4 class="comparison-title">Excel / Tablice / Kanbany</h4>
                            <ul class="comparison-list traditional">
                                <li>
                                    <i class="bi bi-x-circle-fill"></i>
                                    Ręczne wprowadzanie danych - podatne na błędy
                                </li>
                                <li>
                                    <i class="bi bi-x-circle-fill"></i>
                                    Brak automatycznej walidacji dokumentów
                                </li>
                                <li>
                                    <i class="bi bi-x-circle-fill"></i>
                                    Konflikty terminów wykrywane ręcznie
                                </li>
                                <li>
                                    <i class="bi bi-x-circle-fill"></i>
                                    Brak widoczności w czasie rzeczywistym
                                </li>
                                <li>
                                    <i class="bi bi-x-circle-fill"></i>
                                    Czasochłonne aktualizacje (4-6 godzin/tydzień)
                                </li>
                                <li>
                                    <i class="bi bi-x-circle-fill"></i>
                                    Brak integracji z logistyką (pojazdy, mieszkania)
                                </li>
                                <li>
                                    <i class="bi bi-x-circle-fill"></i>
                                    Trudne śledzenie historii zmian
                                </li>
                                <li>
                                    <i class="bi bi-x-circle-fill"></i>
                                    Ryzyko przypisań bez ważnych dokumentów
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="comparison-card our-system">
                            <div class="comparison-icon our-system">
                                <i class="bi bi-rocket-takeoff-fill"></i>
                            </div>
                            <h4 class="comparison-title">System Stocznia</h4>
                            <ul class="comparison-list our-system">
                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    Automatyczne przypisania z walidacją
                                </li>
                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    Automatyczna kontrola ważności dokumentów
                                </li>
                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    Wykrywanie konfliktów w czasie rzeczywistym
                                </li>
                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    Pełna widoczność wszystkich zasobów na żywo
                                </li>
                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    Oszczędność czasu: 30-60 minut/tydzień (70% mniej)
                                </li>
                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    Kompleksowa logistyka: pojazdy, mieszkania, rotacje
                                </li>
                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    Pełna historia zmian i audyt
                                </li>
                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    100% ochrona przed przypisaniami bez dokumentów
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="fw-bold mb-2">
                                            <i class="bi bi-trophy-fill text-warning me-2"></i>
                                            Przewaga konkurencyjna
                                        </h5>
                                        <p class="mb-0 text-muted">
                                            Podczas gdy inni spędzają godziny na ręcznym planowaniu w Excelu, 
                                            Ty masz wszystko zautomatyzowane i pod kontrolą w jednym miejscu.
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                        <a href="{{ route('register') }}" class="btn btn-primary btn-lg">
                                            <i class="bi bi-rocket-takeoff me-2"></i>Wypróbuj za darmo
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Security Section -->
        <section class="security-section">
            <div class="container">
                <h2 class="section-title">Bezpieczeństwo i kontrola dostępu</h2>
                <p class="section-subtitle">Każdy widzi tylko to, co powinien widzieć - zasada najmniejszych uprawnień</p>
                
                <div class="row g-4 mb-5">
                    <div class="col-lg-4 col-md-6">
                        <div class="security-card">
                            <div class="security-icon">
                                <i class="bi bi-shield-lock-fill"></i>
                            </div>
                            <h5 class="security-title">System ról i uprawnień</h5>
                            <p class="security-description mb-3">
                                Zaawansowany system autoryzacji oparty na rolach (RBAC) z precyzyjną kontrolą dostępu
                            </p>
                            <div class="security-feature">
                                <i class="bi bi-check-circle-fill"></i>
                                <strong>Role użytkowników:</strong> Administrator, Kierownik, Pracownik biurowy
                            </div>
                            <div class="security-feature">
                                <i class="bi bi-check-circle-fill"></i>
                                <strong>Granularne uprawnienia:</strong> view, create, update, delete dla każdego zasobu
                            </div>
                            <div class="security-feature">
                                <i class="bi bi-check-circle-fill"></i>
                                <strong>Zasada najmniejszych uprawnień:</strong> każdy widzi tylko to, co potrzebuje
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="security-card">
                            <div class="security-icon">
                                <i class="bi bi-key-fill"></i>
                            </div>
                            <h5 class="security-title">Autentykacja i autoryzacja</h5>
                            <p class="security-description mb-3">
                                Wielowarstwowa ochrona dostępu z walidacją na każdym poziomie
                            </p>
                            <div class="security-feature">
                                <i class="bi bi-check-circle-fill"></i>
                                <strong>Bezpieczne logowanie:</strong> hasła hashowane (bcrypt), CSRF protection
                            </div>
                            <div class="security-feature">
                                <i class="bi bi-check-circle-fill"></i>
                                <strong>Weryfikacja email:</strong> opcjonalna weryfikacja konta
                            </div>
                            <div class="security-feature">
                                <i class="bi bi-check-circle-fill"></i>
                                <strong>Laravel Policies:</strong> kontrola dostępu na poziomie każdego zasobu
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="security-card">
                            <div class="security-icon">
                                <i class="bi bi-database-lock"></i>
                            </div>
                            <h5 class="security-title">Bezpieczeństwo danych</h5>
                            <p class="security-description mb-3">
                                Ochrona wrażliwych danych pracowników, projektów i dokumentacji
                            </p>
                            <div class="security-feature">
                                <i class="bi bi-check-circle-fill"></i>
                                <strong>Izolacja danych:</strong> użytkownicy widzą tylko swoje dane
                            </div>
                            <div class="security-feature">
                                <i class="bi bi-check-circle-fill"></i>
                                <strong>Audyt dostępu:</strong> śledzenie wszystkich operacji
                            </div>
                            <div class="security-feature">
                                <i class="bi bi-check-circle-fill"></i>
                                <strong>Bezpieczne API:</strong> Laravel Sanctum dla tokenów API
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm bg-light">
                            <div class="card-body p-4">
                                <div class="row align-items-center">
                                    <div class="col-md-2 text-center mb-3 mb-md-0">
                                        <i class="bi bi-shield-check" style="font-size: 4rem; color: var(--success-color);"></i>
                                    </div>
                                    <div class="col-md-10">
                                        <h5 class="fw-bold mb-2">
                                            <i class="bi bi-info-circle-fill text-primary me-2"></i>
                                            Bezpieczeństwo na pierwszym miejscu
                                        </h5>
                                        <p class="mb-0 text-muted">
                                            W przeciwieństwie do Exceli i tablic, gdzie każdy ma dostęp do wszystkich danych, 
                                            nasz system zapewnia precyzyjną kontrolę dostępu. Administrator widzi wszystko, 
                                            kierownik zarządza projektami, a pracownik biurowy tylko przegląda dane. 
                                            Wrażliwe informacje są chronione, a każda operacja jest rejestrowana.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Benefits Section -->
        <section class="benefits-section">
            <div class="container">
                <h2 class="section-title text-white mb-5">Dlaczego warto?</h2>
                
                <div class="row">
                    <div class="col-lg-6">
                        <div class="benefit-item d-flex align-items-start">
                            <div class="benefit-icon">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">Oszczędność czasu</h5>
                                <p class="mb-0 opacity-90">
                                    Redukcja czasu planowania z 4-6 godzin do 30-60 minut tygodniowo
                                </p>
                            </div>
                        </div>

                        <div class="benefit-item d-flex align-items-start">
                            <div class="benefit-icon">
                                <i class="bi bi-x-circle"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">Mniej błędów</h5>
                                <p class="mb-0 opacity-90">
                                    95% redukcja błędnych przypisań, 100% eliminacja przypisań bez dokumentów
                                </p>
                            </div>
                        </div>

                        <div class="benefit-item d-flex align-items-start">
                            <div class="benefit-icon">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">Zwiększona efektywność</h5>
                                <p class="mb-0 opacity-90">
                                    +25-30% lepsze wykorzystanie zasobów, +40% terminowość projektów
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="benefit-item d-flex align-items-start">
                            <div class="benefit-icon">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">Bezpieczeństwo prawne</h5>
                                <p class="mb-0 opacity-90">
                                    Automatyczna walidacja dokumentów chroni przed karami i opóźnieniami
                                </p>
                            </div>
                        </div>

                        <div class="benefit-item d-flex align-items-start">
                            <div class="benefit-icon">
                                <i class="bi bi-eye"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">Pełna widoczność</h5>
                                <p class="mb-0 opacity-90">
                                    Wszystkie projekty, pracownicy i zasoby w jednym miejscu, w czasie rzeczywistym
                                </p>
                            </div>
                        </div>

                        <div class="benefit-item d-flex align-items-start">
                            <div class="benefit-icon">
                                <i class="bi bi-lightning-charge"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-2">Szybkie decyzje</h5>
                                <p class="mb-0 opacity-90">
                                    Inteligentne alerty i automatyczne wykrywanie problemów umożliwiają natychmiastową reakcję
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <div class="cta-box">
                    <h2 class="cta-title">Gotowy na zmianę?</h2>
                    <p class="cta-description">
                        Dołącz do firm, które już oszczędzają czas i zwiększają efektywność
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="{{ route('register') }}" class="btn btn-hero btn-hero-primary">
                            <i class="bi bi-rocket-takeoff me-2"></i>Zacznij już dziś
                        </a>
                        <a href="{{ route('login') }}" class="btn btn-hero btn-hero-secondary">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Zaloguj się
                        </a>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
