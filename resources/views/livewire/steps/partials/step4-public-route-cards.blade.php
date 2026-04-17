{{-- Trzy karty: transfer na lotnisko · lotnisko · transfer z lotniska (transport publiczny) --}}
@php
    $effFrom = $effectiveTransferFromAirportLegKind ?? 'public';
@endphp

{{-- 1 — Transfer na lotnisko startowe --}}
<x-logistics.transfer-segment-card
    wire:key="step4-public-card-transfer-pre"
    index="1"
    title="Transfer na lotnisko startowe"
    accent="info"
    :needs-attention="$transferToAirportLegKind === 'own' && $this->preTransferCardNeedsAttention"
>
    <x-slot name="subtitle">
        @if($transferToAirportLegKind === 'own' && ($transferToAirportGroundMode ?? 'car') === 'other')
            @php
                $preSubA = trim((string)($preTransferPublicStationStart ?? ''));
                $preSubB = trim((string)($preTransferPublicStationEnd ?? ''));
            @endphp
            <div class="text-muted" style="font-size: 0.72rem;">
                @if($preSubA !== '' && $preSubB !== '')
                    <i class="bi bi-train-front me-1"></i>{{ $preSubA }} <span class="text-muted">→</span> {{ $preSubB }}
                @else
                    <i class="bi bi-train-front me-1"></i>Dworzec startowy → dworzec docelowy <span class="fst-italic">(uzupełnij w konfiguracji)</span>
                @endif
            </div>
        @else
            <div class="text-muted" style="font-size: 0.72rem;">Baza → (opcjonalne przystanki) → {{ $startAirportData['name'] ?? 'lotnisko' }}</div>
        @endif
    </x-slot>
    <x-slot name="aside">
        @if($transferToAirportLegKind)
            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeTransferToAirportCard" wire:loading.attr="disabled">Usuń</button>
        @endif
    </x-slot>

    @if(!$transferToAirportLegKind)
        <x-logistics.transfer-segment-empty-add>
            <x-slot name="hint">Opcjonalny odcinek przed lotem.</x-slot>
            <button type="button" wire:click="addTransferToAirportCard" wire:loading.attr="disabled"
                    class="btn btn-sm btn-outline-info">
                <i class="bi bi-plus-lg me-1"></i> Dodaj <span class="text-muted fw-normal">(opcjonalnie)</span>
            </button>
        </x-logistics.transfer-segment-empty-add>
    @else
        @if($transferToAirportLegKind === 'public')
            <div class="mb-2">
                <span class="badge rounded-pill" style="font-size: 0.68rem; background: rgba(14,165,233,0.12); color: #7dd3fc;">
                    Transport publiczny
                </span>
            </div>
            @if($selectedEmployeesForTickets->isNotEmpty())
                <x-logistics.public-transport-tickets
                    variant="cards"
                    section-title="Bilety (ten odcinek)"
                    :employees="$selectedEmployeesForTickets"
                    :ticket-costs-by-employee="$toAirportPublicTicketCostsByEmployee"
                    :flat-attachment-uploads="$toAirportTicketFiles"
                    attachment-flat-binding-key="toAirportTicketFiles"
                    :tickets-incomplete="$this->toAirportGroundTicketsIncomplete"
                    :require-attachment="true"
                    ticket-costs-binding-key="toAirportPublicTicketCostsByEmployee"
                    wire:key-prefix="seg-pre-ticket"
                    class="mt-0 pt-0 border-0"
                    style="border-top: none !important;"
                />
            @else
                <p class="small text-muted mb-0">Brak osób w składzie wyjazdu — dodaj uczestników wcześniej.</p>
            @endif
        @else
            @php
                $preOwnCompact = ($preTransferCarSectionCollapsed && $transferToAirportGroundMode === 'car')
                    || ($preTransferOtherSectionCollapsed && $transferToAirportGroundMode === 'other');
            @endphp
            @if($preOwnCompact)
                <x-logistics.transfer-segment-compact-summary>
                    <x-slot name="badge">
                        @if($preTransferCarSectionCollapsed && $transferToAirportGroundMode === 'car')
                            @php
                                $pvSummary = $availableVehicles->firstWhere('id', $preTransferVehicleId);
                                $drSummary = $availableEmployees->firstWhere('id', $preTransferDriverEmployeeId);
                            @endphp
                            <span class="badge rounded-pill text-start" style="font-size: 0.72rem; background: rgba(14,165,233,0.1); color: #bae6fd; line-height: 1.45;">
                                <i class="bi bi-car-front me-1"></i>
                                @if($pvSummary)
                                    <span class="fw-semibold">{{ $pvSummary->registration_number }}</span>
                                    <span class="text-muted fw-normal">· {{ $pvSummary->brand }} {{ $pvSummary->model }}</span>
                                @else
                                    —
                                @endif
                                @if($drSummary)
                                    <span class="text-muted"> · </span>{{ $drSummary->full_name }}
                                @endif
                                @if($preTransferDriverBonusAmount !== null && $preTransferDriverBonusAmount !== '')
                                    <span class="text-muted"> · </span><span class="fw-semibold">{{ $preTransferDriverBonusAmount }} {{ $preTransferDriverBonusCurrency ?? 'PLN' }}</span>
                                @endif
                            </span>
                        @else
                            @php
                                $nPreTk = $selectedEmployeesForTickets->count();
                                $preSumA = trim((string)($preTransferPublicStationStart ?? ''));
                                $preSumB = trim((string)($preTransferPublicStationEnd ?? ''));
                            @endphp
                            <span class="badge rounded-pill text-start" style="font-size: 0.72rem; background: rgba(99,102,241,0.18); color: #c7d2fe; line-height: 1.45; max-width: 100%;">
                                <i class="bi bi-train-front me-1"></i>
                                @if($preSumA !== '' && $preSumB !== '')
                                    <span class="fw-semibold">{{ $preSumA }}</span>
                                    <span class="text-muted"> → </span>
                                    <span class="fw-semibold">{{ $preSumB }}</span>
                                @else
                                    <span class="fst-italic">Dworce nie uzupełnione</span>
                                @endif
                                <span class="text-muted"> · </span>
                                {{ $nPreTk }} {{ $nPreTk === 1 ? 'bilet' : (($nPreTk > 1 && $nPreTk < 5) ? 'bilety' : 'biletów') }}
                            </span>
                        @endif
                    </x-slot>
                    @if($preTransferCarSectionCollapsed && $transferToAirportGroundMode === 'car')
                        <x-slot name="trail">
                            @php
                                $preTrailKm = is_array($preRouteData) && isset($preRouteData['distance']) && (float) $preRouteData['distance'] > 0
                                    ? (float) $preRouteData['distance']
                                    : null;
                                /** Wszystkie punkty odcinka w kolejności: baza, loc:…, lotnisko (sap) — nie tylko opcjonalne loc. */
                                $preRoutePointCount = count($transferToAirportWaypoints ?? []);
                            @endphp
                            <span class="badge rounded-pill text-start" style="font-size: 0.72rem; background: rgba(14,165,233,0.1); color: #bae6fd; line-height: 1.45;">
                                <i class="bi bi-signpost-2 me-1"></i>
                                @if($preTrailKm !== null && $preRoutePointCount > 0)
                                    <span class="fw-semibold">{{ number_format($preTrailKm, 1, ',', '') }} km</span>
                                    <span class="text-muted"> · </span>
                                    {{ $preRoutePointCount }}
                                    @if($preRoutePointCount === 1)
                                        punkt trasy
                                    @elseif($preRoutePointCount >= 2 && $preRoutePointCount <= 4)
                                        punkty trasy
                                    @else
                                        punktów trasy
                                    @endif
                                @elseif($preTrailKm !== null)
                                    <span class="fw-semibold">{{ number_format($preTrailKm, 1, ',', '') }} km</span>
                                @elseif($preRoutePointCount > 0)
                                    {{ $preRoutePointCount }}
                                    @if($preRoutePointCount === 1)
                                        punkt trasy
                                    @elseif($preRoutePointCount >= 2 && $preRoutePointCount <= 4)
                                        punkty trasy
                                    @else
                                        punktów trasy
                                    @endif
                                @else
                                    <span class="fst-italic text-muted">Brak trasy</span>
                                @endif
                            </span>
                        </x-slot>
                    @endif
                    <x-slot name="edit">
                        <button type="button" class="btn btn-sm btn-outline-light border-opacity-25 flex-shrink-0" wire:click="openPreTransferConfigModal" title="Edytuj konfigurację transferu">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </x-slot>
                </x-logistics.transfer-segment-compact-summary>
            @else
                <p class="small text-muted mb-2 mb-md-3">Wybierz środek (samochód lub inny transport), uzupełnij dane i zatwierdź w oknie — potem ustal trasę.</p>
            @endif

            @php
                $preCfgBtnDanger = ! $preOwnCompact
                    || ($transferToAirportGroundMode === 'car' && ($this->preTransferVehicleIncomplete || $this->preTransferDriverIncomplete || $this->preTransferBonusIncomplete))
                    || ($transferToAirportGroundMode === 'other' && (
                        trim((string) ($preTransferPublicStationStart ?? '')) === ''
                        || trim((string) ($preTransferPublicStationEnd ?? '')) === ''
                        || $this->toAirportGroundTicketsIncomplete
                    ));
                $preRouteBtnDanger = $this->routePreBlockIncomplete;
            @endphp
            <x-logistics.transfer-segment-action-row>
                <button type="button"
                        class="btn btn-sm d-inline-flex align-items-center {{ $preCfgBtnDanger ? 'btn-danger' : 'btn-outline-info' }}"
                        wire:click="openPreTransferConfigModal" wire:loading.attr="disabled">
                    <i class="bi bi-sliders me-1"></i> Konfiguruj transfer
                </button>
                @if($preOwnCompact)
                    <button type="button"
                            class="btn btn-sm d-inline-flex align-items-center {{ $preRouteBtnDanger ? 'btn-danger' : 'btn-outline-primary' }}"
                            wire:click="openPreTransferRouteModal" wire:loading.attr="disabled">
                        <i class="bi bi-signpost-split me-1"></i> Konfiguruj trasę
                    </button>
                @endif
            </x-logistics.transfer-segment-action-row>
        @endif
    @endif

    {{-- Zawsze w drzewie (warunek widoczności wewnątrz partiali) — inaczej po „Dodaj” (legKind=null) modal w ogóle się nie renderuje. --}}
    @include('livewire.steps.partials.step4-pre-transfer-config-modal')
    @include('livewire.steps.partials.step4-pre-transfer-route-modal')
