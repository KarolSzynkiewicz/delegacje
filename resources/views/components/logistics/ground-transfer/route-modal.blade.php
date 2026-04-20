{{-- Modal: trasa na lotnisko startowe (po zatwierdzeniu konfiguracji środka) --}}
@if($showPreTransferRouteModal && $isPublicTransport && $transferToAirportLegKind === 'own')
    <div class="modal-portal-to-body" wire:key="step4-pre-transfer-route-modal-root">
        <div class="modal-backdrop fade show"></div>
        <div class="modal fade show employee-assignment-modal" style="display: block;" tabindex="-1" role="dialog" aria-modal="true"
             aria-labelledby="preTransferRouteModalTitle">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content border-secondary" style="background: var(--bg-card, #1e293b); color: #e2e8f0;">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title" id="preTransferRouteModalTitle">
                            <i class="bi bi-signpost-split text-info me-2"></i>Konfiguruj trasę
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closePreTransferRouteModal" aria-label="Zamknij"></button>
                    </div>
                    <div class="modal-body">
                        @if($transferToAirportGroundMode === 'other')
                            <div class="rounded-2 p-3 mb-3" style="background: rgba(14,165,233,0.06); border: 1px solid rgba(14,165,233,0.25);">
                                <div class="small fw-semibold mb-2"><i class="bi bi-train-front me-1"></i> Odcinek (kolej / komunikacja)</div>
                                <p class="small text-muted mb-2" style="font-size: 0.75rem;">Wpisywane w <strong>Konfiguruj transfer</strong>: dworzec startowy i docelowy. Poniżej tylko szacunek km i czasu — bez mapy.</p>
                                <div class="small rounded-2 px-3 py-2"
                                     style="background: rgba(15,23,42,0.45); border: 1px solid rgba(148,163,184,0.25);">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <span class="text-muted">Dworzec startowy</span>
                                        <span class="fw-semibold text-white">{{ trim((string)($preTransferPublicStationStart ?? '')) !== '' ? $preTransferPublicStationStart : '—' }}</span>
                                        <span class="text-muted">→</span>
                                        <span class="text-muted">Dworzec docelowy</span>
                                        <span class="fw-semibold text-white">{{ trim((string)($preTransferPublicStationEnd ?? '')) !== '' ? $preTransferPublicStationEnd : '—' }}</span>
                                    </div>
                                </div>
                            </div>

                            <p class="small text-muted mb-2">Bez trasy z mapy — wpisz dystans i czas tego odcinka (np. między dworcami).</p>
                            @if(!empty($preRouteError))
                                <div class="alert alert-danger py-2 small mb-3">{{ $preRouteError }}</div>
                            @endif
                            <div class="p-3 border rounded bg-secondary bg-opacity-10 mb-0 @if($this->routePreBlockIncomplete) border-danger @endif">
                                <div class="row g-2 align-items-end">
                                    <div class="col-sm-5">
                                        <label class="form-label small text-muted mb-0">Dystans (km)</label>
                                        <input type="number" step="0.1" min="0" wire:model.lazy="preManualRouteDistanceKm" class="form-control form-control-sm" placeholder="np. 42">
                                    </div>
                                    <div class="col-sm-5">
                                        <label class="form-label small text-muted mb-0">Czas (min)</label>
                                        <input type="number" step="1" min="1" wire:model.lazy="preManualRouteDurationMinutes" class="form-control form-control-sm" placeholder="np. 55">
                                    </div>
                                    <div class="col-sm-2">
                                        <button type="button" class="btn btn-sm btn-outline-info w-100" wire:click="applyManualRouteDistance('pre')" wire:loading.attr="disabled">OK</button>
                                    </div>
                                </div>
                                @if($preRouteData)
                                    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                        <span class="small text-muted mb-0" style="font-size: 0.72rem;">Zapisane:</span>
                                        <x-logistics.route-metrics-saved-pill
                                            accent="info"
                                            :distance-km="isset($preRouteData['distance']) ? (float) $preRouteData['distance'] : null"
                                            :duration-seconds="isset($preRouteData['duration']) && $preRouteData['duration'] !== null ? (int) $preRouteData['duration'] : null"
                                        />
                                    </div>
                                @endif
                            </div>
                        @else
                            <p class="small text-muted mb-2" style="font-size: 0.78rem;">
                                <strong>Lotnisko startowe</strong> (z nagłówka) jest na liście jak zwykły przystanek — możesz zmienić kolejność (np. baza → mieszkania → lotnisko → powrót do bazy).
                                Lotniska nie usuwamy; <strong>bazę</strong> możesz usunąć z kolejności, jeśli start jest spoza bazy.
                            </p>

                            <div class="rounded-2 p-3 mb-3" style="background: rgba(14,165,233,0.06); border: 1px solid rgba(14,165,233,0.25);">
                                <div class="small fw-semibold mb-2"><i class="bi bi-signpost-split me-1"></i> Przystanki w kolejności</div>
                                @include('livewire.steps.partials.step4-pre-transfer-route-tiles-editable')
                            </div>

                            @if(!$transferToAirportStartsFromBase)
                                <div class="mb-3">
                                    <button type="button" class="btn btn-sm btn-link text-info text-decoration-none p-0" wire:click="restorePreTransferBase">
                                        <i class="bi bi-plus-circle me-1"></i> Przywróć bazę na początku trasy
                                    </button>
                                </div>
                            @endif

                            <button type="button" class="btn btn-sm btn-outline-primary w-100 mb-3" wire:click="planPreAirportRoute" wire:loading.attr="disabled" wire:target="planPreAirportRoute">
                                <span wire:loading.remove wire:target="planPreAirportRoute"><i class="bi bi-arrow-repeat me-1"></i> Przelicz trasę (ORS)</span>
                                <span wire:loading wire:target="planPreAirportRoute"><span class="spinner-border spinner-border-sm"></span> Przeliczam…</span>
                            </button>

                            {{-- Wynik ORS / błąd / wpis ręczny — zawsze w tym oknie, pod przyciskiem przelicz --}}
                            <div class="rounded-2 border p-3 mb-0 @if($this->routePreBlockIncomplete) border-danger @endif"
                                 style="background: rgba(15,23,42,0.5); border-color: rgba(148,163,184,0.35) !important;">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                    <div class="small fw-semibold mb-0">Dystans i czas (ten odcinek)</div>
                                    @if($preRouteData)
                                        @if($isManualPreRouteDistance)
                                            <span class="badge rounded-pill" style="font-size: 0.65rem; background: rgba(251,191,36,0.15); color: #fcd34d;">Wpisane ręcznie</span>
                                        @else
                                            <span class="badge rounded-pill" style="font-size: 0.65rem; background: rgba(59,130,246,0.15); color: #93c5fd;">Szacunek ORS</span>
                                        @endif
                                    @endif
                                </div>

                                @if(!empty($preRouteError))
                                    <div class="alert alert-danger py-2 small mb-3">{{ $preRouteError }}</div>
                                @elseif(!$preRouteData)
                                    <div class="small text-warning mb-3 py-1 px-2 rounded" style="background: rgba(251,191,36,0.08); font-size: 0.78rem;">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Brak zapisanego wyniku — użyj <strong>Przelicz trasę (ORS)</strong> albo wpisz km i czas poniżej i zatwierdź <strong>OK</strong>.
                                    </div>
                                @endif

                                <div class="row g-2 align-items-end">
                                    <div class="col-sm-5">
                                        <label class="form-label small text-muted mb-0">Dystans (km)</label>
                                        <input type="number" step="0.1" min="0" wire:model.lazy="preManualRouteDistanceKm" class="form-control form-control-sm" placeholder="np. 42">
                                    </div>
                                    <div class="col-sm-5">
                                        <label class="form-label small text-muted mb-0">Czas (min)</label>
                                        <input type="number" step="1" min="1" wire:model.lazy="preManualRouteDurationMinutes" class="form-control form-control-sm" placeholder="np. 55">
                                    </div>
                                    <div class="col-sm-2">
                                        <button type="button" class="btn btn-sm btn-outline-info w-100" wire:click="applyManualRouteDistance('pre')" wire:loading.attr="disabled">OK</button>
                                    </div>
                                </div>

                                @if($preRouteData)
                                    <div class="small text-muted mt-2 mb-0" style="font-size: 0.72rem;">
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <span>Aktualnie w planie:</span>
                                            <x-logistics.route-metrics-saved-pill
                                                accent="info"
                                                :distance-km="isset($preRouteData['distance']) ? (float) $preRouteData['distance'] : null"
                                                :duration-seconds="isset($preRouteData['duration']) && $preRouteData['duration'] !== null ? (int) $preRouteData['duration'] : null"
                                                :stop-count="count($transferToAirportWaypoints ?? [])"
                                                stop-word-set="route_points"
                                            />
                                        </div>
                                        <span class="d-inline-block mt-1">— możesz zmienić pola i zatwierdzić <strong>OK</strong> (wtedy liczone jako ręczne).</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer border-secondary flex-column align-items-stretch gap-2">
                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                            <button type="button" class="btn btn-outline-light" wire:click="closePreTransferRouteModal">Anuluj</button>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-info" wire:click="savePreTransferRouteModal" wire:loading.attr="disabled"
                                        title="Zapisuje kolejność przystanków i km/czas do planu wyjazdu; okno zostaje otwarte.">
                                    <i class="bi bi-floppy me-1"></i>Zapisz
                                </button>
                                <button type="button" class="btn btn-primary" wire:click="confirmPreTransferRouteModal" wire:loading.attr="disabled"
                                        title="To samo co Zapisz, potem zamyka to okno.">
                                    Zapisz i zamknij
                                </button>
                            </div>
                        </div>
                        <p class="small text-muted mb-0" style="font-size: 0.72rem;">
                            <strong>Zapisz</strong> — utrwala trasę w kroku 4, możesz jeszcze poprawiać.
                            <strong>Zapisz i zamknij</strong> — utrwala i wraca do widoku kart.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
