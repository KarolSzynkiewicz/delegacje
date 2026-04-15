<div>
    <!-- Header Info -->
    <div class="rtp-summary-bar d-flex align-items-center gap-3 flex-wrap mb-4 px-4 py-3 rounded-3"
         style="background: linear-gradient(135deg, rgba(99,102,241,0.15) 0%, rgba(139,92,246,0.1) 100%); border: 1px solid rgba(99,102,241,0.25);">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle d-flex align-items-center justify-content-center"
                 style="width: 36px; height: 36px; background: rgba(99,102,241,0.2);">
                <i class="bi bi-calendar3 text-primary" style="font-size: 0.9rem;"></i>
            </div>
            <div>
                <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: .05em;">Wyjazd</div>
                <div class="fw-semibold" style="font-size: 0.95rem;">{{ \Carbon\Carbon::parse($departureDate)->format('d.m.Y') }}</div>
            </div>
        </div>
        <i class="bi bi-arrow-right text-muted"></i>
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle d-flex align-items-center justify-content-center"
                 style="width: 36px; height: 36px; background: rgba(34,197,94,0.15);">
                <i class="bi bi-geo-alt-fill text-success" style="font-size: 0.9rem;"></i>
            </div>
            <div>
                <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: .05em;">Przybycie</div>
                <div class="fw-semibold" style="font-size: 0.95rem;">{{ \Carbon\Carbon::parse($endDate)->format('d.m.Y') }}</div>
            </div>
        </div>
        <div class="ms-auto">
            @if($isPublicTransport)
                <span class="badge rounded-pill px-3 py-2" style="background: rgba(59,130,246,0.2); color: #93c5fd; border: 1px solid rgba(59,130,246,0.3); font-size: 0.8rem;">
                    <i class="bi bi-airplane me-1"></i> Transport publiczny (lot)
                </span>
            @else
                <span class="badge rounded-pill px-3 py-2" style="background: rgba(34,197,94,0.15); color: #86efac; border: 1px solid rgba(34,197,94,0.25); font-size: 0.8rem;">
                    <i class="bi bi-car-front me-1"></i> Własny pojazd
                </span>
            @endif
        </div>
    </div>

    @if($routeError && !$isPublicTransport && (empty($routeWaypoints) || !empty($routeData)))
        <x-ui.alert variant="danger" title="Błąd" dismissible class="mb-3">
            {{ $routeError }}
        </x-ui.alert>
    @endif

    <div class="row g-4">
        <!-- Left Column: Waypoints List -->
        <div class="col-md-4">
            <div class="rtp-card h-100 rounded-3 p-3 position-relative overflow-hidden" style="background: var(--bg-card); border: 1px solid rgba(255,255,255,0.08);">
                @if($isPlanningRoute)
                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center rounded-3 rtp-route-plan-overlay"
                         style="z-index: 20; background: rgba(15,23,42,0.72); backdrop-filter: blur(3px);">
                        <div class="text-center px-4 py-3" style="max-width: 16rem;">
                            <div class="rtp-route-plan-spinner mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center"
                                 style="width: 48px; height: 48px; background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.35);">
                                <div class="spinner-border text-primary" role="status" style="width: 1.5rem; height: 1.5rem;">
                                    <span class="visually-hidden">Ładowanie</span>
                                </div>
                            </div>
                            <div class="fw-semibold text-white mb-1">Przeliczanie trasy…</div>
                            <div class="small" style="color: #94a3b8; font-size: 0.78rem;">Pobieranie trasy z serwisu — reszta formularza działa normalnie.</div>
                        </div>
                    </div>
                @endif
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h6 class="mb-0 fw-semibold" style="font-size: 0.95rem;">
                            <i class="bi bi-signpost-split me-2 text-primary"></i>Kolejność przystanków
                        </h6>
                        <div class="text-muted mt-1" style="font-size: 0.78rem;">
                            @if($isPublicTransport)
                                Lotniska stałe · domy strzałkami · po zmianach użyj <strong class="text-white">Przelicz trasę</strong>
                            @else
                                Strzałki = kolejność · po zmianach użyj <strong class="text-white">Przelicz trasę</strong> (bez auto-API)
                            @endif
                        </div>
                    </div>
                    <span class="badge rounded-pill" style="background: rgba(99,102,241,0.15); color: #a5b4fc; font-size: 0.75rem; padding: 4px 10px;">
                        {{ count($waypointStops) }} stop.
                    </span>
                </div>

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

                                    @if(!empty($waypointStops))
                                        <div class="small text-muted mb-1">Przystanki transferu (kolejność):</div>
                                        <div class="vstack gap-2">
                                            @foreach($waypointStops as $index => $waypoint)
                                                @php
                                                    $isAcc = ($waypoint['type'] ?? '') === 'acc';
                                                    $accommodation = $waypoint['accommodation'] ?? null;
                                                    $location = $waypoint['location'] ?? null;
                                                    $hasCoords = $isAcc
                                                        ? ($accommodation && !empty($accommodation['latitude']) && !empty($accommodation['longitude']))
                                                        : ($location && !empty($location['latitude']) && !empty($location['longitude']));
                                                    $isFirst = $index === 0;
                                                    $isLast = $index === count($waypointStops) - 1;
                                                    $borderColor = $hasCoords
                                                        ? ($isAcc ? 'rgba(34,197,94,0.3)' : 'rgba(251,191,36,0.4)')
                                                        : 'rgba(239,68,68,0.4)';
                                                    $bgColor = $isAcc ? 'rgba(34,197,94,0.05)' : 'rgba(251,191,36,0.06)';
                                                @endphp
                                                <div class="rtp-waypoint-item mb-2 p-2 rounded-3 border"
                                                     style="border-color: {{ $borderColor }} !important; background: {{ $bgColor }};"
                                                     wire:key="pt-sidebar-{{ $waypoint['key'] ?? $index }}">
                                                    <div class="d-flex align-items-start gap-2">
                                                        <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold text-white"
                                                             style="width: 28px; height: 28px; font-size: 0.78rem; flex-shrink: 0;
                                                                    background: {{ $isAcc ? '#22c55e' : '#f59e0b' }};">
                                                            @if($isAcc)
                                                                <i class="bi bi-house-fill" style="font-size: 0.7rem;"></i>
                                                            @else
                                                                <i class="bi bi-geo-alt-fill" style="font-size: 0.7rem;"></i>
                                                            @endif
                                                        </div>
                                                        <div class="flex-grow-1 min-w-0">
                                                            <div class="d-flex align-items-center gap-1">
                                                                <div class="fw-semibold small text-truncate">
                                                                    {{ $isAcc ? ($accommodation['name'] ?? '—') : ($location['name'] ?? 'Przystanek') }}
                                                                </div>
                                                                @if(!$hasCoords)
                                                                    <i class="bi bi-exclamation-triangle-fill text-danger flex-shrink-0" style="font-size: 0.7rem;" title="Brak współrzędnych"></i>
                                                                @endif
                                                            </div>
                                                            @if($isAcc && !empty($waypoint['employees']))
                                                                <div style="font-size: 0.72rem; color: #86efac;">
                                                                    {{ collect($waypoint['employees'])->pluck('full_name')->join(', ') }}
                                                                </div>
                                                            @elseif(!$isAcc && $location)
                                                                <div class="text-muted" style="font-size: 0.72rem;">
                                                                    {{ $location['address'] ?? '' }}@if(!empty($location['city'])), {{ $location['city'] }}@endif
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="d-flex flex-column gap-1 align-items-center" style="flex-shrink: 0;">
                                                            <button type="button" class="rtp-icon-btn"
                                                                    wire:click="moveUp({{ $index }})" wire:loading.attr="disabled"
                                                                    @disabled($isFirst) title="Wyżej">
                                                                <i class="bi bi-chevron-up" style="font-size: 0.7rem;"></i>
                                                            </button>
                                                            <button type="button" class="rtp-icon-btn"
                                                                    wire:click="moveDown({{ $index }})" wire:loading.attr="disabled"
                                                                    @disabled($isLast) title="Niżej">
                                                                <i class="bi bi-chevron-down" style="font-size: 0.7rem;"></i>
                                                            </button>
                                                            @if(!$isAcc)
                                                                <button type="button" class="rtp-icon-btn rtp-icon-btn--danger"
                                                                        wire:click="removeWaypoint({{ $index }})" title="Usuń przystanek">
                                                                    <i class="bi bi-x" style="font-size: 0.75rem;"></i>
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="small text-muted fst-italic">Brak przystanków — wróć do kroku 2 lub dodaj lokalizację w planie wyjazdu.</div>
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
                        {{-- Base (fixed, always first) --}}
                        <div class="rtp-waypoint-item mb-2 p-2 rounded-3 border"
                             style="border-color: rgba(99,102,241,0.4) !important; background: rgba(99,102,241,0.08);">
                            <div class="d-flex align-items-center gap-2">
                                <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold text-white"
                                     style="width: 28px; height: 28px; font-size: 0.78rem; background: #6366f1; flex-shrink: 0;">B</div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold small">{{ $baseData['name'] ?? 'Baza' }}</div>
                                    <div class="text-muted" style="font-size: 0.74rem;">{{ $baseData['address'] ?? '' }}</div>
                                </div>
                                <i class="bi bi-lock text-muted" style="font-size: 0.75rem;" title="Stały punkt startowy"></i>
                            </div>
                        </div>

                        @foreach($waypointStops as $index => $waypoint)
                            @php
                                $isAcc = ($waypoint['type'] ?? '') === 'acc';
                                $accommodation = $waypoint['accommodation'] ?? null;
                                $location = $waypoint['location'] ?? null;
                                $hasCoords = $isAcc
                                    ? (!empty($accommodation['latitude']) && !empty($accommodation['longitude']))
                                    : (!empty($location['latitude']) && !empty($location['longitude']));
                                $isFirst = $index === 0;
                                $isLast  = $index === count($waypointStops) - 1;
                                $borderColor = $hasCoords
                                    ? ($isAcc ? 'rgba(34,197,94,0.3)' : 'rgba(251,191,36,0.4)')
                                    : 'rgba(239,68,68,0.4)';
                                $bgColor = $isAcc ? 'rgba(34,197,94,0.05)' : 'rgba(251,191,36,0.06)';
                            @endphp
                            <div class="rtp-waypoint-item mb-2 p-2 rounded-3 border"
                                 style="border-color: {{ $borderColor }} !important; background: {{ $bgColor }};"
                                 wire:key="waypoint-{{ $waypoint['key'] }}">
                                <div class="d-flex align-items-start gap-2">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold text-white"
                                         style="width: 28px; height: 28px; font-size: 0.78rem; flex-shrink: 0;
                                                background: {{ $isAcc ? '#22c55e' : '#f59e0b' }};">
                                        @if($isAcc)
                                            <i class="bi bi-house-fill" style="font-size: 0.7rem;"></i>
                                        @else
                                            <i class="bi bi-geo-alt-fill" style="font-size: 0.7rem;"></i>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex align-items-center gap-1">
                                            <div class="fw-semibold small text-truncate">
                                                {{ $isAcc ? ($accommodation['name'] ?? '—') : ($location['name'] ?? 'Przystanek') }}
                                            </div>
                                            @if(!$hasCoords)
                                                <i class="bi bi-exclamation-triangle-fill text-danger flex-shrink-0" style="font-size: 0.7rem;" title="Brak współrzędnych"></i>
                                            @endif
                                        </div>
                                        @if($isAcc && !empty($waypoint['employees']))
                                            <div style="font-size: 0.72rem; color: #86efac;">
                                                {{ collect($waypoint['employees'])->pluck('full_name')->join(', ') }}
                                            </div>
                                        @elseif(!$isAcc)
                                            <div class="text-muted" style="font-size: 0.72rem;">
                                                {{ $location['address'] ?? '' }}@if(!empty($location['city'])), {{ $location['city'] }}@endif
                                            </div>
                                        @endif
                                    </div>
                                    <div class="d-flex flex-column gap-1 align-items-center" style="flex-shrink: 0;">
                                        <button type="button" class="rtp-icon-btn"
                                                wire:click="moveUp({{ $index }})" wire:loading.attr="disabled"
                                                @disabled($isFirst) title="Wyżej">
                                            <i class="bi bi-chevron-up" style="font-size: 0.7rem;"></i>
                                        </button>
                                        <button type="button" class="rtp-icon-btn"
                                                wire:click="moveDown({{ $index }})" wire:loading.attr="disabled"
                                                @disabled($isLast) title="Niżej">
                                            <i class="bi bi-chevron-down" style="font-size: 0.7rem;"></i>
                                        </button>
                                        @if(!$isAcc)
                                            <button type="button" class="rtp-icon-btn rtp-icon-btn--danger"
                                                    wire:click="removeWaypoint({{ $index }})" title="Usuń przystanek">
                                                <i class="bi bi-x" style="font-size: 0.75rem;"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                @endif

                {{-- Route distance summary (edytowalne po API i ręcznie) --}}
                @if($routeData)
                    <div class="mt-3 p-3 border rounded bg-success bg-opacity-10 @if($this->routeBlockIncomplete) border-danger @endif">
                        @php
                            $durationSec = isset($routeData['duration']) && $routeData['duration'] !== null
                                ? (int) $routeData['duration']
                                : null;
                            $hours       = $durationSec !== null ? (int) floor($durationSec / 3600) : null;
                            $minutesPart = $durationSec !== null ? (int) floor(($durationSec % 3600) / 60) : null;
                        @endphp
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <div class="small @if($this->routeBlockIncomplete) text-danger fw-semibold @else text-muted @endif mb-0">
                                @if($isPublicTransport)
                                    Dystans transferu (lotnisko → domy)
                                @else
                                    Dystans trasy
                                @endif
                            </div>
                            @if($isManualRouteDistance)
                                <span class="badge rounded-pill" style="font-size: 0.65rem; background: rgba(251,191,36,0.15); color: #fcd34d;">Wpisane ręcznie</span>
                            @else
                                <span class="badge rounded-pill" style="font-size: 0.65rem; background: rgba(59,130,246,0.15); color: #93c5fd;">Szacunek systemu</span>
                            @endif
                        </div>
                        <div class="row g-2 align-items-end">
                            <div class="col-sm-5">
                                <label class="form-label small text-muted mb-0">Dystans (km)</label>
                                <input type="number" step="0.1" min="0" wire:model.live="manualRouteDistanceKm"
                                       class="form-control form-control-sm">
                            </div>
                            <div class="col-sm-5">
                                <label class="form-label small text-muted mb-0">Czas przejazdu (min)</label>
                                <input type="number" step="1" min="1" wire:model.live="manualRouteDurationMinutes"
                                       class="form-control form-control-sm">
                            </div>
                            <div class="col-sm-2">
                                <button type="button" class="btn btn-sm btn-success w-100" wire:click="applyManualRouteDistance"
                                        wire:loading.attr="disabled" title="Zapisuje km i czas z pól powyżej do wyjazdu (po ewentualnej edycji)">
                                    OK
                                </button>
                            </div>
                        </div>
                        <div class="small text-muted mt-2 mb-0" style="font-size: 0.72rem; line-height: 1.45;">
                            <span class="text-muted">Zapisane w wyjeździe:</span>
                            <span class="fw-semibold text-white">{{ number_format((float) ($routeData['distance'] ?? 0), 1) }} km</span>
                            @if($durationSec !== null)
                                <span class="text-muted"> · </span><span class="fw-semibold text-white">@if($hours > 0){{ $hours }}h @endif{{ $minutesPart }}min</span>
                            @endif
                            <span class="d-block mt-1" style="color: #94a3b8;">
                                <strong class="text-white">Przelicz trasę</strong> — ponowny szacunek trasy z systemu (wg aktualnych przystanków).
                                Możesz zmienić km i minuty w polach powyżej i zatwierdzić <strong class="text-white">OK</strong> — wtedy te wartości są zapisywane do wyjazdu.
                            </span>
                        </div>
                    </div>
                @endif

                <div class="mt-3">
                    {{-- Zewnętrzne spany bez klas display z !important — inaczej wire:loading nie ukrywa treści (konflikt z Bootstrap d-inline-flex). --}}
                    <button type="button" class="btn btn-sm btn-outline-primary w-100 position-relative d-flex justify-content-center align-items-center" wire:click="planRoute"
                            wire:loading.attr="disabled" wire:target="planRoute">
                        <span wire:loading.remove wire:target="planRoute">
                            <span class="d-inline-flex align-items-center justify-content-center gap-1">
                                <i class="bi bi-arrow-repeat me-1"></i> Przelicz trasę
                            </span>
                        </span>
                        <span wire:loading wire:target="planRoute">
                            <span class="d-inline-flex align-items-center justify-content-center gap-2">
                                <span class="spinner-border spinner-border-sm" role="status" style="width:0.9rem;height:0.9rem;"></span>
                                Przeliczanie…
                            </span>
                        </span>
                    </button>
                    @if($this->routeBlockIncomplete)
                        <div class="small text-danger mt-2 mb-0">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            Ustal dystans i czas: <strong>Przelicz trasę</strong> (szacunek) albo wpisz km i minuty, potem <strong>OK</strong>.
                        </div>
                    @endif
                </div>

                @if($isPublicTransport && empty($routeData))
                    <div class="mt-2 p-2 border rounded @if($this->routeBlockIncomplete) border-danger @endif" style="background: var(--bg-card); @if($this->routeBlockIncomplete) box-shadow: 0 0 0 1px rgba(239,68,68,0.35); @endif">
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

                {{-- Ręczny dystans/czas gdy API trasy nie zadziała (tryb własny samochód) --}}
                @if(!$isPublicTransport && !empty($routeWaypoints) && empty($routeData))
                    <div class="mt-2 p-3 border rounded" style="background: rgba(239,68,68,0.06); border-color: rgba(239,68,68,0.35);">
                        <div class="small mb-3" style="color: #fca5a5;">
                            <i class="bi bi-exclamation-triangle me-1 text-warning"></i>
                            @if(!empty($routeError))
                                {{ $routeError }}
                            @else
                                Nie udało się wyznaczyć trasy automatycznie.
                            @endif
                            <span class="d-block mt-1 text-muted" style="font-size: 0.78rem;">
                                Wpisz ręcznie dystans (km) oraz szacowany czas przejazdu (min) — zapis jak przy trasie z API (czas w bazie w sekundach).
                            </span>
                        </div>
                        <div class="row g-2 align-items-end">
                            <div class="col-sm-4">
                                <label class="form-label small text-muted mb-0">Dystans (km)</label>
                                <input type="number" step="0.1" min="0" wire:model.live="manualRouteDistanceKm" class="form-control form-control-sm" placeholder="np. 343">
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label small text-muted mb-0">Czas (min)</label>
                                <input type="number" step="1" min="0" wire:model.live="manualRouteDurationMinutes" class="form-control form-control-sm" placeholder="np. 225">
                            </div>
                            <div class="col-sm-4">
                                <button type="button" class="btn btn-sm btn-outline-success w-100" wire:click="applyManualRouteDistance" wire:loading.attr="disabled">
                                    Ustaw ręcznie
                                </button>
                            </div>
                        </div>
                        @if(!empty($isManualRouteDistance) && !empty($routeData))
                            <div class="small text-success mt-2 mb-0">
                                <i class="bi bi-check2-circle me-1"></i> Zapisano dystans i czas ręcznie.
                            </div>
                        @endif
                    </div>
                @endif
            </div>{{-- /left rtp-card --}}
        </div>

        <!-- Right Column: Plan wyjazdu + Transfer config -->
        <div class="col-md-8">

            {{-- Trip plan --}}
            <div class="rtp-card rounded-3 p-3 mb-4" style="background: var(--bg-card); border: 1px solid rgba(255,255,255,0.08);">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0 fw-semibold" style="font-size: 0.95rem;">
                        <i class="bi bi-calendar-check me-2 text-success"></i>Plan wyjazdu
                    </h6>
                </div>

                <form wire:submit.prevent="addExtraStop"
                      class="d-flex gap-2 align-items-center mb-3 p-2 rounded-3"
                      style="background: rgba(99,102,241,0.06); border: 1px dashed rgba(99,102,241,0.3);">
                    <i class="bi bi-plus-circle text-primary" style="font-size: 1rem; flex-shrink: 0;"></i>
                    <select wire:model.live="extraStopLocationId" class="form-select form-select-sm flex-grow-1" style="min-width: 0;">
                        <option value="">— dodaj przystanek z ręki —</option>
                        @foreach($availableLocations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}@if($loc->city) – {{ $loc->city }}@endif</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary px-3 flex-shrink-0"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="addExtraStop"><i class="bi bi-plus-lg"></i> Dodaj</span>
                        <span wire:loading wire:target="addExtraStop"><span class="spinner-border spinner-border-sm"></span></span>
                    </button>
                </form>

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
                            <div class="flex-grow-1 pb-3 @if($this->pickupIncomplete) rounded p-2 border border-danger @endif" style="@if($this->pickupIncomplete) background: rgba(239,68,68,0.06); @endif">
                                <div class="small mb-1 @if($this->pickupIncomplete) text-danger @else text-muted @endif">
                                    Skąd jedzie auto na lotnisko <span class="text-danger">*</span>
                                </div>
                                <select wire:model.live="transferPickupLocationId" class="form-select form-select-sm @if($this->pickupIncomplete) is-invalid @endif">
                                    <option value="">— wybierz miejsce startu auta —</option>
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
                                @if(!empty($waypointStops))
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

                        {{-- Kroki 2…N: ta sama lista co przy aucie — domy + loc: z notatkami; kolejność jak w panelu bocznym --}}
                        @if(empty($waypointStops))
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i>
                                Brak przystanków transferu. Wróć do kroku 2 lub dodaj lokalizację powyżej.
                            </div>
                        @else
                            <div class="trip-plan-list">
                                @foreach($waypointStops as $wp)
                                    @if(($wp['type'] ?? '') === 'loc')
                                        @php
                                            $locIdStr = (string) ($wp['id'] ?? '');
                                            $ptStepNum = $loop->iteration + 1;
                                            $locRow = is_array($wp['location'] ?? null) ? $wp['location'] : [];
                                        @endphp
                                        <div class="trip-stop mb-4 p-3 border rounded" style="border-color: rgba(251,191,36,0.35); background: rgba(251,191,36,0.04);" wire:key="plan-pt-loc-{{ $locIdStr }}">
                                            <div class="d-flex align-items-start gap-3 mb-0">
                                                <div class="stop-number rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                                     style="width: 40px; height: 40px; font-size: 1rem; flex-shrink: 0; background: rgba(251,191,36,0.18); color: #fde68a; border: 1px solid rgba(251,191,36,0.4);">
                                                    {{ $ptStepNum }}
                                                </div>
                                                <div class="flex-grow-1 min-w-0">
                                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                                        <span class="badge rounded-pill border fw-normal"
                                                              style="font-size: 0.68rem; background: rgba(251,191,36,0.08); color: #fcd34d; border-color: rgba(251,191,36,0.35) !important;">Przystanek dodatkowy</span>
                                                        <div class="d-flex flex-column gap-1 align-items-center flex-shrink-0">
                                                            <button type="button" class="rtp-icon-btn"
                                                                    wire:click="moveUp({{ $loop->index }})" wire:loading.attr="disabled"
                                                                    @disabled($loop->first) title="Wyżej">
                                                                <i class="bi bi-chevron-up" style="font-size: 0.75rem;"></i>
                                                            </button>
                                                            <button type="button" class="rtp-icon-btn"
                                                                    wire:click="moveDown({{ $loop->index }})" wire:loading.attr="disabled"
                                                                    @disabled($loop->last) title="Niżej">
                                                                <i class="bi bi-chevron-down" style="font-size: 0.75rem;"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger border-0 px-2 py-0"
                                                                    wire:click="removeWaypoint({{ $loop->index }})"
                                                                    wire:confirm="Usunąć ten przystanek z trasy?"
                                                                    title="Usuń z trasy">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <h6 class="mb-1 fw-semibold">{{ $locRow['name'] ?? '—' }}</h6>
                                                    <div class="small text-muted mb-2">
                                                        {{ $locRow['address'] ?? '' }}@if(!empty($locRow['city'])), {{ $locRow['city'] }}@endif
                                                    </div>
                                                    <label class="form-label small text-muted mb-1">Po co tu jedziemy?</label>
                                                    <textarea
                                                        class="form-control form-control-sm"
                                                        rows="2"
                                                        placeholder="Krótka notatka (np. odbiór dokumentów, spotkanie)…"
                                                        wire:model.live.debounce.500ms="locationStopNotes.{{ $locIdStr }}"
                                                    ></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        @php
                                            $accId = (int) ($wp['id'] ?? 0);
                                            $stop = collect($tripPlan)->first(function ($s) use ($accId) {
                                                return (int) ($s['accommodation']['id'] ?? 0) === $accId;
                                            });
                                            $destNames = $stop
                                                ? collect($stop['employees'])->pluck('full_name')->filter()->values()
                                                : collect($wp['employees'] ?? [])->pluck('full_name')->filter()->values();
                                            $ptStepNum = $loop->iteration + 1;
                                        @endphp
                                        @if($stop)
                                            <div class="trip-stop mb-4 p-3 border rounded" wire:key="plan-pt-acc-{{ $accId }}">
                                                <div class="d-flex align-items-start gap-3 mb-3">
                                                    <div class="stop-number bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                                         style="width: 40px; height: 40px; font-size: 1rem; flex-shrink: 0;">
                                                        {{ $ptStepNum }}
                                                    </div>
                                                    <div class="flex-grow-1 min-w-0">
                                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                                            <div class="min-w-0">
                                                                <h6 class="mb-1 fw-semibold">{{ $stop['accommodation']['name'] }}</h6>
                                                                <div class="small mb-2" style="color:#94a3b8;">
                                                                    <i class="bi bi-geo-alt me-1"></i>Miejsce docelowe dla:
                                                                    <span style="opacity: 0.9;">{{ $destNames->isNotEmpty() ? $destNames->join(', ') : '—' }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex flex-column gap-1 align-items-center flex-shrink-0">
                                                                <button type="button" class="rtp-icon-btn"
                                                                        wire:click="moveUp({{ $loop->index }})" wire:loading.attr="disabled"
                                                                        @disabled($loop->first) title="Wyżej">
                                                                    <i class="bi bi-chevron-up" style="font-size: 0.75rem;"></i>
                                                                </button>
                                                                <button type="button" class="rtp-icon-btn"
                                                                        wire:click="moveDown({{ $loop->index }})" wire:loading.attr="disabled"
                                                                        @disabled($loop->last) title="Niżej">
                                                                    <i class="bi bi-chevron-down" style="font-size: 0.75rem;"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-outline-secondary border-0 px-2 py-0"
                                                                        wire:click="removeWaypoint({{ $loop->index }})"
                                                                        wire:confirm="Usunąć ten dom z kolejności trasy? (Nie usuwa przypisania mieszkania w kroku 2.)"
                                                                        title="Wyjmij z kolejności trasy">
                                                                    <i class="bi bi-x-lg"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <div class="small text-muted mb-2">{{ $stop['accommodation']['address'] }}</div>

                                                        @foreach($stop['employees'] as $employee)
                                                            <x-ui.card variant="hover" class="mb-2 p-2">
                                                                <div class="d-flex align-items-start gap-2">
                                                                    <i class="bi bi-person text-primary mt-1"></i>
                                                                    <div class="flex-grow-1 min-w-0">
                                                                        <div class="fw-semibold small text-truncate">{{ $employee['full_name'] }}</div>
                                                                        @if($employee['project_name'])
                                                                            <div class="small text-muted text-truncate">
                                                                                <i class="bi bi-briefcase me-1"></i>{{ $employee['project_name'] }}
                                                                            </div>
                                                                        @endif
                                                                        @if(!empty($employee['ticket']))
                                                                            <div class="small text-muted mt-1">
                                                                                <i class="bi bi-airplane me-1 text-primary"></i>
                                                                                @if(!empty($startAirportData['name']) && !empty($endAirportData['name']))
                                                                                    {{ $startAirportData['name'] }} → {{ $endAirportData['name'] }}
                                                                                @else
                                                                                    {{ $employee['ticket']['start_airport_name'] ?? '—' }} → {{ $employee['ticket']['end_airport_name'] ?? '—' }}
                                                                                @endif
                                                                                @if(!empty($employee['ticket']['amount']))
                                                                                    &nbsp;·&nbsp;
                                                                                    <i class="bi bi-ticket-perforated me-1"></i>{{ $employee['ticket']['amount'] }} {{ $employee['ticket']['currency'] ?? '' }}
                                                                                @endif
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </x-ui.card>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="trip-stop mb-4 p-3 border rounded border-secondary" wire:key="plan-pt-acc-fallback-{{ $accId }}">
                                                <div class="d-flex align-items-start gap-3">
                                                    <div class="stop-number bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                                         style="width: 40px; height: 40px; font-size: 1rem; flex-shrink: 0;">
                                                        {{ $ptStepNum }}
                                                    </div>
                                                    <div class="flex-grow-1 min-w-0">
                                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                                            <div>
                                                                <h6 class="mb-1 fw-semibold">{{ $wp['accommodation']['name'] ?? 'Dom' }}</h6>
                                                                <div class="small mb-1" style="color:#94a3b8;">
                                                                    <i class="bi bi-geo-alt me-1"></i>Miejsce docelowe dla:
                                                                    <span style="opacity:0.9;">{{ $destNames->isNotEmpty() ? $destNames->join(', ') : '—' }}</span>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex flex-column gap-1 align-items-center flex-shrink-0">
                                                                <button type="button" class="rtp-icon-btn"
                                                                        wire:click="moveUp({{ $loop->index }})" wire:loading.attr="disabled"
                                                                        @disabled($loop->first) title="Wyżej">
                                                                    <i class="bi bi-chevron-up" style="font-size: 0.75rem;"></i>
                                                                </button>
                                                                <button type="button" class="rtp-icon-btn"
                                                                        wire:click="moveDown({{ $loop->index }})" wire:loading.attr="disabled"
                                                                        @disabled($loop->last) title="Niżej">
                                                                    <i class="bi bi-chevron-down" style="font-size: 0.75rem;"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-outline-secondary border-0 px-2 py-0"
                                                                        wire:click="removeWaypoint({{ $loop->index }})"
                                                                        wire:confirm="Usunąć ten dom z kolejności trasy?"
                                                                        title="Wyjmij z kolejności trasy">
                                                                    <i class="bi bi-x-lg"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <div class="small text-muted">{{ $wp['accommodation']['address'] ?? '' }}@if(!empty($wp['accommodation']['city'])), {{ $wp['accommodation']['city'] }}@endif</div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>

                @else
                    {{-- ── Car trip plan: ta sama kolejność co w panelu „Kolejność przystanków” (domy + przystanki ręczne loc:) --}}
                    @if(empty($waypointStops))
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            Brak przystanków. Przypisz mieszkania w kroku 2 lub dodaj lokalizację powyżej.
                        </div>
                    @else
                        <div class="trip-plan-list">
                            @foreach($waypointStops as $wp)
                                @if(($wp['type'] ?? '') === 'loc')
                                    @php $locIdStr = (string) ($wp['id'] ?? ''); @endphp
                                    <div class="trip-stop mb-4 p-3 border rounded" style="border-color: rgba(251,191,36,0.35); background: rgba(251,191,36,0.04);" wire:key="plan-loc-{{ $locIdStr }}">
                                        <div class="d-flex align-items-start gap-3 mb-0">
                                            <div class="stop-number rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                                 style="width: 40px; height: 40px; font-size: 1rem; flex-shrink: 0; background: rgba(251,191,36,0.18); color: #fde68a; border: 1px solid rgba(251,191,36,0.4);">
                                                {{ $loop->iteration }}
                                            </div>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                                    <span class="badge rounded-pill border fw-normal"
                                                          style="font-size: 0.68rem; background: rgba(251,191,36,0.08); color: #fcd34d; border-color: rgba(251,191,36,0.35) !important;">Przystanek dodatkowy</span>
                                                    <button type="button" class="btn btn-sm btn-outline-danger border-0 px-2 py-0"
                                                            wire:click="removeWaypoint({{ $loop->index }})"
                                                            wire:confirm="Usunąć ten przystanek z trasy?"
                                                            title="Usuń z trasy">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                                <h6 class="mb-1 fw-semibold">{{ $wp['location']['name'] ?? '—' }}</h6>
                                                <div class="small text-muted mb-2">
                                                    {{ $wp['location']['address'] ?? '' }}@if(!empty($wp['location']['city'])), {{ $wp['location']['city'] }}@endif
                                                </div>
                                                <label class="form-label small text-muted mb-1">Po co tu jedziemy?</label>
                                                <textarea
                                                    class="form-control form-control-sm"
                                                    rows="2"
                                                    placeholder="Krótka notatka (np. odbiór dokumentów, spotkanie)…"
                                                    wire:model.live.debounce.500ms="locationStopNotes.{{ $locIdStr }}"
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    @php
                                        $accId = (int) ($wp['id'] ?? 0);
                                        $stop = collect($tripPlan)->first(function ($s) use ($accId) {
                                            return (int) ($s['accommodation']['id'] ?? 0) === $accId;
                                        });
                                        $destNames = $stop
                                            ? collect($stop['employees'])->pluck('full_name')->filter()->values()
                                            : collect($wp['employees'] ?? [])->pluck('full_name')->filter()->values();
                                    @endphp
                                    @if($stop)
                                        <div class="trip-stop mb-4 p-3 border rounded" wire:key="plan-acc-{{ $accId }}">
                                            <div class="d-flex align-items-start gap-3 mb-3">
                                                <div class="stop-number bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                                     style="width: 40px; height: 40px; font-size: 1rem; flex-shrink: 0;">
                                                    {{ $loop->iteration }}
                                                </div>
                                                <div class="flex-grow-1 min-w-0">
                                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                                        <div class="min-w-0">
                                                            <h6 class="mb-1 fw-semibold">{{ $stop['accommodation']['name'] }}</h6>
                                                            <div class="small mb-2" style="color:#94a3b8;">
                                                                <i class="bi bi-geo-alt me-1"></i>Miejsce docelowe dla:
                                                                <span style="opacity: 0.9;">{{ $destNames->isNotEmpty() ? $destNames->join(', ') : '—' }}</span>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary border-0 px-2 py-0 flex-shrink-0"
                                                                wire:click="removeWaypoint({{ $loop->index }})"
                                                                wire:confirm="Usunąć ten dom z kolejności trasy? (Nie usuwa przypisania mieszkania w kroku 2.)"
                                                                title="Wyjmij z kolejności trasy">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
                                                    </div>
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
                                    @else
                                        <div class="trip-stop mb-4 p-3 border rounded border-secondary">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="stop-number bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                                     style="width: 40px; height: 40px; font-size: 1rem; flex-shrink: 0;">
                                                    {{ $loop->iteration }}
                                                </div>
                                                <div class="flex-grow-1 min-w-0">
                                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                                        <div>
                                                            <h6 class="mb-1 fw-semibold">{{ $wp['accommodation']['name'] ?? 'Dom' }}</h6>
                                                            <div class="small mb-1" style="color:#94a3b8;">
                                                                <i class="bi bi-geo-alt me-1"></i>Miejsce docelowe dla:
                                                                <span style="opacity:0.9;">{{ $destNames->isNotEmpty() ? $destNames->join(', ') : '—' }}</span>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary border-0 px-2 py-0 flex-shrink-0"
                                                                wire:click="removeWaypoint({{ $loop->index }})"
                                                                wire:confirm="Usunąć ten dom z kolejności trasy?"
                                                                title="Wyjmij z kolejności trasy">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
                                                    </div>
                                                    <div class="small text-muted">{{ $wp['accommodation']['address'] ?? '' }}@if(!empty($wp['accommodation']['city'])), {{ $wp['accommodation']['city'] }}@endif</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>{{-- /rtp-card Plan wyjazdu --}}

            @if($isPublicTransport)
                {{-- Transfer Configuration Card --}}
                @php
                    $transferCardIncomplete = $this->transferVehicleIncomplete || $this->transferDriverIncomplete || $this->transferBonusIncomplete;
                @endphp
                <div class="rtp-card rounded-3 p-3 mb-4" style="background: {{ $transferCardIncomplete ? 'rgba(239,68,68,0.09)' : 'var(--bg-card)' }}; border: 1px solid {{ $transferCardIncomplete ? 'rgba(239,68,68,0.45)' : 'rgba(251,191,36,0.2)' }}; @if($transferCardIncomplete) box-shadow: 0 0 0 1px rgba(239,68,68,0.25); @endif">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width: 36px; height: 36px; background: {{ $transferCardIncomplete ? 'rgba(239,68,68,0.15)' : 'rgba(251,191,36,0.15)' }}; flex-shrink: 0;">
                            <i class="bi bi-car-front-fill {{ $transferCardIncomplete ? 'text-danger' : 'text-warning' }}" style="font-size: 0.9rem;"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-semibold" style="font-size: 0.95rem;">Konfiguracja transferu</h6>
                            <div class="{{ $transferCardIncomplete ? 'text-danger' : 'text-muted' }}" style="font-size: 0.75rem;">
                                @if($transferCardIncomplete)
                                    Uzupełnij pojazd, kierowcę oraz uznanie z walutą.
                                @else
                                    Auto i kierowca spoza bazy na dzień przybycia
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        {{-- Transfer vehicle --}}
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted mb-1">
                                <i class="bi bi-car-front me-1"></i> Pojazd transferu
                                <span class="text-danger">*</span>
                            </label>
                            <select wire:model.live="transferVehicleId" class="form-select form-select-sm @if($this->transferVehicleIncomplete) is-invalid @endif">
                                <option value="">— wybierz pojazd —</option>
                                @foreach($availableVehicles as $v)
                                    <option value="{{ $v->id }}">
                                        {{ $v->registration_number }} – {{ $v->brand }} {{ $v->model }}
                                        @if($v->capacity)({{ $v->capacity }} m.)@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Driver --}}
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted mb-1">
                                <i class="bi bi-person-fill me-1"></i> Kierowca transferu
                                <span class="text-danger">*</span>
                            </label>
                            <select wire:model.live="transferDriverEmployeeId" class="form-select form-select-sm @if($this->transferDriverIncomplete) is-invalid @endif">
                                <option value="">— wybierz kierowcę —</option>
                                @foreach($availableEmployees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if($transferDriverEmployeeId)
                            {{-- Driver bonus --}}
                            <div class="col-md-7">
                                <label class="form-label small fw-semibold text-muted mb-1">
                                    <i class="bi bi-award me-1"></i> Uznanie dla kierowcy
                                    <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    wire:model.live="transferDriverBonusAmount"
                                    class="form-control form-control-sm @if($this->transferBonusIncomplete) is-invalid @endif"
                                    placeholder="np. 200.00"
                                >
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold text-muted mb-1">Waluta <span class="text-danger">*</span></label>
                                <select wire:model.live="transferDriverBonusCurrency" class="form-select form-select-sm @if($this->transferBonusIncomplete) is-invalid @endif">
                                    @foreach($currencyCases as $currency)
                                        <option value="{{ $currency->value }}">{{ $currency->value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="small rounded-2 px-3 py-2"
                                     style="@if($transferCardIncomplete) background: rgba(239,68,68,0.14); border: 1px solid rgba(239,68,68,0.4); color: #fecaca; @else background: rgba(59,130,246,0.08); border: 1px solid rgba(59,130,246,0.2); color: rgba(226,232,240,0.95); @endif">
                                    <i class="bi bi-info-circle me-1 {{ $transferCardIncomplete ? 'text-danger' : 'text-info' }}"></i>
                                    Uznanie bez payrollu — przypisz po zapisie w <strong>Uznaniach/obciążeniach</strong>.
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
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
        .rtp-waypoint-item { transition: box-shadow 0.15s ease, transform 0.1s ease; }
        .rtp-waypoint-item:hover { box-shadow: 0 2px 12px rgba(0,0,0,0.2); transform: translateX(2px); }

        .rtp-icon-btn {
            display: flex; align-items: center; justify-content: center;
            width: 24px; height: 24px;
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 6px;
            background: rgba(255,255,255,0.05);
            color: rgba(255,255,255,0.6);
            cursor: pointer;
            padding: 0;
            transition: all 0.15s ease;
        }
        .rtp-icon-btn:hover:not(:disabled) { background: rgba(255,255,255,0.15); color: #fff; border-color: rgba(255,255,255,0.3); }
        .rtp-icon-btn:disabled { opacity: 0.3; cursor: not-allowed; }
        .rtp-icon-btn--danger:hover:not(:disabled) { background: rgba(239,68,68,0.2); border-color: rgba(239,68,68,0.4); color: #fca5a5; }

        .rtp-summary-bar { transition: box-shadow 0.2s ease; }
        .rtp-summary-bar:hover { box-shadow: 0 4px 20px rgba(99,102,241,0.15); }

        @keyframes rtp-route-overlay-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.35); opacity: 1; }
            50% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); opacity: 0.92; }
        }
        .rtp-route-plan-spinner {
            animation: rtp-route-overlay-pulse 1.8s ease-out infinite;
        }
    </style>
</div>
