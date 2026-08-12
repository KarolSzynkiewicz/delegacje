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
                    <i class="bi bi-airplane me-1"></i> Transport publiczny (lot / dworzec)
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

    @if($isPublicTransport)
    {{-- Transport publiczny: tylko lot/bilety. Transfery ziemne → osobny kreator. --}}
    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="rtp-card rounded-3 p-3 mb-4" style="background: var(--bg-card); border: 1px solid rgba(255,255,255,0.08);">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0 fw-semibold" style="font-size: 0.95rem;">
                        <i class="bi bi-calendar-check me-2 text-success"></i>Plan wyjazdu
                    </h6>
                </div>
                @include('components.logistics.ground-transfer.public-route-cards')
            </div>
        </div>
    </div>

    @else
    {{-- ═══════════════ WŁASNY SAMOCHÓD: układ w stylu GroundTransferSlot ════════════ --}}

    @once('step4-gts-styles')
        <style>
            .step4-gts-route-metric-badge {
                background: rgba(14, 165, 233, 0.22) !important;
                border: 1px solid rgba(125, 211, 252, 0.55) !important;
                color: #e0f2fe !important;
                font-weight: 600;
            }
            .step4-gts-missing-route-badge {
                background: rgba(248, 113, 113, 0.15) !important;
                border: 1px solid rgba(252, 165, 165, 0.55) !important;
                color: #fecaca !important;
                font-weight: 600;
            }
        </style>
    @endonce

    @php
        // Step4 trzyma dystans w km w routeData['distance']; rodzic może mieć route_distance z tego samego źródła
        $ownRouteDistKm = data_get($routeData, 'route_distance', data_get($routeData, 'distance'));
        $ownRouteDurS   = data_get($routeData, 'route_duration', data_get($routeData, 'duration'));
        $ownRouteOk     = $ownRouteDistKm !== null && $ownRouteDistKm !== '' && is_numeric($ownRouteDistKm) && (float) $ownRouteDistKm > 0;
    @endphp

    {{-- ─── Dwukolumnowy układ własnego transportu ─────────────────────────── --}}
    <div class="row g-4">

        {{-- LEWA KOLUMNA: Lista przypisań pracowników z kroków 1–3 --}}
        <div class="col-md-5">
            <div class="rtp-card h-100 rounded-3 p-3" style="background: var(--bg-card); border: 1px solid rgba(255,255,255,0.08);">
                <div class="small fw-semibold text-muted text-uppercase mb-3" style="letter-spacing: 0.05em; font-size: 0.7rem;">
                    <i class="bi bi-people me-1"></i>Przypisania z wyjazdu
                </div>

                @if(empty($personAssignments))
                    <div class="text-muted small">
                        <i class="bi bi-info-circle me-1"></i>Brak przypisanych pracowników. Wróć do kroków 1–3.
                    </div>
                @else
                    <div class="d-flex flex-column gap-2">
                        @foreach($personAssignments as $pa)
                            <div class="rounded-2 p-2" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06);">
                                <div class="fw-semibold small mb-1">
                                    <i class="bi bi-person me-1 text-info"></i>{{ $pa['full_name'] }}
                                </div>
                                @foreach($pa['projects'] as $proj)
                                    <div class="small mb-1 ms-3" style="color: #94a3b8;">
                                        <i class="bi bi-briefcase me-1" style="font-size: 0.7rem; color: #facc15;"></i>
                                        <span style="color: #e2e8f0;">{{ $proj['project_name'] }}</span>
                                        @if($proj['start_date'] || $proj['end_date'])
                                            <span class="ms-1" style="opacity: 0.7;">{{ $proj['start_date'] }}–{{ $proj['end_date'] }}</span>
                                        @endif
                                    </div>
                                @endforeach
                                @if(!empty($pa['housing']))
                                    <div class="small mb-1 ms-3" style="color: #94a3b8;">
                                        <i class="bi bi-house me-1" style="font-size: 0.7rem; color: #86efac;"></i>
                                        <span style="color: #e2e8f0;">{{ $pa['housing']['name'] }}</span>
                                        @if(!empty($pa['housing']['city']) || !empty($pa['housing']['address']))
                                            <span class="ms-1" style="opacity: 0.75;">@if(!empty($pa['housing']['address'])){{ $pa['housing']['address'] }}@endif @if(!empty($pa['housing']['city']))· {{ $pa['housing']['city'] }}@endif</span>
                                        @endif
                                        @if($pa['housing']['start_date'] || $pa['housing']['end_date'])
                                            <span class="ms-1" style="opacity: 0.7;">{{ $pa['housing']['start_date'] }}–{{ $pa['housing']['end_date'] }}</span>
                                        @endif
                                    </div>
                                @endif
                                @if($pa['vehicle'])
                                    <div class="small ms-3" style="color: #94a3b8;">
                                        <i class="bi bi-car-front me-1" style="font-size: 0.7rem; color: #4ade80;"></i>
                                        <span style="color: #e2e8f0;">{{ $pa['vehicle']['registration'] }}</span>
                                        @if($pa['vehicle']['position'])
                                            <span class="ms-1" style="opacity: 0.8;">({{ $pa['vehicle']['position'] }})</span>
                                        @endif
                                        @if($pa['vehicle']['start_date'])
                                            <span class="ms-1" style="opacity: 0.7;">{{ $pa['vehicle']['start_date'] }}–{{ $pa['vehicle']['end_date'] }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- PRAWA KOLUMNA: Konfiguracja transferu i trasy (styl GroundTransferSlot) --}}
        <div class="col-md-7">
            <div class="rtp-card rounded-3 p-3" style="background: var(--bg-card); border: 1px solid rgba(255,255,255,0.08);">

                <div class="small fw-semibold text-muted text-uppercase mb-3" style="letter-spacing: 0.05em; font-size: 0.7rem;">
                    Trasa — przystanki i dystans
                </div>

                {{-- Status trasy (pełna szerokość) --}}
                @if($ownRouteOk)
                    <div class="rounded-2 px-3 py-2 mb-3 d-flex align-items-center gap-2 step4-gts-route-metric-badge">
                        <i class="bi bi-signpost-split"></i>
                        <span class="fw-semibold">
                            {{ number_format((float) $ownRouteDistKm, 1, ',', ' ') }} km
                            @if($ownRouteDurS !== null)
                                · {{ intdiv((int) $ownRouteDurS, 3600) > 0 ? intdiv((int) $ownRouteDurS, 3600).'h ' : '' }}{{ intdiv((int) $ownRouteDurS % 3600, 60) }}min
                            @endif
                        </span>
                        @if($isManualRouteDistance)
                            <i class="bi bi-pencil ms-auto" title="Wpisane ręcznie" style="opacity: 0.7;"></i>
                        @endif
                    </div>
                @else
                    <div class="rounded-2 px-3 py-2 mb-3 d-flex align-items-center gap-2 step4-gts-missing-route-badge">
                        <i class="bi bi-signpost-split"></i>
                        <span class="fw-semibold">Brak trasy</span>
                    </div>
                @endif

                <p class="small text-muted mb-3" style="font-size: 0.8rem;">
                    Przystanki dodajesz w modalu <strong class="text-white">Konfiguruj trasę</strong> (kolejność = kolejność przejazdu, notatki przy każdym punkcie, opcjonalnie km i minuty ręcznie). Pojazd z karty powyżej jest synchronizowany z tą konfiguracją przy zapisie transferu.
                </p>

                {{-- Wiersz podsumowania transferu --}}
                <div class="d-flex flex-wrap align-items-center gap-2 pb-3 mb-3" style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                    <span class="fw-semibold small text-muted">Transfer</span>
                    @if($ownVehicle)
                        <span class="badge rounded-pill bg-secondary bg-opacity-15 text-secondary-emphasis small">
                            <i class="bi bi-car-front me-1"></i>{{ $ownVehicle->registration_number }}
                        </span>
                    @else
                        <span class="badge rounded-pill bg-success bg-opacity-15 text-success border border-success border-opacity-25 small">
                            <i class="bi bi-car-front me-1"></i>Własny pojazd
                        </span>
                    @endif

                    <div class="ms-auto text-end">
                        <div class="small text-muted text-uppercase fw-semibold mb-1" style="font-size: 0.62rem; letter-spacing: 0.04em;">Kierowca</div>
                        <div class="text-white fw-semibold small">
                            <i class="bi bi-person-badge text-info me-1"></i>
                            {{ $ownDriverEmployee?->full_name ?? 'Kierowca zewnętrzny' }}
                        </div>
                        <div style="font-size: 0.75rem;" class="mt-1">
                            @if($transferDriverBonusAmount !== null && $transferDriverBonusAmount !== '' && (float) $transferDriverBonusAmount > 0)
                                <span class="text-success fw-semibold">
                                    {{ number_format((float) $transferDriverBonusAmount, 2, ',', ' ') }} {{ strtoupper($transferDriverBonusCurrency) }}
                                </span>
                            @else
                                <span class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>Brak wynagrodzenia</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Przyciski akcji --}}
                <div class="d-flex flex-wrap gap-2 justify-content-end">
                    <button type="button" class="btn btn-sm btn-outline-info" wire:click="openOwnTransferModal">
                        <i class="bi bi-sliders me-1"></i>Konfiguruj transfer — wynagrodzenie za transport
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="openOwnRouteModal">
                        <i class="bi bi-signpost-split me-1"></i>Konfiguruj trasę
                    </button>
                </div>

            </div>
        </div>

    </div>

    {{-- Modal: Konfiguruj transfer — wynagrodzenie za transport --}}
    @if($showOwnTransferModal)
        @teleport('body')
            <div class="modal fade show d-block departure-planner-teleport-modal" tabindex="-1" role="dialog" aria-modal="true"
                 style="background-color: rgba(0,0,0,0.55);"
                 wire:key="step4-own-transfer-modal">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable my-3" role="document">
                    <div class="modal-content border-secondary" style="background: var(--bg-card, #1e293b); color: #e2e8f0; max-height: min(90vh, 920px);">

                        <div class="modal-header border-secondary">
                            <h5 class="modal-title">
                                <i class="bi bi-sliders text-info me-2"></i>Transfer — wynagrodzenie za transport
                            </h5>
                            <button type="button" class="btn-close btn-close-white" wire:click="closeOwnTransferModal" aria-label="Zamknij"></button>
                        </div>

                        <div class="modal-body">

                            @if($ownVehicle)
                                <div class="rounded-2 p-3 mb-4" style="background: rgba(34,197,94,0.06); border: 1px solid rgba(34,197,94,0.25);">
                                    <div class="small fw-semibold mb-2"><i class="bi bi-car-front text-success me-1"></i>Pojazd wyjazdu</div>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="badge rounded-pill bg-success bg-opacity-15 text-success border border-success border-opacity-25">
                                            {{ $ownVehicle->registration_number }}
                                        </span>
                                        <span class="small text-muted">{{ $ownVehicle->brand }} {{ $ownVehicle->model }}</span>
                                    </div>
                                    <div class="small text-muted mt-2" style="font-size: 0.75rem;">Pojazd jest ustawiony w nagłówku wyjazdu — zmień go w sekcji "Czym".</div>
                                </div>
                            @endif

                            <div class="rounded-2 p-3" style="background: rgba(14,165,233,0.06); border: 1px solid rgba(14,165,233,0.25);">
                                <div class="small fw-semibold mb-3"><i class="bi bi-person-badge text-info me-1"></i>Kierowca i wynagrodzenie</div>
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label small text-muted mb-1">Kierowca (opcjonalnie)</label>
                                        <select wire:model.live="pendingOwnDriverEmployeeId" class="form-select form-select-sm">
                                            <option value="">— wybierz kierowcę —</option>
                                            @foreach($ownDriverCandidates as $emp)
                                                <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                                            @endforeach
                                        </select>
                                        @if($ownDriverCandidates->isEmpty())
                                            <div class="small text-warning mt-1" style="font-size: 0.72rem;">
                                                Brak osób na liście — sprawdź uczestników wyjazdu lub fotel kierowcy w nagłówku.
                                            </div>
                                        @elseif($ownDriverPickerListsAllEmployees)
                                            <div class="small text-muted mt-1" style="font-size: 0.72rem;">
                                                <strong>Kierowca zewnętrzny</strong> (lub brak osoby na fotelu) — wybierz pracownika z uczestników wyjazdu.
                                            </div>
                                        @else
                                            <div class="small text-muted mt-1" style="font-size: 0.72rem;">
                                                Na fotelu jest <strong>konkretny kierowca</strong> — na liście tylko ta osoba. Zmiana: miejsce w aucie w nagłówku.
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-md-7">
                                        <label class="form-label small text-muted mb-1">Uznanie za transport (opcjonalne)</label>
                                        <input type="number" step="0.01" min="0"
                                               wire:model.live.debounce.600ms="pendingOwnBonusAmount"
                                               class="form-control form-control-sm"
                                               placeholder="np. 200.00"
                                               inputmode="decimal"
                                               autocomplete="off">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small text-muted mb-1">Waluta</label>
                                        <select wire:model.live="pendingOwnBonusCurrency" class="form-select form-select-sm">
                                            @foreach($currencyCases as $cur)
                                                <option value="{{ $cur->value }}">{{ $cur->value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-outline-light" wire:click="closeOwnTransferModal">Anuluj</button>
                            <button type="button" class="btn btn-primary" wire:click="confirmOwnTransferModal">Zatwierdź</button>
                        </div>

                    </div>
                </div>
            </div>
        @endteleport
    @endif

    {{-- Modal: Konfiguruj trasę — używa tych samych bloków UI co transfery (x-logistics.route-waypoints-plan) --}}
    @if($showOwnRouteModal)
        @teleport('body')
            <div class="modal fade show d-block departure-planner-teleport-modal" tabindex="-1" role="dialog" aria-modal="true"
                 style="background-color: rgba(0,0,0,0.55);"
                 wire:key="step4-own-route-modal">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable my-3" role="document">
                    <div class="modal-content border-secondary" style="background: var(--bg-card, #1e293b); color: #e2e8f0; max-height: min(90vh, 960px);">

                        <div class="modal-header border-secondary">
                            <h5 class="modal-title">
                                <i class="bi bi-signpost-split text-info me-2"></i>
                                @if($ownVehicle){{ $ownVehicle->registration_number }} — @endif trasa
                            </h5>
                            <button type="button" class="btn-close btn-close-white" wire:click="closeOwnRouteModal" aria-label="Zamknij"></button>
                        </div>

                        <div class="modal-body">
                            <x-logistics.route-waypoints-plan
                                class="rtp-card rounded-3 p-0"
                                title="Plan trasy"
                                :stops="$ownRouteTiles"
                                wire-key-prefix="step4-own-rwp"
                                :available-locations="$availableOwnRouteLocations"
                                :add-disabled="! $pendingWaypointLocationId"
                                add-submit-method="addWaypoint"
                                pending-location-model="pendingWaypointLocationId"
                                move-up-method="moveWaypointUp"
                                move-down-method="moveWaypointDown"
                                remove-method="removeWaypoint"
                                remove-confirm="Usunąć ten przystanek z trasy?"
                                notes-placeholder="np. kto wsiada / wysiada, co zabrać…"
                            >
                                <x-slot name="distance">
                                    <div class="rounded-3 border p-3"
                                         style="background: rgba(15,23,42,0.55); border-color: rgba(148,163,184,0.28) !important;">
                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                            <div class="small fw-semibold text-secondary">Dystans i czas</div>
                                            <x-ui.button
                                                variant="warning"
                                                type="button"
                                                class="btn-sm px-2 py-1"
                                                title="Przelicz trasę (OpenRouteService)"
                                                wire:click="planRoute"
                                                wire:loading.attr="disabled"
                                                wire:target="planRoute"
                                            >
                                                <span wire:loading.remove wire:target="planRoute"><i class="bi bi-arrow-clockwise"></i></span>
                                                <span wire:loading wire:target="planRoute"><span class="spinner-border spinner-border-sm" style="width: 0.9rem; height: 0.9rem;"></span></span>
                                            </x-ui.button>
                                        </div>

                                        @if($ownRouteOk)
                                            <div class="small mb-3" style="color: #94a3b8;">
                                                <span class="text-white fw-semibold">{{ number_format((float) $ownRouteDistKm, 1, ',', ' ') }} km</span>
                                                @if($ownRouteDurS !== null)
                                                    <span class="mx-1 opacity-50">·</span>
                                                    <span class="text-white fw-semibold">
                                                        {{ intdiv((int) $ownRouteDurS, 3600) > 0 ? intdiv((int) $ownRouteDurS, 3600).'h ' : '' }}{{ intdiv((int) $ownRouteDurS % 3600, 60) }} min
                                                    </span>
                                                @endif
                                                @if($isManualRouteDistance)
                                                    <span class="badge rounded-pill ms-1" style="font-size: 0.62rem; background: rgba(251,191,36,0.15); color: #fcd34d;">ręcznie</span>
                                                @endif
                                            </div>
                                        @else
                                            <p class="small text-muted mb-3 mb-lg-2">Brak wyznaczonej trasy — użyj ORS (wymaga przystanków) lub wpisz wartości ręcznie.</p>
                                        @endif

                                        @if($routeError && !$isPublicTransport)
                                            <div class="alert alert-warning py-2 px-3 small mb-2">{{ $routeError }}</div>
                                        @endif

                                        <div class="row g-2 align-items-end">
                                            <div class="col-sm-6">
                                                <label class="form-label small text-muted mb-0">Dystans (km)</label>
                                                <input type="number" step="0.1" min="0"
                                                       wire:model.live.debounce.400ms="manualRouteDistanceKm"
                                                       class="form-control form-control-sm rounded-3"
                                                       style="background: rgba(15,23,42,0.45); border-color: rgba(148,163,184,0.35);"
                                                       placeholder="np. 343">
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="form-label small text-muted mb-0">Czas (min)</label>
                                                <input type="number" step="1" min="1"
                                                       wire:model.live.debounce.400ms="manualRouteDurationMinutes"
                                                       class="form-control form-control-sm rounded-3"
                                                       style="background: rgba(15,23,42,0.45); border-color: rgba(148,163,184,0.35);"
                                                       placeholder="np. 225">
                                            </div>
                                        </div>
                                        <div class="row g-2 mt-0">
                                            <div class="col-12">
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-info w-100 mt-2"
                                                        wire:click="applyManualRouteDistance"
                                                        wire:loading.attr="disabled">
                                                    <i class="bi bi-check2 me-1"></i>Zatwierdź dystans i czas
                                                </button>
                                            </div>
                                        </div>

                                        @if($this->routeBlockIncomplete)
                                            <div class="small text-danger mt-2">
                                                <i class="bi bi-exclamation-circle me-1"></i>
                                                Ustal dystans i czas: <strong>Przelicz trasę</strong> lub wpisz km i minuty, potem <strong>Zatwierdź</strong>.
                                            </div>
                                        @endif
                                    </div>
                                </x-slot>
                            </x-logistics.route-waypoints-plan>
                        </div>

                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-outline-light" wire:click="closeOwnRouteModal">Anuluj</button>
                            <button type="button" class="btn btn-primary" wire:click="closeOwnRouteModal">Zapisz i zamknij</button>
                        </div>

                    </div>
                </div>
            </div>
        @endteleport
    @endif

    @endif{{-- end @if($isPublicTransport) --}}

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
