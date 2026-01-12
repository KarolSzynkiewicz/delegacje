<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 mb-0">Wydaj Sprzęt</h2>
            <x-ui.button variant="ghost" href="{{ route('equipment-issues.index') }}">
                <i class="bi bi-arrow-left"></i> Powrót
            </x-ui.button>
        </div>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Wydaj Nowy Sprzęt">
                <x-ui.errors />

                <form method="POST" action="{{ route('equipment-issues.store') }}">
                    @csrf

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="equipment_id" 
                            label="Sprzęt"
                            required="true"
                        >
                            <option value="">Wybierz sprzęt</option>
                            @foreach($equipment as $item)
                                <option value="{{ $item->id }}" {{ old('equipment_id') == $item->id ? 'selected' : '' }}>
                                    {{ $item->name }} (dostępne: {{ $item->available_quantity }} {{ $item->unit }})
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="employee_id" 
                            label="Pracownik"
                            required="true"
                        >
                            <option value="">Wybierz pracownika</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->full_name }}
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="project_assignment_id" 
                            label="Przypisanie do projektu (opcjonalne)"
                        >
                            <option value="">Brak</option>
                            @foreach($assignments as $assignment)
                                <option value="{{ $assignment->id }}" {{ old('project_assignment_id') == $assignment->id ? 'selected' : '' }}>
                                    {{ $assignment->employee->full_name }} - {{ $assignment->project->name }}
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input 
                                type="number" 
                                name="quantity_issued" 
                                label="Ilość"
                                value="{{ old('quantity_issued', 1) }}"
                                min="1"
                                required="true"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="date" 
                                name="issue_date" 
                                label="Data wydania"
                                value="{{ old('issue_date', date('Y-m-d')) }}"
                                required="true"
                            />
                        </div>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="date" 
                            name="expected_return_date" 
                            label="Oczekiwana data zwrotu"
                            value="{{ old('expected_return_date') }}"
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
                        <x-ui.button variant="ghost" href="{{ route('equipment-issues.index') }}">
                            Anuluj
                        </x-ui.button>
                        <x-ui.button variant="primary" type="submit">
                            <i class="bi bi-box-arrow-up me-1"></i> Wydaj Sprzęt
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
