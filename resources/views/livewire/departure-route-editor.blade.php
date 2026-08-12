<div>
    @if($this->canEdit)
        <button type="button"
                class="btn btn-sm btn-outline-info"
                wire:click="openModal"
                wire:loading.attr="disabled">
            <i class="bi bi-signpost-split me-1"></i>Edytuj trasę
        </button>
    @endif

    @if($showModal)
        @teleport('body')
            <div class="modal fade show d-block departure-planner-teleport-modal" tabindex="-1" role="dialog" aria-modal="true"
                 style="background-color: rgba(0,0,0,0.55);"
                 wire:key="departure-route-editor-modal-{{ $departureId }}">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable my-3" role="document">
                    <div class="modal-content border-secondary" style="background: var(--bg-card, #1e293b); color: #e2e8f0; max-height: min(90vh, 960px);">
                        <div class="modal-header border-secondary">
                            <h5 class="modal-title">
                                <i class="bi bi-signpost-split text-info me-2"></i>
                                Edycja przebiegu trasy — wyjazd #{{ $departureId }}
                            </h5>
                            <button type="button" class="btn-close btn-close-white" wire:click="closeModal" aria-label="Zamknij"></button>
                        </div>

                        <div class="modal-body">
                            <p class="small text-muted mb-3" style="line-height: 1.45;">
                                Zmiana dotyczy tylko kolejności przystanków, notatek oraz dystansu/czasu.
                                Przypisania uczestników (projekt / auto / mieszkanie) pozostają bez zmian.
                            </p>

                            @if($saveError)
                                <div class="alert alert-danger py-2 small mb-3">{{ $saveError }}</div>
                            @endif

                            <x-logistics.route-waypoints-plan
                                class="rtp-card rounded-3 p-0"
                                title="Plan trasy"
                                :stops="$this->routeTiles"
                                wire-key-prefix="dep-rwp-{{ $departureId }}"
                                :available-locations="$this->availableLocations"
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
                                                wire:click="recalculateRouteWithOrs"
                                                wire:loading.attr="disabled"
                                                wire:target="recalculateRouteWithOrs"
                                            >
                                                <span wire:loading.remove wire:target="recalculateRouteWithOrs"><i class="bi bi-arrow-clockwise"></i></span>
                                                <span wire:loading wire:target="recalculateRouteWithOrs"><span class="spinner-border spinner-border-sm" style="width: 0.9rem; height: 0.9rem;"></span></span>
                                            </x-ui.button>
                                        </div>

                                        @if($routeDistanceKm !== null)
                                            <div class="small mb-3" style="color: #94a3b8;">
                                                <span class="text-white fw-semibold">{{ number_format((float) $routeDistanceKm, 1, ',', ' ') }} km</span>
                                                @if($routeDurationSeconds !== null)
                                                    <span class="mx-1 opacity-50">·</span>
                                                    <span class="text-white fw-semibold">
                                                        {{ intdiv((int) $routeDurationSeconds, 3600) > 0 ? intdiv((int) $routeDurationSeconds, 3600).'h ' : '' }}{{ intdiv((int) $routeDurationSeconds % 3600, 60) }} min
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <p class="small text-muted mb-3 mb-lg-2">Brak wyznaczonej trasy — użyj ORS lub wpisz wartości ręcznie.</p>
                                        @endif

                                        @if($orsError)
                                            <div class="alert alert-warning py-2 px-3 small mb-2">{{ $orsError }}</div>
                                        @endif

                                        <div class="row g-2 align-items-end">
                                            <div class="col-sm-6">
                                                <label class="form-label small text-muted mb-0">Dystans (km)</label>
                                                <input type="number" step="0.1" min="0"
                                                       wire:model.live.debounce.400ms="manualDistanceKm"
                                                       class="form-control form-control-sm rounded-3"
                                                       style="background: rgba(15,23,42,0.45); border-color: rgba(148,163,184,0.35);"
                                                       placeholder="np. 343">
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="form-label small text-muted mb-0">Czas (min)</label>
                                                <input type="number" step="1" min="1"
                                                       wire:model.live.debounce.400ms="manualDurationMin"
                                                       class="form-control form-control-sm rounded-3"
                                                       style="background: rgba(15,23,42,0.45); border-color: rgba(148,163,184,0.35);"
                                                       placeholder="np. 225">
                                            </div>
                                        </div>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-info w-100 mt-2"
                                                wire:click="applyManualMetrics"
                                                wire:loading.attr="disabled">
                                            <i class="bi bi-check2 me-1"></i>Zatwierdź dystans i czas
                                        </button>
                                    </div>
                                </x-slot>
                            </x-logistics.route-waypoints-plan>
                        </div>

                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-outline-light" wire:click="closeModal">Anuluj</button>
                            <button type="button" class="btn btn-primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save"><i class="bi bi-check2 me-1"></i>Zapisz trasę</span>
                                <span wire:loading wire:target="save"><span class="spinner-border spinner-border-sm me-1"></span>Zapisuję…</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>
