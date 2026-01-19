<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Przypisanie Pojazdu">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('vehicle-assignments.show', $vehicleAssignment) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Przypisanie Pojazdu">
                <x-ui.errors />

                @if (session('success'))
                    <x-alert type="success" dismissible icon="check-circle">
                        {{ session('success') }}
                    </x-alert>
                @endif

                <form method="POST" action="{{ route('vehicle-assignments.update', $vehicleAssignment) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="employee_id" 
                            label="Pracownik"
                            required="true"
                        >
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ old('employee_id', $vehicleAssignment->employee_id) == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->full_name }}
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="vehicle_id" 
                            label="Pojazd"
                            required="true"
                        >
                            @foreach($vehicles as $veh)
                                <option value="{{ $veh->id }}" {{ old('vehicle_id', $vehicleAssignment->vehicle_id) == $veh->id ? 'selected' : '' }}>
                                    {{ $veh->registration_number }} - {{ $veh->brand }} {{ $veh->model }}
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="mb-3">
                        @php
                            $currentPosition = 'passenger';
                            if ($vehicleAssignment->position) {
                                if ($vehicleAssignment->position instanceof \App\Enums\VehiclePosition) {
                                    $currentPosition = $vehicleAssignment->position->value;
                                } else {
                                    $currentPosition = $vehicleAssignment->position;
                                }
                            }
                            $oldPosition = old('position', $currentPosition);
                        @endphp
                        <x-ui.input 
                            type="select" 
                            name="position" 
                            label="Rola w pojeździe"
                            required="true"
                        >
                            <option value="passenger" {{ $oldPosition == 'passenger' || $oldPosition === 'passenger' ? 'selected' : '' }}>Pasażer</option>
                            <option value="driver" {{ $oldPosition == 'driver' || $oldPosition === 'driver' ? 'selected' : '' }}>Kierowca</option>
                        </x-ui.input>
                        <small class="form-text text-muted">Uwaga: W jednym pojeździe może być tylko jeden kierowca w danym okresie</small>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="date" 
                            name="start_date" 
                            label="Data Rozpoczęcia"
                            value="{{ old('start_date', $vehicleAssignment->start_date->format('Y-m-d')) }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="date" 
                            name="end_date" 
                            label="Data Zakończenia (opcjonalnie)"
                            value="{{ old('end_date', $vehicleAssignment->end_date ? $vehicleAssignment->end_date->format('Y-m-d') : '') }}"
                        />
                    </div>

                    <div class="mb-4">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            label="Uwagi"
                            value="{{ old('notes', $vehicleAssignment->notes) }}"
                            rows="3"
                        />
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Aktualizuj
                        </x-ui.button>
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('vehicle-assignments.index') }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
