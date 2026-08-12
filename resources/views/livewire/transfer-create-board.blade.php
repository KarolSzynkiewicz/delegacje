<div>
    <style>
        .transfer-kanban-card {
            padding: 0.65rem 0.75rem;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }
        .transfer-kanban-card--planned {
            background: linear-gradient(145deg, rgba(251, 191, 36, 0.06) 0%, rgba(15, 23, 42, 0.4) 100%);
            border-color: rgba(251, 191, 36, 0.28) !important;
            box-shadow: inset 0 0 0 1px rgba(251, 191, 36, 0.08) !important;
        }
        .transfer-kanban-card--draft-only {
            box-shadow: 0 0 0 1px rgba(251, 191, 36, 0.35) !important;
            background: rgba(251, 191, 36, 0.04);
        }
        .transfer-kanban-card__top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.35rem;
        }
        .transfer-kanban-card__name {
            font-weight: 600;
            font-size: 0.82rem;
            line-height: 1.25;
            color: var(--text-main, #f1f5f9);
            min-width: 0;
        }
        .transfer-kanban-card__pill {
            flex-shrink: 0;
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 0.2rem 0.45rem;
            border-radius: 6px;
            background: rgba(251, 191, 36, 0.12);
            color: #fcd34d;
            border: 1px solid rgba(251, 191, 36, 0.28);
        }
        .transfer-kanban-card__planned {
            margin-top: 0.35rem;
            padding: 0.5rem 0.55rem;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.22);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        .transfer-kanban-card__planned-title {
            font-size: 0.62rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 0.35rem;
        }
        .transfer-kanban-card__row {
            display: flex;
            align-items: flex-start;
            gap: 0.45rem;
            font-size: 0.78rem;
            line-height: 1.35;
        }
        .transfer-kanban-card__row + .transfer-kanban-card__row {
            margin-top: 0.4rem;
        }
        .transfer-kanban-card__row i {
            flex-shrink: 0;
            margin-top: 0.12rem;
            opacity: 0.85;
        }
        .transfer-kanban-card__role {
            font-weight: 600;
            color: #e2e8f0;
        }
        .transfer-kanban-card__dates {
            font-variant-numeric: tabular-nums;
            color: #cbd5e1;
        }
        .transfer-kanban-card__hint {
            margin-top: 0.45rem;
            padding-top: 0.4rem;
            border-top: 1px dashed rgba(148, 163, 184, 0.25);
            font-size: 0.62rem;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }
        .transfer-kanban-card__hint i {
            color: #94a3b8;
        }
        /* Obecna rola — jeden wiersz z etykietą, bez badge (żeby nie mylić z „bazą danych”) */
        .transfer-kanban-current-role {
            font-size: 0.62rem;
            color: #94a3b8;
            line-height: 1.35;
        }
        .transfer-kanban-current-role strong {
            font-size: 0.68rem;
            font-weight: 600;
            color: #e2e8f0;
        }
        /* Modal wyboru roli: badge „X brak” — bez ostrych żółci */
        .transfer-gap-pill {
            font-size: 0.62rem !important;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            color: #fef3c7 !important;
            background: rgba(217, 119, 6, 0.35) !important;
            border: 1px solid rgba(251, 191, 36, 0.25);
        }
        /* Karta roli wyłączona — czytelny tekst, lekko przyciemnione tło */
        .transfer-role-card--disabled {
            opacity: 1 !important;
            cursor: not-allowed !important;
            background: rgba(15, 23, 42, 0.65) !important;
            border-color: rgba(71, 85, 105, 0.55) !important;
        }
        .transfer-role-card--disabled .transfer-role-card__title {
            color: #cbd5e1 !important;
        }
        .transfer-role-card--disabled .transfer-role-card__note {
            color: #94a3b8 !important;
            font-size: 0.78rem;
            line-height: 1.35;
            margin-top: 0.35rem;
        }
        .transfer-role-card--disabled .transfer-role-card__badge-muted {
            font-size: 0.62rem;
            color: #64748b !important;
            background: rgba(51, 65, 85, 0.6) !important;
            border: 1px solid rgba(100, 116, 139, 0.35);
        }
        .transfer-role-pick:hover {
            border-color: rgba(59, 130, 246, 0.45) !important;
            background: rgba(59, 130, 246, 0.08) !important;
        }
    </style>
    @if(filled($successBanner))
        <div class="alert alert-success py-2 small mb-3">{{ $successBanner }}</div>
    @endif
    @if(session()->has('warning'))
        <div class="alert alert-warning py-2 small mb-3">{{ session('warning') }}</div>
    @endif

    @if($showTransportSwitchModal && $pendingTransportMode)
        @teleport('body')
            <div class="modal fade show d-block departure-planner-teleport-modal" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="boardTransportSwitchModalTitle"
                 style="background-color: rgba(0,0,0,0.55);">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-secondary" style="background: var(--bg-card, #1e293b); color: #e2e8f0;">
                        <div class="modal-header border-secondary">
                            <h5 class="modal-title" id="boardTransportSwitchModalTitle">
                                <i class="bi bi-arrow-left-right text-warning me-2"></i>Zmiana sposobu transportu
                            </h5>
                            <button type="button" class="btn-close btn-close-white" wire:click="cancelTransportModeSwitch" aria-label="Zamknij"></button>
                        </div>
                        <div class="modal-body">
                            @if($pendingTransportMode === 'public')
                                <p class="mb-0">Przejście na transport publiczny wyzeruje wybór pojazdu służbowego.</p>
                            @else
                                <p class="mb-0">Przejście na transport własny wyzeruje wybór lotniska / dworca (start i cel).</p>
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

    @if($wizardPhase !== 'board')
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4 p-3 rounded-3 border" style="border-color: var(--glass-border) !important; background: rgba(0,0,0,0.12);">
            <div class="small">
                <span class="text-muted">Transfer</span>
                <strong class="ms-1">{{ \Carbon\Carbon::parse($departureDate)->format('d.m.Y') }}</strong>
                <span class="text-muted ms-2">
                    @if($wizardPhase === 'followup')
                        Kolejne kroki
                    @elseif($wizardPhase === 'accommodation')
                        Zakwaterowanie
                    @elseif($wizardPhase === 'vehicle')
                        Pojazd
                    @elseif($wizardPhase === 'done')
                        Podsumowanie szkicu
                    @endif
                </span>
            </div>
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary border-secondary"
                style="color: #e2e8f0;"
                wire:click="finishWizardBackToBoard"
                wire:loading.attr="disabled"
                wire:target="finishWizardBackToBoard"
            >
                <i class="bi bi-kanban me-1"></i> Wróć do tablicy
            </button>
        </div>
    @endif

    @if($wizardPhase === 'board')
    @php
        $tripPanelVehicle = $transportMode === 'own' && ! empty($vehicleId)
            ? $this->availableVehicles->firstWhere('id', (int) $vehicleId)
            : null;
        $tripOwnEmptyHint = $mode === 'assignment'
            ? 'Dodaj kogoś do szkicu (przeciągnij), aby zobaczyć siatkę miejsc.'
            : 'Wybierz uczestników poniżej, aby zobaczyć siatkę miejsc.';
        $tripPublicEmptyHint = $mode === 'assignment'
            ? 'Dodaj kogoś do szkicu (przeciągnij), aby wpisać koszty biletów.'
            : 'Wybierz uczestników poniżej, aby wpisać koszty biletów.';
    @endphp
    <x-logistics.trip-details-panel
        class="mb-4"
        :trip-logistics-header="[
            'title' => 'Szczegóły transferu',
            'firstWire' => 'departureDate',
            'firstLabel' => 'Data transferu',
            'datesHelp' => 'Wybierz datę początkową i datę zakończenia.',
            'vehiclePoolHint' => $mode === 'assignment' ? 'transfer_assignment' : 'transfer_transport',
            'enableRelatedDepartureLink' => true,
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
        :employees="$this->effectiveEmployees"
        :defer-seat-grid-until-employees="true"
        :own-transport-empty-hint="$tripOwnEmptyHint"
        :public-transport-empty-hint="$tripPublicEmptyHint"
        seat-grid-wire-key-prefix="transfer-vs"
        :public-tickets-section-title="$this->publicTransportTicketsSectionTitle"
        :ticket-costs-by-employee="$ticketCostsByEmployee"
        :tickets-incomplete="false"
        ticket-wire-key-prefix="transfer-ticket"
        attachment-flat-binding-key="ticketAttachmentUploads"
        :flat-attachment-uploads="$ticketAttachmentUploads"
        :linkable-departures="$this->linkableDepartures"
    />

    @if($transportMode === 'own' && $vehicleId && $this->selectedVehicleActiveEventInfo)
        @php $vehEventInfo = $this->selectedVehicleActiveEventInfo; @endphp
        <div class="alert py-2 px-3 small mb-4 d-flex align-items-start gap-2"
             style="background: rgba(251,191,36,0.08); border: 1px solid rgba(251,191,36,0.35); color: #fcd34d; border-radius: 0.65rem;">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
            <div>
                <strong>Pojazd zajęty w tym czasie:</strong>
                {{ $vehEventInfo['type_label'] }} #{{ $vehEventInfo['event_id'] }}
                ({{ $vehEventInfo['status_label'] }})
                @if($vehEventInfo['from'] || $vehEventInfo['to'])
                    — {{ $vehEventInfo['from'] ?? '?' }} → {{ $vehEventInfo['to'] ?? '?' }}
                @endif
            </div>
        </div>
    @endif

    @if($transportMode === 'own')
        <x-logistics.route-card
            :summary="$this->transferBoardRouteSummary"
            :date-from="$departureDate"
            :date-to="$endDate"
            :selected-employee-ids="$selectedEmployeeIds"
            :ground-transfer-config="$groundTransferConfig"
            :vehicle-id="$vehicleId"
            :vehicle-seats="$vehicleSeats"
            :base-location-id="\App\Models\Location::getBase()->id"
        />
    @endif

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="btn-group" role="group" aria-label="Tryb kreatora">
            <button
                type="button"
                class="btn btn-sm {{ $mode === 'assignment' ? 'btn-primary' : 'btn-outline-secondary' }}"
                wire:click="$set('mode', 'assignment')"
            >
                <i class="bi bi-kanban me-1"></i>
                Przypisania
            </button>
            <button
                type="button"
                class="btn btn-sm {{ $mode === 'transport' ? 'btn-primary' : 'btn-outline-secondary' }}"
                wire:click="$set('mode', 'transport')"
            >
                <i class="bi bi-truck me-1"></i>
                Transport
            </button>
        </div>

        @if($mode === 'assignment' && count($draftProjectByAssignment) > 0)
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="small" style="color: #94a3b8;">
                    Szkic zmian: <strong style="color: #fcd34d;">{{ count($draftProjectByAssignment) }}</strong>
                </span>
                <button type="button" class="btn btn-sm btn-outline-secondary border-secondary" style="color: #e2e8f0;" wire:click="clearDraft">
                    Wyczyść szkic
                </button>
            </div>
        @endif
    </div>

    @if($mode === 'transport')
    <livewire:employee-picker
        :selected-employee-ids="$selectedEmployeeIds"
        :key="'emp-picker-'.$employeePickerKey"
    />

    <div class="d-flex align-items-center gap-3 mb-4">
        <button type="button"
                class="btn btn-primary"
                wire:click="saveSimpleTransfer"
                wire:loading.attr="disabled"
                @disabled($selectedEmployeeIds === [])>
            <span wire:loading.remove wire:target="saveSimpleTransfer">
                <i class="bi bi-floppy me-1"></i> Zapisz transfer
            </span>
            <span wire:loading wire:target="saveSimpleTransfer">
                <span class="spinner-border spinner-border-sm me-1"></span> Zapisuję…
            </span>
        </button>
        @if($selectedEmployeeIds === [])
            <span class="small text-muted">Wybierz uczestników.</span>
        @endif
    </div>
    @else
        <p class="small text-muted mb-3">
            Kolumny = projekty <strong>aktywne</strong> w systemie i <strong>trwające</strong> w pierwszym dniu zakresu (data transferu).
            Puste projekty też są widoczne — możesz na nie przeciągnąć osoby. Szkic doprecyzowujesz w kolejnych krokach; do bazy zapisujesz przyciskiem <strong>Zapisz transfer w systemie</strong> (na dole, gdy masz co najmniej jeden wiersz szkicu).
        </p>

        <div wire:loading.delay class="alert alert-info py-2 small mb-3">
            <i class="bi bi-arrow-repeat"></i> Ładowanie…
        </div>

        @if(count($this->columns) === 0)
            <x-ui.card>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inboxes d-block mb-2" style="font-size: 2rem;"></i>
                    Brak aktywnych projektów obowiązujących w tym dniu — wybierz inną datę lub ustaw zakres dat projektu.
                </div>
            </x-ui.card>
        @else
            <div
                class="transfer-kanban d-flex gap-3 pb-2"
                style="overflow-x: auto; scroll-snap-type: x proximity;"
            >
                @foreach($this->columns as $column)
                    @php
                        $project = $column['project'];
                        $projectId = $project->id;
                    @endphp
                    <div
                        class="flex-shrink-0 rounded-3 border transfer-kanban-column d-flex flex-column"
                        style="width: 280px; min-height: 220px; border-color: var(--glass-border) !important; background: var(--bg-card); scroll-snap-align: start;"
                        x-on:dragover.prevent="$el.classList.add('border-primary')"
                        x-on:dragleave="$el.classList.remove('border-primary')"
                        x-on:drop.prevent="
                            $el.classList.remove('border-primary');
                            const raw = $event.dataTransfer.getData('text/plain');
                            const id = parseInt(raw, 10);
                            if (id) { $wire.startTransferDrop(id, {{ $projectId }}) }
                        "
                    >
                        <div class="px-3 py-2 border-bottom flex-shrink-0" style="border-color: var(--glass-border) !important;">
                            <div class="fw-semibold small text-truncate" title="{{ $project->name }}">
                                {{ $project->name }}
                            </div>
                            @if($project->location)
                                <div class="text-muted" style="font-size: 0.7rem;">
                                    <i class="bi bi-geo-alt"></i> {{ $project->location->name }}
                                </div>
                            @endif
                        </div>
                        <div class="p-2 d-flex flex-column gap-2 flex-grow-1" style="min-height: 140px;">
                            @forelse($column['assignments'] as $assignment)
                                @php
                                    $employee = $assignment->employee;
                                    $isDraft = isset($draftProjectByAssignment[$assignment->id]);
                                    $draftDetails = $draftAssignmentDetails[$assignment->id] ?? null;
                                @endphp
                                <div
                                    draggable="true"
                                    @class([
                                        'rounded-3 border user-select-none transfer-kanban-card',
                                        'transfer-kanban-card--planned' => $draftDetails,
                                        'transfer-kanban-card--draft-only' => $isDraft && ! $draftDetails,
                                    ])
                                    style="cursor: grab; border-color: var(--glass-border) !important;"
                                    x-on:dragstart="
                                        $event.dataTransfer.setData('text/plain', '{{ $assignment->id }}');
                                        $event.dataTransfer.effectAllowed = 'move';
                                    "
                                    x-on:dragend="$el.closest('.transfer-kanban-column')?.classList.remove('border-primary')"
                                >
                                    <div class="transfer-kanban-card__top">
                                        <div class="transfer-kanban-card__name text-truncate" title="{{ $employee?->full_name }}">
                                            {{ $employee?->full_name ?? '?' }}
                                        </div>
                                        @if($draftDetails)
                                            <span class="transfer-kanban-card__pill">Szkic</span>
                                        @elseif($isDraft)
                                            <span class="transfer-kanban-card__pill">Szkic</span>
                                        @endif
                                    </div>

                                    @if($draftDetails)
                                        <div class="transfer-kanban-card__planned">
                                            <div class="transfer-kanban-card__planned-title">Plan po zatwierdzeniu</div>
                                            <div class="transfer-kanban-card__row">
                                                <i class="bi bi-person-badge" style="color: #fcd34d;"></i>
                                                <div>
                                                    <span class="text-muted small d-block" style="font-size: 0.62rem;">Rola</span>
                                                    <span class="transfer-kanban-card__role">{{ $draftDetails['role_name'] }}</span>
                                                </div>
                                            </div>
                                            <div class="transfer-kanban-card__row">
                                                <i class="bi bi-calendar-range text-info"></i>
                                                <div>
                                                    <span class="text-muted small d-block" style="font-size: 0.62rem;">Zakres dat</span>
                                                    <span class="transfer-kanban-card__dates">
                                                        {{ \Carbon\Carbon::parse($draftDetails['start_date'])->format('d.m.Y') }}
                                                        @if($draftDetails['end_date'] !== $draftDetails['start_date'])
                                                            <span class="text-muted"> → </span>{{ \Carbon\Carbon::parse($draftDetails['end_date'])->format('d.m.Y') }}
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                            @php
                                                $planExtras = $this->draftKanbanPlanExtras[$assignment->id] ?? null;
                                            @endphp
                                            @if($planExtras && ! empty($planExtras['project_name']))
                                                <div class="transfer-kanban-card__row">
                                                    <i class="bi bi-kanban" style="color: #a78bfa;"></i>
                                                    <div>
                                                        <span class="text-muted small d-block" style="font-size: 0.62rem;">Docelowy projekt</span>
                                                        <span class="transfer-kanban-card__role">{{ $planExtras['project_name'] }}</span>
                                                    </div>
                                                </div>
                                            @endif
                                            @if($planExtras && ! empty($planExtras['accommodation_name']))
                                                <div class="transfer-kanban-card__row">
                                                    <i class="bi bi-house" style="color: #34d399;"></i>
                                                    <div>
                                                        <span class="text-muted small d-block" style="font-size: 0.62rem;">Mieszkanie po transferze</span>
                                                        <span class="transfer-kanban-card__role">{{ $planExtras['accommodation_name'] }}</span>
                                                    </div>
                                                </div>
                                            @endif
                                            @if($planExtras && ! empty($planExtras['vehicle_label']))
                                                <div class="transfer-kanban-card__row">
                                                    <i class="bi bi-car-front" style="color: #38bdf8;"></i>
                                                    <div>
                                                        <span class="text-muted small d-block" style="font-size: 0.62rem;">Pojazd po transferze</span>
                                                        <span class="transfer-kanban-card__role">{{ $planExtras['vehicle_label'] }}</span>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="transfer-kanban-card__hint">
                                            <i class="bi bi-info-circle"></i>
                                            <span>Jeszcze nie zapisano w systemie — podgląd planowanego przypisania.</span>
                                        </div>
                                    @else
                                        @if($assignment->role)
                                            <div class="mt-1 transfer-kanban-current-role">
                                                Obecna rola: <strong>{{ $assignment->role->name }}</strong>
                                            </div>
                                        @endif
                                        @if($mode === 'assignment')
                                            @php
                                                $liveLog = $this->kanbanLiveLogisticsByAssignmentId[$assignment->id] ?? null;
                                            @endphp
                                            @if($liveLog && (! empty($liveLog['accommodation_name']) || ! empty($liveLog['vehicle_label'])))
                                                <div class="transfer-kanban-card__planned mt-2 pt-2" style="border-top: 1px dashed rgba(148, 163, 184, 0.28);">
                                                    <div class="transfer-kanban-card__planned-title">Aktualnie w ({{ \Carbon\Carbon::parse($transferDate)->format('d.m.Y') }})</div>
                                                    @if(! empty($liveLog['accommodation_name']))
                                                        <div class="transfer-kanban-card__row">
                                                            <i class="bi bi-house" style="color: #34d399;"></i>
                                                            <div>
                                                                <span class="text-muted small d-block" style="font-size: 0.62rem;">Mieszkanie</span>
                                                                <span class="transfer-kanban-card__role">{{ $liveLog['accommodation_name'] }}</span>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    @if(! empty($liveLog['vehicle_label']))
                                                        <div class="transfer-kanban-card__row">
                                                            <i class="bi bi-car-front" style="color: #38bdf8;"></i>
                                                            <div>
                                                                <span class="text-muted small d-block" style="font-size: 0.62rem;">Pojazd</span>
                                                                <span class="transfer-kanban-card__role">{{ $liveLog['vehicle_label'] }}</span>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        @endif
                                        @if($isDraft)
                                            <div class="transfer-kanban-card__hint mb-0 mt-2" style="border-top: none; padding-top: 0;">
                                                <i class="bi bi-pencil-square"></i>
                                                <span>Upuść na projekt i dokończ w oknach, aby ustalić rolę i daty.</span>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            @empty
                                <div
                                    class="rounded-2 flex-grow-1 d-flex align-items-center justify-content-center text-center text-muted small px-2 py-4 border border-secondary border-opacity-25"
                                    style="border-style: dashed !important; min-height: 100px;"
                                >
                                    <span><i class="bi bi-arrow-down-circle d-block mb-1 opacity-50"></i> Upuść osoby tutaj</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
                <span class="small text-muted">
                    {{ count($this->columns) }} projekt(ów) ·
                    {{ collect($this->columns)->sum(fn ($c) => $c['assignments']->count()) }} przypisań
                </span>
                @if(count($draftProjectByAssignment) === 0)
                    <x-ui.button variant="primary" type="button" disabled>Dalej</x-ui.button>
                @else
                    <x-ui.button variant="primary" type="button" wire:click="proceedFromBoard">Dalej</x-ui.button>
                @endif
            </div>
            @if(count($draftProjectByAssignment) > 0)
                <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                    <x-ui.button
                        variant="primary"
                        type="button"
                        wire:click="saveReassignmentTransferToSystem"
                        wire:loading.attr="disabled"
                        wire:target="saveReassignmentTransferToSystem"
                    >
                        <span wire:loading.remove wire:target="saveReassignmentTransferToSystem">
                            <i class="bi bi-floppy me-1"></i> Zapisz transfer w systemie
                        </span>
                        <span wire:loading wire:target="saveReassignmentTransferToSystem">Zapisywanie…</span>
                    </x-ui.button>
                    <span class="small" style="color: #94a3b8;">
                        Zatwierdza zdarzenie i przypisania w bazie, gdy szkic i sekcja „Szczegóły transferu” są kompletne.
                    </span>
                </div>
            @endif
        @endif
    @endif
    @endif

    @if($wizardPhase === 'followup')
        <x-ui.card label="Zakwaterowanie i pojazd po transferze">
            <div class="rounded-3 border px-3 py-2 mb-4 small" style="border-color: rgba(148, 163, 184, 0.35) !important; background: rgba(59, 130, 246, 0.06); color: #cbd5e1;">
                <i class="bi bi-info-circle me-1 text-info"></i>
                <strong>Przypisz nowe:</strong> przy zapisie transferu dotychczasowe przypisania do domu lub auta zostaną <strong>zakończone w dniu transferu</strong>, a w systemie powstaną nowe wpisy z tego szkicu.
                <span class="d-block mt-1"><strong>Nie zmienia się:</strong> przypisania do mieszkania lub pojazdu <strong>nie są przycinane</strong> — zostają bez zmian w systemie (zmienia się wyłącznie projekt wg szkicu z tablicy).</span>
            </div>

            <div class="rounded-3 border p-3 mb-3" style="border-color: var(--glass-border) !important; background: rgba(0,0,0,0.12);">
                <div class="small fw-semibold mb-2" style="color: #f1f5f9;">Dom (mieszkanie)</div>
                <div class="btn-group w-100 flex-wrap" role="group" aria-label="Dom po transferze">
                    <button
                        type="button"
                        class="btn btn-sm flex-grow-1 {{ ! $assignNewAccommodation ? 'btn-primary' : 'btn-outline-secondary border-secondary' }}"
                        style="{{ ! $assignNewAccommodation ? '' : 'color: #e2e8f0;' }}"
                        wire:click="$set('assignNewAccommodation', false)"
                    >
                        Nie zmienia się
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm flex-grow-1 {{ $assignNewAccommodation ? 'btn-primary' : 'btn-outline-secondary border-secondary' }}"
                        style="{{ $assignNewAccommodation ? '' : 'color: #e2e8f0;' }}"
                        wire:click="$set('assignNewAccommodation', true)"
                    >
                        Przypisz nowe
                    </button>
                </div>
            </div>

            <div class="rounded-3 border p-3 mb-4" style="border-color: var(--glass-border) !important; background: rgba(0,0,0,0.12);">
                <div class="small fw-semibold mb-2" style="color: #f1f5f9;">Auta (pojazd)</div>
                <div class="btn-group w-100 flex-wrap" role="group" aria-label="Pojazd po transferze">
                    <button
                        type="button"
                        class="btn btn-sm flex-grow-1 {{ ! $assignNewVehicle ? 'btn-primary' : 'btn-outline-secondary border-secondary' }}"
                        style="{{ ! $assignNewVehicle ? '' : 'color: #e2e8f0;' }}"
                        wire:click="$set('assignNewVehicle', false)"
                    >
                        Nie zmienia się
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm flex-grow-1 {{ $assignNewVehicle ? 'btn-primary' : 'btn-outline-secondary border-secondary' }}"
                        style="{{ $assignNewVehicle ? '' : 'color: #e2e8f0;' }}"
                        wire:click="$set('assignNewVehicle', true)"
                    >
                        Przypisz nowe
                    </button>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary border-secondary" style="color: #e2e8f0;" wire:click="backToBoardFromFollowup">
                    Wróć
                </button>
                <x-ui.button variant="primary" type="button" wire:click="proceedFromFollowup">
                    Dalej
                </x-ui.button>
            </div>
        </x-ui.card>
    @elseif($wizardPhase === 'accommodation')
        <livewire:steps.step2-accommodation-assignments
            :departure-date="$transferDate"
            :end-date="$transferDate"
            :assignments="[]"
            :assignment-ranges="$assignmentRanges"
            :accommodation-assignments="$accommodationAssignments"
            :for-transfer="true"
            :for-transfer-board="true"
            :transfer-wizard-embed="true"
            :allowed-employee-ids="$this->draftEmployeeIds"
            wire:key="transfer-step2-{{ $transferDate }}-{{ md5(json_encode($assignmentRanges)) }}-{{ md5(json_encode($accommodationAssignments)) }}"
        />
    @elseif($wizardPhase === 'vehicle')
        <livewire:steps.step3-vehicle-assignments
            :departure-date="$transferDate"
            :end-date="$transferDate"
            :vehicle-id="null"
            :assignments="[]"
            :assignment-ranges="$assignmentRanges"
            :accommodation-assignments="$accommodationAssignments"
            :vehicle-assignments="$vehicleAssignments"
            :for-transfer="true"
            :for-transfer-board="true"
            :transfer-wizard-embed="true"
            :allowed-employee-ids="$this->draftEmployeeIds"
            wire:key="transfer-step3-{{ $transferDate }}-{{ md5(json_encode($assignmentRanges)) }}-{{ md5(json_encode($vehicleAssignments)) }}-{{ md5(json_encode($accommodationAssignments)) }}"
        />
    @elseif($wizardPhase === 'done')
        <x-ui.card label="Podsumowanie szkicu transferu">
            <p class="small mb-4" style="color: #94a3b8;">
                Data i godzina transferu:
                <strong style="color: #f1f5f9;">{{ \Carbon\Carbon::parse($departureDate)->format('d.m.Y') }}</strong>
            </p>

            <h6 class="text-uppercase small fw-semibold mb-2" style="color: #94a3b8; letter-spacing: 0.04em;">Projekty (z tablicy)</h6>
            @if(count($this->wizardSummarySketchRows) > 0)
                <div class="table-responsive mb-4 rounded-3 border" style="border-color: var(--glass-border) !important;">
                    <table class="table table-sm table-dark mb-0" style="--bs-table-bg: rgba(0,0,0,0.2);">
                        <thead>
                            <tr class="small text-muted">
                                <th>Pracownik</th>
                                <th>Docelowy projekt</th>
                                <th>Rola</th>
                                <th>Zakres</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->wizardSummarySketchRows as $row)
                                <tr>
                                    <td class="fw-semibold" style="color: #e2e8f0;">{{ $row['employee_name'] }}</td>
                                    <td>{{ $row['project_name'] }}</td>
                                    <td>{{ $row['role_name'] }}</td>
                                    <td class="text-nowrap" style="font-variant-numeric: tabular-nums;">{{ $row['date_label'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="small text-warning mb-4">Brak wierszy szkicu — wróć do tablicy.</p>
            @endif

            <h6 class="text-uppercase small fw-semibold mb-2" style="color: #94a3b8; letter-spacing: 0.04em;">Dom po transferze</h6>
            @if($assignNewAccommodation)
                @if(count($this->wizardSummaryAccommodationRows) > 0)
                    <div class="table-responsive mb-4 rounded-3 border" style="border-color: var(--glass-border) !important;">
                        <table class="table table-sm table-dark mb-0" style="--bs-table-bg: rgba(0,0,0,0.2);">
                            <thead>
                                <tr class="small text-muted">
                                    <th>Pracownik</th>
                                    <th>Mieszkanie</th>
                                    <th>Zakres</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->wizardSummaryAccommodationRows as $row)
                                    <tr>
                                        <td class="fw-semibold" style="color: #e2e8f0;">{{ $row['employee_name'] }}</td>
                                        <td>{{ $row['label'] }}</td>
                                        <td class="text-nowrap small">{{ $row['dates'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="small mb-4" style="color: #fbbf24;">Wybrano „Przypisz nowe”, ale brak przypisań w szkicu — użyj kroku mieszkania albo wróć.</p>
                @endif
            @else
                <p class="small mb-4" style="color: #cbd5e1;">
                    <i class="bi bi-house me-1 text-success"></i>
                    <strong>Nie zmienia się</strong> — przy zapisie transferu obecne przypisania mieszkaniowe w systemie <strong>nie będą skracane</strong>.
                </p>
            @endif

            <h6 class="text-uppercase small fw-semibold mb-2" style="color: #94a3b8; letter-spacing: 0.04em;">Pojazd po transferze</h6>
            @if($assignNewVehicle)
                @if(count($this->wizardSummaryVehicleRows) > 0)
                    <div class="table-responsive mb-4 rounded-3 border" style="border-color: var(--glass-border) !important;">
                        <table class="table table-sm table-dark mb-0" style="--bs-table-bg: rgba(0,0,0,0.2);">
                            <thead>
                                <tr class="small text-muted">
                                    <th>Pracownik</th>
                                    <th>Pojazd</th>
                                    <th>Rola</th>
                                    <th>Zakres</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->wizardSummaryVehicleRows as $row)
                                    <tr>
                                        <td class="fw-semibold" style="color: #e2e8f0;">{{ $row['employee_name'] }}</td>
                                        <td>{{ $row['label'] }}</td>
                                        <td>{{ $row['position'] }}</td>
                                        <td class="text-nowrap small">{{ $row['dates'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="small mb-4" style="color: #fbbf24;">Wybrano „Przypisz nowe”, ale brak przypisań w szkicu — użyj kroku pojazdu albo wróć.</p>
                @endif
            @else
                <p class="small mb-4" style="color: #cbd5e1;">
                    <i class="bi bi-car-front me-1 text-success"></i>
                    <strong>Nie zmienia się</strong> — przy zapisie transferu obecne przypisania pojazdu w systemie <strong>nie będą skracane</strong>.
                </p>
            @endif

            <h6 class="text-uppercase small fw-semibold mb-2 mt-2" style="color: #94a3b8; letter-spacing: 0.04em;">Skrócenia i zastąpienia przypisań</h6>
            <p class="small mb-2" style="color: #94a3b8;">
                <strong>Projekt:</strong> dotychczasowe przypisanie kończy się zwykle <strong>dzień przed</strong> datą transferu; nowe zaczyna się w dniu transferu.
                Jeśli pracownik był przypisany do poprzedniego projektu dopiero od dnia transferu, wpis nie da się skrócić — zostanie <strong>usunięty i zastąpiony</strong> nowym (szczegóły w kolumnie „Zmiana”).
                <strong>Mieszkanie / pojazd:</strong> przy „Przypisz nowe” obecne wpisy są skracane tak jak projekt — zwykle <strong>do dnia przed</strong> transferem; nowe zaczynają się w dniu transferu.
            </p>
            @if(count($this->wizardSummaryShortenedRows) > 0)
                <div class="table-responsive mb-4 rounded-3 border" style="border-color: var(--glass-border) !important;">
                    <table class="table table-sm table-dark mb-0" style="--bs-table-bg: rgba(0,0,0,0.2);">
                        <thead>
                            <tr class="small text-muted">
                                <th>Typ</th>
                                <th>Pracownik</th>
                                <th>Obecne</th>
                                <th>Zmiana</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->wizardSummaryShortenedRows as $row)
                                <tr>
                                    <td><span class="badge bg-secondary bg-opacity-25">{{ $row['kind_label'] }}</span></td>
                                    <td class="fw-semibold" style="color: #e2e8f0;">{{ $row['employee_name'] }}</td>
                                    <td>{{ $row['item_label'] }}</td>
                                    <td class="small" style="color: #cbd5e1;">{{ $row['detail'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="small mb-4" style="color: #94a3b8;">Brak wpisów do skrócenia (sprawdź szkic).</p>
            @endif

            <div class="rounded-3 border px-3 py-2 mb-4 small" style="border-color: rgba(148, 163, 184, 0.25) !important; color: #94a3b8;">
                Poniższy przycisk tylko <strong>zamyka podsumowanie</strong> i wraca do tablicy — uaktualnia zakresy przypisań w kreatorze, <strong>nie zapisuje nic w systemie</strong>.
                Zapis w bazie: na tablicy użyj <strong>Zapisz transfer w systemie</strong> (gdy szkic i transport są gotowe), albo wybierz tryb <strong>Transport</strong> i zapis tam.
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <x-ui.button
                    variant="primary"
                    type="button"
                    wire:click="finishWizardBackToBoard"
                    wire:loading.attr="disabled"
                    wire:target="finishWizardBackToBoard"
                >
                    <span wire:loading.remove wire:target="finishWizardBackToBoard">Zamknij podsumowanie i wróć do tablicy</span>
                    <span wire:loading wire:target="finishWizardBackToBoard">Zamykanie…</span>
                </x-ui.button>
            </div>
        </x-ui.card>
    @endif

    {{-- Modal 1: braki w rolach (14 dni od transferu) --}}
    @if($showGapsModal && $gapsModalProject)
        <div class="modal-portal-to-body">
            <div class="modal-backdrop fade show"></div>
            <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
                    <div class="modal-content" style="background: var(--bg-card); border-color: var(--glass-border);">
                        <div class="modal-header border-secondary border-opacity-25">
                            <div>
                                <h5 class="modal-title mb-0" style="color: #f1f5f9;">Jakich ludzi brakuje po przyjeździe?</h5>
                                <small style="color: #94a3b8;">Braki w rolach przez najbliższe 14 dni od daty transferu.</small>
                            </div>
                            <button type="button" class="btn-close btn-close-white" wire:click="closeGapsModal" aria-label="Zamknij"></button>
                        </div>
                        <div class="modal-body">
                            <div class="rounded-3 border p-3 mb-3" style="border-color: var(--glass-border) !important; background: rgba(0,0,0,0.15);">
                                <div class="fw-bold" style="color: #f1f5f9;">{{ $gapsModalProject['name'] }}</div>
                                @if(!empty($gapsModalProject['location']))
                                    <div class="small mt-1" style="color: #94a3b8;"><i class="bi bi-geo-alt"></i> {{ $gapsModalProject['location'] }}</div>
                                @endif
                            </div>
                            <p class="small mb-3" style="color: #cbd5e1;">Wybierz rolę docelową — potwierdzisz zakres dat w kalendarzu.</p>
                            <div class="row g-2">
                                @foreach($gapsModalRoles as $role)
                                    @php $canPick = $this->employeeHasRole($role['id']); @endphp
                                    <div class="col-md-6 col-lg-4">
                                        @if($canPick)
                                            <button
                                                type="button"
                                                class="w-100 text-start rounded-3 border p-3 h-100 transfer-role-pick"
                                                style="background: rgba(255,255,255,0.03); border-color: var(--glass-border) !important; color: #e2e8f0;"
                                                wire:click="selectRoleForTransfer({{ $role['id'] }})"
                                            >
                                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                                    <span class="fw-semibold small">{{ $role['name'] }}</span>
                                                    @if(($role['max_gaps'] ?? 0) > 0 || ($role['min_gaps'] ?? 0) > 0)
                                                        <span class="badge transfer-gap-pill">
                                                            @if(($role['min_gaps'] ?? 0) === ($role['max_gaps'] ?? 0))
                                                                {{ $role['min_gaps'] }} brak.
                                                            @else
                                                                {{ $role['min_gaps'] }}–{{ $role['max_gaps'] }} brak.
                                                            @endif
                                                        </span>
                                                    @else
                                                        <span class="badge transfer-role-card__badge-muted" style="font-size: 0.65rem;">—</span>
                                                    @endif
                                                </div>
                                                <div class="rounded-2 border text-center py-2 small" style="border-style: dashed !important; border-color: rgba(148, 163, 184, 0.35) !important; color: #94a3b8;">
                                                    <i class="bi bi-person"></i> Wybierz rolę
                                                </div>
                                            </button>
                                        @else
                                            <div
                                                class="w-100 rounded-3 border p-3 h-100 transfer-role-card--disabled"
                                                title="Pracownik nie ma tej roli w profilu — nie można wybrać tej roli."
                                            >
                                                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                                    <span class="fw-semibold small transfer-role-card__title">{{ $role['name'] }}</span>
                                                    <span class="badge rounded-pill transfer-role-card__badge-muted">—</span>
                                                </div>
                                                <div class="transfer-role-card__note">
                                                    <i class="bi bi-slash-circle me-1 opacity-75"></i>
                                                    Pracownik nie ma tej roli
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="modal-footer border-secondary border-opacity-25">
                            <button type="button" class="btn btn-secondary" wire:click="closeGapsModal">Anuluj</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal 2: kalendarz zakresu przypisania --}}
    @if($showCalendarModal && $pendingEmployeeId && $pendingTargetProjectId && $selectedRoleId)
        @php
            $emp = \App\Models\Employee::find($pendingEmployeeId);
            $calProject = \App\Models\Project::find($pendingTargetProjectId);
            $calRole = \App\Models\Role::find($selectedRoleId);
            $arrivalCarbon = \Carbon\Carbon::parse($transferDate);
            $calStart = $calendarMonthStart ? \Carbon\Carbon::parse($calendarMonthStart) : $arrivalCarbon->copy()->startOfMonth();
        @endphp
        <div class="modal-portal-to-body">
            <div class="modal-backdrop fade show"></div>
            <div class="modal fade show employee-assignment-modal" style="display: block;" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
                    <div class="modal-content" style="background: var(--bg-card); border-color: var(--glass-border);">
                        <div class="modal-header border-secondary border-opacity-25">
                            <h5 class="modal-title" style="color: #f1f5f9;">
                                Przypisz pracownika: {{ $emp?->full_name ?? '?' }}
                                <br>
                                <small style="color: #94a3b8;">
                                    {{ $calProject?->name }} — {{ $calRole?->name }}
                                </small>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" wire:click="closeCalendarModal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small mb-3">
                                Wybierz zakres dat przypisania (od dnia transferu). Kliknij datę początkową, potem końcową.
                            </p>
                            @if($selectedStartDate)
                                <div class="alert alert-info mb-3 py-2 small">
                                    @if($selectedEndDate && $selectedStartDate !== $selectedEndDate)
                                        <strong>Zakres:</strong>
                                        {{ \Carbon\Carbon::parse($selectedStartDate)->format('d.m.Y') }}
                                        –
                                        {{ \Carbon\Carbon::parse($selectedEndDate)->format('d.m.Y') }}
                                    @else
                                        <strong>Start:</strong> {{ \Carbon\Carbon::parse($selectedStartDate)->format('d.m.Y') }}
                                        — kliknij datę końcową
                                    @endif
                                </div>
                            @endif
                            <x-ui.cal
                                :startDate="$calStart->format('Y-m-d')"
                                :days="0"
                                :availability="$employeeAvailability"
                                :selectedStartDate="$selectedStartDate"
                                :selectedEndDate="$selectedEndDate"
                                onDateClick="selectDate"
                                :showMonthNavigation="true"
                                onPreviousMonth="wire:click=previousMonth"
                                onNextMonth="wire:click=nextMonth"
                                :arrivalDate="$arrivalCarbon->format('Y-m-d')"
                            />
                        </div>
                        <div class="modal-footer flex-column align-items-stretch gap-2 border-secondary border-opacity-25">
                            @error('confirmation')
                                <div class="alert alert-danger mb-0 py-2 w-100 small">{{ $message }}</div>
                            @enderror
                            <div class="d-flex gap-2 justify-content-between w-100 flex-wrap">
                                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="backFromCalendarToGaps">
                                    <i class="bi bi-arrow-left"></i> Wróć do ról
                                </button>
                                <div class="d-flex gap-2 ms-auto">
                                    <button type="button" class="btn btn-secondary" wire:click="closeCalendarModal">Anuluj</button>
                                    <button type="button" class="btn btn-primary" wire:click="confirmTransferAssignment">
                                        Potwierdź
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
