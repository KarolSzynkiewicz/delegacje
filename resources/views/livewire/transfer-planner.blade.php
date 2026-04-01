<div>
    @if($errors->any())
        <x-ui.alert variant="danger" title="Popraw błędy formularza" dismissible class="mb-4">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li class="text-white">{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <!-- Podstawowe dane -->
    <x-ui.card label="Dane transferu" class="mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Data i godzina <span class="text-danger">*</span></label>
                <input
                    type="datetime-local"
                    wire:model.live="transferDate"
                    class="form-control @error('transferDate') is-invalid @enderror"
                >
                @error('transferDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Skąd <span class="text-danger">*</span></label>
                <select
                    wire:model.live="fromLocationId"
                    class="form-select @error('fromLocationId') is-invalid @enderror"
                >
                    <option value="">— wybierz lokalizację —</option>
                    @foreach($this->locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}@if($loc->city), {{ $loc->city }}@endif</option>
                    @endforeach
                </select>
                @error('fromLocationId') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Dokąd <span class="text-danger">*</span></label>
                <select
                    wire:model.live="toLocationId"
                    class="form-select @error('toLocationId') is-invalid @enderror"
                >
                    <option value="">— wybierz lokalizację —</option>
                    @foreach($this->locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}@if($loc->city), {{ $loc->city }}@endif</option>
                    @endforeach
                </select>
                @error('toLocationId') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row g-3 mt-1">
            <!-- Pojazd -->
            <div class="col-md-6">
                <label class="form-label fw-semibold">Pojazd</label>
                <div class="mb-2">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="vehicleSearch"
                        class="form-control form-control-sm"
                        placeholder="Szukaj pojazdu..."
                    >
                </div>
                <select
                    wire:model.live="vehicleId"
                    class="form-select @error('vehicleId') is-invalid @enderror"
                >
                    <option value="">Brak pojazdu / transport własny</option>
                    @foreach($this->vehicles as $v)
                        <option value="{{ $v->id }}">
                            {{ $v->registration_number }}
                            @if($v->brand || $v->model) — {{ trim($v->brand . ' ' . $v->model) }} @endif
                        </option>
                    @endforeach
                </select>
                @error('vehicleId') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <!-- Notatki -->
            <div class="col-md-6">
                <label class="form-label fw-semibold">Notatki</label>
                <textarea
                    wire:model.live.debounce.500ms="notes"
                    class="form-control"
                    rows="3"
                    placeholder="Opcjonalne uwagi..."
                ></textarea>
            </div>
        </div>
    </x-ui.card>

    <!-- Uczestnicy -->
    <x-ui.card label="Uczestnicy" class="mb-4">
        @error('selectedEmployeeIds') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror

        <div class="mb-3">
            <input
                type="text"
                wire:model.live.debounce.300ms="employeeSearch"
                class="form-control form-control-sm"
                placeholder="Szukaj pracownika..."
            >
        </div>

        <div class="row g-2">
            @foreach($this->filteredEmployees as $employee)
                @php $selected = in_array($employee->id, $selectedEmployeeIds); @endphp
                <div class="col-md-4 col-lg-3">
                    <div
                        class="card border rounded-3 p-2 cursor-pointer user-select-none {{ $selected ? 'border-primary bg-primary bg-opacity-10' : 'border-secondary-subtle' }}"
                        wire:click="toggleEmployee({{ $employee->id }})"
                        style="cursor: pointer;"
                    >
                        <div class="d-flex align-items-center gap-2">
                            <div class="form-check mb-0">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    @checked($selected)
                                    readonly
                                >
                            </div>
                            <div class="small">
                                <div class="fw-semibold">{{ $employee->full_name }}</div>
                                @if($employee->phone)
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $employee->phone }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if(count($selectedEmployeeIds) > 0)
            <div class="mt-3 pt-3 border-top">
                <small class="text-muted">
                    Wybrani: {{ count($selectedEmployeeIds) }} pracownik(ów)
                </small>
            </div>
        @endif
    </x-ui.card>

    <!-- Kierowca i wynagrodzenie -->
    <x-ui.card label="Kierowca i wynagrodzenie" class="mb-4">
        <p class="text-muted small mb-3">
            Opcjonalne. Jeśli kierowca otrzymuje wynagrodzenie za ten transfer, zostanie ono
            dodane jako nagroda (bonus) bez blokowania wypłaty — payroll można przypisać później.
        </p>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Kierowca</label>
                <select
                    wire:model.live="driverEmployeeId"
                    class="form-select @error('driverEmployeeId') is-invalid @enderror"
                >
                    <option value="">— brak / nie dotyczy —</option>
                    @foreach($this->employees->whereIn('id', $selectedEmployeeIds) as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                    @endforeach
                </select>
                @error('driverEmployeeId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                @if(count($selectedEmployeeIds) === 0)
                    <div class="form-text text-muted">Najpierw wybierz uczestników.</div>
                @endif
            </div>

            @if($driverEmployeeId)
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Kwota wynagrodzenia</label>
                    <input
                        type="number"
                        wire:model.live.debounce.300ms="driverPaymentAmount"
                        class="form-control @error('driverPaymentAmount') is-invalid @enderror"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                    >
                    @error('driverPaymentAmount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Waluta</label>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="driverPaymentCurrency"
                        class="form-control @error('driverPaymentCurrency') is-invalid @enderror"
                        maxlength="3"
                        placeholder="PLN"
                        style="text-transform: uppercase;"
                    >
                    @error('driverPaymentCurrency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold d-flex align-items-center gap-1">
                        Payroll
                        <x-tooltip title="Opcjonalnie. Jeśli payroll nie istnieje jeszcze, zostaw puste — bonus pojawi się w liście nagród bez payrollu i można go przypisać później.">
                            <i class="bi bi-info-circle text-muted"></i>
                        </x-tooltip>
                    </label>
                    <select
                        wire:model.live="driverPayrollId"
                        class="form-select @error('driverPayrollId') is-invalid @enderror"
                    >
                        <option value="">— przypisz później —</option>
                        @foreach($this->driverPayrolls as $payroll)
                            <option value="{{ $payroll->id }}">{{ $payroll->display_name }}</option>
                        @endforeach
                    </select>
                    @error('driverPayrollId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            @endif
        </div>
    </x-ui.card>

    <!-- Akcje -->
    <div class="d-flex justify-content-end gap-2">
        <x-ui.button variant="ghost" href="{{ route('transfers.index') }}">
            Anuluj
        </x-ui.button>
        <x-ui.button variant="primary" wire:click="save" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">
                <i class="bi bi-check-lg me-1"></i> Zapisz transfer
            </span>
            <span wire:loading wire:target="save">
                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                Zapisywanie...
            </span>
        </x-ui.button>
    </div>
</div>
