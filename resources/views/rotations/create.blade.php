<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 mb-0">Dodaj Nową Rotację</h2>
            <x-ui.button variant="ghost" href="{{ route('rotations.index') }}">
                <i class="bi bi-arrow-left"></i> Powrót
            </x-ui.button>
        </div>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Dodaj Rotację">
                <form method="POST" action="{{ route('rotations.store') }}">
                    @csrf

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="employee_id" 
                            id="employee_id"
                            label="Pracownik"
                            required="true"
                        >
                            <option value="">Wybierz pracownika</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->full_name }} ({{ $employee->email }})
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input 
                                type="date" 
                                name="start_date" 
                                id="start_date"
                                label="Data rozpoczęcia"
                                value="{{ old('start_date') }}"
                                required="true"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="date" 
                                name="end_date" 
                                id="end_date"
                                label="Data zakończenia"
                                value="{{ old('end_date') }}"
                                required="true"
                            />
                        </div>
                    </div>

                    <x-ui.alert variant="info" title="Uwaga" class="mb-4">
                        Status rotacji jest automatycznie określany na podstawie dat:
                        <ul class="mb-0 mt-2">
                            <li><strong>Zaplanowana</strong> - jeśli data rozpoczęcia jest w przyszłości</li>
                            <li><strong>Aktywna</strong> - jeśli trwa obecnie (dzisiaj jest między datą rozpoczęcia a zakończenia)</li>
                            <li><strong>Zakończona</strong> - jeśli data zakończenia jest w przeszłości</li>
                        </ul>
                    </x-ui.alert>

                    <div class="mb-4">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            id="notes"
                            label="Notatki"
                            value="{{ old('notes') }}"
                            rows="4"
                        />
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button variant="primary" type="submit">
                            <i class="bi bi-save me-1"></i> Zapisz
                        </x-ui.button>
                        <x-ui.button variant="ghost" href="{{ route('rotations.index') }}">
                            Anuluj
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