</x-logistics.transfer-segment-card>

{{-- 2 — Odcinek lotu / przesiadki (tylko skąd → dokąd) --}}
<div class="rtp-card rounded-3 p-3 mb-3" style="background: var(--bg-card); border: 1px solid rgba(59,130,246,0.28);" wire:key="step4-public-card-flight">
    <div class="d-flex align-items-center gap-2 mb-2">
        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
             style="width: 32px; height: 32px; font-size: 0.85rem; background: rgba(59,130,246,0.35); flex-shrink: 0;">2</div>
        <div>
            <h6 class="mb-0 fw-semibold" style="font-size: 0.92rem;">
                @if(($publicTransportHubKind ?? null) === 'station')
                    Przesiadki (dworzec)
                @else
                    Lot
                @endif
            </h6>
            <div class="text-muted" style="font-size: 0.72rem;">Jak ustawiłeś w nagłówku wyjazdu</div>
        </div>
    </div>
    @if(!empty($startAirportData) && !empty($endAirportData))
        <div class="fw-semibold mb-1" style="font-size: 1rem;">
            @if(($publicTransportHubKind ?? null) === 'station')
                <i class="bi bi-train-front text-primary me-1"></i>
            @else
                <i class="bi bi-airplane text-primary me-1"></i>
            @endif
            {{ $startAirportData['name'] }} → {{ $endAirportData['name'] }}
        </div>
        <div class="small text-muted" style="font-size: 0.78rem;">Bilety na ten odcinek: sekcja w nagłówku (powyżej kroku 4).</div>
    @else
        <div class="small text-warning">Wybierz punkt startowy i docelowy we wcześniejszym kroku.</div>
    @endif
