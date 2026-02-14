<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Akcje Systemowe" />
    </x-slot>

    <div class="container-xxl">
        @if (session('success'))
            <x-ui.alert variant="success" dismissible class="mb-3">
                {{ session('success') }}
            </x-ui.alert>
        @endif

        @if (session('error'))
            <x-ui.alert variant="danger" dismissible class="mb-3">
                {{ session('error') }}
            </x-ui.alert>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <x-ui.card label="Cache i Optymalizacja">
                    <p class="text-muted mb-4">
                        Zarządzaj cache aplikacji i uprawnieniami. Czyszczenie cache może pomóc rozwiązać problemy z uprawnieniami, widokami i route.
                    </p>

                    <!-- Synchronizacja uprawnień -->
                    <div class="border rounded p-3 mb-3 bg-light">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h5 class="mb-2">
                                    <i class="bi bi-shield-check text-success"></i>
                                    Synchronizuj uprawnienia
                                </h5>
                                <p class="text-muted mb-0 small">
                                    <strong>⚡ Produkcja:</strong> Synchronizuje uprawnienia z routes do bazy danych. 
                                    <strong>Użyj po każdym deploy!</strong>
                                </p>
                            </div>
                            <form method="POST" action="{{ route('system-actions.sync-permissions') }}" class="ms-3">
                                @csrf
                                <x-ui.button 
                                    variant="success" 
                                    type="submit"
                                >
                                    <i class="bi bi-shield-check"></i> Synchronizuj
                                </x-ui.button>
                            </form>
                        </div>
                    </div>

                    <!-- Uruchom migracje -->
                    <div class="border rounded p-3 mb-3 bg-warning bg-opacity-10">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h5 class="mb-2">
                                    <i class="bi bi-database text-warning"></i>
                                    Uruchom migracje
                                </h5>
                                <p class="text-muted mb-0 small">
                                    <strong>🔧 Produkcja:</strong> Uruchamia pending migracje bazy danych. 
                                    <strong>Użyj po deploy ze zmianami w bazie!</strong>
                                </p>
                            </div>
                            <form method="POST" action="{{ route('system-actions.run-migrations') }}" class="ms-3">
                                @csrf
                                <x-ui.button 
                                    variant="warning" 
                                    type="submit"
                                    onclick="return confirm('Czy na pewno chcesz uruchomić migracje bazy danych?')"
                                >
                                    <i class="bi bi-database"></i> Uruchom
                                </x-ui.button>
                            </form>
                        </div>
                    </div>

                    <!-- Lekkie czyszczenie - tylko uprawnienia i route -->
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h5 class="mb-2">
                                    <i class="bi bi-arrow-clockwise text-primary"></i>
                                    Odśwież uprawnienia i route
                                </h5>
                                <p class="text-muted mb-0 small">
                                    <strong>Zalecane:</strong> Usuwa cache uprawnień i route. Szybkie i bezpieczne.
                                </p>
                            </div>
                            <form method="POST" action="{{ route('system-actions.clear-permissions') }}" class="ms-3">
                                @csrf
                                <x-ui.button 
                                    variant="primary" 
                                    type="submit"
                                >
                                    <i class="bi bi-arrow-clockwise"></i> Odśwież
                                </x-ui.button>
                            </form>
                        </div>
                    </div>

                    <!-- Pełne czyszczenie - wszystko -->
                    <div class="border rounded p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h5 class="mb-2">
                                    <i class="bi bi-trash text-danger"></i>
                                    Wyczyść wszystkie cache
                                </h5>
                                <p class="text-muted mb-0 small">
                                    Usuwa wszystkie cache: optimize, permissions, views, routes, cache aplikacji. Może chwilowo spowolnić aplikację.
                                </p>
                            </div>
                            <form method="POST" action="{{ route('system-actions.clear-cache') }}" class="ms-3">
                                @csrf
                                <x-ui.button 
                                    variant="danger" 
                                    type="submit"
                                    onclick="return confirm('Czy na pewno chcesz wyczyścić wszystkie cache? Aplikacja może być chwilowo wolniejsza.')"
                                >
                                    <i class="bi bi-trash"></i> Wyczyść
                                </x-ui.button>
                            </form>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-lg-4">
                <x-ui.card label="Informacje">
                    <dl class="mb-0">
                        <dt class="fw-semibold mb-1">Środowisko:</dt>
                        <dd class="mb-3">
                            <x-ui.badge variant="{{ config('app.env') === 'production' ? 'danger' : 'warning' }}">
                                {{ strtoupper(config('app.env')) }}
                            </x-ui.badge>
                        </dd>

                        <dt class="fw-semibold mb-1">Laravel:</dt>
                        <dd class="mb-0">{{ app()->version() }}</dd>
                    </dl>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-app-layout>
