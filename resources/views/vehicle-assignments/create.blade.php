<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Przypisz Auto do Pracownika">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('employees.show', $employee) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Przypisz Auto do Pracownika">
                <x-ui.errors />

                <form method="POST" action="{{ route('vehicle-assignments.store') }}">
                    @csrf

                    <div class="mb-3">
                        @if($employee)
                            <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                            <label class="form-label fw-semibold">Pracownik</label>
                            <input type="text" value="{{ $employee->full_name }}" disabled
                                class="form-control bg-light">
                        @else
                            <x-ui.input 
                                type="select" 
                                name="employee_id" 
                                label="Pracownik"
                                required="true"
                            >
                                <option value="">Wybierz pracownika</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->full_name }}
                                    </option>
                                @endforeach
                            </x-ui.input>
                        @endif
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="vehicle_id" 
                            label="Pojazd"
                            required="true"
                        >
                            <option value="">Wybierz pojazd</option>
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
                            name="position" 
                            label="Rola w pojeździe"
                            required="true"
                        >
                            <option value="passenger" {{ old('position', 'passenger') == 'passenger' ? 'selected' : '' }}>Pasażer</option>
                            <option value="driver" {{ old('position') == 'driver' ? 'selected' : '' }}>Kierowca</option>
                        </x-ui.input>
                        <small class="form-text text-muted">Uwaga: W jednym pojeździe może być tylko jeden kierowca w danym okresie</small>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="date" 
                            name="start_date" 
                            label="Data Rozpoczęcia"
                            value="{{ old('start_date', $dateFrom ?? '') }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="date" 
                            name="end_date" 
                            label="Data Zakończenia (opcjonalnie)"
                            value="{{ old('end_date', $dateTo ?? '') }}"
                        />
                    </div>

                    <div class="mb-4">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            label="Uwagi"
                            value="{{ old('notes') }}"
                            rows="3"
                        />
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Zapisz
                        </x-ui.button>
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('employees.show', $employee) }}"
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
