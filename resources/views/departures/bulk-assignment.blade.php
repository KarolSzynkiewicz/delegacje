<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Utwórz Wyjazd (Krok 2/2) - Przypisz pracowników">
            <x-slot name="left">
                <form method="GET" action="{{ route('departures.create') }}" class="d-inline">
                    <x-ui.button 
                        variant="ghost" 
                        type="submit"
                        action="back"
                    >
                        ← Wróć do kroku 1
                    </x-ui.button>
                </form>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="container-fluid">
        @if(session('error'))
            <x-ui.alert variant="danger" title="Błąd" dismissible class="mb-3">
                {{ session('error') }}
            </x-ui.alert>
        @endif

        <x-ui.errors />

        <!-- Podsumowanie wyjazdu -->
        <x-ui.card class="mb-4">
            <h5 class="mb-3">📋 Podsumowanie wyjazdu</h5>
            <div class="row">
                <div class="col-md-3">
                    <strong>📅 Data wyjazdu:</strong><br>
                    {{ $departureData['event_date'] }}
                </div>
                <div class="col-md-3">
                    <strong>📅 Data przybycia:</strong><br>
                    {{ $departureData['end_date'] }}
                </div>
                <div class="col-md-3">
                    <strong>📍 Lokalizacja docelowa:</strong><br>
                    {{ $toLocation->name }}
                </div>
                <div class="col-md-3">
                    <strong>👥 Liczba uczestników:</strong><br>
                    {{ count($employees) }}
                </div>
            </div>
        </x-ui.card>

        @livewire('bulk-departure-assignments', [
            'employeeIds' => $employees->pluck('id')->toArray(),
            'arrivalDate' => $arrivalDate->format('Y-m-d'),
            'weekEnd' => $weekEnd->format('Y-m-d'),
            'projectIds' => $projects->pluck('id')->toArray(),
            'roleIds' => $roles->pluck('id')->toArray(),
            'vehicleIds' => $vehicles->pluck('id')->toArray(),
            'accommodationIds' => $accommodations->pluck('id')->toArray()
        ])
    </div>
</x-app-layout>
