<div>
    <div class="mb-3">
        <label class="form-label fw-semibold">Data wyjazdu <span class="text-danger">*</span></label>
        <input 
            type="date" 
            wire:model.live="departureDate" 
            name="departure_date"
            class="form-control"
            min="{{ date('Y-m-d') }}"
            required
        >
        <small class="form-text text-muted">Wybierz datę wyjazdu, aby zobaczyć dostępnych pracowników</small>
    </div>

    @if($departureDate)
        <div class="mb-3">
            <label class="form-label fw-semibold">Dostępni pracownicy <span class="text-danger">*</span></label>
            <p class="small text-muted mb-2">
                Dostępni pracownicy z rotacją aktywną i wymaganymi dokumentami (nie w projekcie) na dzień {{ \Carbon\Carbon::parse($departureDate)->format('Y-m-d') }}
            </p>
            
            @if(count($employees) > 0)
                <select 
                    name="employee_ids[]" 
                    wire:model.live="selectedEmployeeIds"
                    multiple 
                    required 
                    size="10" 
                    class="form-control"
                >
                    @foreach($employees as $employee)
                        <option value="{{ $employee['id'] }}">
                            {{ $employee['full_name'] }}
                            @if($employee['rotation'])
                                (Rotacja: {{ $employee['rotation']['start_date'] }} - {{ $employee['rotation']['end_date'] ?? 'bezterminowa' }})
                            @endif
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted">Przytrzymaj Ctrl/Cmd aby wybrać wielu pracowników</small>
            @else
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Brak dostępnych pracowników na wybraną datę. 
                    Pracownik musi mieć aktywną rotację, wszystkie wymagane dokumenty i nie być przypisanym do projektu.
                </div>
            @endif
        </div>
    @else
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> Wybierz datę wyjazdu, aby zobaczyć dostępnych pracowników.
        </div>
    @endif
</div>
