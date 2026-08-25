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

        <x-ui.card class="mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
                <div>
                    <h5 class="mb-2">
                        <i class="bi bi-person-badge text-warning"></i>
                        Backfill «Utworzono przez»
                    </h5>
                    <p class="text-muted mb-0 small">
                        Przebudowuje indeks backlogu (<code>work_items</code>) i uzupełnia kolumnę
                        <strong>Utworzono przez</strong> ze źródeł: zadań, podzadań, procedur, kompletacji, wzmianek i zatwierdzeń.
                        Idempotentna — można odpalać ponownie.
                    </p>
                </div>
                <form method="POST" action="{{ route('system-actions.sync-work-items') }}" class="flex-shrink-0">
                    @csrf
                    <x-ui.button
                        variant="warning"
                        type="submit"
                        onclick="return confirm('Uzupełnić «Utworzono przez» i przebudować indeks backlogu? Może chwilę potrwać.')"
                    >
                        <i class="bi bi-arrow-repeat"></i> Uzupełnij utworzono przez
                    </x-ui.button>
                </form>
            </div>
        </x-ui.card>

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

                    <!-- Debug Mode Toggle -->
                    <div class="border rounded p-3 mb-3 bg-danger bg-opacity-10">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h5 class="mb-2">
                                    <i class="bi bi-bug text-danger"></i>
                                    Debug Mode
                                    @if(\Illuminate\Support\Facades\Cache::get('force_debug_mode', false))
                                        <x-ui.badge variant="danger">AKTYWNY</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="secondary">WYŁĄCZONY</x-ui.badge>
                                    @endif
                                </h5>
                                <p class="text-muted mb-0 small">
                                    <strong>🐛 Debugowanie:</strong> Włącz aby zobaczyć szczegółowe błędy na produkcji (na 1h). 
                                    <strong class="text-danger">Wyłącz po debugowaniu!</strong>
                                </p>
                            </div>
                            <div class="ms-3 d-flex gap-2">
                                @if(\Illuminate\Support\Facades\Cache::get('force_debug_mode', false))
                                    <form method="POST" action="{{ route('system-actions.debug-off') }}">
                                        @csrf
                                        <x-ui.button 
                                            variant="secondary" 
                                            type="submit"
                                        >
                                            <i class="bi bi-x-circle"></i> Wyłącz
                                        </x-ui.button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('system-actions.debug-on') }}">
                                        @csrf
                                        <x-ui.button 
                                            variant="danger" 
                                            type="submit"
                                            onclick="return confirm('Włączyć debug mode na 1 godzinę? Błędy będą widoczne publicznie!')"
                                        >
                                            <i class="bi bi-bug"></i> Włącz
                                        </x-ui.button>
                                    </form>
                                @endif
                            </div>
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
                    <div class="border rounded p-3 mb-3">
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

                    @if(!app()->environment('production'))
                        <!-- Seedowanie bazy danych - tylko nie-prod -->
                        <div class="border rounded p-3 bg-info bg-opacity-10">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h5 class="mb-2">
                                        <i class="bi bi-database-add text-info"></i>
                                        Seeduj bazę danych
                                    </h5>
                                    <p class="text-muted mb-0 small">
                                        <strong>🌱 Tylko nie-prod:</strong> Uruchamia seedery bazy danych. 
                                        <strong class="text-danger">UWAGA: Może nadpisać istniejące dane!</strong>
                                    </p>
                                </div>
                                <form method="POST" action="{{ route('system-actions.seed-database') }}" class="ms-3">
                                    @csrf
                                    <x-ui.button 
                                        variant="info" 
                                        type="submit"
                                        onclick="return confirm('⚠️ UWAGA: To nadpisze dane w bazie! Czy na pewno chcesz uruchomić seedery?')"
                                    >
                                        <i class="bi bi-database-add"></i> Seeduj
                                    </x-ui.button>
                                </form>
                            </div>
                        </div>
                    @endif
                </x-ui.card>
            </div>

            <div class="col-lg-8 mt-4">
                <x-ui.card label="Rekrutacja">
                    <p class="text-muted mb-4">
                        Narzędzia do utrzymania spójności bazy kandydatów z bazą pracowników.
                    </p>

                    <div class="border rounded p-3 mb-0 bg-success bg-opacity-10">
                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                            <div class="flex-grow-1">
                                <h5 class="mb-2">
                                    <i class="bi bi-people text-success"></i>
                                    Synchronizuj zatrudnionych (pracownicy → kandydaci)
                                </h5>
                                <p class="text-muted mb-0 small">
                                    Porównuje numery telefonów pracowników z kandydatami (z normalizacją jak w rekrutacji).
                                    Pokazuje podgląd: <em>brak w bazie</em>, <em>niezatrudniony</em>, <em>już zatrudniony</em>,
                                    a następnie tworzy / oznacza procesy w statusie <strong>Zatrudniony</strong>.
                                    Idempotentna.
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <livewire:employee-candidate-hire-sync wire:key="employee-candidate-hire-sync" />
                            </div>
                        </div>
                    </div>

                    <div class="border rounded p-3 mt-3 mb-0 bg-primary bg-opacity-10">
                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                            <div class="flex-grow-1">
                                <h5 class="mb-2">
                                    <i class="bi bi-person-lines-fill text-primary"></i>
                                    Import pełnego profilu kandydata
                                </h5>
                                <p class="text-muted mb-0 small">
                                    Import z gotowego CSV o ustalonym schemacie (kandydat + lead + proces + notatki + rola) —
                                    do jednorazowych migracji historycznych baz kandydatów. Wzbogaca istniejących kandydatów
                                    bez nadpisywania danych i ponownie wykorzystuje procesy już wpisane przez codzienny import MBS,
                                    dzięki czemu jest bezpieczny do ponownego uruchomienia na tym samym pliku.
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <livewire:candidate-base-import wire:key="candidate-base-import" />
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-lg-8 mt-4">
                <x-ui.card label="Integracja AI">
                    <p class="text-muted mb-4">
                        Klucz API dostawcy modelu językowego. Aplikacja rozmawia z modelem przez wspólny
                        interfejs (<code>LlmClient</code>), więc zmiana dostawcy to wybór z listy poniżej —
                        bez zmian w kodzie. Klucz zapisujemy zaszyfrowany i nigdy nie pokazujemy w całości.
                    </p>

                    <div class="border rounded p-3 mb-0 bg-primary bg-opacity-10">
                        <h5 class="mb-3">
                            <i class="bi bi-robot text-primary"></i>
                            Dostawca modelu językowego
                        </h5>
                        <livewire:llm-provider-settings wire:key="llm-provider-settings" />
                    </div>
                </x-ui.card>
            </div>

            <div class="col-lg-8 mt-4">
                <x-ui.card label="Backup danych">
                    <p class="text-muted mb-4">
                        Pobierz pełny backup wszystkich danych z bazy danych jako plik JSON. Backup zawiera wszystkie rekordy ze wszystkich tabel — bez plików graficznych przechowywanych na dysku.
                    </p>

                    <div class="border rounded p-3 bg-success bg-opacity-10">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h5 class="mb-2">
                                    <i class="bi bi-download text-success"></i>
                                    Pobierz backup bazy danych
                                </h5>
                                <p class="text-muted mb-0 small">
                                    Eksportuje wszystkie tabele do pliku <code>.json</code>.
                                    Może chwilę potrwać przy dużej bazie.
                                    <strong>Przechowuj w bezpiecznym miejscu!</strong>
                                </p>
                            </div>
                            <a href="{{ route('system-actions.backup-database') }}"
                               class="btn btn-success ms-3"
                               onclick="return confirm('Wygenerować backup bazy danych? Może to chwilę potrwać.')">
                                <i class="bi bi-download"></i> Pobierz backup
                            </a>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-lg-4">
                <x-ui.card label="Dane i spójność" class="mb-4">
                    <p class="text-muted mb-4 small">
                        Jednorazowe akcje naprawcze dla danych w bazie.
                    </p>

                    <!-- Sync typów lokalizacji -->
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h5 class="mb-2">
                                    <i class="bi bi-geo-alt text-primary"></i>
                                    Aktualizuj typy lokalizacji
                                </h5>
                                <p class="text-muted mb-0 small">
                                    Ustawia cele lokalizacji na podstawie powiązanych danych:
                                    mieszkania → <strong>Kwatera</strong>,
                                    projekty → <strong>Projekt</strong>,
                                    naprawy → <strong>Warsztat</strong>.
                                    Idempotentna.
                                </p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('system-actions.sync-location-purposes') }}" class="mt-3">
                            @csrf
                            <x-ui.button variant="primary" type="submit" class="w-100">
                                <i class="bi bi-arrow-repeat"></i> Aktualizuj typy
                            </x-ui.button>
                        </form>
                    </div>

                    <!-- Napraw nazwy lokalizacji -->
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h5 class="mb-2">
                                    <i class="bi bi-pencil-square text-warning"></i>
                                    Napraw nazwy lokalizacji mieszkań
                                </h5>
                                <p class="text-muted mb-0 small">
                                    Gdy nazwa lokalizacji = adres ulicy (błąd ze starego geo-search),
                                    zastępuje ją nazwą powiązanego mieszkania.
                                </p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('system-actions.fix-location-names') }}" class="mt-3">
                            @csrf
                            <x-ui.button variant="warning" type="submit" class="w-100">
                                <i class="bi bi-pencil-square"></i> Napraw nazwy
                            </x-ui.button>
                        </form>
                    </div>
                </x-ui.card>

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
