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

    <x-ui.card label="Informacje podstawowe">
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <h6 class="text-muted small mb-1 d-flex align-items-center gap-1">
                    Data wyjazdu
                    <x-tooltip title="Data i godzina, kiedy pracownicy wyjeżdżają z lokalizacji początkowej. Od tej daty pojazd jest zarezerwowany i niedostępny do innych przypisań.">
                        <i class="bi bi-calendar-check text-info fs-6"></i>
                    </x-tooltip>
                </h6>
                <p class="fw-semibold">{{ $departure->event_date->format('Y-m-d H:i') }}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted small mb-1 d-flex align-items-center gap-1">
                    Data przybycia
                    <x-tooltip title="Data i godzina, kiedy pracownicy docierają do lokalizacji docelowej. Do tej daty pojazd pozostaje zarezerwowany. Musi być późniejsza niż data wyjazdu.">
                        <i class="bi bi-calendar-event text-success fs-6"></i>
                    </x-tooltip>
                </h6>
                <p class="fw-semibold">
                    {{ $departure->end_date?->format('Y-m-d H:i') ?? 'Brak daty przybycia' }}
                    @if($departure->end_date && $departure->getDurationInDays() > 0)
                        <small class="text-muted">({{ $departure->getDurationInDays() }} dni)</small>
                    @endif
                </p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted small mb-1 d-flex align-items-center gap-1">
                    Status wyjazdu
                    <x-tooltip title="Status wyjazdu obliczany jest automatycznie na podstawie dat. 'Oczekuje' = wyjazd w przyszłości. 'W trakcie' = trwa teraz (między datą wyjazdu a datą przybycia). 'Zakończone' = wyjazd się już odbył.">
                        <i class="bi bi-calendar-event text-info fs-6"></i>
                    </x-tooltip>
                </h6>
                @php
                    $visualStatus = $departure->getVisualStatus();
                    $visualVariant = match($visualStatus) {
                        'oczekuje' => 'primary',
                        'w trakcie' => 'warning',
                        'zakończone' => 'success',
                        'anulowany' => 'danger',
                        default => 'accent'
                    };
                @endphp
                <x-ui.badge variant="{{ $visualVariant }}">{{ $visualStatus }}</x-ui.badge>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted small mb-1 d-flex align-items-center gap-1">
                    Pojazd
                    <x-tooltip title="Pojazd używany do transportu pracowników. Jest automatycznie blokowany na cały czas trwania wyjazdu (od daty wyjazdu do daty przybycia) i nie może być przypisany do innych projektów w tym okresie.">
                        <i class="bi bi-truck text-warning fs-6"></i>
                    </x-tooltip>
                </h6>
                <p class="fw-semibold">
                    {{ $departure->vehicle ? $departure->vehicle->registration_number . ' - ' . $departure->vehicle->brand . ' ' . $departure->vehicle->model : '-' }}
                </p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted small mb-1 d-flex align-items-center gap-1">
                    Z (lokalizacja początkowa)
                    <x-tooltip title="Lokalizacja, z której pracownicy rozpoczynają podróż. Zwykle jest to baza/biuro firmy lub poprzednie miejsce pracy.">
                        <i class="bi bi-geo-alt text-danger fs-6"></i>
                    </x-tooltip>
                </h6>
                <p class="fw-semibold">{{ $departure->fromLocation->name }}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted small mb-1 d-flex align-items-center gap-1">
                    Do (lokalizacja docelowa)
                    <x-tooltip title="Lokalizacja docelowa, do której pracownicy dojeżdżają. Tu będą wykonywać pracę na projekcie po przybyciu.">
                        <i class="bi bi-geo-alt-fill text-success fs-6"></i>
                    </x-tooltip>
                </h6>
                <p class="fw-semibold">{{ $departure->toLocation->name }}</p>
            </div>
            @if($departure->notes)
            <div class="col-12">
                <h6 class="text-muted small mb-1 d-flex align-items-center gap-1">
                    Notatki
                    <x-tooltip title="Dodatkowe informacje o wyjeździe: szczegóły dotyczące trasy, miejsca spotkania, wymagań specjalnych, lub innych istotnych uwag logistycznych.">
                        <i class="bi bi-sticky text-warning fs-6"></i>
                    </x-tooltip>
                </h6>
                <p class="mb-0">{{ $departure->notes }}</p>
            </div>
            @endif
        </div>

        <div class="border-top pt-4">
            <h5 class="fw-bold text-dark mb-4 d-flex align-items-center gap-2">
                Uczestnicy
                <x-tooltip title="Lista pracowników uczestniczących w tym wyjeździe i ich przypisania do projektów, pojazdów i zakwaterowania.">
                    <i class="bi bi-people text-primary fs-5"></i>
                </x-tooltip>
            </h5>
            @if($departure->participants->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Pracownik</th>
                                <th>
                                    <i class="bi bi-briefcase me-1"></i>
                                    Projekt
                                </th>
                                <th>
                                    <i class="bi bi-truck me-1"></i>
                                    Pojazd
                                </th>
                                <th>
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
                                    <td>
                                        <x-employee-cell :employee="$participant->employee" />
                                    </td>
                                    <td>
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
                                    <td>
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
                                    <td>
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
                <p class="text-muted">Brak uczestników</p>
            @endif
        </div>
    </x-ui.card>

    @php
        $ticketCosts = $departure->transportCosts->where('cost_type', 'ticket')->values();
        $isPublicTransportDeparture = !$departure->vehicle_id;
    @endphp

    @if($isPublicTransportDeparture)
        <x-ui.card label="Loty / bilety" class="mb-4">
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
        <x-ui.card label="Transfer (lotnisko → domy)" class="mb-4">
            @if(!empty($transfer))
                <div class="row g-4 mb-3">
                    <div class="col-md-4">
                        <h6 class="text-muted small mb-1">Transfer</h6>
                        <p class="fw-semibold mb-0">
                            <a href="{{ route('transfers.show', $transfer) }}" class="text-decoration-none">
                                #{{ $transfer->id }}
                            </a>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted small mb-1">Dystans</h6>
                        <p class="fw-semibold mb-0">{{ $transfer->getFormattedDistance() ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted small mb-1">Czas</h6>
                        <p class="fw-semibold mb-0">{{ $transfer->getFormattedDuration() ?? '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted small mb-1">Pojazd transferu</h6>
                        <p class="fw-semibold mb-0">
                            {{ $transfer->vehicle ? ($transfer->vehicle->registration_number . ' - ' . $transfer->vehicle->brand . ' ' . $transfer->vehicle->model) : '—' }}
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted small mb-1">Kierowca / nagroda</h6>
                        @if($transfer->driverAdjustments->isNotEmpty())
                            @php $adj = $transfer->driverAdjustments->first(); @endphp
                            <p class="fw-semibold mb-0">
                                {{ $adj->employee?->full_name ?? '—' }}
                                — {{ number_format((float) $adj->amount, 2) }} {{ $adj->currency }}
                            </p>
                            @if(!$adj->payroll_id)
                                <div class="small text-muted">Bez payrollu</div>
                            @endif
                        @else
                            <p class="text-muted mb-0">—</p>
                        @endif
                    </div>
                </div>

                <div class="border-top pt-3">
                    <h6 class="fw-semibold mb-3"><i class="bi bi-map me-1"></i>Trasa transferu</h6>
                    @if(!empty($transferRouteStops) && $transferRouteStops->count() > 0)
                        <div class="row g-2">
                            @foreach($transferRouteStops as $i => $loc)
                                @php
                                    $isStart = $i === 0;
                                    $isEnd = $i === ($transferRouteStops->count() - 1);
                                    $badge = $isStart ? 'Start' : ($isEnd ? 'Cel' : 'Przystanek');
                                    $badgeVariant = $isStart ? 'primary' : ($isEnd ? 'success' : 'accent');
                                @endphp
                                <div class="col-md-6 col-lg-4">
                                    <div class="p-2 border rounded-3 d-flex align-items-start gap-2">
                                        <x-ui.badge variant="{{ $badgeVariant }}">{{ $badge }}</x-ui.badge>
                                        <div class="min-w-0">
                                            <div class="fw-semibold small text-truncate">{{ $loc->name }}</div>
                                            @if($loc->city)
                                                <div class="text-muted" style="font-size: 0.75rem;">{{ $loc->city }}</div>
                                            @endif
                                            @if(method_exists($loc, 'hasCoordinates') && !$loc->hasCoordinates())
                                                <div class="text-warning" style="font-size: 0.75rem;">
                                                    <i class="bi bi-exclamation-triangle"></i> brak współrzędnych
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty-state icon="map" message="Brak zapisanej trasy transferu (brak przystanków)" />
                    @endif
                </div>
            @else
                <p class="text-muted mb-0">Brak powiązanego transferu (dla wyjazdów transportem publicznym transfer tworzy się automatycznie w kroku 4).</p>
            @endif
        </x-ui.card>
    @endif

    @if(!$isPublicTransportDeparture)
        <x-ui.card label="Bilety">
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

    @if($departure->hasRouteData() && $departure->route_waypoints)
        <x-ui.card label="Plan trasy">
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <h6 class="text-muted small mb-1 d-flex align-items-center gap-1">
                        Szacowany dystans
                        <x-tooltip title="Całkowity szacowany dystans trasy od bazy przez wszystkie przystanki do miejsca docelowego.">
                            <i class="bi bi-speedometer2 text-info fs-6"></i>
                        </x-tooltip>
                    </h6>
                    <p class="fw-semibold fs-5">{{ $departure->getFormattedDistance() }}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted small mb-1 d-flex align-items-center gap-1">
                        Szacowany czas podróży
                        <x-tooltip title="Całkowity szacowany czas podróży od bazy przez wszystkie przystanki do miejsca docelowego.">
                            <i class="bi bi-clock text-info fs-6"></i>
                        </x-tooltip>
                    </h6>
                    <p class="fw-semibold fs-5">{{ $departure->getFormattedDuration() }}</p>
                </div>
            </div>

            @php
                $waypointAccommodations = $departure->getWaypointAccommodations();
            @endphp

            @if($waypointAccommodations->isNotEmpty())
                <div class="border-top pt-4">
                    <h5 class="fw-bold text-dark mb-4 d-flex align-items-center gap-2">
                        <i class="bi bi-signpost-split text-primary"></i>
                        Przystanki w kolejności
                        <x-tooltip title="Lista przystanków (akomodacji) w kolejności, w jakiej pojazd będzie je odwiedzał podczas wyjazdu.">
                            <i class="bi bi-info-circle text-muted fs-6"></i>
                        </x-tooltip>
                    </h5>
                    @foreach($waypointAccommodations as $index => $accommodation)
                        @php
                            $hasCoords = $accommodation->hasCoordinates();
                            // Get employees assigned to this accommodation for this departure
                            $accommodationEmployees = $departure->accommodationAssignments
                                ->where('accommodation_id', $accommodation->id)
                                ->map(fn($assignment) => $assignment->employee)
                                ->filter();
                        @endphp
                        <div class="waypoint-item mb-2 p-3 border rounded {{ !$hasCoords ? 'border-danger' : '' }}"
                             style="background: var(--bg-card);">
                            <div class="d-flex align-items-start gap-2">
                                <!-- Order number -->
                                <div class="waypoint-number bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                     style="width: 32px; height: 32px; font-size: 0.9rem; flex-shrink: 0;">
                                    {{ $index + 1 }}
                                </div>

                                <!-- Content -->
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="fw-semibold">
                                            <a href="{{ route('accommodations.show', $accommodation) }}" class="text-decoration-none">
                                                {{ $accommodation->name }}
                                            </a>
                                        </div>
                                        @if($hasCoords)
                                            <i class="bi bi-geo-alt-fill text-success" title="Ma współrzędne"></i>
                                        @else
                                            <i class="bi bi-geo-alt text-danger" title="Brak współrzędnych"></i>
                                        @endif
                                    </div>
                                    <div class="small text-muted">
                                        {{ $accommodation->address }}
                                        @if(!empty($accommodation->city))
                                            , {{ $accommodation->city }}
                                        @endif
                                    </div>
                                    @if(!$hasCoords)
                                        <div class="small text-danger mt-1">
                                            <i class="bi bi-exclamation-triangle"></i> Brak współrzędnych - edytuj akomodację i użyj wyszukiwania miejsca
                                        </div>
                                    @endif
                                    @if($accommodationEmployees->isNotEmpty())
                                        <div class="small mt-1">
                                            <span class="text-muted">miejsce docelowe dla:</span>
                                            <span class="fw-semibold">
                                                {{ $accommodationEmployees->pluck('full_name')->join(', ') }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>
    @endif

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
