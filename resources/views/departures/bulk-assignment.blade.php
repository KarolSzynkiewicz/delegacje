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
        @if(session('success'))
            <x-ui.alert variant="success" title="Sukces" dismissible class="mb-3">
                {{ session('success') }}
            </x-ui.alert>
        @endif
        
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
                    <span id="destination-location-display" class="text-muted">Zostanie wykryta automatycznie z przypisanych projektów</span>
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

    @push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            // Listen for destination location updates
            Livewire.on('destination-location-updated', (event) => {
                console.log('Destination location updated event received:', event);
                const displayElement = document.getElementById('destination-location-display');
                if (displayElement) {
                    // Handle different event formats (Livewire 2 vs 3)
                    let locationName = null;
                    if (typeof event === 'string') {
                        // Simple string
                        locationName = event;
                    } else if (event?.locationName) {
                        // Object with locationName property
                        locationName = event.locationName;
                    } else if (Array.isArray(event) && event[0]?.locationName) {
                        // Array format
                        locationName = event[0].locationName;
                    } else if (event?.detail?.[0]?.locationName) {
                        // Detail format
                        locationName = event.detail[0].locationName;
                    }
                    
                    if (locationName) {
                        displayElement.textContent = locationName;
                        displayElement.className = 'text-success fw-semibold';
                    } else {
                        displayElement.textContent = 'Zostanie wykryta automatycznie z przypisanych projektów';
                        displayElement.className = 'text-muted';
                    }
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
