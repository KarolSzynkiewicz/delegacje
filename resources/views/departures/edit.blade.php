<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Wyjazd">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('departures.show', $departure) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Wyjazd">
                <x-ui.errors />

                <form method="POST" action="{{ route('departures.update', $departure) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="vehicle_id" 
                            label="Pojazd (opcjonalne)"
                        >
                            <option value="">Brak pojazdu</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ old('vehicle_id', $departure->vehicle_id) == $vehicle->id ? 'selected' : '' }}>
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
                                <option value="{{ $location->id }}" {{ old('to_location_id', $departure->to_location_id) == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    @livewire('departure-employee-selector', [
                        'departureDate' => old('departure_date', $departure->event_date->format('Y-m-d')),
                        'selectedEmployeeIds' => old('employee_ids', $currentEmployeeIds)
                    ], key('departure-selector-edit'))

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            @foreach(\App\Enums\LogisticsEventStatus::cases() as $status)
                                @if($status !== \App\Enums\LogisticsEventStatus::CANCELLED)
                                    <option value="{{ $status->value }}" {{ old('status', $departure->status->value) === $status->value ? 'selected' : '' }}>
                                        {{ $status->label() }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Status wyjazdu można zmienić tylko jeśli nie jest anulowany</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notatki</label>
                        <textarea name="notes" rows="3" class="form-control">{{ old('notes', $departure->notes) }}</textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('departures.show', $departure) }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Zapisz Zmiany
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>

    @push('scripts')
    <script>
        // Sync Livewire departureDate with hidden input
        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', ({ component }) => {
                if (component.__instance?.name === 'departure-employee-selector') {
                    const date = component.get('departureDate');
                    if (date) {
                        document.getElementById('departure_date').value = date;
                    }
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
