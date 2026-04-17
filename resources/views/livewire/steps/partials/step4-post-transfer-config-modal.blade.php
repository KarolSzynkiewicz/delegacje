{{-- Modal: konfiguracja transferu z lotniska docelowego (własny środek) — jak pre-transfer --}}
@if($showPostTransferConfigModal && $isPublicTransport && $effectiveTransferFromAirportLegKind === 'own')
    @php
        $mGround = $postTransferConfigModalGroundMode ?? $transferFromAirportGroundMode;
    @endphp
    <div class="modal-portal-to-body" wire:key="step4-post-transfer-config-modal-root">
        <div class="modal-backdrop fade show"></div>
        <div class="modal fade show employee-assignment-modal" style="display: block;" tabindex="-1" role="dialog" aria-modal="true"
             aria-labelledby="postTransferConfigModalTitle">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content border-secondary" style="background: var(--bg-card, #1e293b); color: #e2e8f0;">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title" id="postTransferConfigModalTitle">
                            <i class="bi bi-sliders text-success me-2"></i>Konfiguruj transfer
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closePostTransferConfigModal" aria-label="Zamknij"></button>
                    </div>
                    <div class="modal-body">
                        @if($mGround === 'other')
                            <p class="small text-muted mb-3">Inny transport: <strong>bez mapy</strong> — bilety w tym oknie; dystans ustalisz w <strong>Konfiguruj trasę</strong>.</p>
                        @else
                            <p class="small text-muted mb-3">Samochód: <strong>{{ $endAirportData['name'] ?? 'Lotnisko' }} → przystanki → domy</strong> — przystanki i przeliczanie w <strong>Konfiguruj trasę</strong>.</p>
                        @endif
                        <label class="form-label small text-muted mb-1">Środek na odcinku</label>
                        <div class="d-flex gap-2 align-items-stretch logistics-trip-header-control-row mb-3">
                            <button type="button"
                                    class="btn btn-sm flex-fill d-inline-flex align-items-center justify-content-center logistics-trip-header-control {{ $mGround === 'car' ? 'btn-success' : 'btn-outline-secondary' }}"
                                    style="min-height: 2.125rem;"
                                    wire:click="requestPostTransferModalGroundMode('car')">
                                <i class="bi bi-car-front me-1"></i> Samochód
                            </button>
                            <button type="button"
                                    class="btn btn-sm flex-fill d-inline-flex align-items-center justify-content-center logistics-trip-header-control {{ $mGround === 'other' ? 'btn-primary' : 'btn-outline-secondary' }}"
                                    style="min-height: 2.125rem;"
                                    wire:click="requestPostTransferModalGroundMode('other')">
                                <i class="bi bi-bus-front me-1"></i> Inny transport
                            </button>
                        </div>

                        @if($mGround === 'other')
                            @if($selectedEmployeesForTickets->isNotEmpty())
                                <x-logistics.public-transport-tickets
                                    variant="cards"
                                    section-title="Bilety (ten odcinek)"
                                    :employees="$selectedEmployeesForTickets"
                                    :ticket-costs-by-employee="$fromAirportPublicTicketCostsByEmployee"
                                    :flat-attachment-uploads="$fromAirportTicketFiles"
                                    attachment-flat-binding-key="fromAirportTicketFiles"
                                    :tickets-incomplete="$this->fromAirportGroundTicketsIncomplete"
                                    :require-attachment="true"
                                    ticket-costs-binding-key="fromAirportPublicTicketCostsByEmployee"
                                    wire:key-prefix="seg-post-ticket-modal-other"
                                    class="mt-0 pt-0 border-0 mb-0"
                                    style="border-top: none !important;"
                                />
                            @else
                                <p class="small text-muted mb-0">Brak osób w składzie wyjazdu — dodaj uczestników wcześniej.</p>
                            @endif
                        @else
                            @php
                                $postTransferIncomplete = $this->transferVehicleIncomplete || $this->transferDriverIncomplete || $this->transferBonusIncomplete;
                            @endphp
                            <div class="rounded-2 p-3 mb-0" style="background: {{ $postTransferIncomplete ? 'rgba(239,68,68,0.07)' : 'rgba(34,197,94,0.06)' }}; border: 1px solid {{ $postTransferIncomplete ? 'rgba(239,68,68,0.4)' : 'rgba(34,197,94,0.25)' }};">
                                <div class="small fw-semibold mb-2"><i class="bi bi-car-front me-1"></i> Konfiguracja pojazdu (ten odcinek)</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-muted mb-1">Pojazd <span class="text-danger">*</span></label>
                                        <select wire:model="transferVehicleId" class="form-select form-select-sm @if($this->transferVehicleIncomplete) is-invalid @endif">
                                            <option value="">— wybierz pojazd —</option>
                                            @foreach($availableVehicles as $v)
                                                <option value="{{ $v->id }}">{{ $v->registration_number }} – {{ $v->brand }} {{ $v->model }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-muted mb-1">Kierowca <span class="text-danger">*</span></label>
                                        <select wire:model="transferDriverEmployeeId" class="form-select form-select-sm @if($this->transferDriverIncomplete) is-invalid @endif">
                                            <option value="">— wybierz kierowcę —</option>
                                            @foreach($availableEmployees as $emp)
                                                <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-muted mb-1">Uznanie <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0" wire:model.live="transferDriverBonusAmount"
                                               class="form-control form-control-sm @if($this->transferBonusIncomplete) is-invalid @endif"
                                               placeholder="np. 200.00"
                                               inputmode="decimal"
                                               autocomplete="off">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-muted mb-1">Waluta <span class="text-danger">*</span></label>
                                        <select wire:model.live="transferDriverBonusCurrency" class="form-select form-select-sm @if($this->transferBonusIncomplete) is-invalid @endif">
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
                                    @if($transferVehicleId && $transferDriverEmployeeId)
                                        @php $postVehSeatModal = $availableVehicles->firstWhere('id', $transferVehicleId); @endphp
                                        <div class="col-12">
                                            <div class="small fw-semibold text-muted mb-1"><i class="bi bi-grid-3x2-gap me-1"></i> Rozkład miejsc</div>
                                            <x-logistics.vehicle-seat-diagram
                                                :driver-id="$transferDriverEmployeeId"
                                                :employees="$selectedEmployeesForTickets"
                                                :driver-pool="$availableEmployees"
                                                :vehicle="$postVehSeatModal"
                                                accent="success"
                                            />
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer border-secondary">
                        @php
                            $footerPostIncomplete = $this->transferVehicleIncomplete || $this->transferDriverIncomplete || $this->transferBonusIncomplete;
                            $footerOtherBlocked = $mGround === 'other' && ($this->fromAirportGroundTicketsIncomplete || $selectedEmployeesForTickets->isEmpty());
                            $footerCarBlocked = $mGround === 'car' && $footerPostIncomplete;
                            $footerDisabled = $mGround === 'other'
                                ? $footerOtherBlocked
                                : $footerCarBlocked;
                        @endphp
                        <button type="button" class="btn btn-outline-light" wire:click="closePostTransferConfigModal">Anuluj</button>
                        <button type="button" class="btn btn-success" wire:click="confirmPostTransferModal" wire:loading.attr="disabled"
                                @disabled($footerDisabled)>
                            Zatwierdź
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($showPostTransferGroundModeSwitchModal && $pendingPostTransferModalGroundMode)
        @teleport('body')
            <div class="modal fade show d-block departure-planner-teleport-modal" tabindex="-1" role="dialog" aria-modal="true"
                 aria-labelledby="postTransferGroundModeSwitchTitle"
                 style="background-color: rgba(0,0,0,0.55);">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-secondary" style="background: var(--bg-card, #1e293b); color: #e2e8f0;">
                        <div class="modal-header border-secondary">
                            <h5 class="modal-title" id="postTransferGroundModeSwitchTitle">
                                <i class="bi bi-arrow-left-right text-warning me-2"></i>Zmiana środka na odcinku
                            </h5>
                            <button type="button" class="btn-close btn-close-white" wire:click="cancelPostTransferGroundModeSwitch" aria-label="Zamknij"></button>
                        </div>
                        <div class="modal-body">
                            @if($pendingPostTransferModalGroundMode === 'car')
                                <p class="mb-0">Przejście na <strong>samochód</strong> wyzeruje bilety z tego odcinka (inny transport). Uzupełnisz pojazd i kierowcę poniżej.</p>
                            @else
                                <p class="mb-0">Przejście na <strong>inny transport</strong> wyzeruje pojazd, kierowcę i uznanie dla tego odcinka.</p>
                            @endif
                            <p class="fw-semibold mt-3 mb-0">Kontynuować?</p>
                        </div>
                        <div class="modal-footer border-secondary gap-2">
                            <button type="button" class="btn btn-outline-light" wire:click="cancelPostTransferGroundModeSwitch">Anuluj</button>
                            <button type="button" class="btn btn-primary" wire:click="confirmPostTransferGroundModeSwitch">Kontynuuj</button>
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
@endif
