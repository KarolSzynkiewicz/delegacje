{{-- GroundTransferSlot — 1 środek transportu, 1 slot. Niezależny od Step4. --}}
<div wire:key="ground-transfer-slot-{{ $slotKey }}">

    {{-- ── Summary / trigger row ─────────────────────────────────────────── --}}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
        <span class="fw-semibold small text-muted">{{ $contextLabel }}</span>

        @if($isConfigured)
            <span class="badge rounded-pill bg-success bg-opacity-15 text-success border border-success border-opacity-25 small">
                @if($legKind === 'public')
                    <i class="bi bi-people me-1"></i>Transport publiczny
                @elseif($groundMode === 'car')
                    <i class="bi bi-car-front me-1"></i>Samochód
                @else
                    <i class="bi bi-bus-front me-1"></i>Inny transport
                @endif
            </span>

            @if($legKind === 'own' && $vehicleId)
                @php $veh = $availableVehicles->firstWhere('id', $vehicleId); @endphp
                @if($veh)
                    <span class="badge rounded-pill bg-secondary bg-opacity-15 text-secondary-emphasis small">
                        <i class="bi bi-truck me-1"></i>{{ $veh->registration_number }}
                    </span>
                @endif
            @endif

            @if($routeDistance !== null)
                <span class="badge rounded-pill bg-info bg-opacity-10 text-info-emphasis small">
                    {{ number_format($routeDistance / 1000, 1) }} km
                    @if($routeDuration !== null)
                        · {{ intdiv($routeDuration, 3600) > 0 ? intdiv($routeDuration, 3600).'h ' : '' }}{{ intdiv($routeDuration % 3600, 60) }}min
                    @endif
                    @if($routeDistanceIsManual)
                        <i class="bi bi-pencil ms-1" title="Wpisane ręcznie"></i>
                    @endif
                </span>
            @endif
        @else
            <span class="badge rounded-pill bg-warning bg-opacity-15 text-warning-emphasis small">
                <i class="bi bi-exclamation-circle me-1"></i>Nie skonfigurowano
            </span>
        @endif

        <button type="button" class="btn btn-sm btn-outline-info ms-auto" wire:click="openConfigModal">
            <i class="bi bi-sliders me-1"></i>Konfiguruj transfer
        </button>

        @if($legKind === 'own')
            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="openRouteModal">
                <i class="bi bi-signpost-split me-1"></i>Konfiguruj trasę
            </button>
        @endif
    </div>

    {{-- Siatka miejsc i bilety są renderowane w rodzicu (TransferCreateBoard),
         w karcie „Szczegóły transferu”. Tutaj zostawiamy tylko konfigurację trasy
         i modalne okna dodatkowe (konfiguracja + trasa). --}}

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- CONFIG MODAL                                                        --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if($showConfigModal)
        <div class="modal-portal-to-body" wire:key="gts-config-modal-{{ $slotKey }}">
            <div class="modal-backdrop fade show"></div>
            <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog" aria-modal="true"
                 aria-labelledby="gts-config-title-{{ $slotKey }}">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
                    <div class="modal-content border-secondary" style="background: var(--bg-card, #1e293b); color: #e2e8f0;">

                        <div class="modal-header border-secondary">
                            <h5 class="modal-title" id="gts-config-title-{{ $slotKey }}">
                                <i class="bi bi-sliders text-info me-2"></i>{{ $contextLabel }} — konfiguracja
                            </h5>
                            <button type="button" class="btn-close btn-close-white" wire:click="closeConfigModal" aria-label="Zamknij"></button>
                        </div>

                        <div class="modal-body">

                            {{-- Typ odcinka — tylko gdy rodzic nie narzuca trybu --}}
                            @if($externalLegKind === null)
                                <label class="form-label small text-muted mb-1">Typ odcinka</label>
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <button type="button"
                                            class="btn btn-sm flex-fill {{ $pendingLegKind === 'public' ? 'btn-outline-light' : 'btn-outline-secondary' }}"
                                            wire:click="selectLegKind('public')">
                                        <i class="bi bi-people me-1"></i> Transport publiczny
                                    </button>
                                    <button type="button"
                                            class="btn btn-sm flex-fill {{ ($pendingLegKind === 'own' && $pendingGroundMode === 'car') ? 'btn-success' : 'btn-outline-secondary' }}"
                                            wire:click="selectLegKind('own'); $wire.selectGroundMode('car')">
                                        <i class="bi bi-car-front me-1"></i> Samochód
                                    </button>
                                    <button type="button"
                                            class="btn btn-sm flex-fill {{ ($pendingLegKind === 'own' && $pendingGroundMode === 'other') ? 'btn-primary' : 'btn-outline-secondary' }}"
                                            wire:click="selectLegKind('own'); $wire.selectGroundMode('other')">
                                        <i class="bi bi-bus-front me-1"></i> Inny transport
                                    </button>
                                </div>
                            @endif

                            @if($pendingLegKind === null)
                                <p class="small text-muted">Wybierz typ odcinka przed zatwierdzeniem.</p>

                            @elseif($pendingLegKind === 'public')
                                <p class="small text-muted">
                                    Tylko bilety na ten odcinek — koszty biletów wpisujesz na karcie po zamknięciu modala.
                                </p>

                            @elseif($pendingGroundMode === 'other')
                                <p class="small text-muted">
                                    Inny transport (kolej, autobus) — dystans i czas trasy ustawisz w „Konfiguruj trasę".
                                </p>

                            @else
                                {{-- own / car --}}
                                <div class="rounded-2 p-3" style="background: rgba(14,165,233,0.06); border: 1px solid rgba(14,165,233,0.25);">
                                    <div class="small fw-semibold mb-3"><i class="bi bi-car-front me-1"></i> Pojazd i kierowca</div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">Pojazd <span class="text-danger">*</span></label>
                                            <select wire:model.live="vehicleId" class="form-select form-select-sm">
                                                <option value="">— wybierz pojazd —</option>
                                                @foreach($availableVehicles as $v)
                                                    <option value="{{ $v->id }}">{{ $v->registration_number }} – {{ $v->brand }} {{ $v->model }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">Kierowca <span class="text-danger">*</span></label>
                                            <select wire:model.live="driverEmployeeId" class="form-select form-select-sm">
                                                <option value="">— wybierz kierowcę —</option>
                                                @foreach($availableEmployees as $emp)
                                                    <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">Uznanie (opcjonalne)</label>
                                            <input type="number" step="0.01" min="0"
                                                   wire:model.live="driverPaymentAmount"
                                                   class="form-control form-control-sm"
                                                   placeholder="np. 200.00"
                                                   inputmode="decimal"
                                                   autocomplete="off">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">Waluta</label>
                                            <select wire:model.live="driverPaymentCurrency" class="form-select form-select-sm">
                                                @foreach($currencyCases as $cur)
                                                    <option value="{{ $cur->value }}">{{ $cur->value }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            @endif

                        </div>

                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-outline-light" wire:click="closeConfigModal">Anuluj</button>
                            <button type="button" class="btn btn-primary"
                                    wire:click="confirmConfigModal"
                                    @disabled($pendingLegKind === null)>
                                Zatwierdź
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- ROUTE MODAL                                                         --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if($showRouteModal)
        <div class="modal-portal-to-body" wire:key="gts-route-modal-{{ $slotKey }}">
            <div class="modal-backdrop fade show"></div>
            <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog" aria-modal="true"
                 aria-labelledby="gts-route-title-{{ $slotKey }}">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
                    <div class="modal-content border-secondary" style="background: var(--bg-card, #1e293b); color: #e2e8f0;">

                        <div class="modal-header border-secondary">
                            <h5 class="modal-title" id="gts-route-title-{{ $slotKey }}">
                                <i class="bi bi-signpost-split text-info me-2"></i>{{ $contextLabel }} — trasa
                            </h5>
                            <button type="button" class="btn-close btn-close-white" wire:click="closeRouteModal" aria-label="Zamknij"></button>
                        </div>

                        <div class="modal-body">

                            {{-- ── Przystanki (tiles) ─────────────────────────────────── --}}
                            <div class="small fw-semibold mb-2"><i class="bi bi-signpost-split me-1"></i> Kolejność przystanków</div>

                            @if($routeTiles !== [])
                                <div class="vstack gap-2 mb-3">
                                    @foreach($routeTiles as $tile)
                                        <div class="d-flex align-items-start gap-2 p-2 rounded border"
                                             style="border-color: rgba(251,191,36,0.35) !important; background: rgba(15,23,42,0.35);"
                                             wire:key="gts-tile-{{ $slotKey }}-{{ $tile['index'] }}">
                                            <span class="badge bg-secondary rounded-pill align-self-start flex-shrink-0">{{ $loop->iteration }}</span>
                                            <div class="flex-grow-1 small">
                                                <div class="text-uppercase text-muted" style="font-size: 0.65rem; letter-spacing: .04em;">
                                                    @if($loop->first) Skąd
                                                    @elseif($loop->last) Dokąd
                                                    @else Przystanek
                                                    @endif
                                                </div>
                                                <div class="fw-semibold">{{ $tile['name'] }}@if($tile['city']) – {{ $tile['city'] }}@endif</div>
                                                <textarea class="form-control form-control-sm mt-1" rows="1"
                                                          placeholder="Notatka o przystanku…"
                                                          wire:model.lazy="locationStopNotes.{{ $tile['id'] }}"></textarea>
                                            </div>
                                            <div class="d-flex flex-column gap-0 align-self-center flex-shrink-0">
                                                <button type="button" class="rtp-icon-btn"
                                                        wire:click="moveWaypointUp({{ $tile['index'] }})"
                                                        @disabled(! $tile['can_move_up']) title="Wyżej">
                                                    <i class="bi bi-chevron-up"></i>
                                                </button>
                                                <button type="button" class="rtp-icon-btn"
                                                        wire:click="moveWaypointDown({{ $tile['index'] }})"
                                                        @disabled(! $tile['can_move_down']) title="Niżej">
                                                    <i class="bi bi-chevron-down"></i>
                                                </button>
                                                <button type="button" class="btn btn-link btn-sm text-danger p-0"
                                                        wire:click="removeWaypoint({{ $tile['index'] }})"
                                                        title="Usuń">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-muted small mb-3 text-center p-3 rounded"
                                     style="background: rgba(148,163,184,0.06); border: 1px dashed rgba(148,163,184,0.3);">
                                    <i class="bi bi-map me-1"></i> Brak przystanków — dodaj poniżej (pierwszy = skąd, ostatni = dokąd)
                                </div>
                            @endif

                            {{-- ── Dodaj przystanek ────────────────────────────────────── --}}
                            <form wire:submit.prevent="addWaypoint" class="d-flex gap-2 align-items-center mb-3">
                                <select wire:model.live="pendingWaypointLocationId"
                                        class="form-select form-select-sm flex-grow-1">
                                    <option value="">— Dodaj przystanek (wybierz lokalizację) —</option>
                                    @foreach($availableLocations as $loc)
                                        <option value="{{ $loc->id }}">{{ $loc->name }}@if($loc->city) – {{ $loc->city }}@endif</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-info flex-shrink-0"
                                        wire:loading.attr="disabled"
                                        @disabled(! $pendingWaypointLocationId)>
                                    Dodaj
                                </button>
                            </form>

                            {{-- Manual distance / duration --}}
                            <div class="rounded-2 border p-3"
                                 style="background: rgba(15,23,42,0.5); border-color: rgba(148,163,184,0.35) !important;">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="small fw-semibold">Dystans i czas</div>
                                    @if($routeDistance !== null)
                                        @if($routeDistanceIsManual)
                                            <span class="badge rounded-pill" style="font-size: 0.65rem; background: rgba(251,191,36,0.15); color: #fcd34d;">Wpisane ręcznie</span>
                                        @else
                                            <span class="badge rounded-pill" style="font-size: 0.65rem; background: rgba(59,130,246,0.15); color: #93c5fd;">Szacunek ORS</span>
                                        @endif
                                    @endif
                                </div>

                                @if($routeDistance !== null)
                                    <div class="small text-muted mb-2">
                                        Aktualnie: <strong class="text-white">{{ number_format($routeDistance / 1000, 1) }} km</strong>
                                        @if($routeDuration !== null)
                                            · <strong class="text-white">{{ intdiv($routeDuration, 3600) > 0 ? intdiv($routeDuration, 3600).'h ' : '' }}{{ intdiv($routeDuration % 3600, 60) }} min</strong>
                                        @endif
                                    </div>
                                @endif

                                <div class="row g-2 align-items-end">
                                    <div class="col-sm-5">
                                        <label class="form-label small text-muted mb-0">Dystans (km)</label>
                                        <input type="number" step="0.1" min="0"
                                               wire:model.lazy="manualDistanceKm"
                                               class="form-control form-control-sm"
                                               placeholder="np. 42">
                                    </div>
                                    <div class="col-sm-5">
                                        <label class="form-label small text-muted mb-0">Czas (min)</label>
                                        <input type="number" step="1" min="1"
                                               wire:model.lazy="manualDurationMin"
                                               class="form-control form-control-sm"
                                               placeholder="np. 55">
                                    </div>
                                    <div class="col-sm-2">
                                        <button type="button" class="btn btn-sm btn-outline-info w-100"
                                                wire:click="applyManualRoute"
                                                wire:loading.attr="disabled">
                                            OK
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-outline-light" wire:click="closeRouteModal">Anuluj</button>
                            <button type="button" class="btn btn-primary" wire:click="confirmRouteModal">
                                Zapisz i zamknij
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
