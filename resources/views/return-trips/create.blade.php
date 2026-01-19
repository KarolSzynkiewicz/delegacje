<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Utwórz Zjazd">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('return-trips.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Utwórz Nowy Zjazd">
                <x-ui.errors />

                <form method="POST" action="{{ route('return-trips.prepare-form') }}">
                    @csrf

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="vehicle_id" 
                            label="Pojazd powrotny"
                        >
                            <option value="">Brak pojazdu (opcjonalne)</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                    {{ $vehicle->registration_number }} - {{ $vehicle->brand }} {{ $vehicle->model }}
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pracownicy <span class="text-danger">*</span></label>
                        <select name="employee_ids[]" multiple required size="10" class="form-control">
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ in_array($employee->id, old('employee_ids', [])) ? 'selected' : '' }}>
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
                        <x-ui.input 
                            type="date" 
                            name="return_date" 
                            label="Data zjazdu"
                            value="{{ old('return_date', date('Y-m-d')) }}"
                            required="true"
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
                            href="{{ route('return-trips.index') }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Przygotuj Zjazd
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
