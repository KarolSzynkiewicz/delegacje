@php
    $airportName = $startAirportData['name'] ?? 'Lotnisko startowe';
@endphp
<div class="vstack gap-2 mb-3">
    @foreach($preTransferRouteTiles as $tile)
        @if($tile['kind'] === 'base')
            <div class="d-flex align-items-start gap-2 p-2 rounded border pre-transfer-route-tile"
                 style="border-color: rgba(251,191,36,0.45) !important; background: rgba(15,23,42,0.35);"
                 wire:key="pre-transfer-tile-{{ $tile['index'] }}">
                <span class="badge rounded-pill align-self-start" style="background: rgba(251,191,36,0.2); color: #fde68a;">{{ $loop->iteration }}</span>
                <div class="flex-grow-1 small">
                    <div class="text-uppercase text-muted" style="font-size: 0.65rem; letter-spacing: .04em;">Baza</div>
                    <div class="fw-semibold">{{ $tile['display_name'] ?? ($baseLocationData['name'] ?? 'Baza') }}</div>
                </div>
                <div class="d-flex flex-column gap-0 align-self-center">
                    <button type="button" class="rtp-icon-btn" wire:click="movePreWaypointUp({{ $tile['index'] }})" @disabled(!($tile['can_move_up'] ?? false)) title="Wyżej">
                        <i class="bi bi-chevron-up"></i>
                    </button>
                    <button type="button" class="rtp-icon-btn" wire:click="movePreWaypointDown({{ $tile['index'] }})" @disabled(!($tile['can_move_down'] ?? false)) title="Niżej">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <button type="button" class="btn btn-link btn-sm text-warning p-0" title="Pomiń bazę (usuń z kolejności)"
                            wire:click="omitPreTransferBase" wire:loading.attr="disabled">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        @elseif($tile['kind'] === 'loc')
            @php $locRow = $tile['location'] ?? []; @endphp
            <div class="d-flex align-items-start gap-2 p-2 rounded border pre-transfer-route-tile"
                 style="border-color: rgba(251,191,36,0.35) !important; background: rgba(15,23,42,0.35);"
                 wire:key="pre-transfer-tile-{{ $tile['index'] }}">
                <span class="badge bg-secondary rounded-pill align-self-start">{{ $loop->iteration }}</span>
                <div class="flex-grow-1 small">
                    <div class="text-uppercase text-muted" style="font-size: 0.65rem; letter-spacing: .04em;">Przystanek</div>
                    <div class="fw-semibold">{{ $locRow['name'] ?? '—' }}</div>
                    @php $preLocId = (string) ($locRow['id'] ?? ''); @endphp
                    <textarea class="form-control form-control-sm mt-1" rows="2" placeholder="Po co tam jedziemy? …"
                        wire:model.lazy="transferToAirportLocationStopNotes.{{ $preLocId }}"></textarea>
                </div>
                <div class="d-flex flex-column gap-0 align-self-center">
                    <button type="button" class="rtp-icon-btn" wire:click="movePreWaypointUp({{ $tile['index'] }})" @disabled(!($tile['can_move_up'] ?? false)) title="Wyżej">
                        <i class="bi bi-chevron-up"></i>
                    </button>
                    <button type="button" class="rtp-icon-btn" wire:click="movePreWaypointDown({{ $tile['index'] }})" @disabled(!($tile['can_move_down'] ?? false)) title="Niżej">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <button type="button" class="btn btn-link btn-sm text-danger p-0" wire:click="removePreWaypoint({{ $tile['index'] }})" title="Usuń">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        @else
            {{-- sap --}}
            <div class="d-flex align-items-start gap-2 p-2 rounded border pre-transfer-route-tile"
                 style="border-color: rgba(14,165,233,0.45) !important; background: rgba(14,165,233,0.06);"
                 wire:key="pre-transfer-tile-{{ $tile['index'] }}">
                <span class="badge rounded-pill align-self-start" style="background: rgba(14,165,233,0.25); color: #bae6fd;">{{ $loop->iteration }}</span>
                <div class="flex-grow-1 small">
                    <div class="text-uppercase text-muted" style="font-size: 0.65rem; letter-spacing: .04em;">
                        Lotnisko startowe <i class="bi bi-lock-fill text-info ms-1" title="Z nagłówka wyjazdu — nie usuwamy"></i>
                    </div>
                    <div class="fw-semibold">{{ $tile['display_name'] ?? $airportName }}</div>
                </div>
                <div class="d-flex flex-column gap-0 align-self-center">
                    <button type="button" class="rtp-icon-btn" wire:click="movePreWaypointUp({{ $tile['index'] }})" @disabled(!($tile['can_move_up'] ?? false)) title="Wyżej">
                        <i class="bi bi-chevron-up"></i>
                    </button>
                    <button type="button" class="rtp-icon-btn" wire:click="movePreWaypointDown({{ $tile['index'] }})" @disabled(!($tile['can_move_down'] ?? false)) title="Niżej">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                </div>
            </div>
        @endif
    @endforeach
</div>

<form wire:submit.prevent="addExtraStopToPreTransfer" class="d-flex gap-2 align-items-center mb-0">
    <select wire:model.live="transferToAirportExtraStopLocationId" class="form-select form-select-sm flex-grow-1">
        <option value="">— Dodaj przystanek (wybierz lokalizację) —</option>
        @foreach($availableLocations as $loc)
            <option value="{{ $loc->id }}">{{ $loc->name }}@if($loc->city) – {{ $loc->city }}@endif</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-sm btn-outline-info flex-shrink-0" wire:loading.attr="disabled">Dodaj</button>
</form>
