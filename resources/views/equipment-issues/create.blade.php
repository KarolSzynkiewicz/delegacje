<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Wydaj Sprzęt">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('equipment-issues.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Wydaj Nowy Sprzęt">
                <x-ui.errors />

                <form method="POST" action="{{ route('equipment-issues.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">
                            Sprzęt <span class="text-danger">*</span>
                        </label>
                        <select 
                            name="equipment_id" 
                            class="form-control @error('equipment_id') is-invalid @enderror"
                            required
                        >
                            <option value="">Wybierz sprzęt</option>
                            @foreach($equipment as $item)
                                <option value="{{ $item->id }}" {{ old('equipment_id') == $item->id ? 'selected' : '' }}>
                                    {{ $item->name }} (dostępne: {{ $item->available_quantity }} {{ $item->unit }})
                                </option>
                            @endforeach
                        </select>
                        @error('equipment_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Pracownik <span class="text-danger">*</span>
                        </label>
                        <select 
                            name="employee_id" 
                            class="form-control @error('employee_id') is-invalid @enderror"
                            required
                        >
                            <option value="">Wybierz pracownika</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->full_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Przypisanie do projektu (opcjonalne)
                        </label>
                        <select 
                            name="project_assignment_id" 
                            class="form-control @error('project_assignment_id') is-invalid @enderror"
                        >
                            <option value="">Brak</option>
                            @foreach($assignments as $assignment)
                                <option value="{{ $assignment->id }}" {{ old('project_assignment_id') == $assignment->id ? 'selected' : '' }}>
                                    {{ $assignment->employee->full_name }} - {{ $assignment->project->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('project_assignment_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
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
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('equipment-issues.index') }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Wydaj Sprzęt
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
