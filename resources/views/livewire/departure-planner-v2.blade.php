<div data-livewire-preserve-scroll>
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

    @php
        $tripPanelVehicle = $transportMode === 'own' && ! empty($vehicleId)
            ? $this->availableVehicles->firstWhere('id', (int) $vehicleId)
            : null;
    @endphp
    <x-logistics.trip-details-panel
        class="mb-4"
        :trip-logistics-header="[
            'title' => 'Szczegóły wyjazdu',
            'firstWire' => 'departureDate',
            'firstLabel' => 'Data wyjazdu',
            'datesHelp' => 'Wybierz datę wyjazdu i datę zakończenia.',
            'vehiclePoolHint' => 'departure',
        ]"
        :transport-mode="$transportMode"
        :vehicle-id="$vehicleId"
        :selected-vehicle="$tripPanelVehicle"
        :vehicle-seats="$vehicleSeats"
        :employees="$this->selectedEmployees"
        :defer-seat-grid-until-employees="false"
        public-transport-empty-hint="Przypisz pracowników w kroku 1 — wtedy uzupełnisz bilety tutaj (zamiast siatki miejsc)."
        seat-grid-wire-key-prefix="vs"
        :public-tickets-section-title="$this->publicTransportTicketsSectionTitle"
        :ticket-costs-by-employee="$ticketCostsByEmployee"
        :tickets-incomplete="$this->headerTicketsIncomplete"
        ticket-wire-key-prefix="header-ticket"
    />

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
        <x-logistics.route-planning-frame title="Trasa, przystanki i transfer" icon="bi-signpost-split">
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
                :initial-route-segments="$routeSegments"
                :selected-employee-ids="$this->selectedEmployeeIds"
                {{-- Bez hashu segmentów / waypointów: aktualizacja trasy z kroku 4 zmieniałaby key i remountowała komponent — znikałyby m.in. otwarte modale. --}}
                key="step4-{{ $departureDate }}-{{ $transportMode }}-{{ md5(json_encode($accommodationAssignments)) }}-{{ md5(json_encode($assignmentRanges)) }}-{{ md5(json_encode($vehicleAssignments)) }}-{{ $sharedStartAirportLocationId }}-{{ $sharedEndAirportLocationId }}"
            />
        </x-logistics.route-planning-frame>
    @endif
</div>
