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
                <label class="form-label fw-semibold text-muted">Tryb transportu</label>
                <div class="fw-semibold">
                    @if($isPublicTransport)
                        <i class="bi bi-airplane me-1 text-primary"></i> Transport publiczny (lot)
                    @else
                        <i class="bi bi-car-front me-1 text-success"></i> Własny pojazd
                    @endif
                </div>
            </div>
        </div>
    </x-ui.card>

    @if($routeError && !$isPublicTransport)
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
                        @if($isPublicTransport)
                            Lotniska są stałe · przesuń domy strzałkami
                        @else
                            Użyj strzałek aby zmienić kolejność
                        @endif
                    </small>
                </h6>

                @if($isPublicTransport)
                    {{-- ── Public transport: show LOT + TRANSFER sections (display-only) ── --}}
                    @if(empty($startAirportData) || empty($endAirportData))
                        <div class="alert alert-warning mb-3">
                            <i class="bi bi-exclamation-triangle"></i>
                            Wróć do poprzedniego kroku i wybierz lotnisko startowe oraz docelowe.
                        </div>
                    @else
                        <div class="mb-3 p-3 border rounded bg-primary bg-opacity-10 border-primary">
                            <div class="d-flex align-items-start gap-2">
                                <div class="waypoint-number bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                     style="width: 32px; height: 32px; font-size: 0.85rem; flex-shrink: 0;">
                                    1
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold mb-1"><i class="bi bi-airplane me-1"></i>Lot</div>
                                    <div class="small text-muted">
                                        <span class="fw-semibold text-dark">{{ $startAirportData['name'] }}</span>
                                        <span class="text-muted">→</span>
                                        <span class="fw-semibold text-dark">{{ $endAirportData['name'] }}</span>
                                    </div>
                                </div>
                                <i class="bi bi-lock text-muted" title="Stałe dla całej grupy"></i>
                            </div>
                        </div>

                        <div class="p-3 border rounded bg-success bg-opacity-10 border-success">
                            <div class="d-flex align-items-start gap-2">
                                <div class="waypoint-number bg-success text-white rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                     style="width: 32px; height: 32px; font-size: 0.85rem; flex-shrink: 0;">
                                    2
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold mb-2"><i class="bi bi-car-front-fill me-1"></i>Transfer</div>

                                    @php
                                        $transferStartLabel = !empty($pickupLocationData['name'])
                                            ? ($pickupLocationData['name'] . ' (start auta)')
                                            : ($endAirportData['name'] . ' (start z lotniska)');
                                    @endphp

                                    <div class="vstack gap-2 mb-2">
                                        @if(!empty($pickupLocationData['name']))
                                            <div class="d-flex align-items-start gap-2">
                                                <div class="badge bg-secondary">1</div>
                                                <div class="flex-grow-1">
                                                    @php
                                                        $pickupHasCoords = !empty($pickupLocationData['latitude']) && !empty($pickupLocationData['longitude']);
                                                    @endphp
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="fw-semibold small">{{ $transferStartLabel }}</div>
                                                        @if($pickupHasCoords)
                                                            <i class="bi bi-geo-alt-fill text-success" title="Ma współrzędne"></i>
                                                        @else
                                                            <i class="bi bi-geo-alt text-danger" title="Brak współrzędnych"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-start gap-2">
                                                <div class="badge bg-secondary">2</div>
                                                <div class="flex-grow-1">
                                                    @php
                                                        $endAirportHasCoords = !empty($endAirportData['latitude']) && !empty($endAirportData['longitude']);
                                                    @endphp
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="fw-semibold small">{{ $endAirportData['name'] }}</div>
                                                        @if($endAirportHasCoords)
                                                            <i class="bi bi-geo-alt-fill text-success" title="Ma współrzędne"></i>
                                                        @else
                                                            <i class="bi bi-geo-alt text-danger" title="Brak współrzędnych"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="d-flex align-items-start gap-2">
                                                <div class="badge bg-secondary">1</div>
                                                <div class="flex-grow-1">
                                                    @php
                                                        $endAirportHasCoords = !empty($endAirportData['latitude']) && !empty($endAirportData['longitude']);
                                                    @endphp
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="fw-semibold small">{{ $endAirportData['name'] }}</div>
                                                        @if($endAirportHasCoords)
                                                            <i class="bi bi-geo-alt-fill text-success" title="Ma współrzędne"></i>
                                                        @else
                                                            <i class="bi bi-geo-alt text-danger" title="Brak współrzędnych"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    @if(!empty($waypointAccommodations))
                                        <div class="small text-muted mb-1">Domy (kolejność):</div>
                                        <div class="vstack gap-2">
                                            @foreach($waypointAccommodations as $waypoint)
                                                @php $acc = $waypoint['accommodation']; @endphp
                                                <div class="d-flex align-items-start gap-2">
                                                    <div class="badge bg-secondary">
                                                        @if(!empty($pickupLocationData['name']))
                                                            {{ $loop->iteration + 2 }}
                                                        @else
                                                            {{ $loop->iteration + 1 }}
                                                        @endif
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        @php
                                                            $accHasCoords = !empty($acc['latitude']) && !empty($acc['longitude']);
                                                        @endphp
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="fw-semibold small">{{ $acc['name'] }}</div>
                                                            @if($accHasCoords)
                                                                <i class="bi bi-geo-alt-fill text-success" title="Ma współrzędne"></i>
                                                            @else
                                                                <i class="bi bi-geo-alt text-danger" title="Brak współrzędnych"></i>
                                                            @endif
                                                        </div>
                                                        <div class="text-muted" style="font-size: 0.78rem;">{{ $acc['address'] }}@if(!empty($acc['city'])), {{ $acc['city'] }}@endif</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="small text-muted fst-italic">Brak domów — wróć do kroku 2 i przypisz mieszkania.</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                @else
                    {{-- ── Car trip: original waypoint list ── --}}
                    @if(empty($routeWaypoints))
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            Brak przypisanych mieszkań. Wróć do kroku 2.
                        </div>
                    @else
                        @php $baseData = $baseLocationData; @endphp
                        <div class="waypoint-item mb-2 p-3 border rounded bg-primary bg-opacity-10 border-primary">
                            <div class="d-flex align-items-center gap-2">
                                <div class="waypoint-number bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                     style="width: 32px; height: 32px; font-size: 0.9rem;">0</div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">{{ $baseData['name'] ?? 'Baza' }}</div>
                                    <div class="small text-muted">{{ $baseData['address'] ?? '' }}</div>
                                </div>
                            </div>
                        </div>

                        @foreach($waypointAccommodations as $index => $waypoint)
                            @php
                                $accommodation = $waypoint['accommodation'];
                                $hasCoords     = !empty($accommodation['latitude']) && !empty($accommodation['longitude']);
                                $isFirst       = $index === 0;
                                $isLast        = $index === count($waypointAccommodations) - 1;
                            @endphp
                            <div class="waypoint-item mb-2 p-3 border rounded {{ !$hasCoords ? 'border-danger' : '' }}"
                                 style="background: var(--bg-card);" wire:key="waypoint-{{ $waypoint['id'] }}">
                                <div class="d-flex align-items-start gap-2">
                                    <div class="waypoint-number bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                         style="width: 32px; height: 32px; font-size: 0.9rem; flex-shrink: 0;">
                                        {{ $index + 1 }}
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="fw-semibold">{{ $accommodation['name'] }}</div>
                                            @if($hasCoords)
                                                <i class="bi bi-geo-alt-fill text-success"></i>
                                            @else
                                                <i class="bi bi-geo-alt text-danger"></i>
                                            @endif
                                        </div>
                                        <div class="small text-muted">
                                            {{ $accommodation['address'] }}
                                            @if(!empty($accommodation['city'])), {{ $accommodation['city'] }}@endif
                                        </div>
                                        @if(!empty($waypoint['employees']))
                                            <div class="small mt-1">
                                                <span class="text-muted">miejsce docelowe dla:</span>
                                                <span class="fw-semibold">{{ collect($waypoint['employees'])->pluck('full_name')->join(', ') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="d-flex flex-column gap-1" style="flex-shrink: 0;">
                                        <button type="button" class="btn btn-sm btn-outline-secondary p-0"
                                                style="width: 26px; height: 26px; line-height: 1;"
                                                wire:click="moveUp({{ $index }})" wire:loading.attr="disabled"
                                                @disabled($isFirst)>
                                            <i class="bi bi-chevron-up" style="font-size: 0.75rem;"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary p-0"
                                                style="width: 26px; height: 26px; line-height: 1;"
                                                wire:click="moveDown({{ $index }})" wire:loading.attr="disabled"
                                                @disabled($isLast)>
                                            <i class="bi bi-chevron-down" style="font-size: 0.75rem;"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                @endif

                {{-- Route distance summary --}}
                @if($routeData)
                    <div class="mt-3 p-3 border rounded bg-success bg-opacity-10">
                        @php
                            $durationSec = isset($routeData['duration']) && $routeData['duration'] !== null
                                ? (int) $routeData['duration']
                                : null;
                            $hours       = $durationSec !== null ? (int) floor($durationSec / 3600) : null;
                            $minutesPart = $durationSec !== null ? (int) floor(($durationSec % 3600) / 60) : null;
                        @endphp
                        <div class="small text-muted mb-1">
                            @if($isPublicTransport)
                                Dystans transferu (lotnisko → domy)
                            @else
                                Dystans trasy
                            @endif
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="small text-muted">Dystans</div>
                                <div class="fw-semibold">{{ number_format($routeData['distance'], 1) }} km</div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">Czas</div>
                                <div class="fw-semibold">
                                    @if($durationSec === null)
                                        —
                                    @else
                                        @if($hours > 0){{ $hours }}h @endif{{ $minutesPart }}min
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($isPlanningRoute)
                    <div class="mt-3 text-center">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <div class="small text-muted mt-2">Planowanie trasy...</div>
                    </div>
                @endif

                <div class="mt-3">
                    <button type="button" class="btn btn-sm btn-outline-primary w-100" wire:click="planRoute" wire:loading.attr="disabled">
                        <i class="bi bi-arrow-repeat me-1"></i> Przelicz trasę
                    </button>
                </div>

                @if($isPublicTransport && empty($routeData))
                    <div class="mt-2 p-2 border rounded" style="background: var(--bg-card);">
                        <div class="small text-muted mb-2">
                            <i class="bi bi-exclamation-triangle me-1 text-warning"></i>
                            Nie można obliczyć trasy —
                            @if(!empty($manualRouteHint))
                                problem z znalezieniem lokalizacji <span class="fw-semibold">{{ $manualRouteHint }}</span>.
                            @else
                                problem z wyznaczeniem trasy.
                            @endif
                            Wpisz ręcznie dystans (km) oraz szacowany czas przejazdu (min) — zapisują się tak samo jak przy trasie z API (dystans w km, czas w sekundach w bazie).
                        </div>
                        <div class="row g-2 align-items-end">
                            <div class="col-sm-4">
                                <label class="form-label small text-muted mb-0">Dystans (km)</label>
                                <input
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    wire:model.live="manualRouteDistanceKm"
                                    class="form-control form-control-sm"
                                    placeholder="np. 18.5"
                                >
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label small text-muted mb-0">Czas (min)</label>
                                <input
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    wire:model.live="manualRouteDurationMinutes"
                                    class="form-control form-control-sm"
                                    placeholder="np. 35"
                                >
                            </div>
                            <div class="col-sm-4">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-success w-100"
                                    wire:click="applyManualRouteDistance"
                                    wire:loading.attr="disabled"
                                >
                                    Ustaw ręcznie
                                </button>
                            </div>
                        </div>
                        @if(!empty($this->transferGoogleMapsUrl))
                            <div class="small mt-2">
                                <a href="{{ $this->transferGoogleMapsUrl }}" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                                    <i class="bi bi-map me-1"></i> Sprawdź na Google Maps (otwórz trasę)
                                </a>
                            </div>
                        @endif
                        @if(!empty($isManualRouteDistance))
                            <div class="small text-success mt-2">
                                <i class="bi bi-check2-circle me-1"></i>
                                Używasz ręcznie wpisanego dystansu i czasu (zapis jak przy trasie z API).
                            </div>
                        @endif
                        @if(!empty($routeError))
                            <div class="alert alert-danger py-2 small mt-2 mb-0">{{ $routeError }}</div>
                        @endif
                    </div>
                @endif
            </x-ui.card>
        </div>

        <!-- Right Column: Plan wyjazdu + Transfer config -->
        <div class="col-md-8">

            {{-- Trip plan --}}
            <x-ui.card class="mb-4">
                <h6 class="mb-3">
                    <i class="bi bi-calendar-check"></i> Plan wyjazdu
                </h6>

                @if($isPublicTransport)
                    {{-- ── Public transport route visual ── --}}
                    <div class="transfer-route mb-3">

                        {{-- Step 0: Pickup location (editable inline) --}}
                        <div class="d-flex align-items-stretch gap-3 mb-0">
                            <div class="route-line-col d-flex flex-column align-items-center" style="width: 40px; flex-shrink: 0;">
                                <div class="route-dot bg-info border border-info text-white rounded-circle d-flex align-items-center justify-content-center"
                                     style="width: 36px; height: 36px; flex-shrink: 0;">
                                    <i class="bi bi-house-fill" style="font-size: 0.85rem;"></i>
                                </div>
                                <div class="route-connector bg-info bg-opacity-25 flex-grow-1" style="width: 2px; min-height: 20px; margin: 2px auto;"></div>
                            </div>
                            <div class="flex-grow-1 pb-3">
                                <div class="small text-muted mb-1">Skąd jedzie auto na lotnisko <span class="text-muted">(opcjonalnie)</span></div>
                                <select wire:model.live="transferPickupLocationId" class="form-select form-select-sm">
                                    <option value="">— auto startuje bezpośrednio z lotniska —</option>
                                    @foreach($availableLocations as $loc)
                                        <option value="{{ $loc->id }}">{{ $loc->name }}@if($loc->city) – {{ $loc->city }}@endif</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Step 1: End airport --}}
                        <div class="d-flex align-items-stretch gap-3 mb-0">
                            <div class="route-line-col d-flex flex-column align-items-center" style="width: 40px; flex-shrink: 0;">
                                <div class="route-dot bg-success text-white rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                     style="width: 36px; height: 36px; flex-shrink: 0; font-size: 0.85rem;">
                                    <i class="bi bi-airplane-fill"></i>
                                </div>
                                @if(!empty($tripPlan))
                                    <div class="route-connector bg-secondary bg-opacity-25 flex-grow-1" style="width: 2px; min-height: 20px; margin: 2px auto;"></div>
                                @endif
                            </div>
                            <div class="flex-grow-1 pb-3">
                                @if(!empty($endAirportData))
                                    <div class="fw-semibold">{{ $endAirportData['name'] }}</div>
                                    @if(!empty($endAirportData['address']))
                                        <div class="small text-muted">{{ $endAirportData['address'] }}</div>
                                    @endif
                                    <span class="badge bg-success mt-1" style="font-size: 0.7rem;">Lądowanie / start transferu</span>
                                @else
                                    <div class="text-muted fst-italic small">Nie wybrano lotniska docelowego</div>
                                @endif
                            </div>
                        </div>

                        {{-- Steps 2…N: Accommodations from tripPlan (reorder here) --}}
                        @foreach($tripPlan as $stop)
                            @php $isLastStop = $loop->last; @endphp
                            <div class="d-flex align-items-stretch gap-3 mb-0">
                                <div class="route-line-col d-flex flex-column align-items-center" style="width: 40px; flex-shrink: 0;">
                                    <div class="route-dot bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                         style="width: 36px; height: 36px; flex-shrink: 0; font-size: 0.85rem;">
                                        {{ $loop->iteration + 1 }}
                                    </div>
                                    @if(!$isLastStop)
                                        <div class="route-connector bg-secondary bg-opacity-25 flex-grow-1" style="width: 2px; min-height: 20px; margin: 2px auto;"></div>
                                    @endif
                                </div>
                                <div class="flex-grow-1 pb-3">
                                    <div class="d-flex align-items-start justify-content-between gap-2">
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">{{ $stop['accommodation']['name'] }}</div>
                                            <div class="small text-muted">{{ $stop['accommodation']['address'] }}</div>
                                        </div>
                                        <div class="d-flex flex-column gap-1" style="flex-shrink: 0;">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary p-0"
                                                style="width: 26px; height: 26px; line-height: 1;"
                                                wire:click="moveUp({{ $loop->index }})"
                                                wire:loading.attr="disabled"
                                                @disabled($loop->first)
                                                title="Przesuń ten dom wyżej"
                                            >
                                                <i class="bi bi-chevron-up" style="font-size: 0.75rem;"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary p-0"
                                                style="width: 26px; height: 26px; line-height: 1;"
                                                wire:click="moveDown({{ $loop->index }})"
                                                wire:loading.attr="disabled"
                                                @disabled($loop->last)
                                                title="Przesuń ten dom niżej"
                                            >
                                                <i class="bi bi-chevron-down" style="font-size: 0.75rem;"></i>
                                            </button>
                                        </div>
                                    </div>
                                    @foreach($stop['employees'] as $emp)
                                        <x-ui.card variant="hover" class="mt-2 p-2">
                                            <div class="d-flex align-items-start gap-2">
                                                <i class="bi bi-person text-primary mt-1"></i>
                                                <div class="flex-grow-1 min-w-0">
                                                    <div class="fw-semibold small text-truncate">{{ $emp['full_name'] }}</div>
                                                    @if($emp['project_name'])
                                                        <div class="small text-muted text-truncate">
                                                            <i class="bi bi-briefcase me-1"></i>{{ $emp['project_name'] }}
                                                        </div>
                                                    @endif
                                                    @if(!empty($emp['ticket']))
                                                        <div class="small text-muted mt-1">
                                                            <i class="bi bi-airplane me-1 text-primary"></i>
                                                            @if(!empty($startAirportData['name']) && !empty($endAirportData['name']))
                                                                {{ $startAirportData['name'] }} → {{ $endAirportData['name'] }}
                                                            @else
                                                                {{ $emp['ticket']['start_airport_name'] ?? '—' }} → {{ $emp['ticket']['end_airport_name'] ?? '—' }}
                                                            @endif
                                                            @if(!empty($emp['ticket']['amount']))
                                                                &nbsp;·&nbsp;
                                                                <i class="bi bi-ticket-perforated me-1"></i>{{ $emp['ticket']['amount'] }} {{ $emp['ticket']['currency'] ?? '' }}
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </x-ui.card>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        @if(empty($tripPlan))
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i>
                                Brak przypisanych mieszkań. Wróć do kroku 2.
                            </div>
                        @endif
                    </div>

                @else
                    {{-- ── Car trip plan ── --}}
                    @if(empty($tripPlan))
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            Brak danych. Upewnij się, że przypisałeś uczestników do mieszkań i projektów.
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
                                                <x-ui.card variant="hover" class="mb-2 p-2">
                                                    <div class="fw-semibold mb-1">{{ $employee['full_name'] }}</div>

                                                    @if($employee['project_name'])
                                                        <div class="small mb-1">
                                                            <i class="bi bi-briefcase"></i>
                                                            <span class="text-muted">Projekt:</span>
                                                            <span class="fw-semibold">{{ $employee['project_name'] }}</span>
                                                        </div>
                                                    @endif

                                                    @if($employee['distance'] !== null)
                                                        <div class="small mb-1">
                                                            <i class="bi bi-arrow-right-circle"></i>
                                                            <span class="text-muted">Dystans dom–projekt:</span>
                                                            <span class="fw-semibold">{{ number_format($employee['distance'], 1) }} km</span>
                                                        </div>
                                                    @endif
                                                    @if($employee['vehicle_name'])
                                                        <div class="small">
                                                            <i class="bi bi-car-front"></i>
                                                            <span class="text-muted">Pojazd:</span>
                                                            <span class="fw-semibold">{{ $employee['vehicle_name'] }}</span>
                                                        </div>
                                                    @endif
                                                </x-ui.card>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif
            </x-ui.card>

            @if($isPublicTransport)
                {{-- Transfer Configuration Card --}}
                <x-ui.card class="mb-4">
                    <h6 class="mb-4">
                        <i class="bi bi-car-front-fill me-1 text-warning"></i> Konfiguracja transferu
                    </h6>

                    <div class="row g-3">
                        {{-- Transfer vehicle --}}
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">
                                Pojazd transferu
                                <small class="text-muted fw-normal">(opcjonalnie)</small>
                            </label>
                            <select wire:model.live="transferVehicleId" class="form-select">
                                <option value="">Brak / nie wiadomo jeszcze</option>
                                @foreach($availableVehicles as $v)
                                    <option value="{{ $v->id }}">
                                        {{ $v->registration_number }} – {{ $v->brand }} {{ $v->model }}
                                        @if($v->capacity)({{ $v->capacity }} miejsc)@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Driver --}}
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">
                                Kierowca transferu
                                <small class="text-muted fw-normal">(opcjonalnie)</small>
                            </label>
                            <select wire:model.live="transferDriverEmployeeId" class="form-select">
                                <option value="">Brak kierowcy</option>
                                @foreach($availableEmployees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if($transferDriverEmployeeId)
                            {{-- Driver bonus --}}
                            <div class="col-md-7">
                                <label class="form-label fw-semibold">
                                    Nagroda dla kierowcy
                                    <small class="text-muted fw-normal">(opcjonalnie)</small>
                                </label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    wire:model.live="transferDriverBonusAmount"
                                    class="form-control"
                                    placeholder="np. 200.00"
                                >
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Waluta nagrody</label>
                                <select wire:model.live="transferDriverBonusCurrency" class="form-select">
                                    @foreach($currencyCases as $currency)
                                        <option value="{{ $currency->value }}">{{ $currency->value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-info py-2 mb-0">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Nagroda zostanie zapisana bez przypisanego payrollu.
                                    Po zapisie możesz go przypisać w zakładce <strong>Nagrody/kary</strong>.
                                </div>
                            </div>
                        @endif
                    </div>
                </x-ui.card>
            @endif

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
        .waypoint-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    </style>
</div>
