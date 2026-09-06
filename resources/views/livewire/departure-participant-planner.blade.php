<div data-livewire-preserve-scroll>
    @php
        $forTransfer = $this->isEdit;
        $allowedEmployeeIds = $this->allowedEmployeeIds;
        $alwaysAllowEmployeeIds = $this->allowedEmployeeIds;
        $cancelHref = $this->cancelHref;
        $tripPanelVehicle = $transportMode === 'own' ? $this->selectedVehicle : null;
    @endphp

    @if($errors->any())
        <x-ui.alert variant="danger" title="Nie można zapisać" dismissible class="mb-4">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li class="text-white">{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <x-logistics.trip-details-panel
        class="mb-4"
        :read-only="true"
        :trip-logistics-header="[
            'title' => 'Szczegóły wyjazdu',
            'firstWire' => 'departureDate',
            'firstLabel' => 'Data wyjazdu',
            'readOnlyHelp' => 'Daty i auto bez zmian. Kierowcę możesz zmienić na siatce miejsc poniżej.',
        ]"
        :end-date="$endDate"
        :departure-date="$departureDate"
        :public-transport-hub-kind="$publicTransportHubKind"
        :shared-start-airport-location-id="$sharedStartAirportLocationId"
        :shared-end-airport-location-id="$sharedEndAirportLocationId"
        :available-vehicles="$this->availableVehicles"
        :available-public-transport-hubs="$this->availablePublicTransportHubs"
        :transport-mode="$transportMode"
        :vehicle-id="$vehicleId"
        :selected-vehicle="$tripPanelVehicle"
        :vehicle-seats="$vehicleSeats"
        :employees="$this->seatEmployees"
        :ticket-employees="$this->selectedEmployees"
        :defer-seat-grid-until-employees="false"
        :seats-interactive="$transportMode === 'own'"
        public-transport-empty-hint="Przypisz osobę do projektu w kroku 1 — wtedy uzupełnisz koszt biletu."
        seat-grid-wire-key-prefix="pp-vs"
        public-tickets-section-title="Bilet (kwota i waluta)"
        :ticket-costs-by-employee="$ticketCostsByEmployee"
        :tickets-incomplete="$this->headerTicketsIncomplete"
        :require-attachment-tickets="false"
        ticket-wire-key-prefix="participant-ticket"
    />

    @php
        $participantPlannerTabs = [
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
        ];
    @endphp
    <x-ui.tabs
        :tabs="$participantPlannerTabs"
        :activeTab="$currentStep"
        id="participantPlannerTabs"
    />

    @if($currentStep === 1)
        <livewire:steps.step1-project-assignments
            :departure-date="$departureDate"
            :end-date="$endDate"
            :vehicle-id="$vehicleId"
            :assignments="$assignments"
            :assignment-ranges="$assignmentRanges"
            :vehicle-seats="$vehicleSeats"
            :for-transfer="$forTransfer"
            :allowed-employee-ids="$allowedEmployeeIds"
            :always-allow-employee-ids="$alwaysAllowEmployeeIds"
            :participant-planner-embed="true"
            :cancel-href="$cancelHref"
            key="pp-step1-{{ $departureId }}-{{ $lockEmployeeId }}-{{ $departureDate }}-{{ md5(json_encode($assignmentRanges)) }}"
        />
    @elseif($currentStep === 2)
        <livewire:steps.step2-accommodation-assignments
            :departure-date="$departureDate"
            :end-date="$endDate"
            :assignments="$assignments"
            :assignment-ranges="$assignmentRanges"
            :accommodation-assignments="$accommodationAssignments"
            :for-transfer="$forTransfer"
            :allowed-employee-ids="$allowedEmployeeIds"
            key="pp-step2-{{ $departureId }}-{{ $lockEmployeeId }}-{{ md5(json_encode($assignmentRanges)) }}-{{ md5(json_encode($accommodationAssignments)) }}"
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
            :for-transfer="$forTransfer"
            :allowed-employee-ids="$allowedEmployeeIds"
            :participant-planner-embed="true"
            key="pp-step3-{{ $departureId }}-{{ $lockEmployeeId }}-{{ md5(json_encode($assignmentRanges)) }}-{{ md5(json_encode($vehicleAssignments)) }}"
        />
    @endif
</div>
