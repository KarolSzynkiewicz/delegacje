<div>
    @if($showIncompleteSaveModal)
        @teleport('body')
            <div class="modal fade show d-block departure-planner-teleport-modal" tabindex="-1" role="dialog" aria-modal="true"
                 style="background-color: rgba(0,0,0,0.55);">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-danger" style="background: var(--bg-card, #1e293b); color: #e2e8f0;">
                        <div class="modal-header border-secondary">
                            <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Niekompletne dane</h5>
                            <button type="button" class="btn-close btn-close-white" wire:click="cancelIncompleteSaveModal" aria-label="Zamknij"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-3">Możesz wrócić i uzupełnić braki, albo zapisać wyjazd mimo to (np. dokończysz później).</p>
                            <div class="fw-semibold text-danger mb-2">Brakuje m.in.:</div>
                            <ul class="mb-0 ps-3">
                                @foreach($incompleteSaveMessages as $msg)
                                    <li class="mb-1">{{ $msg }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="modal-footer border-secondary gap-2">
                            <button type="button" class="btn btn-outline-light" wire:click="cancelIncompleteSaveModal">Wróć do edycji</button>
                            <button type="button" class="btn btn-warning" wire:click="confirmSaveDespiteIncompleteGaps">
                                <i class="bi bi-floppy me-1"></i>Zapisz mimo braków
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    @if($showTransportSwitchModal && $pendingTransportMode)
        @teleport('body')
            <div class="modal fade show d-block departure-planner-teleport-modal" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="transportSwitchModalTitle"
                 style="background-color: rgba(0,0,0,0.55);">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-secondary" style="background: var(--bg-card, #1e293b); color: #e2e8f0;">
                        <div class="modal-header border-secondary">
                            <h5 class="modal-title" id="transportSwitchModalTitle">
                                <i class="bi bi-arrow-left-right text-warning me-2"></i>Zmiana sposobu wyjazdu
                            </h5>
                            <button type="button" class="btn-close btn-close-white" wire:click="cancelTransportModeSwitch" aria-label="Zamknij"></button>
                        </div>
                        <div class="modal-body">
                            @if($pendingTransportMode === 'public')
                                <p class="mb-0">Przejście na transport publiczny wyzeruje: wybór pojazdu wyjazdu, miejsca w aucie, przypisania pojazdów w kroku 3 (dojazdy) oraz dane trasy z kroku 4.</p>
                            @else
                                <p class="mb-0">Przejście na transport własny wyzeruje: bilety lotnicze, lotniska, konfigurację transferu oraz dane trasy z kroku 4.</p>
                            @endif
                            <p class="fw-semibold mt-3 mb-0">Kontynuować?</p>
                        </div>
                        <div class="modal-footer border-secondary gap-2">
                            <button type="button" class="btn btn-outline-light" wire:click="cancelTransportModeSwitch">Anuluj</button>
                            <button type="button" class="btn btn-primary" wire:click="confirmTransportModeSwitch">Kontynuuj</button>
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    @if($showDateChangeModal)
        @teleport('body')
            <div class="modal fade show d-block departure-planner-teleport-modal" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="dateChangeModalTitle"
                 style="background-color: rgba(0,0,0,0.55);">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-secondary" style="background: var(--bg-card, #1e293b); color: #e2e8f0;">
                        <div class="modal-header border-secondary">
                            <h5 class="modal-title" id="dateChangeModalTitle">
                                <i class="bi bi-calendar-range text-warning me-2"></i>Zmiana dat wyjazdu
                            </h5>
                            <button type="button" class="btn-close btn-close-white" wire:click="cancelDateChange" aria-label="Zamknij"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0">Zmiana daty wyjazdu lub zakończenia unieważnia dotychczasowe przypisania: inne dostępności mieszkań, inne okna przypisań do projektów i przeliczenia zapotrzebowań. Zostaną wyzerowane m.in. przypisania w krokach 1–3, bilety, konfiguracja transferu oraz dane trasy z kroku 4.</p>
                            <p class="fw-semibold mt-3 mb-0">Kontynuować?</p>
                            @error('departureDate')
                                <div class="text-danger small mt-2 mb-0">{{ $message }}</div>
                            @enderror
                            @error('endDate')
                                <div class="text-danger small mt-2 mb-0">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="modal-footer border-secondary gap-2">
                            <button type="button" class="btn btn-outline-light" wire:click="cancelDateChange">Anuluj</button>
                            <button type="button" class="btn btn-primary" wire:click="confirmDateChange">Kontynuuj</button>
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    @if($currentStep === 4 && $errors->any())
        <x-ui.alert variant="danger" title="Nie można zapisać wyjazdu" dismissible class="mb-4">
            <div class="fw-semibold mb-2">Popraw poniższe błędy i spróbuj ponownie:</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li class="text-white">{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    {{-- ── Szczegóły wyjazdu (header card) ── --}}
    <x-ui.card class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center"
                 style="width: 32px; height: 32px; background: rgba(99,102,241,0.2);">
                <i class="bi bi-suitcase-lg" style="font-size: 0.9rem; color: #a5b4fc;"></i>
            </div>
            <h6 class="mb-0 fw-bold" style="letter-spacing: .02em;">Szczegóły wyjazdu</h6>
        </div>

        <div class="row g-3 align-items-end">
            {{-- Kiedy (jak lotniska: jedna linia pól, etykiety nad polami) --}}
            @php
                $missingDepartureDate = empty($departureDate);
                $missingEndDate = empty($endDate);
                $datesIncomplete = $missingDepartureDate || $missingEndDate;
            @endphp
            <div class="col-md-4">
                <div class="rounded-3 p-2 transition-all"
                     style="{{ $datesIncomplete ? 'border: 1px solid rgba(239,68,68,0.65) !important; background: rgba(239,68,68,0.12) !important; box-shadow: 0 0 0 1px rgba(239,68,68,0.15);' : '' }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-6 min-w-0">
                            <label class="form-label small mb-1 {{ $missingDepartureDate ? 'text-danger fw-semibold' : 'text-muted' }}">
                                Data wyjazdu <span class="text-danger">*</span>
                            </label>
                            <input type="date"
                                   wire:model.live="departureDate"
                                   class="form-control form-control-sm w-100 @if($errors->has('departureDate') || $missingDepartureDate) is-invalid @endif">
                        </div>
                        <div class="col-6 min-w-0">
                            <label class="form-label small mb-1 {{ $missingEndDate ? 'text-danger fw-semibold' : 'text-muted' }}">
                                Data zakończenia <span class="text-danger">*</span>
                            </label>
                            <input type="date"
                                   wire:model.live="endDate"
                                   class="form-control form-control-sm w-100 @if($errors->has('departureDate') || $missingEndDate) is-invalid @endif"
                                   @if($departureDate) min="{{ $departureDate }}" @endif>
                        </div>
                        @if($datesIncomplete)
                            <div class="col-12">
                                <div class="small text-danger mb-0" style="font-size: 0.72rem;">
                                    <i class="bi bi-exclamation-circle me-1"></i>Wybierz datę wyjazdu i datę zakończenia.
                                </div>
                            </div>
                        @endif
                        @error('departureDate')
                            <div class="col-12">
                                <div class="invalid-feedback d-block" style="font-size:.72rem;">{{ $message }}</div>
                            </div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Czym — toggle --}}
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Czym</label>
                <div class="d-flex gap-2">
                    <button type="button"
                            wire:click="requestSetTransportMode('public')"
                            class="btn btn-sm flex-fill {{ $transportMode === 'public' ? 'btn-primary' : 'btn-outline-secondary' }}">
                        <i class="bi bi-airplane me-1"></i> Publiczny
                    </button>
                    <button type="button"
                            wire:click="requestSetTransportMode('own')"
                            class="btn btn-sm flex-fill {{ $transportMode === 'own' ? 'btn-success' : 'btn-outline-secondary' }}">
                        <i class="bi bi-car-front me-1"></i> Własny
                    </button>
                </div>
            </div>

            {{-- Trzecia karta: lotniska lub numer auta --}}
            <div class="col-md-5">
                @if($transportMode === 'public')
                    @php
                        $missingStartAirport = empty($sharedStartAirportLocationId);
                        $missingEndAirport = empty($sharedEndAirportLocationId);
                        $airportsIncomplete = $missingStartAirport || $missingEndAirport;
                    @endphp
                    {{-- Transport publiczny: lotniska --}}
                    <div class="row g-2 rounded-3 p-2 transition-all"
                         style="{{ $airportsIncomplete ? 'border: 1px solid rgba(239,68,68,0.65) !important; background: rgba(239,68,68,0.12) !important; box-shadow: 0 0 0 1px rgba(239,68,68,0.15);' : '' }}">
                        <div class="col-6">
                            <label class="form-label small mb-1 {{ $missingStartAirport ? 'text-danger fw-semibold' : 'text-muted' }}">
                                Lotnisko startowe <span class="text-danger">*</span>
                            </label>
                            <select wire:model.live="sharedStartAirportLocationId"
                                    class="form-select form-select-sm @if($errors->has('sharedStartAirportLocationId') || $missingStartAirport) is-invalid @endif">
                                <option value="">— wybierz —</option>
                                @foreach($this->availableAirports as $airport)
                                    <option value="{{ $airport->id }}"
                                        @disabled(!empty($sharedEndAirportLocationId) && (int)$sharedEndAirportLocationId === (int)$airport->id)>
                                        {{ $airport->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('sharedStartAirportLocationId') <div class="invalid-feedback" style="font-size:.72rem;">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1 {{ $missingEndAirport ? 'text-danger fw-semibold' : 'text-muted' }}">
                                Lotnisko docelowe <span class="text-danger">*</span>
                            </label>
                            <select wire:model.live="sharedEndAirportLocationId"
                                    class="form-select form-select-sm @if($errors->has('sharedEndAirportLocationId') || $missingEndAirport) is-invalid @endif">
                                <option value="">— wybierz —</option>
                                @foreach($this->availableAirports as $airport)
                                    <option value="{{ $airport->id }}"
                                        @disabled(!empty($sharedStartAirportLocationId) && (int)$sharedStartAirportLocationId === (int)$airport->id)>
                                        {{ $airport->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('sharedEndAirportLocationId') <div class="invalid-feedback" style="font-size:.72rem;">{{ $message }}</div> @enderror
                        </div>
                        @if($airportsIncomplete)
                            <div class="col-12">
                                <div class="small text-danger" style="font-size: 0.72rem;">
                                    <i class="bi bi-exclamation-circle me-1"></i>Wybierz lotnisko startowe i docelowe (wymagane przy locie).
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    {{-- Własny transport: wybór pojazdu (obowiązkowy) --}}
                    <label class="form-label small text-muted mb-1">Pojazd <span class="text-danger">*</span></label>
                    <select wire:model.live="vehicleId" class="form-select form-select-sm @error('vehicleId') is-invalid @enderror">
                        <option value="" disabled {{ empty($vehicleId) ? 'selected' : '' }}>— wybierz pojazd —</option>
                        @foreach($this->availableVehicles as $v)
                            <option value="{{ $v->id }}">
                                {{ $v->registration_number }} – {{ $v->brand }} {{ $v->model }}
                                @if($v->capacity) ({{ $v->capacity }} m.) @endif
                            </option>
                        @endforeach
                    </select>
                    @error('vehicleId') <div class="invalid-feedback d-block" style="font-size:.72rem;">{{ $message }}</div> @enderror
                    @if(!empty($vehicleId))
                        @php
                            $headerTransportVehicle = $this->availableVehicles->firstWhere('id', (int) $vehicleId);
                            $headerExpiredOc = $headerTransportVehicle && $headerTransportVehicle->hasExpiredInsurance();
                            $headerExpiredPrzeglad = $headerTransportVehicle && $headerTransportVehicle->hasExpiredInspection();
                            $headerDocWarning = '';
                            if ($headerExpiredOc) {
                                $headerDocWarning .= 'nieważne OC';
                            }
                            if ($headerExpiredPrzeglad) {
                                $headerDocWarning .= ($headerDocWarning !== '' ? ' oraz ' : '').'nieważny przegląd';
                            }
                        @endphp
                        @if($headerDocWarning !== '')
                            <small class="text-warning d-block mt-1">
                                <i class="bi bi-exclamation-triangle me-1"></i>Uwaga: {{ $headerDocWarning }}
                            </small>
                        @endif
                    @endif
                    @if($this->availableVehicles->isEmpty())
                        <div class="small text-warning mt-1">
                            <i class="bi bi-exclamation-triangle me-1"></i>Brak aut dostępnych w bazie
                        </div>
                    @endif
                @endif
            </div>
        </div>

        {{-- Miejsca w pojeździe (gdy wybrany pojazd) --}}
        @if($transportMode === 'own' && !empty($vehicleId))
            @php
                $selectedVehicle  = $this->availableVehicles->firstWhere('id', (int)$vehicleId);
                $capacity         = $selectedVehicle?->capacity ?? max(1, count($vehicleSeats));
                $driverSeat       = $vehicleSeats[0] ?? null;
                $isExternalDriver = (bool)($driverSeat['external_driver'] ?? true);
                $driverEmployeeId = (int)($driverSeat['employee_id'] ?? 0);
                $occupiedByDriver = $driverEmployeeId;
                // For passenger slots, map employees from vehicleSeats[1..] first, then fill empties
                $assignedPassengers = [];
                foreach ($vehicleSeats as $si => $s) {
                    if ($si > 0) $assignedPassengers[$si] = (int)($s['employee_id'] ?? 0);
                }
                // Collect employees NOT in driver seat
                $passengerPool = $this->selectedEmployees->filter(fn($e) => $e->id !== $occupiedByDriver)->values();
                // Build occupied list from vehicleSeats (backend order) – filter out driver, drop nulls
                $occupiedFromSeats = [];
                foreach ($assignedPassengers as $empId) {
                    if ($empId && $empId !== $occupiedByDriver) {
                        $emp = $passengerPool->firstWhere('id', $empId);
                        if ($emp) $occupiedFromSeats[] = $emp;
                    }
                }
                // Add any selected employees not yet in a seat (e.g. newly added)
                $seatedIds = array_column($occupiedFromSeats, 'id');
                foreach ($passengerPool as $emp) {
                    if (!in_array($emp->id, $seatedIds)) {
                        $occupiedFromSeats[] = $emp;
                    }
                }
                // Build compacted passengerSlots: occupied LEFT, empty RIGHT — no gaps
                $passengerSlots = [];
                for ($pi = 1; $pi < $capacity; $pi++) {
                    $passengerSlots[$pi] = $occupiedFromSeats[$pi - 1] ?? null;
                }
                $occupiedCount = ($isExternalDriver ? 1 : ($driverEmployeeId ? 1 : 0))
                               + collect($passengerSlots)->filter()->count();
                // Employees available to be assigned as driver (those already in passenger pool)
                $driverCandidates = $passengerPool;
            @endphp

            <style>
                .vs-seat {
                    width: 130px; min-height: 130px; height: auto;
                    border-radius: 14px;
                    display: flex; flex-direction: column;
                    align-items: center; justify-content: center;
                    gap: 6px; padding: 10px;
                    transition: all .15s ease;
                    position: relative;
                }
                .vs-seat-driver {
                    background: rgba(99,102,241,0.13);
                    border: 1.5px solid rgba(99,102,241,0.4);
                    width: 150px; min-height: 145px;
                }
                .vs-seat-driver:hover { border-color: rgba(99,102,241,0.7); background: rgba(99,102,241,0.2); }
                .vs-seat-occupied {
                    background: rgba(59,130,246,0.10);
                    border: 1.5px solid rgba(59,130,246,0.55);
                    box-shadow:
                        0 10px 26px rgba(0,0,0,0.28),
                        inset 0 1px 0 rgba(255,255,255,0.08);
                    cursor: grab;
                }
                .vs-seat-occupied:hover {
                    border-color: rgba(59,130,246,0.55);
                    background: rgba(59,130,246,0.13);
                    box-shadow:
                        0 12px 30px rgba(0,0,0,0.32),
                        0 0 0 3px rgba(59,130,246,0.14),
                        inset 0 1px 0 rgba(255,255,255,0.10);
                }
                .vs-seat-occupied:active { cursor: grabbing; }
                .vs-seat-empty { background: rgba(255,255,255,0.015); border: 1.5px dashed rgba(255,255,255,0.12); }
                .vs-seat-drag-over { border-color: rgba(99,102,241,0.8) !important; background: rgba(99,102,241,0.18) !important; }
                .vs-label { font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; font-weight: 600; opacity: .55; }
                .vs-seat-icon { font-size: 1.6rem; opacity: .25; line-height: 1; }
                .vs-avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
                .vs-avatar-placeholder { width: 36px; height: 36px; border-radius: 50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.75rem; }
            </style>

            <div class="mt-3 pt-3" style="border-top: 1px solid rgba(255,255,255,0.08);">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-car-front text-muted"></i>
                    <span class="small fw-semibold">Miejsca w pojeździe{{ $selectedVehicle ? ' – '.$selectedVehicle->brand.' '.$selectedVehicle->model : '' }}</span>
                    @if(!empty($vehicleSeats))
                        @php
                            // Badge should reflect total people assigned to the trip vs vehicle capacity,
                            // even if some people don't fit into rendered seats.
                            $totalTripPeople = $this->selectedEmployees->count() + ($isExternalDriver ? 1 : 0);
                            $isMissingDriver = (! $isExternalDriver) && empty($driverEmployeeId);
                            $isOverCapacity = $totalTripPeople > $capacity;
                        @endphp
                        @if($isMissingDriver)
                        <span class="badge rounded-pill" style="background:rgba(245,158,11,0.16); color:#fcd34d; font-size:.7rem; border:1px solid rgba(245,158,11,0.35);">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>Brak kierowcy
                        </span>
                        @elseif($isOverCapacity)
                        <span class="badge rounded-pill" style="background:rgba(239,68,68,0.18); color:#fca5a5; font-size:.7rem; border:1px solid rgba(239,68,68,0.35);">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $totalTripPeople }}/{{ $capacity }} — za dużo osób!
                        </span>
                        @else
                        <span class="badge rounded-pill" style="background:rgba(255,255,255,0.08); color:#94a3b8; font-size:.7rem;">
                            {{ $totalTripPeople }}/{{ $capacity }} zajęte
                        </span>
                        @endif
                    @endif
                </div>

                @if(empty($vehicleSeats))
                    <div class="text-muted small d-flex align-items-center gap-2" style="opacity:.5;">
                        <div class="spinner-border spinner-border-sm" role="status" style="width:14px; height:14px; border-width:2px;"></div>
                        Ładowanie miejsc…
                    </div>
                @else
                <div class="d-flex flex-wrap gap-2 align-items-start"
                     x-data="{
                         dragging: null,
                         startDrag(id) { this.dragging = id; },
                         overDriver: false,
                         dropOnDriver() {
                             if (this.dragging) { $wire.assignDriverSeatEmployee(this.dragging); this.dragging = null; }
                             this.overDriver = false;
                         }
                     }">

                    {{-- ── Fotel kierowcy ── --}}
                    <div wire:key="vs-driver"
                         class="vs-seat vs-seat-driver"
                         :class="{ 'vs-seat-drag-over': overDriver }"
                         x-on:dragover.prevent="overDriver = true"
                         x-on:dragleave="overDriver = false"
                         x-on:drop.prevent="dropOnDriver()">

                        <span class="vs-label" style="color:#a5b4fc; opacity:.8;">
                            <i class="bi bi-steering-wheel me-1"></i>Kierowca
                        </span>

                        @if(!$isExternalDriver && $driverEmployeeId && ($driverEmp = $this->selectedEmployees->firstWhere('id', $driverEmployeeId)))
                            {{-- Pracownik jako kierowca --}}
                            @if($driverEmp->image_url)
                                <img src="{{ $driverEmp->image_url }}" class="vs-avatar" alt="">
                            @else
                                <div class="vs-avatar-placeholder" style="background:rgba(99,102,241,0.35); color:#a5b4fc;">
                                    {{ mb_strtoupper(mb_substr($driverEmp->first_name,0,1).mb_substr($driverEmp->last_name,0,1)) }}
                                </div>
                            @endif
                            <span class="small fw-semibold text-center text-truncate w-100" style="font-size:.75rem; max-width:120px;">
                                {{ $driverEmp->full_name }}
                            </span>
                            <button type="button"
                                    class="btn btn-link text-danger p-0 position-absolute"
                                    style="top:6px; right:6px; font-size:.75rem; line-height:1;"
                                    title="Usuń z roli kierowcy"
                                    wire:click="assignDriverSeatEmployee(null)">
                                <i class="bi bi-x-circle-fill"></i>
                            </button>
                        @elseif($isExternalDriver)
                            {{-- Kierowca zewnętrzny --}}
                            <i class="bi bi-person-fill vs-seat-icon" style="opacity:.3; font-size:1.8rem;"></i>
                            <span class="small text-center" style="font-size:.72rem; color:#94a3b8; line-height:1.3;">Zewnętrzny</span>
                        @else
                            {{-- Brak kierowcy wewnętrznego – dropdown wyboru --}}
                            <i class="bi bi-exclamation-triangle" style="color:#f59e0b; font-size:1.1rem; opacity:.8;"></i>
                            <span class="small text-center" style="font-size:.68rem; color:#f59e0b; line-height:1.2; margin-bottom:2px;">Wybierz kierowcę</span>
                            <select class="form-select form-select-sm w-100"
                                    style="font-size:.7rem; padding: 3px 24px 3px 6px; height:28px; min-height:28px; border-radius:8px;"
                                    x-on:change="$wire.assignDriverSeatEmployee($event.target.value ? parseInt($event.target.value) : null)">
                                <option value="">— pasażer —</option>
                                @foreach($driverCandidates as $dc)
                                    <option value="{{ $dc->id }}">{{ $dc->full_name }}</option>
                                @endforeach
                            </select>
                        @endif

                        {{-- Checkbox zewnętrzny --}}
                        <label class="d-flex align-items-center gap-1 mt-auto" style="cursor:pointer; font-size:.7rem; white-space:nowrap;">
                            <input type="checkbox" class="form-check-input mt-0" style="width:11px;height:11px;"
                                   {{ $isExternalDriver ? 'checked' : '' }}
                                   wire:change="toggleExternalDriver()">
                            <span style="color:#94a3b8;">Zewnętrzny</span>
                        </label>
                    </div>

                    {{-- ── Fotele pasażerów ── --}}
                    @foreach($passengerSlots as $slotIdx => $emp)
                        @if($emp)
                            {{-- Zajęty fotel --}}
                            <div wire:key="vs-seat-{{ $slotIdx }}-{{ $emp->id }}"
                                 class="vs-seat vs-seat-occupied"
                                 draggable="true"
                                 x-on:dragstart="startDrag({{ $emp->id }})"
                                 title="Przeciągnij na fotel kierowcy">
                                <span class="vs-label">Pasażer {{ $slotIdx }}</span>
                                @if($emp->image_url)
                                    <img src="{{ $emp->image_url }}" class="vs-avatar" alt="">
                                @else
                                    <div class="vs-avatar-placeholder" style="background:rgba(148,163,184,0.2); color:#94a3b8;">
                                        {{ mb_strtoupper(mb_substr($emp->first_name,0,1).mb_substr($emp->last_name,0,1)) }}
                                    </div>
                                @endif
                                <span class="small text-center text-truncate w-100" style="font-size:.75rem; max-width:110px;">
                                    {{ $emp->full_name }}
                                </span>
                                <i class="bi bi-grip-horizontal position-absolute text-muted" style="bottom:6px; font-size:.65rem; opacity:.4;"></i>
                            </div>
                        @else
                            {{-- Wolny fotel --}}
                            <div wire:key="vs-seat-{{ $slotIdx }}-empty"
                                 class="vs-seat vs-seat-empty">
                                <span class="vs-label">Pasażer {{ $slotIdx }}</span>
                                <svg width="32" height="38" viewBox="0 0 32 38" fill="none" xmlns="http://www.w3.org/2000/svg" opacity=".22">
                                    <rect x="2" y="2" width="28" height="20" rx="6" stroke="currentColor" stroke-width="2"/>
                                    <path d="M4 22 Q2 30 6 34 Q10 38 16 38 Q22 38 26 34 Q30 30 28 22" stroke="currentColor" stroke-width="2" fill="none"/>
                                    <line x1="6" y1="22" x2="26" y2="22" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <span style="font-size:.72rem; color:#64748b;">Wolne</span>
                            </div>
                        @endif
                    @endforeach
                </div>
                @endif {{-- end @if(empty($vehicleSeats)) --}}
            </div>
        @endif

        {{-- Bilety (transport publiczny + pracownicy przypisani) --}}
        @if($transportMode === 'public' && $this->selectedEmployees->isNotEmpty())
            @php $ticketsIncomplete = $this->headerTicketsIncomplete; @endphp
            <div class="mt-3 pt-3 rounded-3 px-2 py-2 px-md-3 transition-all"
                 style="border-top: 1px solid rgba(255,255,255,0.08);
                        {{ $ticketsIncomplete ? 'border: 1px solid rgba(239,68,68,0.55) !important; background: rgba(239,68,68,0.07); box-shadow: 0 0 0 1px rgba(239,68,68,0.12);' : '' }}">
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <i class="bi bi-ticket-perforated {{ $ticketsIncomplete ? 'text-danger' : 'text-info' }}" style="font-size:0.9rem;"></i>
                    <span class="small fw-semibold {{ $ticketsIncomplete ? 'text-danger' : '' }}">Bilety lotnicze</span>
                    @if($ticketsIncomplete)
                        <span class="badge rounded-pill" style="font-size: 0.65rem; background: rgba(239,68,68,0.2); color: #fecaca; border: 1px solid rgba(239,68,68,0.4);">
                            Uzupełnij kwoty, waluty i załączniki
                        </span>
                    @endif
                </div>

                <style>
                    /* Inline styles to avoid CSS cache/build issues */
                    .ticket-grid{
                        display:grid;
                        grid-template-columns: repeat(7, minmax(140px, 1fr));
                        gap: 10px;
                    }
                    @media (max-width: 1400px){ .ticket-grid{ grid-template-columns: repeat(5, minmax(140px, 1fr)); } }
                    @media (max-width: 1100px){ .ticket-grid{ grid-template-columns: repeat(3, minmax(160px, 1fr)); } }
                    @media (max-width: 700px){ .ticket-grid{ grid-template-columns: repeat(2, minmax(160px, 1fr)); } }

                    .ticket-card{
                        background: rgba(255,255,255,0.04);
                        border: 1px solid rgba(255,255,255,0.10);
                        border-radius: 14px;
                        padding: 10px;
                        box-shadow: 0 10px 26px rgba(0,0,0,0.20);
                        min-height: 110px;
                    }
                    .ticket-card:hover{
                        border-color: rgba(59,130,246,0.35);
                        background: rgba(59,130,246,0.06);
                    }
                    .ticket-card.ticket-card--incomplete{
                        border-color: rgba(239,68,68,0.55) !important;
                        background: rgba(239,68,68,0.06) !important;
                        box-shadow: 0 0 0 1px rgba(239,68,68,0.12);
                    }
                    .ticket-icon{
                        width: 28px; height: 28px;
                        border-radius: 10px;
                        display:flex; align-items:center; justify-content:center;
                        background: rgba(34,211,238,0.10);
                        border: 1px solid rgba(34,211,238,0.25);
                        color: #67e8f9;
                        flex: 0 0 auto;
                    }
                    .ticket-employee-name{
                        font-weight: 600;
                        font-size: .78rem;
                        display:flex;
                        gap: 6px;
                        align-items:center;
                    }
                    .ticket-person-icon{ color: rgba(148,163,184,0.9); }
                    .ticket-subtext{ font-size: .72rem; opacity: .75; }
                    .ticket-form-row{ display:flex; gap: 8px; align-items: start; }
                    .ticket-amount{ flex: 1 1 auto; min-width: 0; }
                    .ticket-currency{ flex: 0 0 68px; }
                    .ticket-card .form-control-sm,
                    .ticket-card .form-select-sm{
                        border-radius: 10px !important;
                        font-size: .75rem !important;
                        padding: 0.2rem 0.45rem !important;
                    }
                    .ticket-file-input{
                        position:absolute !important;
                        left:-9999px !important;
                        width:1px !important;
                        height:1px !important;
                        opacity:0 !important;
                    }
                    .ticket-attach-btn{
                        width: 30px; height: 30px;
                        border-radius: 12px;
                        display:flex; align-items:center; justify-content:center;
                        background: rgba(255,255,255,0.06);
                        border: 1px solid rgba(255,255,255,0.12);
                        color: rgba(148,163,184,0.95);
                        cursor: pointer;
                        transition: all .15s ease;
                        flex: 0 0 auto;
                    }
                    .ticket-attach-btn:hover{
                        background: rgba(59,130,246,0.10);
                        border-color: rgba(59,130,246,0.35);
                        color: #93c5fd;
                    }
                    .ticket-attach-btn.is-attached{
                        background: rgba(16,185,129,0.12);
                        border-color: rgba(16,185,129,0.35);
                        color: #6ee7b7;
                    }
                </style>

                <div class="ticket-grid">
                    @foreach($this->selectedEmployees as $employee)
                        @php
                            $ticket = $ticketCostsByEmployee[$employee->id] ?? [];
                            $hasAttachment = !empty($ticket['attachment']);
                            $fileInputId = 'ticket-attachment-'.$employee->id;
                            $amt = $ticket['amount'] ?? null;
                            $cur = strtoupper(trim((string) ($ticket['currency'] ?? 'PLN')));
                            $ticketRowIncomplete = $amt === null || $amt === '' || !is_numeric($amt) || (float) $amt <= 0
                                || strlen($cur) !== 3
                                || !$hasAttachment;
                        @endphp
                        <div class="ticket-card {{ $ticketRowIncomplete ? 'ticket-card--incomplete' : '' }}" wire:key="header-ticket-{{ $employee->id }}">
                            <div class="ticket-employee-name" title="{{ $employee->full_name }}">
                                <span class="ticket-person-icon"><i class="bi bi-person-circle"></i></span>
                                <span class="text-truncate d-inline-block" style="max-width: 110px;">{{ $employee->full_name }}</span>
                            </div>

                            <div class="ticket-form-row mt-2">
                                <div class="ticket-amount">
                                    <input type="number" step="0.01" min="0"
                                           wire:model.live="ticketCostsByEmployee.{{ $employee->id }}.amount"
                                           class="form-control form-control-sm @error('ticketCostsByEmployee.'.$employee->id.'.amount') is-invalid @enderror"
                                           placeholder="0.00">
                                    @error('ticketCostsByEmployee.'.$employee->id.'.amount')
                                        <div class="invalid-feedback" style="font-size:.72rem;">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="ticket-currency">
                                    <select wire:model.live="ticketCostsByEmployee.{{ $employee->id }}.currency" class="form-select form-select-sm">
                                        <option value="PLN">PLN</option>
                                        <option value="EUR">EUR</option>
                                        <option value="USD">USD</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-2 d-flex align-items-center justify-content-between gap-2">
                                <label class="ticket-attach-btn {{ $hasAttachment ? 'is-attached' : '' }}"
                                       for="{{ $fileInputId }}"
                                       title="{{ $hasAttachment ? 'Załącznik dodany (kliknij aby zmienić)' : 'Dodaj załącznik' }}">
                                    <i class="bi bi-paperclip"></i>
                                </label>
                                <span class="small text-muted text-truncate" style="font-size:.72rem; max-width: 180px;">
                                    @if($hasAttachment)
                                        Załącznik dodany
                                    @else
                                        Dodaj bilet
                                    @endif
                                </span>
                                <input id="{{ $fileInputId }}"
                                       type="file"
                                       wire:model="ticketCostsByEmployee.{{ $employee->id }}.attachment"
                                       class="ticket-file-input">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </x-ui.card>

    <!-- Step Navigation -->
    @php
        $departurePlannerTabs = [
            1 => [
                'label' => 'Krok 1: Przypisania do projektów',
                'wireClick' => 'goToStep(1)',
            ],
            2 => [
                'label' => 'Krok 2: Przypisania do mieszkań',
                'wireClick' => 'goToStep(2)',
                'warning' => $this->step2TabIncomplete,
            ],
            3 => [
                'label' => 'Krok 3: Przypisania do pojazdów',
                'wireClick' => 'goToStep(3)',
                'warning' => $this->step3TabIncomplete,
            ],
            4 => [
                'label' => 'Krok 4: Planowanie trasy',
                'wireClick' => 'goToStep(4)',
                'warning' => $this->step4TabIncomplete,
            ],
        ];
    @endphp
    <x-ui.tabs 
        :tabs="$departurePlannerTabs"
        :activeTab="$currentStep"
        id="departureStepsTabs"
    />

    @if($currentStep === 4 && ($this->step2TabIncomplete || $this->step3TabIncomplete))
        @php
            $prevStepsBadge = $this->step2TabIncomplete && $this->step3TabIncomplete
                ? 'Uzupełnij mieszkania i pojazdy dojazdu'
                : ($this->step2TabIncomplete
                    ? 'Uzupełnij przypisania do mieszkań'
                    : 'Uzupełnij pojazdy dojazdowe');
        @endphp
        <div class="mb-4 rounded-3 px-2 py-2 px-md-3 transition-all"
             style="border: 1px solid rgba(239,68,68,0.55) !important; background: rgba(239,68,68,0.07); box-shadow: 0 0 0 1px rgba(239,68,68,0.12);">
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <i class="bi bi-house-exclamation text-danger" style="font-size:0.9rem;"></i>
                <span class="small fw-semibold text-danger">Wcześniejsze kroki</span>
                <span class="badge rounded-pill" style="font-size: 0.65rem; background: rgba(239,68,68,0.2); color: #fecaca; border: 1px solid rgba(239,68,68,0.4);">
                    {{ $prevStepsBadge }}
                </span>
            </div>
            <p class="small text-muted mb-2 mb-0" style="font-size: 0.8rem;">
                @if($this->step2TabIncomplete)
                    <span class="text-danger fw-semibold">Krok 2:</span> część osób nie ma przypisanego mieszkania.
                @endif
                @if($this->step2TabIncomplete && $this->step3TabIncomplete)
                    <span class="text-muted"> · </span>
                @endif
                @if($this->step3TabIncomplete)
                    <span class="text-danger fw-semibold">Krok 3:</span> część osób nie ma przypisanego pojazdu dojazdowego.
                @endif
            </p>
            <div class="d-flex flex-wrap gap-2 mt-2">
                @if($this->step2TabIncomplete)
                    <button type="button" class="btn btn-sm btn-outline-light border-opacity-25" wire:click="goToStep(2)">
                        <i class="bi bi-arrow-left-circle me-1"></i>Otwórz krok 2
                    </button>
                @endif
                @if($this->step3TabIncomplete)
                    <button type="button" class="btn btn-sm btn-outline-light border-opacity-25" wire:click="goToStep(3)">
                        <i class="bi bi-arrow-left-circle me-1"></i>Otwórz krok 3
                    </button>
                @endif
            </div>
        </div>
    @endif

    <!-- Step Content -->
    @if($currentStep === 1)
        <livewire:steps.step1-project-assignments
            :departure-date="$departureDate"
            :end-date="$endDate"
            :vehicle-id="$vehicleId"
            :assignments="$assignments"
            :assignment-ranges="$assignmentRanges"
            :vehicle-seats="$vehicleSeats"
            key="step1-{{ $departureDate }}-{{ $endDate }}-{{ $vehicleId }}-{{ md5(json_encode($assignments)) }}-{{ md5(json_encode($assignmentRanges)) }}-{{ md5(json_encode($vehicleSeats)) }}"
        />
    @elseif($currentStep === 2)
        <livewire:steps.step2-accommodation-assignments
            :departure-date="$departureDate"
            :end-date="$endDate"
            :assignments="$assignments"
            :assignment-ranges="$assignmentRanges"
            :accommodation-assignments="$accommodationAssignments"
            key="step2-{{ $departureDate }}-{{ md5(json_encode($assignments)) }}-{{ md5(json_encode($assignmentRanges)) }}-{{ md5(json_encode($accommodationAssignments)) }}"
        />
    @elseif($currentStep === 3)
        <livewire:steps.step3-vehicle-assignments
            :departure-date="$departureDate"
            :end-date="$endDate"
            :vehicle-id="$vehicleId"
            :assignments="$assignments"
            :assignment-ranges="$assignmentRanges"
            :accommodation-assignments="$accommodationAssignments"
            :vehicle-assignments="$vehicleAssignments"
            key="step3-{{ $departureDate }}-{{ $vehicleId }}-{{ md5(json_encode($assignments)) }}-{{ md5(json_encode($assignmentRanges)) }}-{{ md5(json_encode($vehicleAssignments)) }}"
        />
    @elseif($currentStep === 4)
        <livewire:steps.step4-route-planning
            :departure-date="$departureDate"
            :end-date="$endDate"
            :vehicle-id="$vehicleId"
            :accommodation-assignments="$accommodationAssignments"
            :assignment-ranges="$assignmentRanges"
            :vehicle-assignments="$vehicleAssignments"
            :ticket-costs-by-employee="$ticketCostsByEmployee"
            :shared-start-airport-location-id="$sharedStartAirportLocationId"
            :shared-end-airport-location-id="$sharedEndAirportLocationId"
            :initial-route-waypoints="data_get($routeData, 'route_waypoints', [])"
            :initial-location-stop-notes="data_get($routeData, 'location_stop_notes', [])"
            :initial-route-distance="data_get($routeData, 'route_distance')"
            :initial-route-duration="data_get($routeData, 'route_duration')"
            :initial-route-manual="(bool) data_get($routeData, 'route_distance_is_manual', false)"
            :initial-transfer-config="$transferConfig"
            key="step4-{{ $departureDate }}-{{ $transportMode }}-{{ md5(json_encode(data_get($routeData, 'route_waypoints', []))) }}-{{ md5(json_encode($accommodationAssignments)) }}-{{ md5(json_encode($assignmentRanges)) }}-{{ md5(json_encode($vehicleAssignments)) }}-{{ $sharedStartAirportLocationId }}-{{ $sharedEndAirportLocationId }}"
        />
    @endif
</div>
