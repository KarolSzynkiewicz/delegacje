<div>
    @if($showTransportSwitchModal && $pendingTransportMode)
        @teleport('body')
            <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true"
                 style="background-color: rgba(0,0,0,0.55); z-index: 1055;">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-secondary" style="background: var(--bg-card, #1e293b); color: #e2e8f0;">
                        <div class="modal-header border-secondary">
                            <h5 class="modal-title"><i class="bi bi-arrow-left-right text-warning me-2"></i>Zmiana sposobu powrotu</h5>
                            <button type="button" class="btn-close btn-close-white" wire:click="cancelTransportModeSwitch" aria-label="Zamknij"></button>
                        </div>
                        <div class="modal-body">
                            @if($pendingTransportMode === 'public')
                                <p class="mb-0">Przejście na transport publiczny wyzeruje wybór pojazdu oraz wprowadzone kwoty biletów (możesz je uzupełnić ponownie).</p>
                            @else
                                <p class="mb-0">Przejście na własny pojazd wyzeruje lotniska i kwoty biletów — zjazd zapiszesz z pojazdem firmowym.</p>
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

    <style>
        .rtp-glass { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(12px); }
        .rtp-section { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07); border-radius: 14px; padding: 1.25rem; }
        .rtp-emp-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 0.75rem 1rem; cursor: pointer; transition: all 0.15s ease; }
        .rtp-emp-card:hover { background: rgba(255,255,255,0.1); border-color: rgba(99,102,241,0.4); }
        .rtp-emp-card.selected { background: rgba(99,102,241,0.15); border-color: rgba(99,102,241,0.5); }
        .rtp-avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
        .rtp-avatar-placeholder { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; color: white; }
        .rtp-mini-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 1rem; height: 100%; }
    </style>

    @if($errorMessage)
        <div class="alert alert-danger mb-4 d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            {{ $errorMessage }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger mb-4">{{ session('error') }}</div>
    @endif

    {{-- Szczegóły zjazdu — ten sam układ co „Szczegóły wyjazdu” --}}
    <x-ui.card class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center"
                 style="width: 32px; height: 32px; background: rgba(99,102,241,0.2);">
                <i class="bi bi-suitcase-lg" style="font-size: 0.9rem; color: #a5b4fc;"></i>
            </div>
            <h6 class="mb-0 fw-bold" style="letter-spacing: .02em;">Szczegóły zjazdu</h6>
        </div>

        @php
            $missingReturnDate = empty($returnDate);
            $missingEndDate = empty($endDate);
            $datesIncomplete = $missingReturnDate || $missingEndDate;
        @endphp

        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <div class="rounded-3 p-2 transition-all"
                     style="{{ $datesIncomplete ? 'border: 1px solid rgba(239,68,68,0.65) !important; background: rgba(239,68,68,0.12) !important; box-shadow: 0 0 0 1px rgba(239,68,68,0.15);' : '' }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-6 min-w-0">
                            <label class="form-label small mb-1 {{ $missingReturnDate ? 'text-danger fw-semibold' : 'text-muted' }}">
                                Data zjazdu <span class="text-danger">*</span>
                            </label>
                            <input type="date"
                                   wire:model.live="returnDate"
                                   class="form-control form-control-sm w-100 @if($errors->has('returnDate') || $missingReturnDate) is-invalid @endif">
                        </div>
                        <div class="col-6 min-w-0">
                            <label class="form-label small mb-1 {{ $missingEndDate ? 'text-danger fw-semibold' : 'text-muted' }}">
                                Data zakończenia <span class="text-danger">*</span>
                            </label>
                            <input type="date"
                                   wire:model.live="endDate"
                                   class="form-control form-control-sm w-100 @if($errors->has('endDate') || $missingEndDate) is-invalid @endif"
                                   @if($returnDate) min="{{ $returnDate }}" @endif>
                        </div>
                        @if($datesIncomplete)
                            <div class="col-12">
                                <div class="small text-danger mb-0" style="font-size: 0.72rem;">
                                    <i class="bi bi-exclamation-circle me-1"></i>Wybierz datę zjazdu i datę zakończenia.
                                </div>
                            </div>
                        @endif
                        @error('returnDate')
                            <div class="col-12">
                                <div class="invalid-feedback d-block" style="font-size:.72rem;">{{ $message }}</div>
                            </div>
                        @enderror
                        @error('endDate')
                            <div class="col-12">
                                <div class="invalid-feedback d-block" style="font-size:.72rem;">{{ $message }}</div>
                            </div>
                        @enderror
                    </div>
                </div>
            </div>

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

            <div class="col-md-5">
                @if($transportMode === 'public')
                    @php
                        $missingStartAirport = empty($sharedStartAirportLocationId);
                        $missingEndAirport = empty($sharedEndAirportLocationId);
                        $airportsIncomplete = $missingStartAirport || $missingEndAirport;
                    @endphp
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
                    @if($this->availableVehicles->isEmpty() && $returnDate)
                        <div class="small text-warning mt-1">
                            <i class="bi bi-exclamation-triangle me-1"></i>Brak aut dostępnych dla tego okresu (poza bazą i wolnych w wyjeździe/zjeździe).
                        </div>
                    @endif
                @endif
            </div>
        </div>

        {{-- Miejsca w pojeździe — ten sam układ co /departures/create-v2 (kierowca zewnętrzny = jedno zajęte miejsce) --}}
        @if($transportMode === 'own' && $vehicleId)
            @php
                $selectedVehicle  = $this->availableVehicles->firstWhere('id', (int) $vehicleId);
                $capacity         = $selectedVehicle?->capacity ?? max(1, count($vehicleSeats));
                $driverSeat       = $vehicleSeats[0] ?? null;
                $isExternalDriver = (bool) ($driverSeat['external_driver'] ?? true);
                $driverEmployeeId = (int) ($driverSeat['employee_id'] ?? 0);
                $occupiedByDriver = $driverEmployeeId;
                $assignedPassengers = [];
                foreach ($vehicleSeats as $si => $s) {
                    if ($si > 0) {
                        $assignedPassengers[$si] = (int) ($s['employee_id'] ?? 0);
                    }
                }
                $passengerPool = $this->selectedEmployees->filter(fn ($e) => $e->id !== $occupiedByDriver)->values();
                $occupiedFromSeats = [];
                foreach ($assignedPassengers as $empId) {
                    if ($empId && $empId !== $occupiedByDriver) {
                        $emp = $passengerPool->firstWhere('id', $empId);
                        if ($emp) {
                            $occupiedFromSeats[] = $emp;
                        }
                    }
                }
                $seatedIds = array_column($occupiedFromSeats, 'id');
                foreach ($passengerPool as $emp) {
                    if (! in_array($emp->id, $seatedIds)) {
                        $occupiedFromSeats[] = $emp;
                    }
                }
                $passengerSlots = [];
                for ($pi = 1; $pi < $capacity; $pi++) {
                    $passengerSlots[$pi] = $occupiedFromSeats[$pi - 1] ?? null;
                }
                $driverCandidates = $passengerPool;
            @endphp

            <style>
                .rtp-vs-seat {
                    width: 130px; min-height: 130px; height: auto;
                    border-radius: 14px;
                    display: flex; flex-direction: column;
                    align-items: center; justify-content: center;
                    gap: 6px; padding: 10px;
                    transition: all .15s ease;
                    position: relative;
                }
                .rtp-vs-seat-driver {
                    background: rgba(99,102,241,0.13);
                    border: 1.5px solid rgba(99,102,241,0.4);
                    width: 150px; min-height: 145px;
                }
                .rtp-vs-seat-driver:hover { border-color: rgba(99,102,241,0.7); background: rgba(99,102,241,0.2); }
                .rtp-vs-seat-occupied {
                    background: rgba(59,130,246,0.10);
                    border: 1.5px solid rgba(59,130,246,0.55);
                    box-shadow:
                        0 10px 26px rgba(0,0,0,0.28),
                        inset 0 1px 0 rgba(255,255,255,0.08);
                    cursor: grab;
                }
                .rtp-vs-seat-occupied:hover {
                    border-color: rgba(59,130,246,0.55);
                    background: rgba(59,130,246,0.13);
                    box-shadow:
                        0 12px 30px rgba(0,0,0,0.32),
                        0 0 0 3px rgba(59,130,246,0.14),
                        inset 0 1px 0 rgba(255,255,255,0.10);
                }
                .rtp-vs-seat-occupied:active { cursor: grabbing; }
                .rtp-vs-seat-empty { background: rgba(255,255,255,0.015); border: 1.5px dashed rgba(255,255,255,0.12); }
                .rtp-vs-seat-drag-over { border-color: rgba(99,102,241,0.8) !important; background: rgba(99,102,241,0.18) !important; }
                .rtp-vs-label { font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; font-weight: 600; opacity: .55; }
                .rtp-vs-seat-icon { font-size: 1.6rem; opacity: .25; line-height: 1; }
                .rtp-vs-avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
                .rtp-vs-avatar-placeholder { width: 36px; height: 36px; border-radius: 50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.75rem; }
            </style>

            <div class="mt-3 pt-3" style="border-top: 1px solid rgba(255,255,255,0.08);">
                <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                    <i class="bi bi-car-front text-muted"></i>
                    <span class="small fw-semibold">Miejsca w pojeździe{{ $selectedVehicle ? ' – '.$selectedVehicle->brand.' '.$selectedVehicle->model : '' }}</span>
                    @if(!empty($vehicleSeats))
                        @php
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

                        <div wire:key="rtp-vs-driver"
                             class="rtp-vs-seat rtp-vs-seat-driver"
                             :class="{ 'rtp-vs-seat-drag-over': overDriver }"
                             x-on:dragover.prevent="overDriver = true"
                             x-on:dragleave="overDriver = false"
                             x-on:drop.prevent="dropOnDriver()">

                            <span class="rtp-vs-label" style="color:#a5b4fc; opacity:.8;">
                                <i class="bi bi-steering-wheel me-1"></i>Kierowca
                            </span>

                            @if(!$isExternalDriver && $driverEmployeeId && ($driverEmp = $this->selectedEmployees->firstWhere('id', $driverEmployeeId)))
                                @if($driverEmp->image_url)
                                    <img src="{{ $driverEmp->image_url }}" class="rtp-vs-avatar" alt="">
                                @else
                                    <div class="rtp-vs-avatar-placeholder" style="background:rgba(99,102,241,0.35); color:#a5b4fc;">
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
                                <i class="bi bi-person-fill rtp-vs-seat-icon" style="opacity:.3; font-size:1.8rem;"></i>
                                <span class="small text-center" style="font-size:.72rem; color:#94a3b8; line-height:1.3;">Zewnętrzny</span>
                            @else
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

                            <label class="d-flex align-items-center gap-1 mt-auto" style="cursor:pointer; font-size:.7rem; white-space:nowrap;">
                                <input type="checkbox" class="form-check-input mt-0" style="width:11px;height:11px;"
                                       {{ $isExternalDriver ? 'checked' : '' }}
                                       wire:change="toggleExternalDriver()">
                                <span style="color:#94a3b8;">Zewnętrzny</span>
                            </label>
                        </div>

                        @foreach($passengerSlots as $slotIdx => $emp)
                            @if($emp)
                                <div wire:key="rtp-vs-seat-{{ $slotIdx }}-{{ $emp->id }}"
                                     class="rtp-vs-seat rtp-vs-seat-occupied"
                                     draggable="true"
                                     x-on:dragstart="startDrag({{ $emp->id }})"
                                     title="Przeciągnij na fotel kierowcy">
                                    <span class="rtp-vs-label">Pasażer {{ $slotIdx }}</span>
                                    @if($emp->image_url)
                                        <img src="{{ $emp->image_url }}" class="rtp-vs-avatar" alt="">
                                    @else
                                        <div class="rtp-vs-avatar-placeholder" style="background:rgba(148,163,184,0.2); color:#94a3b8;">
                                            {{ mb_strtoupper(mb_substr($emp->first_name,0,1).mb_substr($emp->last_name,0,1)) }}
                                        </div>
                                    @endif
                                    <span class="small text-center text-truncate w-100" style="font-size:.75rem; max-width:110px;">
                                        {{ $emp->full_name }}
                                    </span>
                                    <i class="bi bi-grip-horizontal position-absolute text-muted" style="bottom:6px; font-size:.65rem; opacity:.4;"></i>
                                </div>
                            @else
                                <div wire:key="rtp-vs-seat-{{ $slotIdx }}-empty"
                                     class="rtp-vs-seat rtp-vs-seat-empty">
                                    <span class="rtp-vs-label">Pasażer {{ $slotIdx }}</span>
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
                @endif
            </div>
        @endif
    </x-ui.card>

    <div class="row g-4">
        {{-- ── SEKCJA 2: Pracownicy ── --}}
        @php
            $employeesPickIncomplete = ! empty($returnDate) && empty($selectedEmployeeIds) && count($this->employeesList) > 0;
        @endphp
        <div class="col-md-5">
            <div class="rtp-section h-100 rounded-3 transition-all"
                 style="{{ $employeesPickIncomplete ? 'border: 1px solid rgba(239,68,68,0.65) !important; background: rgba(239,68,68,0.12) !important; box-shadow: 0 0 0 1px rgba(239,68,68,0.15);' : '' }}">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width: 32px; height: 32px; background: rgba(99,102,241,0.2);">
                            <i class="bi bi-people text-primary" style="font-size: 0.85rem;"></i>
                        </div>
                        <h6 class="mb-0 fw-semibold">Kto wraca do bazy?</h6>
                    </div>
                    @if(!empty($selectedEmployeeIds))
                        <span class="badge rounded-pill" style="background: rgba(99,102,241,0.2); color: #a5b4fc;">
                            {{ count($selectedEmployeeIds) }} wybranych
                        </span>
                    @endif
                </div>

                <div class="mb-3">
                    <div class="position-relative">
                        <i class="bi bi-search position-absolute text-muted" style="left: 12px; top: 50%; transform: translateY(-50%); pointer-events: none;"></i>
                        <input type="text" wire:model.live="employeeSearch" class="form-control ps-5"
                               placeholder="Szukaj pracownika...">
                    </div>
                    @if($returnDate)
                        <div class="small text-muted mt-1">
                            <i class="bi bi-geo-alt me-1"></i>Tylko pracownicy spoza bazy na {{ \Carbon\Carbon::parse($returnDate)->format('d.m.Y') }}
                        </div>
                    @endif
                    @error('selectedEmployeeIds') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    @if($employeesPickIncomplete)
                        <div class="small text-danger mt-1" style="font-size: 0.72rem;">
                            <i class="bi bi-exclamation-circle me-1"></i>Wybierz co najmniej jednego uczestnika zjazdu.
                        </div>
                    @endif
                </div>

                <div class="vstack gap-2" style="max-height: 380px; overflow-y: auto;">
                    @forelse($this->employeesList as $employee)
                        @php $isSelected = in_array($employee['id'], $selectedEmployeeIds); @endphp
                        <div class="rtp-emp-card {{ $isSelected ? 'selected' : '' }}"
                             wire:click="toggleEmployee({{ $employee['id'] }})"
                             wire:key="emp-{{ $employee['id'] }}">
                            <div class="d-flex align-items-center gap-3">
                                @if(!empty($employee['image_url']))
                                    <img src="{{ $employee['image_url'] }}" alt="" class="rtp-avatar">
                                @else
                                    <div class="rtp-avatar-placeholder">
                                        {{ mb_strtoupper(mb_substr($employee['first_name'], 0, 1).mb_substr($employee['last_name'], 0, 1)) }}
                                    </div>
                                @endif
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold small text-truncate">{{ $employee['full_name'] }}</div>
                                    @if(!empty($employee['roles']))
                                        <div class="text-muted" style="font-size: 0.72rem;">
                                            {{ collect($employee['roles'])->pluck('name')->join(', ') }}
                                        </div>
                                    @endif
                                </div>
                                @if($isSelected)
                                    <i class="bi bi-check-circle-fill text-primary flex-shrink-0"></i>
                                @else
                                    <i class="bi bi-circle text-muted flex-shrink-0"></i>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">
                            @if(empty($returnDate))
                                <i class="bi bi-calendar3 d-block mb-2" style="font-size: 2rem;"></i>
                                Wybierz datę zjazdu
                            @else
                                <i class="bi bi-people d-block mb-2" style="font-size: 2rem;"></i>
                                Brak pracowników spoza bazy
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── SEKCJA 3: Bilety / trasa ── --}}
        <div class="col-md-7">
            {{-- Koszty biletów (transport publiczny) --}}
            @if($this->isPublicTransport && $this->selectedEmployees->isNotEmpty())
                <div class="rtp-section mb-3">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width: 32px; height: 32px; background: rgba(59,130,246,0.2);">
                            <i class="bi bi-ticket-perforated text-info" style="font-size: 0.85rem;"></i>
                        </div>
                        <h6 class="mb-0 fw-semibold">Bilety lotnicze</h6>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm mb-0" style="font-size: 0.875rem;">
                            <thead>
                                <tr class="text-muted" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: .03em;">
                                    <th class="fw-semibold ps-0">Pracownik</th>
                                    <th class="fw-semibold">Kwota</th>
                                    <th class="fw-semibold">Waluta</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->selectedEmployees as $employee)
                                    <tr wire:key="ticket-{{ $employee->id }}">
                                        <td class="ps-0 align-middle">
                                            <div class="d-flex align-items-center gap-2">
                                                @if($employee->image_url)
                                                    <img src="{{ $employee->image_url }}" class="rtp-avatar" style="width:28px;height:28px;" alt="">
                                                @else
                                                    <div class="rtp-avatar-placeholder" style="width:28px;height:28px;font-size:0.65rem;">
                                                        {{ mb_strtoupper(mb_substr($employee->first_name,0,1).mb_substr($employee->last_name,0,1)) }}
                                                    </div>
                                                @endif
                                                <span class="fw-semibold">{{ $employee->full_name }}</span>
                                            </div>
                                        </td>
                                        <td class="align-middle" style="min-width: 110px;">
                                            <input type="number" step="0.01" min="0"
                                                   wire:model.live="ticketCostsByEmployee.{{ $employee->id }}.amount"
                                                   class="form-control form-control-sm @error('ticketCostsByEmployee.'.$employee->id.'.amount') is-invalid @enderror"
                                                   placeholder="0.00">
                                            @error('ticketCostsByEmployee.'.$employee->id.'.amount')
                                                <div class="invalid-feedback" style="font-size:0.72rem;">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td class="align-middle" style="min-width: 90px;">
                                            <select wire:model.live="ticketCostsByEmployee.{{ $employee->id }}.currency"
                                                    class="form-select form-select-sm">
                                                @foreach($this->currencyCases as $currency)
                                                    <option value="{{ $currency->value }}" {{ ($ticketCostsByEmployee[$employee->id]['currency'] ?? 'PLN') === $currency->value ? 'selected' : '' }}>
                                                        {{ $currency->value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Podgląd zjazdu (po prepareReturn): skrócenia + konsekwencje auta powrotnego --}}
            @if($showPreview && !empty($previewData))
                <div class="rtp-section mb-3" style="border-color: {{ $previewData['is_valid'] ? 'rgba(34,197,94,0.3)' : 'rgba(239,68,68,0.3)' }} !important;">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width: 32px; height: 32px; background: {{ $previewData['is_valid'] ? 'rgba(34,197,94,0.2)' : 'rgba(239,68,68,0.15)' }};">
                            <i class="bi bi-{{ $previewData['is_valid'] ? 'check-circle text-success' : 'exclamation-triangle text-danger' }}" style="font-size: 0.85rem;"></i>
                        </div>
                        <h6 class="mb-0 fw-semibold">Podgląd zjazdu</h6>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <div class="small text-muted">Data zjazdu</div>
                            <div class="fw-semibold">{{ $previewData['return_date'] }}</div>
                        </div>
                        @if($previewData['end_date'] && $previewData['end_date'] !== $previewData['return_date'])
                            <div class="col-6">
                                <div class="small text-muted">Data końcowa</div>
                                <div class="fw-semibold">{{ $previewData['end_date'] }}</div>
                            </div>
                        @endif
                        <div class="col-6">
                            <div class="small text-muted">Uczestnicy zjazdu</div>
                            <div class="fw-semibold">{{ $previewData['employees_count'] }} os.</div>
                        </div>
                        @if($previewData['vehicle'])
                            <div class="col-12">
                                <div class="small text-muted">Pojazd powrotny</div>
                                <div class="fw-semibold">{{ $previewData['vehicle'] }}</div>
                                @if(!empty($previewData['vehicle_fill']) && ($previewData['vehicle_fill']['capacity'] ?? 0) > 0)
                                    @php $vf = $previewData['vehicle_fill']; @endphp
                                    <div class="small mt-2">
                                        <span class="text-muted">Miejsca w tym zjeździe (kierowca + uczestnicy, kierowca zewn. = 1 miejsce):</span>
                                        <span class="{{ ($vf['over_capacity'] ?? false) ? 'text-danger fw-semibold' : 'text-success' }}">
                                            {{ $vf['occupied'] }} / {{ $vf['capacity'] }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    @php
                        $blockingConflicts = collect($previewData['conflicts'] ?? [])->where('is_blocking', true);
                    @endphp
                    @if($blockingConflicts->isNotEmpty())
                        <div class="vstack gap-2 mb-3">
                            @foreach($blockingConflicts as $conflict)
                                <div class="d-flex align-items-start gap-2 p-2 rounded" style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.35);">
                                    <i class="bi bi-x-circle text-danger flex-shrink-0 mt-1"></i>
                                    <span class="small">{{ $conflict['message'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- 1) Skrócenie przypisań uczestników --}}
                    <div class="mb-4">
                        <div class="small text-uppercase fw-semibold mb-2" style="font-size: 0.68rem; letter-spacing: .06em; color: #94a3b8;">
                            1. Odpięcie przypisań uczestników (koniec {{ $previewData['return_date'] }})
                        </div>
                        <p class="small text-muted mb-2" style="font-size: 0.78rem;">
                            Dla wybranych osób data końcowa przypisań zostanie ustawiona na dzień zjazdu — skrócone zostaną powiązania z projektem, mieszkaniem i pojazdem (jeśli dotyczą).
                        </p>
                        @if(!empty($previewData['participant_rows']))
                            <div class="table-responsive rounded border" style="border-color: rgba(255,255,255,0.1) !important;">
                                <table class="table table-sm table-borderless mb-0 align-middle" style="font-size: 0.82rem;">
                                    <thead>
                                        <tr class="text-muted" style="border-bottom: 1px solid rgba(255,255,255,0.08);">
                                            <th class="ps-3 py-2"><i class="bi bi-person me-1"></i>Osoba</th>
                                            <th class="py-2"><i class="bi bi-briefcase me-1"></i>Projekt</th>
                                            <th class="py-2"><i class="bi bi-car-front me-1"></i>Auto</th>
                                            <th class="pe-3 py-2"><i class="bi bi-house me-1"></i>Dom</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($previewData['participant_rows'] as $row)
                                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                                <td class="ps-3 py-2 fw-semibold text-white">{{ $row['full_name'] }}</td>
                                                <td class="py-2">{{ $row['projects_label'] }}</td>
                                                <td class="py-2">{{ $row['vehicle_label'] }}</td>
                                                <td class="pe-3 py-2">{{ $row['house_label'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="small text-muted fst-italic">Brak aktywnych przypisań do skrócenia w tym dniu (np. już zakończone).</div>
                        @endif
                    </div>

                    {{-- 2) Inni użytkownicy auta powrotnego --}}
                    @if(!empty($previewData['requires_consequences_confirm']) && !empty($previewData['displaced_without_vehicle']))
                        <div class="mb-3 p-3 rounded-3" style="background: rgba(251,191,36,0.08); border: 1px solid rgba(251,191,36,0.28);">
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <i class="bi bi-car-front text-warning flex-shrink-0 mt-1"></i>
                                <div>
                                    <div class="fw-semibold text-warning mb-1">Tego auta już nie będzie… (po dniu zjazdu)</div>
                                    @if(!empty($previewData['vehicle']))
                                        <p class="small text-muted mb-2" style="font-size: 0.78rem;">Pojazd: <strong class="text-white">{{ $previewData['vehicle'] }}</strong></p>
                                    @endif
                                    <p class="small text-muted mb-2" style="font-size: 0.78rem;">
                                        Odpięte zostaną przypisania do <strong>pojazdu powrotnego</strong> u osób <strong>spoza tego zjazdu</strong> — od dnia zjazdu pozostaną <strong>bez przypisanego auta</strong>:
                                    </p>
                                    <p class="small mb-2 text-white" style="font-size: 0.9rem;">
                                        {{ collect($previewData['displaced_without_vehicle'])->pluck('full_name')->implode(', ') }}
                                        <span class="text-muted"> — bez auta.</span>
                                    </p>
                                </div>
                            </div>

                            <div class="form-check mt-3 pt-2" style="border-top: 1px solid rgba(251,191,36,0.2);">
                                <input class="form-check-input" type="checkbox" id="acceptReturnConsequences" wire:model.live="acceptReturnConsequences">
                                <label class="form-check-label small" for="acceptReturnConsequences">
                                    Potwierdzam konsekwencje i zapisuję zjazd (w tym skrócenie przypisań do auta powrotnego dla osób wymienionych powyżej).
                                </label>
                            </div>
                            @error('acceptReturnConsequences')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif

                    @if($previewData['is_valid'] && $blockingConflicts->isEmpty())
                        @if(empty($previewData['displaced_without_vehicle']))
                            <div class="d-flex align-items-center gap-2 text-success">
                                <i class="bi bi-check-circle-fill"></i>
                                <span class="small fw-semibold">Podgląd gotowy — możesz zapisać zjazd.</span>
                            </div>
                        @endif
                    @elseif(!$previewData['is_valid'])
                        <div class="small text-danger"><i class="bi bi-info-circle me-1"></i>Usuń blokady powyżej, aby móc zapisać zjazd.</div>
                    @endif
                </div>
            @endif

            {{-- Notatki --}}
            <div class="rtp-section">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width: 32px; height: 32px; background: rgba(255,255,255,0.08);">
                        <i class="bi bi-chat-text text-muted" style="font-size: 0.85rem;"></i>
                    </div>
                    <h6 class="mb-0 fw-semibold">Notatki <span class="text-muted fw-normal small">(opcjonalnie)</span></h6>
                </div>
                <textarea wire:model.live="notes" class="form-control" rows="3" placeholder="Dodatkowe uwagi do zjazdu..."></textarea>
            </div>
        </div>
    </div>

    {{-- ── FOOTER: Akcje ── --}}
    <div class="d-flex align-items-center justify-content-between mt-4 pt-3" style="border-top: 1px solid rgba(255,255,255,0.08);">
        <a href="{{ route('return-trips.index') }}" class="btn btn-ghost">
            <i class="bi bi-arrow-left me-1"></i> Anuluj
        </a>

        <div class="d-flex flex-column align-items-end gap-2">
            @error('preview')
                <div class="text-danger small text-end"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
            @enderror

        <div class="d-flex gap-2">
            @if(!$showPreview)
                <button type="button" class="btn btn-outline-primary"
                        wire:click="prepareReturn" wire:loading.attr="disabled"
                        @disabled($this->returnTripPrepareBlocked)>
                    <span wire:loading.remove wire:target="prepareReturn">
                        <i class="bi bi-eye me-1"></i> Podgląd zjazdu
                    </span>
                    <span wire:loading wire:target="prepareReturn">
                        <span class="spinner-border spinner-border-sm me-2"></span> Sprawdzam...
                    </span>
                </button>
            @else
                <button type="button" class="btn btn-outline-secondary"
                        wire:click="prepareReturn">
                    <i class="bi bi-arrow-clockwise me-1"></i> Odśwież podgląd
                </button>
                @if(!empty($previewData['is_valid']))
                    <button type="button" class="btn btn-success"
                            wire:click="saveReturn" wire:loading.attr="disabled"
                            @disabled(!empty($previewData['requires_consequences_confirm']) && !$acceptReturnConsequences)>
                        <span wire:loading.remove wire:target="saveReturn">
                            <i class="bi bi-floppy me-1"></i> Zapisz zjazd
                        </span>
                        <span wire:loading wire:target="saveReturn">
                            <span class="spinner-border spinner-border-sm me-2"></span> Zapisuję...
                        </span>
                    </button>
                @endif
            @endif
        </div>
        </div>
    </div>
</div>
