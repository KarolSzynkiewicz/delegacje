<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Zjazd">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('return-trips.show', $returnTrip) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Zjazd">
                    <x-ui.errors />

                    <form method="POST" action="{{ route('return-trips.prepare-form') }}">
                        @csrf
                        <input type="hidden" name="edit_mode" value="1">
                        <input type="hidden" name="return_trip_id" value="{{ $returnTrip->id }}">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pojazd powrotny</label>
                            <select name="vehicle_id" class="form-select">
                                <option value="">Brak pojazdu (opcjonalne)</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" {{ old('vehicle_id', $returnTrip->vehicle_id) == $vehicle->id ? 'selected' : '' }}>
                                        {{ $vehicle->registration_number }} - {{ $vehicle->brand }} {{ $vehicle->model }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pracownicy *</label>
                            <select name="employee_ids[]" multiple required size="10" class="form-select">
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ in_array($employee->id, old('employee_ids', $currentEmployeeIds)) ? 'selected' : '' }}>
                                        {{ $employee->full_name }} 
                                        @if($employee->assignments->count() > 0)
                                            (Projekt: {{ $employee->assignments->first()->project->name ?? '-' }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Przytrzymaj Ctrl/Cmd aby wybrać wielu pracowników</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Data zjazdu *</label>
                            <input type="date" name="return_date" value="{{ old('return_date', $returnTrip->event_date->format('Y-m-d')) }}" required
                                min="{{ date('Y-m-d') }}"
                                class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Notatki</label>
                            <textarea name="notes" rows="3" class="form-control">{{ old('notes', $returnTrip->notes) }}</textarea>
                        </div>

                <div class="d-flex gap-2">
                    <x-ui.button 
                        variant="ghost" 
                        href="{{ route('return-trips.show', $returnTrip) }}"
                        action="cancel"
                    >
                        Anuluj
                    </x-ui.button>
                    <x-ui.button 
                        variant="primary" 
                        type="submit"
                        action="save"
                    >
                        Przygotuj Zmiany
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
</x-app-layout>
