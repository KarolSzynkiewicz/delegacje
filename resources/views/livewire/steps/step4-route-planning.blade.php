<div>
    <!-- Header Info -->
    <x-ui.card class="mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold text-muted">Data wyjazdu</label>
                <div class="fw-semibold">{{ \Carbon\Carbon::parse($departureDate)->format('d.m.Y') }}</div>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold text-muted">Data przybycia</label>
                <div class="fw-semibold">{{ \Carbon\Carbon::parse($endDate)->format('d.m.Y') }}</div>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold text-muted">Liczba przystanków</label>
                <div class="fw-semibold">{{ count($routeWaypoints) }}</div>
            </div>
        </div>
    </x-ui.card>

    @if($routeError)
        <x-ui.alert variant="danger" title="Błąd" dismissible class="mb-3">
            {{ $routeError }}
        </x-ui.alert>
    @endif

    <div class="row g-4">
        <!-- Left Column: Waypoints List -->
        <div class="col-md-4">
            <x-ui.card>
                <h6 class="mb-3">
                    <i class="bi bi-map"></i> Kolejność przystanków
                    <small class="text-muted d-block mt-1" style="font-size: 0.85rem;">
                        Użyj strzałek aby zmienić kolejność
                    </small>
                </h6>

                @if(empty($routeWaypoints))
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Brak przypisanych mieszkań. Wróć do kroku 2 aby przypisać mieszkania uczestnikom.
                    </div>
                @else
                    <!-- Start: Base Location -->
                    @php
                        $baseData = $baseLocationData;
                        $baseHasCoords = !empty($baseData['latitude']) && !empty($baseData['longitude']);
                    @endphp
                    <div class="waypoint-item mb-2 p-3 border rounded bg-primary bg-opacity-10 {{ $baseHasCoords ? 'border-primary' : 'border-danger' }}"
                         style="cursor: default;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="waypoint-number bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                 style="width: 32px; height: 32px; font-size: 0.9rem;">
                                0
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="fw-semibold">{{ $baseData['name'] ?? 'Baza' }}</div>
                                    @if($baseHasCoords)
                                        <i class="bi bi-geo-alt-fill text-success" title="Ma współrzędne"></i>
                                    @else
                                        <i class="bi bi-geo-alt text-danger" title="Brak współrzędnych"></i>
                                    @endif
                                </div>
                                <div class="small text-muted">{{ $baseData['address'] ?? '' }}</div>
                                @if(!$baseHasCoords)
                                    <div class="small text-danger mt-1">
                                        <i class="bi bi-exclamation-triangle"></i> Brak współrzędnych - edytuj lokalizację i użyj wyszukiwania miejsca
                                    </div>
                                @endif
                            </div>
                            <div class="text-primary">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Waypoints List with Up/Down buttons -->
                    @foreach($waypointAccommodations as $index => $waypoint)
                        @php
                            $accommodation = $waypoint['accommodation'];
                            $hasCoords = !empty($accommodation['latitude']) && !empty($accommodation['longitude']);
                            $isFirst = $index === 0;
                            $isLast  = $index === count($waypointAccommodations) - 1;
                        @endphp
                        <div class="waypoint-item mb-2 p-3 border rounded {{ !$hasCoords ? 'border-danger' : '' }}"
                             style="background: var(--bg-card);" wire:key="waypoint-{{ $waypoint['id'] }}">
                            <div class="d-flex align-items-start gap-2">
                                <!-- Order number -->
                                <div class="waypoint-number bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                     style="width: 32px; height: 32px; font-size: 0.9rem; flex-shrink: 0;">
                                    {{ $index + 1 }}
                                </div>

                                <!-- Content -->
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="fw-semibold">{{ $accommodation['name'] }}</div>
                                        @if($hasCoords)
                                            <i class="bi bi-geo-alt-fill text-success" title="Ma współrzędne"></i>
                                        @else
                                            <i class="bi bi-geo-alt text-danger" title="Brak współrzędnych"></i>
                                        @endif
                                    </div>
                                    <div class="small text-muted">
                                        {{ $accommodation['address'] }}
                                        @if(!empty($accommodation['city']))
                                            , {{ $accommodation['city'] }}
                                        @endif
                                    </div>
                                    @if(!$hasCoords)
                                        <div class="small text-danger mt-1">
                                            <i class="bi bi-exclamation-triangle"></i> Brak współrzędnych - edytuj akomodację i użyj wyszukiwania miejsca
                                        </div>
                                    @endif
                                    @if(!empty($waypoint['employees']) && count($waypoint['employees']) > 0)
                                        <div class="small mt-1">
                                            <span class="text-muted">miejsce docelowe dla:</span>
                                            <span class="fw-semibold">
                                                {{ collect($waypoint['employees'])->pluck('full_name')->join(', ') }}
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Up / Down buttons -->
                                <div class="d-flex flex-column gap-1" style="flex-shrink: 0;">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary p-0"
                                        style="width: 26px; height: 26px; line-height: 1;"
                                        wire:click="moveUp({{ $index }})"
                                        wire:loading.attr="disabled"
                                        @disabled($isFirst)
                                        title="Przesuń wyżej"
                                    >
                                        <i class="bi bi-chevron-up" style="font-size: 0.75rem;"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary p-0"
                                        style="width: 26px; height: 26px; line-height: 1;"
                                        wire:click="moveDown({{ $index }})"
                                        wire:loading.attr="disabled"
                                        @disabled($isLast)
                                        title="Przesuń niżej"
                                    >
                                        <i class="bi bi-chevron-down" style="font-size: 0.75rem;"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <!-- Route Info -->
                    @if($routeData)
                        <div class="mt-3 p-3 border rounded bg-success bg-opacity-10">
                            @php
                                $durationSec = (int) ($routeData['duration'] ?? 0);
                                $hours   = (int) floor($durationSec / 3600);
                                $minutes = (int) floor(($durationSec % 3600) / 60);
                            @endphp
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="small text-muted">Dystans</div>
                                    <div class="fw-semibold">{{ number_format($routeData['distance'], 1) }} km</div>
                                </div>
                                <div class="col-6">
                                    <div class="small text-muted">Czas</div>
                                    <div class="fw-semibold">
                                        @if($hours > 0)
                                            {{ $hours }}h {{ $minutes }}min
                                        @else
                                            {{ $minutes }}min
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($isPlanningRoute)
                        <div class="mt-3 text-center">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Planowanie trasy...</span>
                            </div>
                            <div class="small text-muted mt-2">Planowanie trasy...</div>
                        </div>
                    @endif
                @endif
            </x-ui.card>
        </div>

        <!-- Right Column: Plan wyjazdu -->
        <div class="col-md-8">
            <x-ui.card>
                <h6 class="mb-3">
                    <i class="bi bi-calendar-check"></i> Plan wyjazdu
                </h6>

                @if(empty($tripPlan))
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Brak danych do wyświetlenia. Upewnij się, że przypisałeś uczestników do mieszkań i projektów.
                    </div>
                @else
                    <div class="trip-plan-list">
                        @foreach($tripPlan as $stop)
                            <div class="trip-stop mb-4 p-3 border rounded">
                                <div class="d-flex align-items-start gap-3 mb-3">
                                    <div class="stop-number bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                         style="width: 40px; height: 40px; font-size: 1rem; flex-shrink: 0;">
                                        {{ $loop->iteration }}
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-semibold">{{ $stop['accommodation']['name'] }}</h6>
                                        <div class="small text-muted mb-2">{{ $stop['accommodation']['address'] }}</div>

                                        @foreach($stop['employees'] as $employee)
                                            <div class="employee-details mb-2 p-2 bg-light rounded">
                                                <div class="fw-semibold mb-1">{{ $employee['full_name'] }}</div>

                                                @if($employee['project_name'])
                                                    <div class="small mb-1">
                                                        <i class="bi bi-briefcase"></i>
                                                        <span class="text-muted">Projekt:</span>
                                                        <span class="fw-semibold">{{ $employee['project_name'] }}</span>
                                                    </div>
                                                @endif

                                                @if(!empty($vehicleId))
                                                    @if($employee['distance'] !== null)
                                                        <div class="small mb-1">
                                                            <i class="bi bi-arrow-right-circle"></i>
                                                            <span class="text-muted">Dystans dom-projekt:</span>
                                                            <span class="fw-semibold">{{ number_format($employee['distance'], 1) }} km</span>
                                                        </div>
                                                    @elseif($employee['project_name'])
                                                        <div class="small mb-1 text-warning">
                                                            <i class="bi bi-exclamation-triangle"></i>
                                                            Nie można obliczyć dystansu (brak współrzędnych)
                                                        </div>
                                                    @endif
                                                @endif

                                                @if(!empty($vehicleId))
                                                    @if($employee['vehicle_name'])
                                                        <div class="small">
                                                            <i class="bi bi-car-front"></i>
                                                            <span class="text-muted">Pojazd:</span>
                                                            <span class="fw-semibold">{{ $employee['vehicle_name'] }}</span>
                                                        </div>
                                                    @else
                                                        <div class="small text-muted">
                                                            <i class="bi bi-car-front"></i>
                                                            Brak przypisanego pojazdu
                                                        </div>
                                                    @endif
                                                @else
                                                    <div class="small">
                                                        <i class="bi bi-train-front"></i>
                                                        <span class="text-muted">Podróż:</span>
                                                        <span class="fw-semibold">transportem publicznym</span>
                                                    </div>
                                                    @if(!empty($employee['ticket']))
                                                        @if(!empty($employee['ticket']['start_airport_name']) && !empty($employee['ticket']['end_airport_name']))
                                                            <div class="small mt-1">
                                                                <i class="bi bi-airplane"></i>
                                                                <span class="text-muted">Lotnisko:</span>
                                                                <span class="fw-semibold">{{ $employee['ticket']['start_airport_name'] }} → {{ $employee['ticket']['end_airport_name'] }}</span>
                                                            </div>
                                                        @endif
                                                        <div class="small mt-1">
                                                            <i class="bi bi-ticket-perforated"></i>
                                                            <span class="text-muted">Bilet:</span>
                                                            <span class="fw-semibold">
                                                                {{ $employee['ticket']['amount'] ?? '—' }}
                                                                {{ $employee['ticket']['currency'] ?? '' }}
                                                            </span>
                                                            @if(!empty($employee['ticket']['attachment_path']))
                                                                <span class="text-muted"> (załącznik dodany)</span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>

    <!-- Footer: Navigation -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="d-flex justify-content-end gap-2">
                <x-ui.button
                    variant="ghost"
                    wire:click="$dispatch('go-to-step', { step: 3 })"
                    action="cancel"
                >
                    ← Wróć do poprzedniej karty
                </x-ui.button>
                <button
                    type="button"
                    class="btn btn-primary"
                    wire:click="$dispatch('save-departure')"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-75"
                >
                    <span wire:loading.remove>
                        <i class="bi bi-floppy me-1"></i> Zapisz wyjazd
                    </span>
                    <span wire:loading>
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Zapisywanie...
                    </span>
                </button>
            </div>
        </div>
    </div>

    <style>
        .waypoint-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
    </style>
</div>
