{{-- Modal: konfiguracja transferu na lotnisko (własny środek) — wzorowane na modalach kalendarza (step 2) --}}
@if($showPreTransferConfigModal && $isPublicTransport && $transferToAirportLegKind === 'own')
    @php
        $mGround = $preTransferConfigModalGroundMode ?? $transferToAirportGroundMode;
    @endphp
    <div class="modal-portal-to-body" wire:key="step4-pre-transfer-config-modal-root">
        <div class="modal-backdrop fade show"></div>
        <div class="modal fade show employee-assignment-modal" style="display: block;" tabindex="-1" role="dialog" aria-modal="true"
             aria-labelledby="preTransferConfigModalTitle">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content border-secondary" style="background: var(--bg-card, #1e293b); color: #e2e8f0;">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title" id="preTransferConfigModalTitle">
                            <i class="bi bi-sliders text-info me-2"></i>Konfiguruj transfer
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closePreTransferConfigModal" aria-label="Zamknij"></button>
                    </div>
                    <div class="modal-body">
                        @if($mGround === 'other')
                            <p class="small text-muted mb-3">Inny transport: <strong>dworzec startowy → dworzec docelowy</strong> (np. kolej), osobno od trasy samochodem.</p>
                        @else
                            <p class="small text-muted mb-3">Samochód: <strong>baza → przystanki → {{ $startAirportData['name'] ?? 'lotnisko' }}</strong> (ustalasz w „Konfiguruj trasę”).</p>
                        @endif
                        <label class="form-label small text-muted mb-1">Środek na odcinku</label>
                        <div class="d-flex gap-2 align-items-stretch logistics-trip-header-control-row mb-3">
                            <button type="button"
                                    class="btn btn-sm flex-fill d-inline-flex align-items-center justify-content-center logistics-trip-header-control {{ $mGround === 'car' ? 'btn-success' : 'btn-outline-secondary' }}"
                                    style="min-height: 2.125rem;"
                                    wire:click="requestPreTransferModalGroundMode('car')">
                                <i class="bi bi-car-front me-1"></i> Samochód
                            </button>
                            <button type="button"
                                    class="btn btn-sm flex-fill d-inline-flex align-items-center justify-content-center logistics-trip-header-control {{ $mGround === 'other' ? 'btn-primary' : 'btn-outline-secondary' }}"
                                    style="min-height: 2.125rem;"
                                    wire:click="requestPreTransferModalGroundMode('other')">
                                <i class="bi bi-bus-front me-1"></i> Inny transport
                            </button>
                        </div>

                        @if($mGround === 'other')
                            <div class="rounded-2 p-3 mb-3" style="background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.28);">
                                <div class="small fw-semibold mb-2"><i class="bi bi-train-front me-1"></i> Dworce (widoczne w podglądzie karty)</div>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-0">Dworzec startowy <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm @if(trim((string)($preTransferPublicStationStart ?? '')) === '') is-invalid @endif"
                                               wire:model.live="preTransferPublicStationStart" placeholder="np. Gdańsk Główny" maxlength="500" autocomplete="off">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-0">Dworzec docelowy <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm @if(trim((string)($preTransferPublicStationEnd ?? '')) === '') is-invalid @endif"
                                               wire:model.live="preTransferPublicStationEnd" placeholder="np. Lotnisko Gdańsk Rębiechowo" maxlength="500" autocomplete="off">
                                    </div>
                                </div>
                            </div>
                            @if($selectedEmployeesForTickets->isNotEmpty())
                                <x-logistics.public-transport-tickets
                                    variant="cards"
                                    section-title="Bilety (ten odcinek)"
                                    :employees="$selectedEmployeesForTickets"
                                    :ticket-costs-by-employee="$toAirportPublicTicketCostsByEmployee"
                                    :tickets-incomplete="$this->toAirportGroundTicketsIncomplete"
                                    :require-attachment="true"
                                    ticket-costs-binding-key="toAirportPublicTicketCostsByEmployee"
                                    wire:key-prefix="seg-pre-ticket-modal-other"
                                    class="mt-0 pt-0 border-0 mb-0"
                                    style="border-top: none !important;"
                                />
                            @else
                                <p class="small text-muted mb-0">Brak osób w składzie wyjazdu — dodaj uczestników wcześniej.</p>
                            @endif
                        @else
                            @php
                                $preTransferIncomplete = $this->preTransferVehicleIncomplete || $this->preTransferDriverIncomplete || $this->preTransferBonusIncomplete;
                            @endphp
                            <div class="rounded-2 p-3 mb-0" style="background: {{ $preTransferIncomplete ? 'rgba(239,68,68,0.07)' : 'rgba(14,165,233,0.06)' }}; border: 1px solid {{ $preTransferIncomplete ? 'rgba(239,68,68,0.4)' : 'rgba(14,165,233,0.25)' }};">
                                <div class="small fw-semibold mb-2"><i class="bi bi-car-front me-1"></i> Konfiguracja pojazdu (ten odcinek)</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-muted mb-1">Pojazd <span class="text-danger">*</span></label>
                                        <select wire:model="preTransferVehicleId" class="form-select form-select-sm @if($this->preTransferVehicleIncomplete) is-invalid @endif">
                                            <option value="">— wybierz pojazd —</option>
                                            @foreach($availableVehicles as $v)
                                                <option value="{{ $v->id }}">{{ $v->registration_number }} – {{ $v->brand }} {{ $v->model }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-muted mb-1">Kierowca <span class="text-danger">*</span></label>
                                        <select wire:model="preTransferDriverEmployeeId" class="form-select form-select-sm @if($this->preTransferDriverIncomplete) is-invalid @endif">
                                            <option value="">— wybierz kierowcę —</option>
                                            @foreach($availableEmployees as $emp)
                                                <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-muted mb-1">Uznanie <span class="text-danger">*</span></label>
                                        {{-- Bez disabled: Livewire nie synchronizuje wire:model po przełączeniu disabled — pola „martwe” mimo wybranego kierowcy. Walidacja przy Zatwierdź. --}}
                                        <input type="number" step="0.01" min="0" wire:model.live="preTransferDriverBonusAmount"
                                               class="form-control form-control-sm @if($this->preTransferBonusIncomplete) is-invalid @endif"
                                               placeholder="np. 200.00"
                                               inputmode="decimal"
                                               autocomplete="off">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-muted mb-1">Waluta <span class="text-danger">*</span></label>
                                        <select wire:model.live="preTransferDriverBonusCurrency" class="form-select form-select-sm @if($this->preTransferBonusIncomplete) is-invalid @endif">
                                            @foreach($currencyCases as $currency)
                                                <option value="{{ $currency->value }}">{{ $currency->value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <div class="small rounded-2 px-3 py-2"
                                             style="background: rgba(59,130,246,0.08); border: 1px solid rgba(59,130,246,0.2); color: rgba(226,232,240,0.95);">
                                            <i class="bi bi-info-circle me-1 text-info"></i>
                                            Uznanie bez payrollu — przypisz po zapisie w <strong>Uznaniach/obciążeniach</strong>.
                                        </div>
                                    </div>
                                    @if($preTransferVehicleId && $preTransferDriverEmployeeId)
                                        @php $preVehSeat = $availableVehicles->firstWhere('id', $preTransferVehicleId); @endphp
                                        <div class="col-12">
                                            <div class="small fw-semibold text-muted mb-1"><i class="bi bi-grid-3x2-gap me-1"></i> Rozkład miejsc</div>
                                            <x-logistics.vehicle-seat-diagram
                                                :driver-id="$preTransferDriverEmployeeId"
                                                :employees="$selectedEmployeesForTickets"
                                                :driver-pool="$availableEmployees"
                                                :vehicle="$preVehSeat"
                                                accent="info"
                                            />
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer border-secondary">
                        @php
                            $footerPreIncomplete = $this->preTransferVehicleIncomplete || $this->preTransferDriverIncomplete || $this->preTransferBonusIncomplete;
                            $footerStationsIncomplete = $mGround === 'other' && (
                                trim((string)($preTransferPublicStationStart ?? '')) === '' || trim((string)($preTransferPublicStationEnd ?? '')) === ''
                            );
                            $footerOtherBlocked = $mGround === 'other' && ($this->toAirportGroundTicketsIncomplete || $footerStationsIncomplete);
                            $footerCarBlocked = $mGround === 'car' && $footerPreIncomplete;
                            $footerDisabled = $mGround === 'other'
                                ? ($selectedEmployeesForTickets->isEmpty() || $footerOtherBlocked)
                                : $footerCarBlocked;
                        @endphp
                        <button type="button" class="btn btn-outline-light" wire:click="closePreTransferConfigModal">Anuluj</button>
                        <button type="button" class="btn btn-primary" wire:click="confirmPreTransferModal" wire:loading.attr="disabled"
                                @disabled($footerDisabled)>
                            Zatwierdź
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($showPreTransferGroundModeSwitchModal && $pendingPreTransferModalGroundMode)
        @teleport('body')
            <div class="modal fade show d-block departure-planner-teleport-modal" tabindex="-1" role="dialog" aria-modal="true"
                 aria-labelledby="preTransferGroundModeSwitchTitle"
                 style="background-color: rgba(0,0,0,0.55);">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-secondary" style="background: var(--bg-card, #1e293b); color: #e2e8f0;">
                        <div class="modal-header border-secondary">
                            <h5 class="modal-title" id="preTransferGroundModeSwitchTitle">
                                <i class="bi bi-arrow-left-right text-warning me-2"></i>Zmiana środka na odcinku
                            </h5>
                            <button type="button" class="btn-close btn-close-white" wire:click="cancelPreTransferGroundModeSwitch" aria-label="Zamknij"></button>
                        </div>
                        <div class="modal-body">
                            @if($pendingPreTransferModalGroundMode === 'car')
                                <p class="mb-0">Przejście na <strong>samochód</strong> wyzeruje bilety, dworce (opis) oraz dane <strong>innego transportu</strong> w tym oknie. Ustawioną trasę waypointów zaczniesz od domyślnej (baza → lotnisko).</p>
                            @else
                                <p class="mb-0">Przejście na <strong>inny transport</strong> wyzeruje pojazd, kierowcę, uznanie oraz <strong>waypointy mapy</strong> (przystanki autem) dla tego odcinka.</p>
                            @endif
                            <p class="fw-semibold mt-3 mb-0">Kontynuować?</p>
                        </div>
                        <div class="modal-footer border-secondary gap-2">
                            <button type="button" class="btn btn-outline-light" wire:click="cancelPreTransferGroundModeSwitch">Anuluj</button>
                            <button type="button" class="btn btn-primary" wire:click="confirmPreTransferGroundModeSwitch">Kontynuuj</button>
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
@endif
