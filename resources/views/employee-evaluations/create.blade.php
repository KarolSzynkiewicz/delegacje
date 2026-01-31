<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 mb-0">Dodaj Nową Ocenę</h2>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Dodaj Nową Ocenę">
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
                        <div class="col-md-6 mb-3">
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

                        <div class="col-md-6 mb-3">
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
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
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

                        <div class="col-md-6 mb-3">
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
                            rows="4"
                        />
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button variant="primary" type="submit">
                            <i class="bi bi-save me-1"></i> Dodaj Ocenę
                        </x-ui.button>
                        <x-ui.button variant="ghost" href="{{ route('employee-evaluations.index') }}">Anuluj</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
