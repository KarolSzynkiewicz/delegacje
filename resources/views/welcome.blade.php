<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Stocznia - System zarządzania logistyką</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
            }
            .hero-section {
                text-align: center;
                color: white;
            }
            .hero-section h1 {
                font-size: 3.5rem;
                font-weight: bold;
                margin-bottom: 1rem;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            }
            .hero-section p {
                font-size: 1.3rem;
                margin-bottom: 2rem;
                opacity: 0.95;
            }
            .btn-group-custom {
                display: flex;
                gap: 1rem;
                justify-content: center;
                flex-wrap: wrap;
            }
            .btn-custom {
                padding: 0.75rem 2rem;
                font-size: 1.1rem;
                border-radius: 50px;
                transition: all 0.3s ease;
            }
            .btn-primary-custom {
                background-color: #fff;
                color: #667eea;
                border: none;
            }
            .btn-primary-custom:hover {
                background-color: #f0f0f0;
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            }
            .btn-secondary-custom {
                background-color: transparent;
                color: white;
                border: 2px solid white;
            }
            .btn-secondary-custom:hover {
                background-color: rgba(255,255,255,0.1);
                transform: translateY(-2px);
            }
            .features-section {
                background: white;
                padding: 4rem 0;
                margin-top: 3rem;
                border-radius: 20px;
            }
            .feature-card {
                padding: 2rem;
                text-align: center;
                border-radius: 15px;
                background: #f8f9fa;
                transition: all 0.3s ease;
                height: 100%;
            }
            .feature-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                background: white;
            }
            .feature-card h5 {
                color: #667eea;
                font-weight: bold;
                margin-bottom: 1rem;
            }
            .feature-card p {
                color: #666;
                margin-bottom: 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="hero-section">
                <h1>Stocznia</h1>
                <p>System zarządzania logistyką i delegowaniem pracowników</p>
                
                <div class="btn-group-custom">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-custom btn-primary-custom">Przejdź do Panelu</a>
                        <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-custom btn-secondary-custom">Wyloguj się</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-custom btn-primary-custom">Zaloguj się</a>
                        <a href="{{ route('register') }}" class="btn btn-custom btn-secondary-custom">Zarejestruj się</a>
                    @endauth
                </div>
            </div>

            @if (!Auth::check())
                <div class="features-section">
                    <div class="container">
                        <h2 class="text-center mb-4" style="color: #333;">Funkcjonalności</h2>
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="feature-card">
                                    <h5>👥 Zarządzanie Pracownikami</h5>
                                    <p>Prowadź bazę pracowników z ich rolami, dokumentami i uprawnieniami</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="feature-card">
                                    <h5>🏠 Akomodacje</h5>
                                    <p>Zarządzaj dostępnymi mieszkaniami dla pracowników delegowanych</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="feature-card">
                                    <h5>🚗 Flota Pojazdów</h5>
                                    <p>Monitoruj stan techniczny i przeglądy pojazdów firmowych</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="feature-card">
                                    <h5>📍 Lokalizacje</h5>
                                    <p>Zarządzaj miejscami pracy i stoczniami</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="feature-card">
                                    <h5>📋 Projekty</h5>
                                    <p>Twórz i zarządzaj projektami oraz delegacjami pracowników</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="feature-card">
                                    <h5>⏱️ Zapisy Czasu</h5>
                                    <p>Rejestruj i monitoruj czas pracy pracowników</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
