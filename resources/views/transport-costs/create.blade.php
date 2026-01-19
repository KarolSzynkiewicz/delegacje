<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dodaj Koszt Transportu">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('transport-costs.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Dodaj Nowy Koszt Transportu">
                <form method="POST" action="{{ route('transport-costs.store') }}">
                    @csrf

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="logistics_event_id" 
                            label="Zdarzenie logistyczne (opcjonalne)"
                        >
                            <option value="">Brak</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" {{ old('logistics_event_id') == $event->id ? 'selected' : '' }}>
                                    {{ $event->type->label() }} - {{ $event->event_date->format('Y-m-d H:i') }}
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="vehicle_id" 
                            label="Pojazd (opcjonalne)"
                        >
                            <option value="">Brak</option>
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
                            name="transport_id" 
                            label="Transport (opcjonalne)"
                        >
                            <option value="">Brak</option>
                            @foreach($transports as $transport)
                                <option value="{{ $transport->id }}" {{ old('transport_id') == $transport->id ? 'selected' : '' }}>
                                    {{ $transport->mode->label() }} - {{ $transport->departure_datetime->format('Y-m-d H:i') }}
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="cost_type" 
                            label="Typ kosztu"
                            required="true"
                        >
                            <option value="">Wybierz typ</option>
                            <option value="fuel" {{ old('cost_type') == 'fuel' ? 'selected' : '' }}>Paliwo</option>
                            <option value="ticket" {{ old('cost_type') == 'ticket' ? 'selected' : '' }}>Bilet</option>
                            <option value="parking" {{ old('cost_type') == 'parking' ? 'selected' : '' }}>Parking</option>
                            <option value="toll" {{ old('cost_type') == 'toll' ? 'selected' : '' }}>Opłata drogowa</option>
                            <option value="other" {{ old('cost_type') == 'other' ? 'selected' : '' }}>Inne</option>
                        </x-ui.input>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input 
                                type="number" 
                                name="amount" 
                                label="Kwota"
                                value="{{ old('amount') }}"
                                step="0.01"
                                min="0"
                                required="true"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="text" 
                                name="currency" 
                                label="Waluta"
                                value="{{ old('currency', 'PLN') }}"
                                maxlength="3"
                                required="true"
                            />
                        </div>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="date" 
                            name="cost_date" 
                            label="Data kosztu"
                            value="{{ old('cost_date', date('Y-m-d')) }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="description" 
                            label="Opis"
                            value="{{ old('description') }}"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="receipt_number" 
                            label="Numer paragonu"
                            value="{{ old('receipt_number') }}"
                        />
                    </div>

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
                            href="{{ route('transport-costs.index') }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Zapisz
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
