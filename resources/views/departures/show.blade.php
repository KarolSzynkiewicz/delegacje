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
                                        ->where('is_cancelled', false)
                                        ->first();
                                    
                                    $vehicleAssignment = $departure->vehicleAssignments
                                        ->where('employee_id', $participant->employee_id)
                                        ->where('is_cancelled', false)
                                        ->first();
                                    
                                    $accommodationAssignment = $departure->accommodationAssignments
                                        ->where('employee_id', $participant->employee_id)
                                        ->where('is_cancelled', false)
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
