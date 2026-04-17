@php
    $airportName = $startAirportData['name'] ?? 'Lotnisko startowe';
@endphp
<div class="vstack gap-2 mb-0">
    @foreach($preTransferRouteTiles as $tile)
        @if($tile['kind'] === 'base')
            <div class="d-flex align-items-start gap-2 p-2 rounded border"
                 style="border-color: rgba(251,191,36,0.45) !important; background: rgba(15,23,42,0.35);"
                 wire:key="pre-transfer-ro-{{ $tile['index'] }}">
                <span class="badge rounded-pill align-self-start" style="background: rgba(251,191,36,0.2); color: #fde68a;">{{ $loop->iteration }}</span>
                <div class="flex-grow-1 small">
                    <div class="text-uppercase text-muted" style="font-size: 0.65rem; letter-spacing: .04em;">Baza</div>
                    <div class="fw-semibold">{{ $tile['display_name'] ?? ($baseLocationData['name'] ?? 'Baza') }}</div>
                </div>
            </div>
        @elseif($tile['kind'] === 'loc')
            @php $locRow = $tile['location'] ?? []; @endphp
            <div class="d-flex align-items-start gap-2 p-2 rounded border"
                 style="border-color: rgba(251,191,36,0.35) !important; background: rgba(15,23,42,0.35);"
                 wire:key="pre-transfer-ro-{{ $tile['index'] }}">
                <span class="badge bg-secondary rounded-pill align-self-start">{{ $loop->iteration }}</span>
                <div class="flex-grow-1 small">
                    <div class="text-uppercase text-muted" style="font-size: 0.65rem; letter-spacing: .04em;">Przystanek</div>
                    <div class="fw-semibold">{{ $locRow['name'] ?? '—' }}</div>
                </div>
            </div>
        @else
            <div class="d-flex align-items-start gap-2 p-2 rounded border"
                 style="border-color: rgba(14,165,233,0.45) !important; background: rgba(14,165,233,0.06);"
                 wire:key="pre-transfer-ro-{{ $tile['index'] }}">
                <span class="badge rounded-pill align-self-start" style="background: rgba(14,165,233,0.25); color: #bae6fd;">{{ $loop->iteration }}</span>
                <div class="flex-grow-1 small">
                    <div class="text-uppercase text-muted" style="font-size: 0.65rem; letter-spacing: .04em;">Lotnisko startowe</div>
                    <div class="fw-semibold">{{ $tile['display_name'] ?? $airportName }}</div>
                </div>
            </div>
        @endif
    @endforeach
</div>
