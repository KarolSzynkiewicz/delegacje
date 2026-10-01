<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ ($isReassignmentPlan ?? false) ? 'Plan zmian (transfer)' : (($transportParentContext['title'] ?? null) ?: 'Szczegóły transportu') }}">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('transfers.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                @if(in_array($transfer->status, [\App\Enums\LogisticsEventStatus::PLANNED, \App\Enums\LogisticsEventStatus::COMPLETED]))
                    @if(($isReassignmentPlan ?? false) && ! empty($canMutateParticipants))
                        <x-ui.button
                            variant="ghost"
                            href="{{ route('transfers.create', ['parent' => $transfer->id]) }}"
                            action="create"
                        >
                            Dodaj transport
                        </x-ui.button>
                        <x-ui.button
                            variant="ghost"
                            href="{{ route('transfers.create', ['plan' => $transfer->id]) }}"
                            action="create"
                        >
                            Dopisz uczestnika
                        </x-ui.button>
                    @elseif(! ($isReassignmentPlan ?? false) && ! empty($canMutateParticipants))
                        <x-ui.button
                            variant="ghost"
                            href="{{ route('transfers.participants.create', $transfer) }}"
                            action="create"
                        >
                            Dopisz uczestnika
                        </x-ui.button>
                    @endif
                    <form method="POST" action="{{ route('transfers.cancel', $transfer) }}" class="d-inline">
                        @csrf
                        <x-ui.button
                            variant="danger"
                            type="submit"
                            onclick="return confirm('{{ ($isReassignmentPlan ?? false) ? 'Anulować plan zmian? Przypisania wrócą do stanu sprzed planu, a powiązane transporty zostaną anulowane.' : 'Anulować ten transport?' }}')"
                        >
                            <i class="bi bi-x-circle me-1"></i>
                            {{ ($isReassignmentPlan ?? false) ? 'Anuluj plan' : 'Anuluj transport' }}
                        </x-ui.button>
                    </form>
                @endif
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-alert type="success" dismissible icon="check-circle">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert type="danger" dismissible icon="exclamation-triangle">{{ session('error') }}</x-alert>
    @endif

    @once('transfer-show-route-note-styles')
        <style>
            .transfer-route-stop-note {
                background: linear-gradient(90deg, rgba(99, 102, 241, 0.22) 0%, rgba(30, 41, 59, 0.85) 100%);
                border: 1px solid rgba(165, 180, 252, 0.45);
                border-left: 4px solid #a5b4fc;
                color: #f1f5f9;
                line-height: 1.45;
                box-shadow: 0 1px 0 rgba(255, 255, 255, 0.06) inset;
            }
            .transfer-route-stop-note__label {
                display: block;
                font-size: 0.65rem;
                font-weight: 700;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                color: #c7d2fe;
                margin-bottom: 0.25rem;
            }
            .transfer-route-stop-note__text {
                display: block;
                color: #f8fafc;
                font-weight: 500;
            }
            .transfer-assignment-cell {
                display: block;
                max-width: 24rem;
            }
            .transfer-assignment-cell--changed {
                padding: 0.45rem 0.6rem;
                border-radius: 8px;
                background: rgba(251, 191, 36, 0.07);
                box-shadow: inset 3px 0 0 #fbbf24;
            }
            .transfer-assignment-cell--unchanged {
                padding: 0.45rem 0.6rem;
                border-radius: 8px;
                border: 1px solid rgba(59, 130, 246, 0.4);
                background: rgba(59, 130, 246, 0.07);
            }
            .transfer-change-hotspot {
                position: relative;
                display: inline-flex;
                vertical-align: middle;
                margin-right: 0.25rem;
            }
            .transfer-change-popover {
                position: absolute;
                left: 0;
                top: calc(100% + 0.45rem);
                z-index: 40;
                min-width: 14rem;
                max-width: 20rem;
                padding: 0.7rem 0.85rem;
                border-radius: 10px;
                background: rgba(15, 23, 42, 0.97);
                border: 1px solid rgba(251, 191, 36, 0.35);
                box-shadow: 0 12px 32px rgba(0, 0, 0, 0.45);
            }
            .transfer-change-popover::after {
                content: '';
                position: absolute;
                left: 1rem;
                bottom: 100%;
                border: 6px solid transparent;
                border-bottom-color: rgba(251, 191, 36, 0.35);
            }
            .transfer-show .table-responsive {
                overflow: visible;
            }
            .transfer-show .departure-participants-table {
                overflow: visible;
            }
            .transfer-show .departure-participants-table td {
                overflow: visible;
            }
            .transfer-change-popover__label {
                font-size: 0.62rem;
                font-weight: 700;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                color: #fcd34d;
                margin-bottom: 0.3rem;
            }
            .transfer-change-popover__title {
                display: block;
                font-weight: 600;
                color: #f1f5f9;
                line-height: 1.3;
            }
            .transfer-change-popover__meta {
                display: block;
                font-size: 0.75rem;
                color: #94a3b8;
                margin-top: 0.15rem;
                line-height: 1.35;
            }
            .transfer-change-pill {
                background: rgba(251, 191, 36, 0.2) !important;
                color: #fcd34d !important;
                border: 1px solid rgba(251, 191, 36, 0.35);
                font-size: 0.62rem;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                cursor: pointer;
                vertical-align: middle;
            }
            .transfer-change-pill:hover,
            .transfer-change-pill:focus-visible,
            .transfer-change-hotspot [aria-expanded="true"].transfer-change-pill {
                background: rgba(251, 191, 36, 0.32) !important;
                border-color: rgba(251, 191, 36, 0.55);
            }
            .transfer-keep-pill {
                background: rgba(59, 130, 246, 0.18) !important;
                color: #93c5fd !important;
                border: 1px solid rgba(59, 130, 246, 0.35);
                font-size: 0.62rem;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }
            .transfer-assignment-legend {
                display: inline-flex;
                align-items: center;
                font-size: 0.62rem;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                padding: 0.15rem 0.45rem;
                border-radius: 6px;
            }
            .transfer-assignment-legend--changed {
                background: rgba(251, 191, 36, 0.2);
                color: #fcd34d;
                border: 1px solid rgba(251, 191, 36, 0.35);
            }
            .transfer-assignment-legend--unchanged {
                background: rgba(59, 130, 246, 0.18);
                color: #93c5fd;
                border: 1px solid rgba(59, 130, 246, 0.35);
            }
            .transfer-show .departure-participants-table thead th {
                border-bottom-color: rgba(255, 255, 255, 0.08);
            }
            .transfer-show .departure-participants-table tbody tr:last-child td {
                border-bottom: 0;
            }
        </style>
    @endonce

    @unless($isReassignmentPlan ?? false)
    @php
        $panel = $transportCreatorPanel ?? null;
        $parentCtx = $transportParentContext ?? null;
    @endphp

    @if($parentCtx)
        <div class="mb-4 rounded-3 px-3 py-3 d-flex flex-wrap align-items-center justify-content-between gap-3"
             style="background: rgba(59,130,246,0.10); border: 1px solid rgba(59,130,246,0.35);">
            <div class="min-w-0">
                <div class="small text-uppercase fw-semibold mb-1" style="color: #93c5fd; letter-spacing: .04em;">
                    <i class="bi bi-link-45deg me-1"></i>Doklejony przejazd
                </div>
                <div class="fw-semibold text-white">{{ $parentCtx['title'] }}</div>
                <div class="small text-muted mt-1">
                    {{ $parentCtx['label'] }} · {{ $parentCtx['subtitle'] }}
                </div>
            </div>
            <a href="{{ $parentCtx['url'] }}" class="btn btn-sm btn-outline-info flex-shrink-0">
                <i class="bi bi-box-arrow-up-right me-1"></i>Otwórz rodzica
            </a>
        </div>
    @endif

    @if($panel)
        <x-logistics.trip-details-panel
            class="mb-4"
            :read-only="true"
            :seats-interactive="false"
            :trip-logistics-header="[
                'title' => 'Szczegóły transportu',
                'firstWire' => 'departureDate',
                'firstLabel' => 'Data przejazdu',
                'readOnlyHelp' => 'Podgląd zapisanego transportu — bez edycji.',
            ]"
            :end-date="$panel['endDate']"
            :departure-date="$panel['departureDate']"
            :public-transport-hub-kind="$panel['publicTransportHubKind']"
            :shared-start-airport-location-id="$panel['sharedStartAirportLocationId']"
            :shared-end-airport-location-id="$panel['sharedEndAirportLocationId']"
            :available-vehicles="$panel['availableVehicles']"
            :available-public-transport-hubs="$panel['availablePublicTransportHubs']"
            :transport-mode="$panel['transportMode']"
            :vehicle-id="$panel['vehicleId']"
            :selected-vehicle="$panel['selectedVehicle']"
            :vehicle-seats="$panel['vehicleSeats']"
            :employees="$panel['employees']"
            :defer-seat-grid-until-employees="false"
            seat-grid-wire-key-prefix="transfer-show-vs"
            :public-tickets-section-title="$panel['ticketsSectionTitle']"
            :ticket-costs-by-employee="$panel['ticketCostsByEmployee']"
            :tickets-incomplete="false"
            :require-attachment-tickets="false"
            ticket-wire-key-prefix="transfer-show-ticket"
        />
    @endif

    <!-- Trasa -->
    @php
        $isPublicTransport = ! $transfer->vehicle_id && $transfer->has_transport;
        $canEditRoute = (bool) $transfer->vehicle_id
            && in_array($transfer->status, [\App\Enums\LogisticsEventStatus::PLANNED, \App\Enums\LogisticsEventStatus::COMPLETED], true);
        $routeEstablished = isset($routeStopRows) && $routeStopRows->count() > 0;
    @endphp
    @if(! $isPublicTransport)
    <x-ui.card label="Trasa — przystanki i dystans" class="mb-4">
        @if($canEditRoute && ! $routeEstablished)
            <x-ui.empty-state icon="signpost-split" message="Ustal trasę">
                <p class="small text-muted mt-2 mb-0" style="line-height: 1.55; max-width: 28rem; margin-inline: auto;">
                    Kolejność przystanków, dystans i czas jazdy dopiszesz tutaj — teraz albo później.
                </p>
                <livewire:departure-route-editor
                    :departure="$transfer"
                    trigger-label="Ustal trasę"
                    trigger-variant="primary"
                    :key="'transfer-route-empty-'.$transfer->id"
                />
            </x-ui.empty-state>
        @else
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
            <div class="row g-4 flex-grow-1">
                <div class="col-md-4">
                    <h6 class="text-muted small mb-1">Dystans</h6>
                    <p class="fw-semibold mb-0">{{ $transfer->getFormattedDistance() ?? '—' }}</p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted small mb-1">Czas</h6>
                    <p class="fw-semibold mb-0">{{ $transfer->getFormattedDuration() ?? '—' }}</p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted small mb-1">Przystanki</h6>
                    <p class="fw-semibold mb-0">{{ $routeStopCount ?? ($routeStopRows->count() ?? 0) }}</p>
                </div>
            </div>
            @if($canEditRoute)
                <livewire:departure-route-editor
                    :departure="$transfer"
                    :key="'transfer-route-'.$transfer->id"
                />
            @endif
        </div>

        <div class="mt-3">
            @if($routeEstablished)
                <div class="d-flex flex-column gap-2">
                    @foreach($routeStopRows as $i => $row)
                        @php
                            $isStart = $i === 0;
                            $isEnd = $i === ($routeStopRows->count() - 1);
                            $badge = $isStart ? 'Start' : ($isEnd ? 'Cel' : 'Przystanek');
                            $badgeVariant = $isStart ? 'primary' : ($isEnd ? 'success' : 'accent');
                            $locModel = ($row['kind'] ?? '') === 'extra_location'
                                ? ($routeStopLocationsById[$row['model_id']] ?? null)
                                : null;
                        @endphp
                        <div class="p-3 border rounded-3 d-flex align-items-start gap-2 w-100">
                            <x-ui.badge variant="{{ $badgeVariant }}">{{ $badge }}</x-ui.badge>
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold">{{ $row['name'] ?? '—' }}</div>
                                @if(!empty($row['address_line']))
                                    <div class="text-muted small">{{ $row['address_line'] }}</div>
                                @endif
                                @if(!empty($row['employees_label']))
                                    <div class="text-muted small mt-1">{{ $row['employees_label'] }}</div>
                                @endif
                                @if(!empty($row['purpose']))
                                    <div class="small mt-2 px-3 py-2 rounded-3 transfer-route-stop-note">
                                        <span class="transfer-route-stop-note__label">Notatka</span>
                                        <span class="transfer-route-stop-note__text">{{ $row['purpose'] }}</span>
                                    </div>
                                @endif
                                @if($locModel && !$locModel->hasCoordinates())
                                    <div class="text-warning small mt-1">
                                        <i class="bi bi-exclamation-triangle"></i> brak współrzędnych
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @elseif(! $canEditRoute)
                <x-ui.empty-state icon="map" message="Brak zapisanej trasy (brak przystanków)" />
            @endif
        </div>
        @endif
    </x-ui.card>
    @endif

    @if($isPublicTransport && ! empty($groundLegTicketRows))
        <x-logistics.ground-transfer-tickets :rows="$groundLegTicketRows" />
    @endif
    @endunless

    @if($isReassignmentPlan ?? false)
        @php
            $legs = $linkedTransportLegs ?? collect();
            $canAddTransport = in_array($transfer->status, [\App\Enums\LogisticsEventStatus::PLANNED, \App\Enums\LogisticsEventStatus::COMPLETED], true);
        @endphp
        <x-ui.card label="Transporty" class="mb-4">
            @if($legs->isNotEmpty())
                <div class="d-flex flex-column gap-3 mb-3">
                    @foreach($legs as $leg)
                        @php $driverAdj = $leg->driverAdjustments->first(); @endphp
                        <div class="rounded-3 p-3 border" style="border-color: rgba(255,255,255,0.08) !important; background: rgba(255,255,255,0.03);">
                            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                                <div>
                                    <a href="{{ route('transfers.show', $leg) }}" class="fw-semibold text-decoration-none">
                                        Transport #{{ $leg->id }}
                                    </a>
                                    <span class="text-muted small ms-1">{{ $leg->event_date?->format('d.m.Y H:i') }}</span>
                                    @if($leg->status === \App\Enums\LogisticsEventStatus::CANCELLED)
                                        <x-ui.badge variant="danger" class="ms-1">Anulowany</x-ui.badge>
                                    @endif
                                </div>
                                <a href="{{ route('transfers.show', $leg) }}" class="btn btn-sm btn-outline-light border-opacity-25">
                                    Szczegóły
                                </a>
                            </div>
                            <div class="row g-2 small">
                                <div class="col-12 col-md-4">
                                    <div class="text-muted" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: .04em;">Pojazd</div>
                                    <div class="fw-semibold">
                                        @if($leg->vehicle)
                                            {{ $leg->vehicle->registration_number }} — {{ trim($leg->vehicle->brand.' '.$leg->vehicle->model) }}
                                        @elseif($leg->has_transport)
                                            Transport publiczny
                                        @else
                                            —
                                        @endif
                                    </div>
                                </div>
                                <div class="col-12 col-md-5">
                                    <div class="text-muted" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: .04em;">Trasa</div>
                                    <div class="fw-semibold">
                                        {{ $leg->fromLocation?->name ?? '—' }}
                                        <span class="mx-1 text-muted">→</span>
                                        {{ $leg->toLocation?->name ?? '—' }}
                                    </div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <div class="text-muted" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: .04em;">Kierowca</div>
                                    <div class="fw-semibold">
                                        @if($driverAdj)
                                            {{ $driverAdj->employee?->full_name ?? '—' }}
                                        @else
                                            —
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted small mb-3">Brak doklejonych transportów. Najpierw zapisz plan zmian, potem dodaj kto/czym jedzie.</p>
            @endif
            @if($canAddTransport)
                <a href="{{ route('transfers.create', ['parent' => $transfer->id]) }}" class="btn btn-sm btn-outline-info">
                    <i class="bi bi-plus-lg me-1"></i>Dodaj transport
                </a>
            @endif
        </x-ui.card>
    @endif

    <!-- Uczestnicy / zmiany przypisań -->
    <x-ui.card label="{{ ($isReassignmentPlan ?? false) ? 'Zmiany przypisań' : 'Uczestnicy przejazdu' }}" class="mb-4 transfer-show">
        @if($transfer->has_reassignment)
            <p class="small text-muted mb-3">
                <span class="transfer-assignment-legend transfer-assignment-legend--changed me-2">zmiana</span>
                klik → poprzednie przypisanie (popover)
                <span class="transfer-assignment-legend transfer-assignment-legend--unchanged ms-3 me-2">bez zmian</span>
                dom / auto / projekt pozostawione jak było
            </p>
        @else
            <p class="small text-muted mb-3">
                Lista kto jedzie tym przejazdem. Wypisanie usuwa tylko z transportu — nie zmienia projektu, auta ani zakwaterowania.
            </p>
        @endif

        @if(($participantRows ?? collect())->isNotEmpty())
            <div class="table-responsive rounded-3 border" style="border-color: rgba(255,255,255,0.08) !important;">
                <table class="table table-hover departure-participants-table mb-0 align-middle">
                    <thead class="table-light" style="--bs-table-bg: rgba(255,255,255,0.04);">
                        <tr>
                            <th class="text-uppercase small text-muted fw-semibold py-3 ps-4">Pracownik</th>
                            @if($transfer->has_reassignment)
                                <th class="text-uppercase small text-muted fw-semibold py-3">
                                    <i class="bi bi-briefcase me-1"></i>
                                    Projekt
                                </th>
                                <th class="text-uppercase small text-muted fw-semibold py-3">
                                    <i class="bi bi-truck me-1"></i>
                                    Pojazd
                                </th>
                                <th class="text-uppercase small text-muted fw-semibold py-3">
                                    <i class="bi bi-house me-1"></i>
                                    Zakwaterowanie
                                </th>
                            @endif
                            @if(! empty($canMutateParticipants))
                                <th class="text-uppercase small text-muted fw-semibold py-3 pe-4 text-end">Akcja</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($participantRows as $participant)
                            @php
                                $employeeId = (int) $participant->employee_id;
                                $cells = $assignmentCellsByEmployee[$employeeId] ?? [
                                    'project' => ['state' => 'empty', 'before' => null, 'after' => null, 'current' => null],
                                    'vehicle' => ['state' => 'empty', 'before' => null, 'after' => null, 'current' => null],
                                    'accommodation' => ['state' => 'empty', 'before' => null, 'after' => null, 'current' => null],
                                ];
                            @endphp
                            <tr>
                                <td class="ps-4 py-3{{ (! $transfer->has_reassignment && empty($canMutateParticipants)) ? ' pe-4' : '' }}">
                                    <x-employee-cell :employee="$participant->employee" />
                                </td>
                                @if($transfer->has_reassignment)
                                    <td class="py-3">
                                        @include('transfers.partials.assignment-cell', ['cell' => $cells['project'], 'emptyLabel' => 'Nie przypisany'])
                                    </td>
                                    <td class="py-3">
                                        @include('transfers.partials.assignment-cell', ['cell' => $cells['vehicle'], 'emptyLabel' => '—'])
                                    </td>
                                    <td class="py-3{{ empty($canMutateParticipants) ? ' pe-4' : '' }}">
                                        @include('transfers.partials.assignment-cell', ['cell' => $cells['accommodation'], 'emptyLabel' => '—'])
                                    </td>
                                @endif
                                @if(! empty($canMutateParticipants))
                                    <td class="py-3 pe-4 text-end">
                                        <div class="d-flex flex-wrap justify-content-end gap-2">
                                            @if(! empty($canRemoveParticipants))
                                                <form method="POST"
                                                      action="{{ route('transfers.participants.remove', [$transfer, $participant->employee]) }}"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Wypisać {{ addslashes($participant->employee->full_name ?? 'uczestnika') }} {{ $transfer->has_reassignment ? 'z planu? Przypisania tej osoby zostaną przywrócone do stanu sprzed planu.' : 'z tego przejazdu? Przypisania (projekt / auto / dom) pozostaną bez zmian.' }}');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-opacity-50">
                                                        <i class="bi bi-person-dash me-1"></i>Wypisz
                                                    </button>
                                                </form>
                                            @else
                                                <span class="small text-muted align-self-center" title="{{ $transfer->has_reassignment ? 'Nie można wypisać ostatniego uczestnika — anuluj plan.' : 'Nie można wypisać ostatniego uczestnika — anuluj transport.' }}">
                                                    <i class="bi bi-lock-fill me-1"></i>Ostatni
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted mb-0">{{ $transfer->has_reassignment ? 'Brak uczestników planu' : 'Brak osób w przejeździe' }}</p>
        @endif
    </x-ui.card>

    <!-- Wynagrodzenie kierowcy -->
    @if($transfer->driverAdjustments->count() > 0)
        <x-ui.card label="Wynagrodzenie kierowcy" class="mb-4">
            @foreach($transfer->driverAdjustments as $adj)
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-person-badge fs-4 text-primary"></i>
                        <div>
                            <div class="fw-semibold">{{ $adj->employee?->full_name ?? '—' }}</div>
                            <div class="text-success fw-semibold fs-5">
                                {{ number_format($adj->amount, 2) }} {{ $adj->currency }}
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        @if($adj->payroll_id && $adj->payroll)
                            <div class="small text-muted">Payroll:</div>
                            <a href="{{ route('payrolls.show', $adj->payroll) }}" class="small">
                                {{ $adj->payroll->display_name }}
                            </a>
                        @else
                            <x-ui.badge variant="warning">Bez payrollu</x-ui.badge>
                            <div class="small text-muted mt-1">
                                Przypisz payroll w
                                <a href="{{ route('adjustments.edit', $adj) }}">edycji uznania</a>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </x-ui.card>
    @endif

    @unless($isReassignmentPlan ?? false)
    <div class="mb-4">
        <livewire:logistics-event-warehouse-transfers :event="$transfer" :key="'tr-wh-mm-'.$transfer->id" />
    </div>
    @endunless

    <!-- Meta -->
    <x-ui.card label="Informacje systemowe">
        <div class="row g-3">
            <div class="col-md-4">
                <h6 class="text-muted small mb-1">ID</h6>
                <p class="mb-0 font-monospace">{{ $transfer->id }}</p>
            </div>
            <div class="col-md-4">
                <h6 class="text-muted small mb-1">Utworzony przez</h6>
                <p class="mb-0">{{ $transfer->creator?->name ?? '—' }}</p>
            </div>
            <div class="col-md-4">
                <h6 class="text-muted small mb-1">Data utworzenia</h6>
                <p class="mb-0">{{ $transfer->created_at->format('d.m.Y H:i') }}</p>
            </div>
        </div>
    </x-ui.card>

    <x-comments
        :commentable="$transfer"
        commentable-type="logistics_event"
    />
</x-app-layout>
