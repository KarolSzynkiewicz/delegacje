<div>
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

    {{-- ── SEKCJA 1: Daty i transport ── --}}
    <div class="rtp-section mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center"
                 style="width: 32px; height: 32px; background: rgba(34,197,94,0.2);">
                <i class="bi bi-calendar-event text-success" style="font-size: 0.85rem;"></i>
            </div>
            <h6 class="mb-0 fw-semibold">Szczegóły zjazdu</h6>
        </div>

        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Data zjazdu <span class="text-danger">*</span></label>
                <input type="date" wire:model.live="returnDate" class="form-control @error('returnDate') is-invalid @enderror">
                @error('returnDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Data końcowa <span class="text-muted fw-normal">(opcjonalnie)</span></label>
                <input type="date" wire:model.live="endDate" class="form-control" min="{{ $returnDate }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Pojazd <span class="text-muted fw-normal">(opcjonalnie)</span></label>
                <select wire:model.live="vehicleId" class="form-select">
                    <option value="">Transport publiczny / brak</option>
                    @foreach($this->availableVehicles as $v)
                        <option value="{{ $v->id }}">{{ $v->registration_number }} – {{ $v->brand }} {{ $v->model }}</option>
                    @endforeach
                </select>
                @if($this->availableVehicles->isEmpty() && $returnDate)
                    <div class="small text-muted mt-1"><i class="bi bi-info-circle me-1"></i>Brak aut spoza bazy na {{ \Carbon\Carbon::parse($returnDate)->format('d.m.Y') }}</div>
                @endif
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Tryb</label>
                <div class="pt-2">
                    @if($this->isPublicTransport)
                        <span class="badge px-3 py-2 rounded-pill" style="background: rgba(59,130,246,0.2); color: #93c5fd; border: 1px solid rgba(59,130,246,0.3);">
                            <i class="bi bi-airplane me-1"></i> Transport publiczny
                        </span>
                    @else
                        <span class="badge px-3 py-2 rounded-pill" style="background: rgba(34,197,94,0.15); color: #86efac; border: 1px solid rgba(34,197,94,0.25);">
                            <i class="bi bi-car-front me-1"></i> Własny pojazd
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Lotniska dla transportu publicznego --}}
        @if($this->isPublicTransport)
            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label class="form-label small text-muted mb-1">Lotnisko startowe (tam) <span class="text-danger">*</span></label>
                    <select wire:model.live="sharedStartAirportLocationId" class="form-select @error('sharedStartAirportLocationId') is-invalid @enderror">
                        <option value="">— wybierz lotnisko —</option>
                        @foreach($this->availableAirports as $airport)
                            <option value="{{ $airport->id }}">{{ $airport->name }}@if($airport->city) – {{ $airport->city }}@endif</option>
                        @endforeach
                    </select>
                    @error('sharedStartAirportLocationId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label small text-muted mb-1">Lotnisko docelowe (z powrotem) <span class="text-danger">*</span></label>
                    <select wire:model.live="sharedEndAirportLocationId" class="form-select">
                        <option value="">— wybierz lotnisko —</option>
                        @foreach($this->availableAirports as $airport)
                            <option value="{{ $airport->id }}">{{ $airport->name }}@if($airport->city) – {{ $airport->city }}@endif</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif
    </div>

    <div class="row g-4">
        {{-- ── SEKCJA 2: Pracownicy ── --}}
        <div class="col-md-5">
            <div class="rtp-section h-100">
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

            {{-- Podgląd trasy (po prepareReturn) --}}
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
                            <div class="small text-muted">Pracownicy</div>
                            <div class="fw-semibold">{{ $previewData['employees_count'] }} os.</div>
                        </div>
                        @if($previewData['vehicle'])
                            <div class="col-12">
                                <div class="small text-muted">Pojazd</div>
                                <div class="fw-semibold">{{ $previewData['vehicle'] }}</div>
                            </div>
                        @endif
                    </div>

                    @if(!empty($previewData['conflicts']))
                        <div class="vstack gap-2">
                            @foreach($previewData['conflicts'] as $conflict)
                                <div class="d-flex align-items-start gap-2 p-2 rounded" style="background: rgba({{ $conflict['is_blocking'] ? '239,68,68' : '251,191,36' }},0.1); border: 1px solid rgba({{ $conflict['is_blocking'] ? '239,68,68' : '251,191,36' }},0.3);">
                                    <i class="bi bi-{{ $conflict['is_blocking'] ? 'x-circle text-danger' : 'exclamation-triangle text-warning' }} flex-shrink-0 mt-1"></i>
                                    <span class="small">{{ $conflict['message'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="d-flex align-items-center gap-2 text-success">
                            <i class="bi bi-check-circle-fill"></i>
                            <span class="small fw-semibold">Brak konfliktów — zjazd można zapisać</span>
                        </div>
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

        <div class="d-flex gap-2">
            @if(!$showPreview)
                <button type="button" class="btn btn-outline-primary"
                        wire:click="prepareReturn" wire:loading.attr="disabled">
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
                            wire:click="saveReturn" wire:loading.attr="disabled">
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
