<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Utwórz Wyjazd">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('departures.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
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

            <x-ui.card label="Utwórz Nowy Wyjazd">
                <x-ui.errors />

                <form method="POST" action="{{ route('departures.store') }}">
                    @csrf

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="vehicle_id" 
                            label="Pojazd (opcjonalne)"
                        >
                            <option value="">Brak pojazdu</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                    {{ $vehicle->registration_number }} - {{ $vehicle->brand }} {{ $vehicle->model }}
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="to_location_id" 
                            label="Lokalizacja docelowa"
                            required="true"
                        >
                            <option value="">Wybierz lokalizację</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ old('to_location_id') == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    @livewire('departure-employee-selector', ['departureDate' => old('departure_date', date('Y-m-d'))], key('departure-selector'))

                    <div class="mb-4">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            label="Notatki"
                            value="{{ old('notes') }}"
                            rows="3"
                        />
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('departures.index') }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Utwórz Wyjazd
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>

</x-app-layout>
