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
                        Zarządzaj cache aplikacji. Czyszczenie cache może pomóc rozwiązać problemy z uprawnieniami, widokami i route.
                    </p>

                    <div class="border rounded p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h5 class="mb-2">
                                    <i class="bi bi-trash text-danger"></i>
                                    Wyczyść wszystkie cache
                                </h5>
                                <p class="text-muted mb-0 small">
                                    Usuwa cache: optimize, permissions, views, routes, cache aplikacji.
                                </p>
                            </div>
                            <form method="POST" action="{{ route('system-actions.clear-cache') }}" class="ms-3">
                                @csrf
                                <x-ui.button 
                                    variant="danger" 
                                    type="submit"
                                    onclick="return confirm('Czy na pewno chcesz wyczyścić wszystkie cache?')"
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
