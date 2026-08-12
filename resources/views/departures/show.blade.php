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
                    <x-tooltip title="Transport publiczny: lot i bilety w wyjeździe; dojazd na/z lotniska planujesz osobnym transferem. Transport własny: przejazd pojazdem firmowym — szczegóły pojazdu w tabeli uczestników.">
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
                                <th class="text-uppercase small text-muted fw-semibold py-3">
                                    <i class="bi bi-house me-1"></i>
                                    Zakwaterowanie
                                </th>
                                @if(!empty($canRemoveParticipants))
                                    <th class="text-uppercase small text-muted fw-semibold py-3 pe-4 text-end">Akcja</th>
                                @endif
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

                                    $removalBlock = ($participantRemovalBlocks ?? [])[(int) $participant->employee_id] ?? null;
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
                                    <td class="py-3{{ empty($canRemoveParticipants) ? ' pe-4' : '' }}">
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
                                    @if(!empty($canRemoveParticipants))
                                        <td class="py-3 pe-4 text-end">
                                            @if($removalBlock)
                                                <span class="small text-muted"
                                                      title="{{ $removalBlock }}"
                                                      data-bs-toggle="tooltip"
                                                      data-bs-placement="left">
                                                    <i class="bi bi-lock-fill me-1"></i>Zablokowane
                                                </span>
                                            @else
                                                <form method="POST"
                                                      action="{{ route('departures.participants.remove', [$departure, $participant->employee]) }}"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Wypisać {{ addslashes($participant->employee->full_name ?? 'uczestnika') }} z tego wyjazdu? Usunięte zostaną przypisania (projekt / auto / mieszkanie) tej osoby powiązane z wyjazdem.');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-opacity-50">
                                                        <i class="bi bi-person-dash me-1"></i>Wypisz
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    @endif
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
                                @php $ticketFileUrl = \App\Support\PublicDiskFileUrl::url($ticketCost->file_path); @endphp
                                <tr>
                                    <td>{{ $ticketCost->description ?: 'Bilet' }}</td>
                                    <td>{{ number_format((float) $ticketCost->amount, 2) }} {{ $ticketCost->currency }}</td>
                                    <td>{{ $ticketCost->cost_date?->format('Y-m-d') ?? '-' }}</td>
                                    <td>{{ $ticketCost->notes ?: '—' }}</td>
                                    <td>
                                        @if($ticketFileUrl)
                                            <a href="{{ $ticketFileUrl }}" target="_blank" class="text-decoration-none">
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

    @php
        $hasLinkedTransfers = isset($linkedTransfers) && $linkedTransfers->isNotEmpty();
        $hasGroundLegTickets = ! empty($groundLegTicketRows);
    @endphp

    @if($isPublicTransportDeparture && ($hasLinkedTransfers || $hasGroundLegTickets))
        <x-ui.card label="Powiązane transfery" class="mb-0">
            <x-logistics.ground-transfer-tickets :rows="$groundLegTicketRows ?? []" />

            @if($hasLinkedTransfers)
                <div class="d-flex flex-column gap-3">
                    @foreach($linkedTransfers as $transfer)
                        @php
                            $driverAdj = $transfer->driverAdjustments->first();
                        @endphp
                        <div class="rounded-3 p-3 border" style="border-color: rgba(255,255,255,0.08) !important; background: rgba(255,255,255,0.03);">
                            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                                <div>
                                    <a href="{{ route('transfers.show', $transfer) }}" class="fw-semibold text-decoration-none">
                                        Transfer #{{ $transfer->id }}
                                    </a>
                                    @if($linkedTransfers->count() > 1)
                                        <span class="text-muted small ms-1">({{ $loop->iteration }}/{{ $linkedTransfers->count() }})</span>
                                    @endif
                                </div>
                                <a href="{{ route('transfers.show', $transfer) }}" class="btn btn-sm btn-outline-light border-opacity-25">
                                    Szczegóły
                                </a>
                            </div>
                            <div class="row g-2 small">
                                <div class="col-6 col-md-3">
                                    <div class="text-muted" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: .04em;">Dystans</div>
                                    <div class="fw-semibold">{{ $transfer->getFormattedDistance() ?? '—' }}</div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="text-muted" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: .04em;">Czas</div>
                                    <div class="fw-semibold">{{ $transfer->getFormattedDuration() ?? '—' }}</div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <div class="text-muted" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: .04em;">Pojazd</div>
                                    <div class="fw-semibold">
                                        {{ $transfer->vehicle ? ($transfer->vehicle->registration_number.' — '.$transfer->vehicle->brand.' '.$transfer->vehicle->model) : '—' }}
                                    </div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <div class="text-muted" style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: .04em;">Kierowca / uznanie</div>
                                    <div class="fw-semibold">
                                        @if($driverAdj)
                                            {{ $driverAdj->employee?->full_name ?? '—' }}
                                            · {{ number_format((float) $driverAdj->amount, 2) }} {{ $driverAdj->currency }}
                                        @else
                                            —
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if($transfer->fromLocation || $transfer->toLocation)
                                <div class="small text-muted mt-2 pt-2 border-top" style="border-color: rgba(255,255,255,0.08) !important;">
                                    {{ $transfer->fromLocation?->name ?? '—' }}
                                    <span class="mx-1">→</span>
                                    {{ $transfer->toLocation?->name ?? '—' }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mt-3 pt-3 border-top d-flex flex-wrap align-items-center gap-2" style="border-color: rgba(255,255,255,0.08) !important;">
                <span class="small text-muted">Transfer na/z lotniska dodajesz osobno w kreatorze transferów.</span>
                <a href="{{ route('transfers.create') }}" class="btn btn-sm btn-outline-info">
                    <i class="bi bi-plus-lg me-1"></i>Nowy transfer
                </a>
            </div>
        </x-ui.card>
    @elseif($isPublicTransportDeparture)
        <x-ui.card label="Transfery" class="mb-0">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <p class="text-muted mb-0 small">
                    Brak powiązanych transferów. Dojazd na lotnisko lub z lotniska do mieszkań planujesz w osobnym kreatorze — nie w wyjeździe.
                </p>
                <a href="{{ route('transfers.create') }}" class="btn btn-sm btn-outline-info flex-shrink-0">
                    <i class="bi bi-plus-lg me-1"></i>Nowy transfer
                </a>
            </div>
        </x-ui.card>
    @endif

    @if(!$isPublicTransportDeparture)
        @php
            $ticketCosts = $departure->transportCosts->where('cost_type', 'ticket')->values();
        @endphp

        @if($ticketCosts->isNotEmpty())
        <x-ui.card label="Bilety" class="mb-0">
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
                            @php $ticketFileUrl = \App\Support\PublicDiskFileUrl::url($ticketCost->file_path); @endphp
                            <tr>
                                <td>{{ $ticketCost->description ?: 'Bilet' }}</td>
                                <td>{{ number_format((float) $ticketCost->amount, 2) }} {{ $ticketCost->currency }}</td>
                                <td>{{ $ticketCost->cost_date?->format('Y-m-d') ?? '-' }}</td>
                                <td>{{ $ticketCost->notes ?: '—' }}</td>
                                <td>
                                    @if($ticketFileUrl)
                                        <a href="{{ $ticketFileUrl }}" target="_blank" class="text-decoration-none">
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
        </x-ui.card>
        @endif
    @endif

    @if(!$isPublicTransportDeparture)
        @php
            $routeStops = $departure->getRouteStopsForDetailView();
        @endphp
        <x-ui.card label="Przebieg trasy" class="mb-0">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                <p class="small text-muted mb-0" style="line-height: 1.55; max-width: 42rem;">
                    Kolejność z planu wyjazdu: start z bazy, przystanki po drodze (mieszkania i ewentualne lokalizacje dodane ręcznie), na końcu adres docelowy zapisany przy wyjeździe.
                </p>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <a href="{{ route('departures.route-pdf', $departure) }}"
                       class="btn btn-sm btn-outline-secondary"
                       target="_blank"
                       rel="noopener">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF dla kierowcy
                    </a>
                    @if($departure->status !== \App\Enums\LogisticsEventStatus::CANCELLED)
                        <livewire:departure-route-editor :departure="$departure" :key="'dep-route-'.$departure->id" />
                    @endif
                </div>
            </div>

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

    @if(($relatedUznania ?? collect())->isNotEmpty())
    <x-ui.card label="Powiązane uznania" class="mb-0">
            <div class="table-responsive rounded-3 border" style="border-color: rgba(255,255,255,0.08) !important;">
                <table class="table table-hover departure-participants-table mb-0 align-middle">
                    <thead class="table-light" style="--bs-table-bg: rgba(255,255,255,0.04);">
                        <tr>
                            <th class="text-uppercase small text-muted fw-semibold py-3 ps-4">Pracownik</th>
                            <th class="text-uppercase small text-muted fw-semibold py-3">Kwota</th>
                            <th class="text-uppercase small text-muted fw-semibold py-3">Data</th>
                            <th class="text-uppercase small text-muted fw-semibold py-3">Powiązanie</th>
                            <th class="text-uppercase small text-muted fw-semibold py-3">Notatka</th>
                            <th class="text-uppercase small text-muted fw-semibold py-3">Payroll</th>
                            <th class="text-uppercase small text-muted fw-semibold py-3 pe-4">Szczegóły</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($relatedUznania as $adj)
                            <tr>
                                <td class="ps-4 py-3">
                                    @if($adj->employee)
                                        <x-employee-cell :employee="$adj->employee" />
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="py-3">
                                    <span class="fw-semibold">{{ number_format((float) $adj->amount, 2) }}</span>
                                    <span class="text-muted">{{ $adj->currency }}</span>
                                </td>
                                <td class="py-3">{{ $adj->date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="py-3">
                                    @if((int) $adj->logistics_event_id === (int) $departure->id)
                                        <span class="small">Kierowanie pojazdem wyjazdu</span>
                                    @else
                                        <a href="{{ route('transfers.show', $adj->logistics_event_id) }}" class="text-decoration-none small">
                                            Transfer #{{ $adj->logistics_event_id }}
                                        </a>
                                    @endif
                                </td>
                                <td class="py-3 small text-muted" style="max-width: 14rem;">{{ $adj->notes ?: '—' }}</td>
                                <td class="py-3">
                                    @if($adj->payroll_id)
                                        <a href="{{ route('payrolls.show', $adj->payroll_id) }}" class="text-decoration-none small">#{{ $adj->payroll_id }}</a>
                                    @else
                                        <span class="small text-muted">Bez payrollu</span>
                                    @endif
                                </td>
                                <td class="py-3 pe-4">
                                    <a href="{{ route('adjustments.show', $adj) }}" class="text-decoration-none small">Zobacz</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
