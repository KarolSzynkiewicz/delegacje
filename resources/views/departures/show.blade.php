<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Szczegóły Wyjazdu">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('departures.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                @if($departure->status === \App\Enums\LogisticsEventStatus::PLANNED || $departure->status === \App\Enums\LogisticsEventStatus::COMPLETED)
                    <x-ui.button 
                        variant="danger" 
                        href="{{ route('departures.prepare-cancellation', $departure) }}"
                        action="cancel"
                    >
                        Anuluj Wyjazd
                    </x-ui.button>
                @endif
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif
    @if(session('error'))
        <x-alert type="danger" dismissible icon="exclamation-triangle">
            {{ session('error') }}
        </x-alert>
    @endif

    <div class="departure-show d-flex flex-column gap-4">

    <x-ui.card label="Informacje podstawowe" class="mb-0">
        <div class="row g-4 gy-4 departure-info-grid">
            <div class="col-md-6">
                <h6 class="text-muted small mb-2 d-flex align-items-center gap-1">
                    Data wyjazdu
                    <x-tooltip title="Data i godzina, kiedy pracownicy wyjeżdżają z lokalizacji początkowej. Od tej daty pojazd jest zarezerwowany i niedostępny do innych przypisań.">
                        <i class="bi bi-calendar-check text-info fs-6"></i>
                    </x-tooltip>
                </h6>
                <p class="fw-semibold mb-0 fs-6">{{ $departure->event_date->format('Y-m-d H:i') }}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted small mb-2 d-flex align-items-center gap-1">
                    Data przybycia
                    <x-tooltip title="Data i godzina, kiedy pracownicy docierają do lokalizacji docelowej. Do tej daty pojazd pozostaje zarezerwowany. Musi być późniejsza niż data wyjazdu.">
                        <i class="bi bi-calendar-event text-success fs-6"></i>
                    </x-tooltip>
                </h6>
                <p class="fw-semibold mb-0 fs-6">
                    {{ $departure->end_date?->format('Y-m-d H:i') ?? 'Brak daty przybycia' }}
                    @if($departure->end_date && $departure->getDurationInDays() > 0)
                        <small class="text-muted">({{ $departure->getDurationInDays() }} dni)</small>
                    @endif
                </p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted small mb-2 d-flex align-items-center gap-1">
                    Status wyjazdu
                    <x-tooltip title="Status wyjazdu obliczany jest automatycznie na podstawie dat. 'Oczekuje' = wyjazd w przyszłości. 'W trakcie' = trwa teraz (między datą wyjazdu a datą przybycia). 'Zakończone' = wyjazd się już odbył.">
                        <i class="bi bi-calendar-event text-info fs-6"></i>
                    </x-tooltip>
                </h6>
                @php
                    $visualStatus = $departure->getVisualStatus();
                    $statusPillClass = match ($visualStatus) {
                        'oczekuje' => 'departure-status-pill departure-status-pill--pending',
                        'w trakcie' => 'departure-status-pill departure-status-pill--active',
                        'zakończone' => 'departure-status-pill departure-status-pill--done',
                        'anulowany' => 'departure-status-pill departure-status-pill--cancelled',
                        default => 'departure-status-pill departure-status-pill--pending',
                    };
                @endphp
                <span class="{{ $statusPillClass }}">{{ $visualStatus }}</span>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted small mb-2 d-flex align-items-center gap-1">
                    Typ transportu
                    <x-tooltip title="Transport publiczny: lot i ewentualnie transfer z lotniska. Transport własny: przejazd pojazdem firmowym — szczegóły pojazdu w tabeli uczestników.">
                        <i class="bi bi-signpost-split text-warning fs-6"></i>
                    </x-tooltip>
                </h6>
                @if($departure->vehicle_id)
                    <p class="fw-semibold mb-0 fs-6">Transport własny</p>
                    @if($departure->vehicle)
                        <p class="small text-muted mb-0 mt-1">{{ $departure->vehicle->registration_number }} — {{ $departure->vehicle->brand }} {{ $departure->vehicle->model }}</p>
                    @endif
                @else
                    <p class="fw-semibold mb-0 fs-6">Transport publiczny</p>
                @endif
            </div>
            @if($departure->notes)
            <div class="col-12 mt-2">
                <h6 class="text-muted small mb-2 d-flex align-items-center gap-1">
                    Notatki
                    <x-tooltip title="Dodatkowe informacje o wyjeździe: szczegóły dotyczące trasy, miejsca spotkania, wymagań specjalnych, lub innych istotnych uwag logistycznych.">
                        <i class="bi bi-sticky text-warning fs-6"></i>
                    </x-tooltip>
                </h6>
                <p class="mb-0 text-body-secondary" style="line-height: 1.55;">{{ $departure->notes }}</p>
            </div>
            @endif
        </div>
    </x-ui.card>

    <x-ui.card label="Uczestnicy" class="mb-0">
            @if($departure->participants->count() > 0)
                <div class="table-responsive rounded-3 border" style="border-color: rgba(255,255,255,0.08) !important;">
                    <table class="table table-hover departure-participants-table mb-0 align-middle">
                        <thead class="table-light" style="--bs-table-bg: rgba(255,255,255,0.04);">
                            <tr>
                                <th class="text-uppercase small text-muted fw-semibold py-3 ps-4">Pracownik</th>
                                <th class="text-uppercase small text-muted fw-semibold py-3">
                                    <i class="bi bi-briefcase me-1"></i>
                                    Projekt
                                </th>
                                <th class="text-uppercase small text-muted fw-semibold py-3">
                                    <i class="bi bi-truck me-1"></i>
                                    Pojazd
                                </th>
                                <th class="text-uppercase small text-muted fw-semibold py-3 pe-4">
                                    <i class="bi bi-house me-1"></i>
                                    Zakwaterowanie
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($departure->participants as $participant)
                                @php
                                    $projectAssignment = $departure->projectAssignments
                                        ->where('employee_id', $participant->employee_id)
                                        ->first();
                                    
                                    $vehicleAssignment = $departure->vehicleAssignments
                                        ->where('employee_id', $participant->employee_id)
                                        ->first();
                                    
                                    $accommodationAssignment = $departure->accommodationAssignments
                                        ->where('employee_id', $participant->employee_id)
                                        ->first();
                                @endphp
                                <tr>
                                    <td class="ps-4 py-3">
                                        <x-employee-cell :employee="$participant->employee" />
                                    </td>
                                    <td class="py-3">
                                        @if($projectAssignment)
                                            <div>
                                                <span class="badge bg-success me-1">✓</span>
                                                <a href="{{ route('project-assignments.show', $projectAssignment) }}" class="text-decoration-none">
                                                    {{ $projectAssignment->project->name }}
                                                </a>
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                {{ $projectAssignment->start_date->format('d.m.Y') }} - {{ $projectAssignment->end_date ? $projectAssignment->end_date->format('d.m.Y') : 'brak daty' }}
                                            </small>
                                        @else
                                            @if($departure->status === \App\Enums\LogisticsEventStatus::CANCELLED)
                                                <span class="text-muted small">
                                                    <i class="bi bi-info-circle"></i> Powiązanie usunięto w momencie anulowania wyjazdu
                                                </span>
                                            @else
                                                <span class="text-muted">
                                                    <i class="bi bi-dash-circle"></i> Nie przypisany
                                                </span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="py-3">
                                        @if($vehicleAssignment)
                                            <div>
                                                <span class="badge bg-success me-1">✓</span>
                                                <a href="{{ route('vehicle-assignments.show', $vehicleAssignment) }}" class="text-decoration-none">
                                                    {{ $vehicleAssignment->vehicle->registration_number }}
                                                </a>
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                {{ $vehicleAssignment->start_date->format('d.m.Y') }} - {{ $vehicleAssignment->end_date ? $vehicleAssignment->end_date->format('d.m.Y') : 'brak daty' }}
                                            </small>
                                        @else
                                            @if($departure->status === \App\Enums\LogisticsEventStatus::CANCELLED)
                                                <span class="text-muted small">
                                                    <i class="bi bi-info-circle"></i> Powiązanie usunięto w momencie anulowania wyjazdu
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="py-3 pe-4">
                                        @if($accommodationAssignment)
                                            <div>
                                                <span class="badge bg-success me-1">✓</span>
                                                <a href="{{ route('accommodation-assignments.show', $accommodationAssignment) }}" class="text-decoration-none">
                                                    {{ $accommodationAssignment->accommodation->name }}
                                                </a>
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                {{ $accommodationAssignment->start_date->format('d.m.Y') }} - {{ $accommodationAssignment->end_date ? $accommodationAssignment->end_date->format('d.m.Y') : 'brak daty' }}
                                            </small>
                                        @else
                                            @if($departure->status === \App\Enums\LogisticsEventStatus::CANCELLED)
                                                <span class="text-muted small">
                                                    <i class="bi bi-info-circle"></i> Powiązanie usunięto w momencie anulowania wyjazdu
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted mb-0">Brak uczestników</p>
            @endif
    </x-ui.card>

    @php
        $ticketCosts = $departure->transportCosts->where('cost_type', 'ticket')->values();
        $isPublicTransportDeparture = !$departure->vehicle_id;
    @endphp

    @if($isPublicTransportDeparture)
        <x-ui.card label="Loty / bilety" class="mb-0">
            @if($ticketCosts->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Opis</th>
                                <th>Kwota</th>
                                <th>Data</th>
                                <th>Notatka</th>
                                <th>Załącznik</th>
                                <th>Szczegóły</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ticketCosts as $ticketCost)
                                <tr>
                                    <td>{{ $ticketCost->description ?: 'Bilet' }}</td>
                                    <td>{{ number_format((float) $ticketCost->amount, 2) }} {{ $ticketCost->currency }}</td>
                                    <td>{{ $ticketCost->cost_date?->format('Y-m-d') ?? '-' }}</td>
                                    <td>{{ $ticketCost->notes ?: '—' }}</td>
                                    <td>
                                        @if($ticketCost->file_path)
                                            <a href="{{ asset('storage/' . $ticketCost->file_path) }}" target="_blank" class="text-decoration-none">
                                                <i class="bi bi-paperclip"></i> Podgląd
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('transport-costs.show', $ticketCost) }}" class="text-decoration-none">
                                            Zobacz
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted mb-0">Brak kosztów biletów dla tego wyjazdu.</p>
            @endif
        </x-ui.card>
    @endif

    @if($isPublicTransportDeparture)
        <x-ui.card label="Transfer" class="mb-0">
            @if(!empty($transfer))
                <div class="transfer-summary-grid mb-3">
                    <div class="row g-3 g-md-4">
                        <div class="col-12 col-md-4">
                            <h6 class="text-muted small mb-1 text-uppercase" style="font-size: 0.68rem; letter-spacing: .04em;">Zdarzenie</h6>
                            <p class="fw-semibold mb-0">
                                <a href="{{ route('transfers.show', $transfer) }}" class="text-decoration-none">
                                    Transfer #{{ $transfer->id }}
                                </a>
                            </p>
                        </div>
                        <div class="col-6 col-md-4">
                            <h6 class="text-muted small mb-1 text-uppercase" style="font-size: 0.68rem; letter-spacing: .04em;">Dystans</h6>
                            <p class="fw-semibold mb-0">{{ $transfer->getFormattedDistance() ?? '—' }}</p>
                        </div>
                        <div class="col-6 col-md-4">
                            <h6 class="text-muted small mb-1 text-uppercase" style="font-size: 0.68rem; letter-spacing: .04em;">Czas</h6>
                            <p class="fw-semibold mb-0">{{ $transfer->getFormattedDuration() ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="row g-3 g-md-4 mt-1 mt-md-2 pt-3 border-top" style="border-color: rgba(255,255,255,0.08) !important;">
                        <div class="col-12 col-md-6">
                            <h6 class="text-muted small mb-1 text-uppercase" style="font-size: 0.68rem; letter-spacing: .04em;">Pojazd transferu</h6>
                            <p class="fw-semibold mb-0">
                                {{ $transfer->vehicle ? ($transfer->vehicle->registration_number.' — '.$transfer->vehicle->brand.' '.$transfer->vehicle->model) : '—' }}
                            </p>
                        </div>
                        <div class="col-12 col-md-6">
                            <h6 class="text-muted small mb-1 text-uppercase" style="font-size: 0.68rem; letter-spacing: .04em;">Kierowca / uznanie</h6>
                            @if($transfer->driverAdjustments->isNotEmpty())
                                @php $adj = $transfer->driverAdjustments->first(); @endphp
                                <p class="fw-semibold mb-0">
                                    {{ $adj->employee?->full_name ?? '—' }}
                                    <span class="text-muted">·</span>
                                    {{ number_format((float) $adj->amount, 2) }} {{ $adj->currency }}
                                </p>
                                @if(!$adj->payroll_id)
                                    <div class="small text-muted mt-1">Bez payrollu</div>
                                @endif
                            @else
                                <p class="text-muted mb-0">—</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="border-top pt-3">
                    <h6 class="fw-semibold mb-3"><i class="bi bi-map me-1"></i>Trasa transferu</h6>

                    @if(!empty($transferTicketFlightLine))
                        <div class="mb-3 p-2 px-3 rounded-2 small" style="background: rgba(59,130,246,0.08); border: 1px solid rgba(59,130,246,0.2);">
                            <span class="text-muted">Lot pasażerski:</span>
                            <span class="fw-semibold ms-1"><i class="bi bi-airplane text-primary me-1"></i>{{ $transferTicketFlightLine }}</span>
                        </div>
                    @endif

                    @php
                        $stepNum = 1;
                        $arrivalAirportTitle = null;
                        if (!empty($transferTicketFlightLine) && preg_match('/→\s*(.+)$/u', $transferTicketFlightLine, $__m)) {
                            $arrivalAirportTitle = trim($__m[1]);
                        }
                    @endphp

                    <div class="vstack gap-3 mb-0">
                        @if($transfer->fromLocation)
                            <div class="p-3 rounded-3 border" style="border-color: rgba(14,165,233,0.35) !important; background: rgba(14,165,233,0.06);">
                                <div class="d-flex align-items-baseline gap-2 flex-wrap mb-1">
                                    <span class="badge bg-primary" style="font-size: 0.75rem;">{{ $stepNum }}</span>
                                    <span class="fw-semibold">Skąd rusza</span>
                                </div>
                                <div class="fw-semibold">{{ $transfer->fromLocation->name }}</div>
                                @if($transfer->fromLocation->address || $transfer->fromLocation->city)
                                    <div class="small text-muted mt-1" style="line-height: 1.45;">
                                        {{ $transfer->fromLocation->address }}@if($transfer->fromLocation->city), {{ $transfer->fromLocation->city }}@endif
                                    </div>
                                @endif
                            </div>
                            @php $stepNum++; @endphp

                            <div class="p-3 rounded-3 border" style="border-color: rgba(59,130,246,0.35) !important; background: rgba(59,130,246,0.06);">
                                <div class="d-flex align-items-baseline gap-2 flex-wrap mb-1">
                                    <span class="badge bg-primary" style="font-size: 0.75rem;">{{ $stepNum }}</span>
                                    <span class="fw-semibold">Lotnisko</span>
                                </div>
                                <div class="small text-muted mb-2">Odbiór po przylocie</div>
                                @if($transferEndAirportLocation)
                                    <div class="fw-semibold">{{ $transferEndAirportLocation->name }}</div>
                                    @if($transferEndAirportLocation->address || $transferEndAirportLocation->city)
                                        <div class="small text-muted mt-1" style="line-height: 1.45;">
                                            {{ $transferEndAirportLocation->address }}@if($transferEndAirportLocation->city), {{ $transferEndAirportLocation->city }}@endif
                                        </div>
                                    @endif
                                @else
                                    <div class="fw-semibold">{{ $arrivalAirportTitle ?? 'Lotnisko docelowe' }}</div>
                                @endif
                            </div>
                            @php $stepNum++; @endphp
                        @endif

                        @forelse($transferDrivingStops as $row)
                            @if($row['kind'] === 'extra_location')
                                <div class="p-3 rounded-3 border" style="border-color: rgba(251,191,36,0.45) !important; background: rgba(251,191,36,0.05);">
                                    <div class="d-flex align-items-baseline gap-2 flex-wrap mb-1">
                                        <span class="badge bg-secondary" style="font-size: 0.75rem;">{{ $stepNum }}</span>
                                        <span class="badge rounded-pill border fw-normal"
                                              style="font-size: 0.7rem; background: rgba(251,191,36,0.1); color: #fcd34d; border-color: rgba(251,191,36,0.35) !important;">Przystanek dodatkowy</span>
                                    </div>
                                    <div class="fw-semibold">{{ $row['name'] }}</div>
                                    @if(!empty($row['address_line']))
                                        <div class="small text-muted">{{ $row['address_line'] }}</div>
                                    @endif
                                    @if(!empty($row['purpose']))
                                        <div class="mt-2 pt-2 border-top border-secondary border-opacity-25">
                                            <div class="small text-muted mb-1">Po co tu jedziemy?</div>
                                            <div class="small" style="white-space: pre-wrap;">{{ $row['purpose'] }}</div>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="p-3 rounded-3 border" style="border-color: rgba(34,197,94,0.35) !important; background: rgba(34,197,94,0.04);">
                                    <div class="d-flex align-items-baseline gap-2 flex-wrap mb-1">
                                        <span class="badge bg-secondary" style="font-size: 0.75rem;">{{ $stepNum }}</span>
                                        <span class="badge rounded-pill border fw-normal"
                                              style="font-size: 0.7rem; background: rgba(34,197,94,0.1); color: #86efac; border-color: rgba(34,197,94,0.3) !important;">Mieszkanie / dom</span>
                                    </div>
                                    <div class="fw-semibold">{{ $row['name'] }}</div>
                                    @if(!empty($row['address_line']))
                                        <div class="small text-muted">{{ $row['address_line'] }}</div>
                                    @endif
                                    @if(!empty($row['employees_label']))
                                        <div class="small mt-2" style="color:#94a3b8;">
                                            <span class="text-muted">Wysiadka dla:</span> {{ $row['employees_label'] }}
                                        </div>
                                    @endif
                                </div>
                            @endif
                            @php $stepNum++; @endphp
                        @empty
                            @if($stepNum < 2)
                                <x-ui.empty-state icon="map" message="Brak zapisanej kolejności przystanków w wyjeździe (krok 4)." />
                            @endif
                        @endforelse
                    </div>
                </div>
            @else
                <p class="text-muted mb-0">Brak powiązanego transferu (dla wyjazdów transportem publicznym transfer tworzy się automatycznie w kroku 4).</p>
            @endif
        </x-ui.card>
    @endif

    @if(!$isPublicTransportDeparture)
        <x-ui.card label="Bilety" class="mb-0">
        @php
            $ticketCosts = $departure->transportCosts->where('cost_type', 'ticket')->values();
        @endphp

        @if($ticketCosts->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Opis</th>
                            <th>Kwota</th>
                            <th>Data</th>
                            <th>Notatka</th>
                            <th>Załącznik</th>
                            <th>Szczegóły</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ticketCosts as $ticketCost)
                            <tr>
                                <td>{{ $ticketCost->description ?: 'Bilet' }}</td>
                                <td>{{ number_format((float) $ticketCost->amount, 2) }} {{ $ticketCost->currency }}</td>
                                <td>{{ $ticketCost->cost_date?->format('Y-m-d') ?? '-' }}</td>
                                <td>{{ $ticketCost->notes ?: '—' }}</td>
                                <td>
                                    @if($ticketCost->file_path)
                                        <a href="{{ asset('storage/' . $ticketCost->file_path) }}" target="_blank" class="text-decoration-none">
                                            <i class="bi bi-paperclip"></i> Podgląd
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('transport-costs.show', $ticketCost) }}" class="text-decoration-none">
                                        Zobacz
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted mb-0">Brak kosztów biletów dla tego wyjazdu.</p>
        @endif
    </x-ui.card>
    @endif

    @if(!$isPublicTransportDeparture)
        @php
            $routeStops = $departure->getRouteStopsForDetailView();
        @endphp
        <x-ui.card label="Przebieg trasy" class="mb-0">
            <p class="small text-muted mb-4" style="line-height: 1.55;">
                Kolejność z planu wyjazdu: start z bazy, przystanki po drodze (mieszkania i ewentualne lokalizacje dodane ręcznie), na końcu adres docelowy zapisany przy wyjeździe.
            </p>

            @if($departure->hasRouteData())
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 h-100" style="background: rgba(59,130,246,0.08); border: 1px solid rgba(59,130,246,0.22);">
                            <h6 class="text-muted small mb-2 d-flex align-items-center gap-1">
                                Szacowany dystans
                                <x-tooltip title="Całkowity szacowany dystans od bazy przez przystanki do miejsca docelowego.">
                                    <i class="bi bi-speedometer2 text-info fs-6"></i>
                                </x-tooltip>
                            </h6>
                            <p class="fw-semibold fs-5 mb-0">{{ $departure->getFormattedDistance() }}</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 h-100" style="background: rgba(59,130,246,0.08); border: 1px solid rgba(59,130,246,0.22);">
                            <h6 class="text-muted small mb-2 d-flex align-items-center gap-1">
                                Szacowany czas jazdy
                                <x-tooltip title="Szacowany czas w drodze (bez postojów).">
                                    <i class="bi bi-clock text-info fs-6"></i>
                                </x-tooltip>
                            </h6>
                            <p class="fw-semibold fs-5 mb-0">{{ $departure->getFormattedDuration() }}</p>
                        </div>
                    </div>
                </div>
            @elseif(! $routeStops->isEmpty())
                <div class="alert alert-info py-3 small mb-4">
                    <i class="bi bi-info-circle me-1"></i>
                    Brak zapisanych metryk trasy (dystans / czas). Przystanki poniżej pochodzą z kolejności wybranej przy tworzeniu wyjazdu.
                </div>
            @endif

            <div class="vstack gap-3">
                <div class="d-flex gap-3 p-3 rounded-3 border" style="border-color: rgba(99,102,241,0.35) !important; background: rgba(99,102,241,0.06);">
                    <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold text-white flex-shrink-0"
                         style="width: 40px; height: 40px; font-size: 0.95rem; background: #6366f1;">
                        A
                    </div>
                    <div class="min-w-0 flex-grow-1">
                        <div class="small text-muted mb-1">Start</div>
                        <div class="fw-semibold">{{ $departure->fromLocation->name }}</div>
                        @if($departure->fromLocation->address)
                            <div class="small text-muted mt-1" style="line-height: 1.45;">{{ $departure->fromLocation->address }}@if($departure->fromLocation->city), {{ $departure->fromLocation->city }}@endif</div>
                        @endif
                    </div>
                </div>

                @foreach($routeStops as $stop)
                    <div class="d-flex gap-3 p-3 rounded-3 border"
                         style="border-color: {{ $stop['kind'] === 'extra_location' ? 'rgba(251,191,36,0.35)' : 'rgba(34,197,94,0.35)' }} !important; background: {{ $stop['kind'] === 'extra_location' ? 'rgba(251,191,36,0.05)' : 'rgba(34,197,94,0.05)' }};">
                        <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold flex-shrink-0"
                             style="width: 40px; height: 40px; font-size: 0.95rem;
                                    background: {{ $stop['kind'] === 'extra_location' ? 'rgba(251,191,36,0.2)' : 'rgba(34,197,94,0.25)' }};
                                    color: {{ $stop['kind'] === 'extra_location' ? '#fde68a' : '#bbf7d0' }};
                                    border: 1px solid {{ $stop['kind'] === 'extra_location' ? 'rgba(251,191,36,0.45)' : 'rgba(34,197,94,0.45)' }};">
                            {{ $stop['position'] }}
                        </div>
                        <div class="min-w-0 flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                @if($stop['kind'] === 'extra_location')
                                    <span class="badge rounded-pill border fw-normal"
                                          style="font-size: 0.68rem; background: rgba(251,191,36,0.08); color: #fcd34d; border-color: rgba(251,191,36,0.35) !important;">Przystanek dodatkowy</span>
                                @else
                                    <span class="badge rounded-pill border fw-normal"
                                          style="font-size: 0.68rem; background: rgba(34,197,94,0.1); color: #86efac; border-color: rgba(34,197,94,0.35) !important;">Mieszkanie</span>
                                @endif
                            </div>
                            <div class="fw-semibold">
                                @if($stop['kind'] === 'accommodation')
                                    <a href="{{ route('accommodations.show', $stop['model_id']) }}" class="text-decoration-none">{{ $stop['name'] }}</a>
                                @else
                                    {{ $stop['name'] }}
                                @endif
                            </div>
                            @if($stop['address_line'] !== '')
                                <div class="small text-muted mt-1" style="line-height: 1.45;">{{ $stop['address_line'] }}</div>
                            @endif
                            @if(!empty($stop['employees_label']))
                                <div class="small mt-2">
                                    <span class="text-muted">Miejsce docelowe dla:</span>
                                    <span class="fw-semibold">{{ $stop['employees_label'] }}</span>
                                </div>
                            @endif
                            @if(!empty($stop['purpose']))
                                <div class="mt-3 p-2 rounded-3" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.06);">
                                    <div class="small text-muted mb-1">Po co tu jedziemy</div>
                                    <div class="small" style="line-height: 1.5; white-space: pre-wrap;">{{ $stop['purpose'] }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                <div class="d-flex gap-3 p-3 rounded-3 border" style="border-color: rgba(16,185,129,0.4) !important; background: rgba(16,185,129,0.06);">
                    <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold text-white flex-shrink-0"
                         style="width: 40px; height: 40px; font-size: 0.95rem; background: #22c55e;">
                        <i class="bi bi-flag-fill" style="font-size: 1rem;"></i>
                    </div>
                    <div class="min-w-0 flex-grow-1">
                        <div class="small text-muted mb-1">Cel podróży (lokalizacja docelowa)</div>
                        <div class="fw-semibold">{{ $departure->toLocation->name }}</div>
                        @if($departure->toLocation->address)
                            <div class="small text-muted mt-1" style="line-height: 1.45;">{{ $departure->toLocation->address }}@if($departure->toLocation->city), {{ $departure->toLocation->city }}@endif</div>
                        @endif
                    </div>
                </div>
            </div>
        </x-ui.card>
    @endif

    </div>

    <!-- Komentarze -->
    <x-comments 
        :commentable="$departure" 
        commentable-type="logistics_event"
    />

    @push('scripts')
    <script>
        // Initialize tooltips on page load
        document.addEventListener('DOMContentLoaded', () => {
            initializeTooltips();
        });

        function initializeTooltips() {
            document.querySelectorAll('.tooltip-hotspot').forEach(function(tooltipElement) {
                // Remove old listeners by cloning (prevents duplicate listeners)
                const newElement = tooltipElement.cloneNode(true);
                tooltipElement.parentNode.replaceChild(newElement, tooltipElement);
                
                // Add new listeners
                newElement.addEventListener('click', function(e) {
                    e.stopPropagation();
                    newElement.classList.toggle('active');
                });

                // Close tooltip when clicking outside
                document.addEventListener('click', function(e) {
                    if (!newElement.contains(e.target)) {
                        newElement.classList.remove('active');
                    }
                });

                // Close tooltip on Escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        newElement.classList.remove('active');
                    }
                });
            });
        }
    </script>
    @endpush
</x-app-layout>