</div>

{{-- 3 — Transfer z lotniska docelowego --}}
<x-logistics.transfer-segment-card
    wire:key="step4-public-card-transfer-post"
    index="3"
    title="Transfer z lotniska docelowego"
    accent="success"
    :needs-attention="$this->postTransferCardNeedsAttention"
>
    <x-slot name="subtitle">
        <div class="text-muted" style="font-size: 0.72rem;">{{ $endAirportData['name'] ?? 'Lotnisko' }} → przystanki</div>
    </x-slot>
    <x-slot name="aside">
        @if($postAirportTransferUserEnabled)
            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeTransferFromAirportCard" wire:loading.attr="disabled" title="Usuń planowany transfer z lotniska do mieszkań (odcinek opcjonalny)">
                Usuń
            </button>
        @endif
    </x-slot>

    @if($transferFromAirportModePickerOpen)
        <x-logistics.transfer-segment-mode-picker intro="Środek transportu po przylocie (osobno od innych odcinków):">
            <button type="button" class="btn btn-sm btn-outline-light" wire:click="selectTransferFromAirportLegKind('public')">
                <i class="bi bi-people me-1"></i> Transport publiczny
            </button>
            <button type="button" class="btn btn-sm btn-outline-success" wire:click="selectTransferFromAirportLegKind('own')">
                <i class="bi bi-car-front me-1"></i> Autem
            </button>
            <button type="button" class="btn btn-link btn-sm text-muted py-0" wire:click="cancelTransferFromAirportPicker">Anuluj</button>
        </x-logistics.transfer-segment-mode-picker>
    @elseif(!$postAirportTransferUserEnabled || ($transferFromAirportLegKind === null && count($waypointStops) === 0))
        <x-logistics.transfer-segment-empty-add>
            <x-slot name="hint">
                @if(!$postAirportTransferUserEnabled)
                    Opcjonalny odcinek po locie — dodaj, jeśli organizujesz dojazd z lotniska do mieszkań (np. gdy lotnisko jest daleko). Możesz też pominąć, jeśli uczestnicy dojadą sami.
                @else
                    Brak przystanków — przypisz mieszkania w kroku 2 lub dodaj odcinek.
                @endif
            </x-slot>
            <button type="button" wire:click="addTransferFromAirportCard" class="btn btn-sm btn-outline-success">
                <i class="bi bi-plus-lg me-1"></i> Dodaj <span class="text-muted fw-normal">(opcjonalnie)</span>
            </button>
        </x-logistics.transfer-segment-empty-add>
    @else
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="badge rounded-pill" style="font-size: 0.68rem; background: rgba(34,197,94,0.12); color: #86efac;">
                @if($effFrom === 'public')
                    Transport publiczny
                @elseif($transferFromAirportGroundMode === 'other')
                    Inny transport
                @else
                    Autem
                @endif
            </span>
            <button type="button" class="btn btn-link btn-sm py-0 text-muted" wire:click="addTransferFromAirportCard">Zmień środek</button>
        </div>

        @if($effFrom === 'public')
            <p class="small text-muted mb-3" style="font-size: 0.78rem;">
                <i class="bi bi-info-circle me-1"></i>
                Bilety i szacunek dystansu (lotnisko → domy) ustawiasz w oknie — spójnie z transferem przed lotem.
            </p>
            <x-logistics.transfer-segment-action-row>
                <button type="button"
                        class="btn btn-sm d-inline-flex align-items-center btn-outline-success"
                        wire:click="openPostTransferRouteModal" wire:loading.attr="disabled">
                    <i class="bi bi-sliders me-1"></i> Konfiguruj odcinek (bilety i dystans)
                </button>
            </x-logistics.transfer-segment-action-row>
        @else
            @php
                $postOwnCompact = ($postTransferCarSectionCollapsed && $transferFromAirportGroundMode === 'car')
                    || ($postTransferOtherSectionCollapsed && $transferFromAirportGroundMode === 'other');
            @endphp

            @if($postOwnCompact)
                <x-logistics.transfer-segment-compact-summary>
                    <x-slot name="badge">
                        @if($transferFromAirportGroundMode === 'car')
                            @php
                                $pvPost = $availableVehicles->firstWhere('id', $transferVehicleId);
                                $drPost = $availableEmployees->firstWhere('id', $transferDriverEmployeeId);
                            @endphp
                            <span class="badge rounded-pill text-start" style="font-size: 0.72rem; background: rgba(34,197,94,0.12); color: #86efac; line-height: 1.45;">
                                <i class="bi bi-car-front me-1"></i>
                                @if($pvPost)
                                    <span class="fw-semibold">{{ $pvPost->registration_number }}</span>
                                    <span class="text-muted fw-normal">· {{ $pvPost->brand }} {{ $pvPost->model }}</span>
                                @else
                                    —
                                @endif
                                @if($drPost)
                                    <span class="text-muted"> · </span>{{ $drPost->full_name }}
                                @endif
                                @if($transferDriverBonusAmount !== null && $transferDriverBonusAmount !== '')
                                    <span class="text-muted"> · </span><span class="fw-semibold">{{ $transferDriverBonusAmount }} {{ $transferDriverBonusCurrency ?? 'PLN' }}</span>
                                @endif
                            </span>
                        @else
                            @php
                                $nPostTk = $selectedEmployeesForTickets->count();
                            @endphp
                            <span class="badge rounded-pill text-start" style="font-size: 0.72rem; background: rgba(99,102,241,0.18); color: #c7d2fe; line-height: 1.45; max-width: 100%;">
                                <i class="bi bi-train-front me-1"></i>
                                <span class="fw-semibold">Inny transport</span>
                                <span class="text-muted"> · </span>
                                {{ $nPostTk }} {{ $nPostTk === 1 ? 'bilet' : (($nPostTk > 1 && $nPostTk < 5) ? 'bilety' : 'biletów') }}
                            </span>
                        @endif
                    </x-slot>
                    @if($transferFromAirportGroundMode === 'car')
                        <x-slot name="trail">
                            @php
                                $postTrailKm = is_array($routeData) && isset($routeData['distance']) && (float) $routeData['distance'] > 0
                                    ? (float) $routeData['distance']
                                    : null;
                                $postStopCount = count($routeWaypoints ?? []);
                            @endphp
                            <span class="badge rounded-pill text-start" style="font-size: 0.72rem; background: rgba(34,197,94,0.12); color: #86efac; line-height: 1.45;">
                                <i class="bi bi-signpost-2 me-1"></i>
                                @if($postTrailKm !== null && $postStopCount > 0)
                                    <span class="fw-semibold">{{ number_format($postTrailKm, 1, ',', '') }} km</span>
                                    <span class="text-muted"> · </span>
                                    {{ $postStopCount }}
                                    @if($postStopCount === 1)
                                        przystanek
                                    @elseif($postStopCount >= 2 && $postStopCount <= 4)
                                        przystanki
                                    @else
                                        przystanków
                                    @endif
                                @elseif($postTrailKm !== null)
                                    <span class="fw-semibold">{{ number_format($postTrailKm, 1, ',', '') }} km</span>
                                @elseif($postStopCount > 0)
                                    {{ $postStopCount }}
                                    @if($postStopCount === 1)
                                        przystanek
                                    @elseif($postStopCount >= 2 && $postStopCount <= 4)
                                        przystanki
                                    @else
                                        przystanków
                                    @endif
                                @else
                                    <span class="fst-italic text-muted">Brak trasy</span>
                                @endif
                            </span>
                        </x-slot>
                    @endif
                    <x-slot name="edit">
                        <button type="button" class="btn btn-sm btn-outline-light border-opacity-25 flex-shrink-0" wire:click="openPostTransferConfigModal" title="Edytuj konfigurację transferu">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </x-slot>
                </x-logistics.transfer-segment-compact-summary>

                @php
                    $postCompactCfgDanger = ($transferFromAirportGroundMode === 'car' && ($this->transferVehicleIncomplete || $this->transferDriverIncomplete || $this->transferBonusIncomplete))
                        || ($transferFromAirportGroundMode === 'other' && $this->fromAirportGroundTicketsIncomplete);
                    $postCompactRouteDanger = $this->routeBlockIncomplete;
                @endphp
                <p class="small text-muted mb-3" style="font-size: 0.78rem;">
                    <i class="bi bi-info-circle me-1"></i>
                    Szczegóły trasy, przystanków i dystansu ustalasz w oknie — tak jak przy transferze na lotnisko startowe.
                </p>
                <x-logistics.transfer-segment-action-row>
                    <button type="button"
                            class="btn btn-sm d-inline-flex align-items-center {{ $postCompactCfgDanger ? 'btn-danger' : 'btn-outline-success' }}"
                            wire:click="openPostTransferConfigModal" wire:loading.attr="disabled">
                        <i class="bi bi-sliders me-1"></i> Konfiguruj transfer
                    </button>
                    <button type="button"
                            class="btn btn-sm d-inline-flex align-items-center {{ $postCompactRouteDanger ? 'btn-danger' : 'btn-outline-primary' }}"
                            wire:click="openPostTransferRouteModal" wire:loading.attr="disabled">
                        <i class="bi bi-signpost-split me-1"></i> Konfiguruj trasę
                    </button>
                </x-logistics.transfer-segment-action-row>
            @else
                <p class="small text-muted mb-2 mb-md-3">Wybierz środek (samochód lub inny transport), uzupełnij dane i zatwierdź w oknie.</p>
                @php
                    $postCfgBtnDanger = ($transferFromAirportGroundMode === 'car' && ($this->transferVehicleIncomplete || $this->transferDriverIncomplete || $this->transferBonusIncomplete))
                        || ($transferFromAirportGroundMode === 'other' && $this->fromAirportGroundTicketsIncomplete);
                    $postRouteBtnDanger = $this->routeBlockIncomplete;
                @endphp
                <x-logistics.transfer-segment-action-row>
                    <button type="button"
                            class="btn btn-sm d-inline-flex align-items-center {{ $postCfgBtnDanger ? 'btn-danger' : 'btn-outline-success' }}"
                            wire:click="openPostTransferConfigModal" wire:loading.attr="disabled">
                        <i class="bi bi-sliders me-1"></i> Konfiguruj transfer
                    </button>
                    <button type="button"
                            class="btn btn-sm d-inline-flex align-items-center {{ $postRouteBtnDanger ? 'btn-danger' : 'btn-outline-primary' }}"
                            wire:click="openPostTransferRouteModal" wire:loading.attr="disabled">
                        <i class="bi bi-signpost-split me-1"></i> Konfiguruj trasę
                    </button>
                </x-logistics.transfer-segment-action-row>
            @endif
        @endif
    @endif
</x-logistics.transfer-segment-card>

@include('livewire.steps.partials.step4-post-transfer-config-modal')
@include('livewire.steps.partials.step4-post-transfer-route-modal')
