<div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold d-flex align-items-center gap-1">
                Data wyjazdu <span class="text-danger">*</span>
                <x-tooltip title="Kiedy pracownicy wyjeżdżają. Od tej daty pojazd jest zarezerwowany. System pokaże tylko pracowników dostępnych w tym dniu.">
                    <i class="bi bi-calendar-check text-info fs-6"></i>
                </x-tooltip>
            </label>
            <input 
                type="date" 
                wire:model.live="departureDate" 
                name="departure_date"
                class="form-control"
                required
            >
            <small class="form-text text-muted">Wybierz datę, aby zobaczyć dostępnych pracowników</small>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold d-flex align-items-center gap-1">
                Data przybycia <span class="text-danger">*</span>
                <x-tooltip title="Kiedy pracownicy docierają do celu. Do tej daty pojazd pozostaje zarezerwowany. Musi być późniejsza niż data wyjazdu.">
                    <i class="bi bi-calendar-event text-success fs-6"></i>
                </x-tooltip>
            </label>
            <input 
                type="date" 
                wire:model.live="endDate"
                name="end_date"
                class="form-control"
                min="{{ $departureDate }}"
                required
            >
            <small class="form-text text-muted">Data dotarcia do miejsca docelowego</small>
        </div>
    </div>

    @if($departureDate)
        <div class="mb-3">
            <label class="form-label fw-semibold d-flex align-items-center gap-1">
                Dostępni pracownicy <span class="text-danger">*</span>
                <x-tooltip title="Lista pracowników dostępnych w wybranym dniu. System automatycznie filtruje osoby z aktywną rotacją, kompletem dokumentów i bez przypisań do projektów.">
                    <i class="bi bi-people text-primary fs-6"></i>
                </x-tooltip>
            </label>
            <p class="small text-muted mb-2">
                Dostępni pracownicy z rotacją aktywną i wymaganymi dokumentami (nie w projekcie, nie w podróży) 
                @if($endDate && $endDate != $departureDate)
                    w okresie {{ \Carbon\Carbon::parse($departureDate)->format('Y-m-d') }} - {{ \Carbon\Carbon::parse($endDate)->format('Y-m-d') }}
                @else
                    na dzień {{ \Carbon\Carbon::parse($departureDate)->format('Y-m-d') }}
                @endif
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
                        <option 
                            value="{{ $employee['id'] }}"
                            @if(in_array($employee['id'], $selectedEmployeeIds)) selected @endif
                        >
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
                    Pracownik musi mieć aktywną rotację, wszystkie wymagane dokumenty, nie być przypisanym do projektu i nie być w podróży.
                </div>
            @endif
        </div>
    @else
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> Wybierz datę wyjazdu, aby zobaczyć dostępnych pracowników.
        </div>
    @endif

    @if($this->isDateInPast)
        <div class="mb-3">
            <div class="form-check form-check-inline">
                <input type="checkbox" class="form-check-input" id="confirm-past-date" name="confirm_past_date">
                <label class="form-check-label small text-muted" for="confirm-past-date">
                    Data w przeszłości
                </label>
            </div>
        </div>
    @endif
</div>
