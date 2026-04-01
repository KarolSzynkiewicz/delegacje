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

            <div class="col-md-4">
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

    <!-- Trasa -->
    <x-ui.card label="Trasa" class="mb-4">
        <p class="text-muted small mb-3">
            Dodaj co najmniej 2 lokalizacje: startową i docelową. Możesz dodać punkty pośrednie.
            Po dodaniu minimum 2 punktów trasa zostanie automatycznie obliczona.
        </p>

        @error('waypointLocationIds') <div class="alert alert-danger py-2 mb-3">{{ $message }}</div> @enderror

        <!-- Aktualna trasa -->
        @if(count($waypointLocationIds) > 0)
            <div class="mb-3">
                @foreach($this->waypointLocations as $index => $loc)
                    <div class="d-flex align-items-center gap-2 mb-2 p-2 border rounded-3
                        {{ $index === 0 ? 'border-primary bg-primary bg-opacity-10' : ($index === count($waypointLocationIds) - 1 ? 'border-success bg-success bg-opacity-10' : 'border-secondary-subtle') }}">
                        <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold text-white
                            {{ $index === 0 ? 'bg-primary' : ($index === count($waypointLocationIds) - 1 ? 'bg-success' : 'bg-secondary') }}"
                            style="width: 28px; height: 28px; font-size: 0.8rem; flex-shrink: 0;">
                            {{ $index + 1 }}
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold small">{{ $loc->name }}</div>
                            @if($loc->city)
                                <div class="text-muted" style="font-size: 0.75rem;">{{ $loc->city }}</div>
                            @endif
                            @if(!$loc->hasCoordinates())
                                <div class="text-danger" style="font-size: 0.75rem;">
                                    <i class="bi bi-exclamation-triangle"></i> Brak współrzędnych — trasa może nie działać
                                </div>
                            @endif
                        </div>
                        <div class="d-flex gap-1">
                            @if($index > 0)
                                <button type="button" wire:click="moveWaypointUp({{ $index }})"
                                    class="btn btn-sm btn-outline-secondary py-0 px-1" title="Przesuń w górę">
                                    <i class="bi bi-arrow-up"></i>
                                </button>
                            @endif
                            @if($index < count($waypointLocationIds) - 1)
                                <button type="button" wire:click="moveWaypointDown({{ $index }})"
                                    class="btn btn-sm btn-outline-secondary py-0 px-1" title="Przesuń w dół">
                                    <i class="bi bi-arrow-down"></i>
                                </button>
                            @endif
                            <button type="button" wire:click="removeWaypoint({{ $index }})"
                                class="btn btn-sm btn-outline-danger py-0 px-1" title="Usuń">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Dodaj lokalizację -->
        <div class="d-flex gap-2 align-items-end">
            <div class="flex-grow-1">
                <label class="form-label fw-semibold small mb-1">Dodaj lokalizację</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="locationSearch"
                    class="form-control form-control-sm mb-1"
                    placeholder="Szukaj lokalizacji..."
                >
                <select wire:model.live="addLocationId" class="form-select form-select-sm">
                    <option value="">— wybierz —</option>
                    @foreach($this->filteredLocationsForPicker as $loc)
                        <option value="{{ $loc->id }}">
                            {{ $loc->name }}@if($loc->city), {{ $loc->city }}@endif
                            @if(!$loc->hasCoordinates()) ⚠ @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="button" wire:click="addWaypoint"
                class="btn btn-outline-primary btn-sm"
                @if(!$addLocationId) disabled @endif>
                <i class="bi bi-plus-lg"></i> Dodaj
            </button>
        </div>

        <!-- Wynik trasy -->
        @if($isPlanningRoute)
            <div class="mt-3 text-muted small">
                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                Obliczam trasę...
            </div>
        @elseif($routeError)
            <x-ui.alert variant="warning" class="mt-3">
                <i class="bi bi-exclamation-triangle me-1"></i>{{ $routeError }}
            </x-ui.alert>
        @elseif($routeData)
            <div class="mt-3 p-3 border rounded-3 bg-success bg-opacity-10 border-success">
                <div class="d-flex gap-4">
                    <div>
                        <div class="text-muted small">Dystans</div>
                        <div class="fw-semibold">
                            {{ number_format($routeData['distance'], 1) }} km
                        </div>
                    </div>
                    <div>
                        <div class="text-muted small">Czas jazdy</div>
                        <div class="fw-semibold">
                            @php
                                $h = floor($routeData['duration'] / 3600);
                                $m = floor(($routeData['duration'] % 3600) / 60);
                            @endphp
                            @if($h > 0){{ $h }} h @endif{{ $m }} min
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </x-ui.card>

    <!-- Uczestnicy -->
    <x-ui.card label="Uczestnicy" class="mb-4">
        @error('selectedEmployeeIds') <div class="alert alert-danger py-2 mb-2">{{ $message }}</div> @enderror

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
                        class="p-2 border rounded-3 user-select-none {{ $selected ? 'border-primary bg-primary bg-opacity-10' : 'border-secondary-subtle' }}"
                        wire:click="toggleEmployee({{ $employee->id }})"
                        style="cursor: pointer;"
                    >
                        <div class="d-flex align-items-center gap-2">
                            <input type="checkbox" class="form-check-input" @checked($selected) readonly>
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
            <div class="mt-2">
                <small class="text-muted">Wybrani: {{ count($selectedEmployeeIds) }} pracownik(ów)</small>
            </div>
        @endif
    </x-ui.card>

    <!-- Kierowca i wynagrodzenie -->
    <x-ui.card label="Kierowca i wynagrodzenie" class="mb-4">
        <p class="text-muted small mb-3">
            Opcjonalne. Bonus zostanie zapisany bez payrollu — można go przypisać później w widoku Kary/Nagrody.
        </p>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Kierowca</label>
                <select
                    wire:model.live="driverEmployeeId"
                    class="form-select @error('driverEmployeeId') is-invalid @enderror"
                    @if(count($selectedEmployeeIds) === 0) disabled @endif
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
                    <select
                        wire:model.live="driverPaymentCurrency"
                        class="form-select @error('driverPaymentCurrency') is-invalid @enderror"
                    >
                        @foreach($this->currencies as $currency)
                            <option value="{{ $currency->value }}">{{ $currency->label() }}</option>
                        @endforeach
                    </select>
                    @error('driverPaymentCurrency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold d-flex align-items-center gap-1">
                        Payroll
                        <x-tooltip title="Opcjonalnie. Jeśli payroll jeszcze nie istnieje, zostaw puste. Bonus pojawi się w liście nagród bez payrollu i można go przypisać później.">
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
