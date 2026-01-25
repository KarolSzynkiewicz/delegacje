<div>
    <div class="mb-3">
        <label class="form-label fw-semibold">Data zjazdu <span class="text-danger">*</span></label>
        <input 
            type="date" 
            wire:model.live="returnDate" 
            name="return_date"
            class="form-control"
            min="{{ date('Y-m-d') }}"
            required
        >
        <small class="form-text text-muted">Wybierz datę zjazdu, aby zobaczyć dostępnych pracowników</small>
    </div>

    @if($returnDate)
        <div class="mb-3">
            <label class="form-label fw-semibold">Pracownicy <span class="text-danger">*</span></label>
            <p class="small text-muted mb-2">
                Pracownicy z aktywnymi przypisaniami (projekt, dom, auto) na dzień {{ \Carbon\Carbon::parse($returnDate)->format('Y-m-d') }}
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
                            @if($employee['project'])
                                (Projekt: {{ $employee['project'] }})
                            @endif
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted">Przytrzymaj Ctrl/Cmd aby wybrać wielu pracowników</small>
            @else
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Brak pracowników z aktywnymi przypisaniami na wybraną datę.
                </div>
            @endif
        </div>
    @else
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> Wybierz datę zjazdu, aby zobaczyć dostępnych pracowników.
        </div>
    @endif
</div>
