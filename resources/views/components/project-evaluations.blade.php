@php
    use App\Models\EmployeeEvaluation;
    
    // Pobierz ID pracowników przypisanych do tego projektu
    $employeeIds = $project->assignments()
        ->pluck('employee_id')
        ->unique()
        ->toArray();
    
    // Pobierz oceny dla tych pracowników
    $evaluations = EmployeeEvaluation::whereIn('employee_id', $employeeIds)
        ->with(['employee', 'createdBy'])
        ->orderBy('created_at', 'desc')
        ->get();
    
    // Pobierz pracowników przypisanych do projektu
    $employees = $project->assignments()
        ->with('employee')
        ->get()
        ->pluck('employee')
        ->filter()
        ->unique('id')
        ->sortBy('last_name');
@endphp

<div>
    <!-- Formularz dodawania oceny -->
    <x-ui.card label="Dodaj Nową Ocenę" class="mb-4">
        <x-ui.errors />
        
        <form action="{{ route('employee-evaluations.store') }}" method="POST">
            @csrf
            
            <div class="mb-3">
                <label class="form-label">Pracownik *</label>
                <select name="employee_id" class="form-control @error('employee_id') is-invalid @enderror" required>
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
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Zaangażowanie (1-10) *</label>
                    <input 
                        type="number" 
                        name="engagement" 
                        class="form-control @error('engagement') is-invalid @enderror"
                        min="1" 
                        max="10" 
                        value="{{ old('engagement') }}"
                        required
                    >
                    @error('engagement')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Umiejętności (1-10) *</label>
                    <input 
                        type="number" 
                        name="skills" 
                        class="form-control @error('skills') is-invalid @enderror"
                        min="1" 
                        max="10" 
                        value="{{ old('skills') }}"
                        required
                    >
                    @error('skills')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Porządek (1-10) *</label>
                    <input 
                        type="number" 
                        name="orderliness" 
                        class="form-control @error('orderliness') is-invalid @enderror"
                        min="1" 
                        max="10" 
                        value="{{ old('orderliness') }}"
                        required
                    >
                    @error('orderliness')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Zachowanie (1-10) *</label>
                    <input 
                        type="number" 
                        name="behavior" 
                        class="form-control @error('behavior') is-invalid @enderror"
                        min="1" 
                        max="10" 
                        value="{{ old('behavior') }}"
                        required
                    >
                    @error('behavior')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            
            <div class="mb-3">
                <x-ui.input 
                    type="textarea" 
                    name="notes" 
                    label="Uwagi"
                    value="{{ old('notes') }}"
                    rows="3"
                />
            </div>
            
            <div class="d-flex justify-content-end">
                <x-ui.button variant="primary" type="submit">
                    <i class="bi bi-save me-1"></i> Dodaj Ocenę
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
    
    <!-- Lista ocen -->
    <x-ui.card label="Oceny Pracowników">
        @if($evaluations->count() > 0)
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Pracownik</th>
                            <th class="text-center">Zaangażowanie</th>
                            <th class="text-center">Umiejętności</th>
                            <th class="text-center">Porządek</th>
                            <th class="text-center">Zachowanie</th>
                            <th class="text-center">Średnia</th>
                            <th>Oceniający</th>
                            <th class="text-end">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($evaluations as $evaluation)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-calendar me-2 text-muted"></i>
                                        {{ $evaluation->created_at->format('Y-m-d H:i') }}
                                    </div>
                                </td>
                                <td>
                                    <x-employee-cell :employee="$evaluation->employee" />
                                </td>
                                <td class="text-center">
                                    <x-ui.badge variant="{{ $evaluation->engagement >= 7 ? 'success' : ($evaluation->engagement >= 4 ? 'warning' : 'danger') }}">
                                        {{ $evaluation->engagement }}/10
                                    </x-ui.badge>
                                </td>
                                <td class="text-center">
                                    <x-ui.badge variant="{{ $evaluation->skills >= 7 ? 'success' : ($evaluation->skills >= 4 ? 'warning' : 'danger') }}">
                                        {{ $evaluation->skills }}/10
                                    </x-ui.badge>
                                </td>
                                <td class="text-center">
                                    <x-ui.badge variant="{{ $evaluation->orderliness >= 7 ? 'success' : ($evaluation->orderliness >= 4 ? 'warning' : 'danger') }}">
                                        {{ $evaluation->orderliness }}/10
                                    </x-ui.badge>
                                </td>
                                <td class="text-center">
                                    <x-ui.badge variant="{{ $evaluation->behavior >= 7 ? 'success' : ($evaluation->behavior >= 4 ? 'warning' : 'danger') }}">
                                        {{ $evaluation->behavior }}/10
                                    </x-ui.badge>
                                </td>
                                <td class="text-center">
                                    <strong>{{ number_format($evaluation->average_score, 2) }}/10</strong>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-person-circle me-2 text-muted"></i>
                                        {{ $evaluation->createdBy->name ?? '-' }}
                                    </div>
                                </td>
                                <td class="text-end">
                                    <x-action-buttons
                                        viewRoute="{{ route('employee-evaluations.show', $evaluation) }}"
                                        editRoute="{{ route('employee-evaluations.edit', $evaluation) }}"
                                        deleteRoute="{{ route('employee-evaluations.destroy', $evaluation) }}"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-ui.empty-state 
                icon="star" 
                message="Brak ocen dla pracowników tego projektu"
            />
        @endif
    </x-ui.card>
</div>
