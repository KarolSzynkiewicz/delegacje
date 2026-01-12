<x-app-layout>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Dodaj Nowy Projekt">
                <form method="POST" action="{{ route('projects.store') }}">
                    @csrf

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="location_id" 
                            label="Lokalizacja"
                            required="true"
                        >
                            <option value="">Wybierz lokalizację</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }} ({{ $location->address }})
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="name" 
                            label="Nazwa Projektu"
                            value="{{ old('name') }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="client_name" 
                            label="Klient"
                            value="{{ old('client_name') }}"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="textarea" 
                            name="description" 
                            label="Opis"
                            value="{{ old('description') }}"
                            rows="4"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="status" 
                            label="Status"
                            required="true"
                        >
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Aktywny</option>
                            <option value="on_hold" {{ old('status') == 'on_hold' ? 'selected' : '' }}>Wstrzymany</option>
                            <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Zakończony</option>
                            <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Anulowany</option>
                        </x-ui.input>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="number" 
                            name="budget" 
                            label="Budżet (PLN)"
                            value="{{ old('budget') }}"
                            step="0.01"
                        />
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button variant="primary" type="submit">
                            <i class="bi bi-save me-1"></i> Zapisz
                        </x-ui.button>
                        <x-ui.button variant="ghost" href="{{ route('projects.index') }}">Anuluj</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
