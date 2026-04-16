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

        @if($transportMode === 'own' && $vehicleId)
            @php $seat = $this->returnVehicleSeatSummary; @endphp
            @if($seat)
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="small">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Zapełnienie pojazdu w dniu zjazdu</span>
                                <span class="{{ $seat['over_capacity'] ? 'text-danger fw-semibold' : 'text-success' }}">
                                    {{ $seat['occupied'] }} / {{ $seat['capacity'] ?: '—' }} os.
                                </span>
                            </div>
                            @if($seat['capacity'] > 0)
                                <div class="progress" style="height: 6px; background: rgba(255,255,255,0.08); max-width: 420px;">
                                    <div class="progress-bar {{ $seat['over_capacity'] ? 'bg-danger' : 'bg-success' }}"
                                         role="progressbar"
                                         style="width: {{ min(100, round($seat['occupied'] / $seat['capacity'] * 100)) }}%"></div>
                                </div>
                            @endif
                            @if($seat['over_capacity'])
                                <div class="text-danger mt-1"><i class="bi bi-x-circle me-1"></i>Za dużo osób względem pojemności pojazdu.</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
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
                                        <span class="text-muted">Miejsca w aucie (dzień zjazdu):</span>
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
