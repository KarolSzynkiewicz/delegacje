{{-- GroundTransferSlot — 1 środek transportu, 1 slot. Niezależny od Step4. --}}
<div wire:key="ground-transfer-slot-{{ $slotKey }}">

    @once('ground-transfer-slot-summary-styles')
        <style>
            .transfer-gts-route-metric-badge {
                background: rgba(14, 165, 233, 0.22) !important;
                border: 1px solid rgba(125, 211, 252, 0.55) !important;
                color: #e0f2fe !important;
                font-weight: 600;
            }
            .transfer-gts-missing-route-badge {
                background: rgba(248, 113, 113, 0.15) !important;
                border: 1px solid rgba(252, 165, 165, 0.55) !important;
                color: #fecaca !important;
                font-weight: 600;
            }
        </style>
    @endonce

    {{-- ── Summary / trigger row ─────────────────────────────────────────── --}}
    <div class="d-flex flex-wrap align-items-start gap-2 mb-2">
        <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1 min-w-0">
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
                    <span class="badge rounded-pill small transfer-gts-route-metric-badge">
                        {{ number_format($routeDistance / 1000, 1) }} km
                        @if($routeDuration !== null)
                            · {{ intdiv($routeDuration, 3600) > 0 ? intdiv($routeDuration, 3600).'h ' : '' }}{{ intdiv($routeDuration % 3600, 60) }}min
                        @endif
                        @if($routeDistanceIsManual)
                            <i class="bi bi-pencil ms-1" title="Wpisane ręcznie"></i>
                        @endif
                    </span>
                @elseif(($legKind ?? null) === 'own' && ($groundMode ?? null) === 'car')
                    <span class="badge rounded-pill small transfer-gts-missing-route-badge">
                        <i class="bi bi-signpost-split me-1"></i>Brak trasy
                    </span>
                @endif
            @else
                <span class="badge rounded-pill bg-warning bg-opacity-15 text-warning-emphasis small">
                    <i class="bi bi-exclamation-circle me-1"></i>Nie skonfigurowano
                </span>
            @endif
        </div>

        <div class="d-flex flex-column align-items-end gap-2 ms-auto">
            @if(($legKind ?? null) === 'own' && ($groundMode ?? null) === 'car')
                <div class="text-end small" style="min-width: 11rem; max-width: 20rem;">
                    <div class="text-muted text-uppercase fw-semibold" style="font-size: 0.65rem; letter-spacing: 0.04em;">Kierowca</div>
                    <div class="text-white fw-semibold text-truncate" title="{{ $this->panelDriverLabel }}">
                        <i class="bi bi-person-badge text-info me-1"></i>{{ $this->panelDriverLabel }}
                    </div>
                    <div class="mt-1" style="font-size: 0.78rem;">
                        @if($driverPaymentAmount !== null && $driverPaymentAmount !== '' && (float) $driverPaymentAmount > 0)
                            <span style="color: rgba(226, 232, 240, 0.75);">Uznanie:</span>
                            <span class="text-success fw-semibold ms-1">{{ number_format((float) $driverPaymentAmount, 2, ',', ' ') }} {{ strtoupper($driverPaymentCurrency) }}</span>
                        @else
                            <span class="text-danger fw-semibold"><i class="bi bi-exclamation-circle me-1"></i>Brak wynagrodzenia</span>
                        @endif
                    </div>
                </div>
            @endif
            <div class="d-flex flex-wrap gap-2 justify-content-end">
                <button type="button" class="btn btn-sm btn-outline-info" wire:click="openConfigModal">
                    <i class="bi bi-sliders me-1"></i>Konfiguruj transfer — wynagrodzenie za transport
                </button>

                @if($legKind === 'own')
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="openRouteModal">
                        <i class="bi bi-signpost-split me-1"></i>Konfiguruj trasę
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Siatka miejsc i bilety są renderowane w rodzicu (TransferCreateBoard),
         w karcie „Szczegóły transferu”. Tutaj zostawiamy tylko konfigurację trasy
         i modalne okna dodatkowe (konfiguracja + trasa). --}}

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- CONFIG MODAL                                                        --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if($showConfigModal)
        {{-- Teleport do body: inaczej position:fixed jest przycinany przez przodka z backdrop-filter (.app-content-wrapper). --}}
        @teleport('body')
            <div class="modal fade show d-block departure-planner-teleport-modal" tabindex="-1" role="dialog" aria-modal="true"
                 aria-labelledby="gts-config-title-{{ $slotKey }}"
                 style="background-color: rgba(0,0,0,0.55);"
                 wire:key="gts-config-modal-{{ $slotKey }}">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable my-3" role="document">
                    <div class="modal-content border-secondary" style="background: var(--bg-card, #1e293b); color: #e2e8f0; max-height: min(90vh, 920px);">

                        <div class="modal-header border-secondary">
                            <h5 class="modal-title" id="gts-config-title-{{ $slotKey }}">
                                <i class="bi bi-sliders text-info me-2"></i>{{ $contextLabel }} — wynagrodzenie za transport
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
                                                @if($configModalVehicleOptions->count() !== 1)
                                                    <option value="">— wybierz pojazd —</option>
                                                @endif
                                                @foreach($configModalVehicleOptions as $v)
                                                    <option value="{{ $v->id }}">{{ $v->registration_number }} – {{ $v->brand }} {{ $v->model }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">Kierowca <span class="text-danger">*</span></label>
                                            <select wire:model.live="driverEmployeeId" class="form-select form-select-sm">
                                                @if($configModalDriverOptions->count() !== 1)
                                                    <option value="">— wybierz kierowcę —</option>
                                                @endif
                                                @foreach($configModalDriverOptions as $emp)
                                                    <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted mb-1">Uznanie (opcjonalne)</label>
                                            <input type="number" step="0.01" min="0"
                                                   wire:model.live.debounce.600ms="driverPaymentAmount"
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
        @endteleport
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- ROUTE MODAL                                                         --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if($showRouteModal)
        @teleport('body')
            <div class="modal fade show d-block departure-planner-teleport-modal" tabindex="-1" role="dialog" aria-modal="true"
                 aria-labelledby="gts-route-title-{{ $slotKey }}"
                 style="background-color: rgba(0,0,0,0.55);"
                 wire:key="gts-route-modal-{{ $slotKey }}">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable my-3" role="document">
                    <div class="modal-content border-secondary" style="background: var(--bg-card, #1e293b); color: #e2e8f0; max-height: min(90vh, 960px);">

                        <div class="modal-header border-secondary">
                            <h5 class="modal-title" id="gts-route-title-{{ $slotKey }}">
                                <i class="bi bi-signpost-split text-info me-2"></i>{{ $contextLabel }} — trasa
                            </h5>
                            <button type="button" class="btn-close btn-close-white" wire:click="closeRouteModal" aria-label="Zamknij"></button>
                        </div>

                        <div class="modal-body">
                            <x-logistics.route-waypoints-plan
                                class="rtp-card rounded-3 p-0"
                                title="Plan trasy"
                                :stops="$routeTiles"
                                wire-key-prefix="gts-rwp-{{ $slotKey }}"
                                :available-locations="$availableLocations"
                                :add-disabled="! $pendingWaypointLocationId"
                                remove-confirm="Usunąć ten przystanek z trasy?"
                            >
                                <x-slot name="distance">
                                    <div class="rounded-3 border p-3"
                                         style="background: rgba(15,23,42,0.55); border-color: rgba(148,163,184,0.28) !important;">
                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                            <div class="small fw-semibold text-secondary">Dystans i czas</div>
                                            @if($groundMode === 'car')
                                                <x-ui.button
                                                    variant="warning"
                                                    type="button"
                                                    class="btn-sm px-2 py-1"
                                                    title="Przelicz trasę (OpenRouteService)"
                                                    wire:click="recalculateRouteWithOrs"
                                                    wire:loading.attr="disabled"
                                                    wire:target="recalculateRouteWithOrs"
                                                >
                                                    <span wire:loading.remove wire:target="recalculateRouteWithOrs"><i class="bi bi-arrow-clockwise"></i></span>
                                                    <span wire:loading wire:target="recalculateRouteWithOrs"><span class="spinner-border spinner-border-sm" style="width: 0.9rem; height: 0.9rem;"></span></span>
                                                </x-ui.button>
                                            @endif
                                        </div>

                                        @if($routeDistance !== null)
                                            <div class="small mb-3" style="color: #94a3b8;">
                                                <span class="text-white fw-semibold">{{ number_format($routeDistance / 1000, 1) }} km</span>
                                                @if($routeDuration !== null)
                                                    <span class="mx-1 opacity-50">·</span>
                                                    <span class="text-white fw-semibold">{{ intdiv($routeDuration, 3600) > 0 ? intdiv($routeDuration, 3600).'h ' : '' }}{{ intdiv($routeDuration % 3600, 60) }} min</span>
                                                @endif
                                            </div>
                                        @else
                                            <p class="small text-muted mb-3 mb-lg-2">Brak wyznaczonej trasy — użyj ORS (wymaga przystanków) lub wpisz wartości ręcznie.</p>
                                        @endif

                                        @if($routeOrsError)
                                            <div class="alert alert-warning py-2 px-3 small mb-2">{{ $routeOrsError }}</div>
                                        @endif

                                        <div class="row g-2 align-items-end">
                                            <div class="col-sm-6">
                                                <label class="form-label small text-muted mb-0">Dystans (km)</label>
                                                <input type="number" step="0.1" min="0"
                                                       wire:model.live.debounce.400ms="manualDistanceKm"
                                                       class="form-control form-control-sm rounded-3"
                                                       style="background: rgba(15,23,42,0.45); border-color: rgba(148,163,184,0.35);"
                                                       placeholder="np. 42">
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="form-label small text-muted mb-0">Czas (min)</label>
                                                <input type="number" step="1" min="1"
                                                       wire:model.live.debounce.400ms="manualDurationMin"
                                                       class="form-control form-control-sm rounded-3"
                                                       style="background: rgba(15,23,42,0.45); border-color: rgba(148,163,184,0.35);"
                                                       placeholder="np. 55">
                                            </div>
                                        </div>
                                    </div>
                                </x-slot>
                            </x-logistics.route-waypoints-plan>
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
        @endteleport
    @endif

</div>
