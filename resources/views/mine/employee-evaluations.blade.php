<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Oceny Pracowników Zespołu</h2>
        </div>
    </x-slot>

    @if (empty($employeeIds))
        <x-ui.card>
            <div class="text-center py-5">
                <i class="bi bi-star fs-1 text-muted"></i>
                <p class="text-muted mt-3">Brak pracowników w projektach zespołu.</p>
            </div>
        </x-ui.card>
    @else
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
        
        <livewire:employee-evaluations-table :filterEmployeeIds="$employeeIds" />
    @endif
</x-app-layout>
