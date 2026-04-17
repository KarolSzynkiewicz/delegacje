{{-- Modal: bilety + dystans / trasa lotnisko docelowe → domy (wspólny wzorzec z pre-transfer route modal). --}}
@if($showPostTransferRouteModal && $isPublicTransport && $postAirportTransferUserEnabled)
    @php
        $effModal = $effectiveTransferFromAirportLegKind ?? 'public';
    @endphp
    <div class="modal-portal-to-body" wire:key="step4-post-transfer-route-modal-root">
        <div class="modal-backdrop fade show"></div>
        <div class="modal fade show employee-assignment-modal" style="display: block;" tabindex="-1" role="dialog" aria-modal="true"
             aria-labelledby="postTransferRouteModalTitle">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content border-secondary" style="background: var(--bg-card, #1e293b); color: #e2e8f0;">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title" id="postTransferRouteModalTitle">
                            <i class="bi bi-signpost-split text-success me-2"></i>
                            @if($effModal === 'public')
                                Transfer z lotniska — bilety i dystans
                            @elseif(($transferFromAirportGroundMode ?? 'car') === 'car')
                                Trasa: lotnisko docelowe → domy
                            @else
                                Szacunek odcinka (inny transport)
                            @endif
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closePostTransferRouteModal" aria-label="Zamknij"></button>
                    </div>
                    <div class="modal-body">
                        @if($effModal === 'public')
                            @if($selectedEmployeesForTickets->isNotEmpty())
                                <x-logistics.public-transport-tickets
                                    variant="cards"
                                    section-title="Bilety (ten odcinek)"
                                    :employees="$selectedEmployeesForTickets"
                                    :ticket-costs-by-employee="$fromAirportPublicTicketCostsByEmployee"
                                    :flat-attachment-uploads="$fromAirportTicketFiles"
                                    attachment-flat-binding-key="fromAirportTicketFiles"
                                    :tickets-incomplete="$this->fromAirportGroundTicketsIncomplete"
                                    :require-attachment="true"
                                    ticket-costs-binding-key="fromAirportPublicTicketCostsByEmployee"
                                    wire:key-prefix="seg-post-ticket-modal"
                                    class="mt-0 pt-0 border-0 mb-3"
                                    style="border-top: none !important;"
                                />
                            @else
                                <p class="small text-muted mb-3">Brak osób w składzie wyjazdu — dodaj uczestników wcześniej.</p>
                            @endif

                            <p class="small text-muted mb-3" style="font-size: 0.78rem;">
                                <i class="bi bi-info-circle me-1"></i>
                                Komunikacja zbiorowa — bez mapy przystanków; uzupełnij bilety i szacunek odcinka.
                            </p>

                            @if($routeData)
                                <div class="p-3 border rounded bg-success bg-opacity-10 @if($this->routeBlockIncomplete) border-danger @endif mb-3">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                        <div class="small @if($this->routeBlockIncomplete) text-danger fw-semibold @else text-muted @endif mb-0">Dystans transferu (lotnisko → domy)</div>
                                        @if($isManualRouteDistance)
                                            <span class="badge rounded-pill" style="font-size: 0.65rem; background: rgba(251,191,36,0.15); color: #fcd34d;">Wpisane ręcznie</span>
                                        @else
                                            <span class="badge rounded-pill" style="font-size: 0.65rem; background: rgba(59,130,246,0.15); color: #93c5fd;">Szacunek systemu</span>
                                        @endif
                                    </div>
                                    <div class="row g-2 align-items-end">
                                        <div class="col-sm-5">
                                            <label class="form-label small text-muted mb-0">Dystans (km)</label>
                                            <input type="number" step="0.1" min="0" wire:model.live.debounce.300ms="manualRouteDistanceKm" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-sm-5">
                                            <label class="form-label small text-muted mb-0">Czas przejazdu (min)</label>
                                            <input type="number" step="1" min="1" wire:model.live.debounce.300ms="manualRouteDurationMinutes" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-sm-2">
                                            <button type="button" class="btn btn-sm btn-success w-100" wire:click="applyManualRouteDistance('post')" wire:loading.attr="disabled">OK</button>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                        <span class="small text-muted mb-0" style="font-size: 0.72rem;">Zapisane:</span>
                                        <x-logistics.route-metrics-saved-pill
                                            accent="success"
                                            :distance-km="isset($routeData['distance']) ? (float) $routeData['distance'] : null"
                                            :duration-seconds="isset($routeData['duration']) && $routeData['duration'] !== null ? (int) $routeData['duration'] : null"
                                        />
                                    </div>
                                </div>
                            @endif
                            @if(empty($routeData))
                                <div class="mt-0 mb-0 p-2 border rounded @if($this->routeBlockIncomplete) border-danger @endif" style="background: var(--bg-card); @if($this->routeBlockIncomplete) box-shadow: 0 0 0 1px rgba(239,68,68,0.35); @endif">
                                    <div class="small text-muted mb-2">
                                        <i class="bi bi-exclamation-triangle me-1 text-warning"></i>
                                        Nie można obliczyć trasy automatycznie —
                                        @if(!empty($manualRouteHint))
                                            problem z lokalizacją <span class="fw-semibold">{{ $manualRouteHint }}</span>.
                                        @else
                                            wpisz dystans i czas (transport publiczny z lotniska).
                                        @endif
                                    </div>
                                    <div class="row g-2 align-items-end">
                                        <div class="col-sm-4">
                                            <label class="form-label small text-muted mb-0">Dystans (km)</label>
                                            <input type="number" step="0.1" min="0" wire:model.live.debounce.300ms="manualRouteDistanceKm" class="form-control form-control-sm" placeholder="np. 18.5">
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label small text-muted mb-0">Czas (min)</label>
                                            <input type="number" step="0.1" min="0" wire:model.live.debounce.300ms="manualRouteDurationMinutes" class="form-control form-control-sm" placeholder="np. 35">
                                        </div>
                                        <div class="col-sm-4">
                                            <button type="button" class="btn btn-sm btn-outline-success w-100" wire:click="applyManualRouteDistance" wire:loading.attr="disabled">Ustaw ręcznie</button>
                                        </div>
                                    </div>
                                    @if(!empty($this->transferGoogleMapsUrl))
                                        <div class="small mt-2">
                                            <a href="{{ $this->transferGoogleMapsUrl }}" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                                                <i class="bi bi-map me-1"></i> Google Maps
                                            </a>
                                        </div>
                                    @endif
                                    @if(!empty($routeError))
                                        <div class="alert alert-danger py-2 small mt-2 mb-0">{{ $routeError }}</div>
                                    @endif
                                </div>
                            @endif

                        @elseif($effModal === 'own' && ($transferFromAirportGroundMode ?? 'car') === 'other')
                            <p class="small text-muted mb-3" style="font-size: 0.78rem;">
                                <i class="bi bi-info-circle me-1"></i>
                                Bez mapy przystanków — ustal szacunek km i czasu dla dojazdu z lotniska.
                            </p>

                            @if($routeData)
                                <div class="p-3 border rounded bg-success bg-opacity-10 @if($this->routeBlockIncomplete) border-danger @endif mb-3">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                        <div class="small @if($this->routeBlockIncomplete) text-danger fw-semibold @else text-muted @endif mb-0">Dystans transferu (lotnisko → domy)</div>
                                        @if($isManualRouteDistance)
                                            <span class="badge rounded-pill" style="font-size: 0.65rem; background: rgba(251,191,36,0.15); color: #fcd34d;">Wpisane ręcznie</span>
                                        @else
                                            <span class="badge rounded-pill" style="font-size: 0.65rem; background: rgba(59,130,246,0.15); color: #93c5fd;">Szacunek</span>
                                        @endif
                                    </div>
                                    <div class="row g-2 align-items-end">
                                        <div class="col-sm-5">
                                            <label class="form-label small text-muted mb-0">Dystans (km)</label>
                                            <input type="number" step="0.1" min="0" wire:model.live.debounce.300ms="manualRouteDistanceKm" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-sm-5">
                                            <label class="form-label small text-muted mb-0">Czas (min)</label>
                                            <input type="number" step="1" min="1" wire:model.live.debounce.300ms="manualRouteDurationMinutes" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-sm-2">
                                            <button type="button" class="btn btn-sm btn-success w-100" wire:click="applyManualRouteDistance('post')" wire:loading.attr="disabled">OK</button>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                        <span class="small text-muted mb-0" style="font-size: 0.72rem;">Zapisane:</span>
                                        <x-logistics.route-metrics-saved-pill
                                            accent="success"
                                            :distance-km="isset($routeData['distance']) ? (float) $routeData['distance'] : null"
                                            :duration-seconds="isset($routeData['duration']) && $routeData['duration'] !== null ? (int) $routeData['duration'] : null"
                                        />
                                    </div>
                                </div>
                            @endif
                            @if(empty($routeData))
                                <div class="mt-0 mb-0 p-2 border rounded @if($this->routeBlockIncomplete) border-danger @endif" style="background: var(--bg-card); @if($this->routeBlockIncomplete) box-shadow: 0 0 0 1px rgba(239,68,68,0.35); @endif">
                                    <div class="small text-muted mb-2">
                                        <i class="bi bi-exclamation-triangle me-1 text-warning"></i>
                                        Wpisz dystans i czas (inny transport z lotniska) —
                                        @if(!empty($manualRouteHint))
                                            Uwaga: <span class="fw-semibold">{{ $manualRouteHint }}</span>.
                                        @endif
                                    </div>
                                    <div class="row g-2 align-items-end">
                                        <div class="col-sm-4">
                                            <label class="form-label small text-muted mb-0">Dystans (km)</label>
                                            <input type="number" step="0.1" min="0" wire:model.live.debounce.300ms="manualRouteDistanceKm" class="form-control form-control-sm" placeholder="np. 18.5">
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label small text-muted mb-0">Czas (min)</label>
                                            <input type="number" step="0.1" min="0" wire:model.live.debounce.300ms="manualRouteDurationMinutes" class="form-control form-control-sm" placeholder="np. 35">
                                        </div>
                                        <div class="col-sm-4">
                                            <button type="button" class="btn btn-sm btn-outline-success w-100" wire:click="applyManualRouteDistance" wire:loading.attr="disabled">Ustaw ręcznie</button>
                                        </div>
                                    </div>
                                    @if(!empty($this->transferGoogleMapsUrl))
                                        <div class="small mt-2">
                                            <a href="{{ $this->transferGoogleMapsUrl }}" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                                                <i class="bi bi-map me-1"></i> Google Maps
                                            </a>
                                        </div>
                                    @endif
                                    @if(!empty($routeError))
                                        <div class="alert alert-danger py-2 small mt-2 mb-0">{{ $routeError }}</div>
                                    @endif
                                </div>
                            @endif

                        @else
                            {{-- own + car --}}
                            @include('livewire.steps.partials.step4-card3-post-transfer-waypoints')

                            <button type="button" class="btn btn-sm btn-outline-primary w-100 mb-3" wire:click="planRoute" wire:loading.attr="disabled" wire:target="planRoute">
                                <span wire:loading.remove wire:target="planRoute"><i class="bi bi-arrow-repeat me-1"></i> Przelicz trasę (lotnisko docelowe → domy)</span>
                                <span wire:loading wire:target="planRoute"><span class="spinner-border spinner-border-sm"></span></span>
                            </button>
                            @if($routeData)
                                <div class="p-3 border rounded bg-success bg-opacity-10 @if($this->routeBlockIncomplete) border-danger @endif mb-3">
                                    <div class="small @if($this->routeBlockIncomplete) text-danger fw-semibold @else text-muted @endif mb-2">Dystans: lotnisko docelowe → przystanki</div>
                                    <div class="row g-2 align-items-end">
                                        <div class="col-sm-5">
                                            <label class="form-label small text-muted mb-0">Dystans (km)</label>
                                            <input type="number" step="0.1" min="0" wire:model.live.debounce.300ms="manualRouteDistanceKm" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-sm-5">
                                            <label class="form-label small text-muted mb-0">Czas (min)</label>
                                            <input type="number" step="1" min="1" wire:model.live.debounce.300ms="manualRouteDurationMinutes" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-sm-2">
                                            <button type="button" class="btn btn-sm btn-success w-100" wire:click="applyManualRouteDistance('post')" wire:loading.attr="disabled">OK</button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(empty($routeData))
                                @if(!empty($routeError))
                                    <div class="alert alert-danger py-2 small mb-3">{{ $routeError }}</div>
                                @endif
                                @if(!empty($manualRouteHint))
                                    <p class="small text-muted mb-2">
                                        <i class="bi bi-exclamation-triangle me-1 text-warning"></i>
                                        Problem z lokalizacją: <span class="fw-semibold">{{ $manualRouteHint }}</span>.
                                    </p>
                                @endif
                                @if(!empty($this->transferGoogleMapsUrl))
                                    <div class="small mb-3">
                                        <a href="{{ $this->transferGoogleMapsUrl }}" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                                            <i class="bi bi-map me-1"></i> Google Maps
                                        </a>
                                    </div>
                                @endif
                                <div class="p-3 border rounded bg-success bg-opacity-10 @if($this->routeBlockIncomplete) border-danger @endif mb-0">
                                    <div class="small text-muted mb-2">Dystans i czas (ręcznie)</div>
                                    <div class="row g-2 align-items-end">
                                        <div class="col-sm-4">
                                            <label class="form-label small text-muted mb-0">Dystans (km)</label>
                                            <input type="number" step="0.1" min="0" wire:model.live.debounce.300ms="manualRouteDistanceKm" class="form-control form-control-sm" placeholder="np. 18,5">
                                        </div>
                                        <div class="col-sm-4">
                                            <label class="form-label small text-muted mb-0">Czas (min)</label>
                                            <input type="number" step="1" min="0" wire:model.live.debounce.300ms="manualRouteDurationMinutes" class="form-control form-control-sm" placeholder="np. 35">
                                        </div>
                                        <div class="col-sm-4">
                                            <button type="button" class="btn btn-sm btn-outline-success w-100" wire:click="applyManualRouteDistance" wire:loading.attr="disabled">Ustaw ręcznie</button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                    <div class="modal-footer border-secondary flex-column align-items-stretch gap-2">
                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                            <button type="button" class="btn btn-outline-light" wire:click="closePostTransferRouteModal">Anuluj</button>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-success" wire:click="savePostTransferRouteModal" wire:loading.attr="disabled">
                                    <i class="bi bi-floppy me-1"></i>Zapisz
                                </button>
                                <button type="button" class="btn btn-success" wire:click="confirmPostTransferRouteModal" wire:loading.attr="disabled">
                                    Zapisz i zamknij
                                </button>
                            </div>
                        </div>
                        <p class="small text-muted mb-0" style="font-size: 0.72rem;">
                            <strong>Zapisz</strong> — utrwala trasę w kroku 4. <strong>Zapisz i zamknij</strong> — zamyka to okno.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
