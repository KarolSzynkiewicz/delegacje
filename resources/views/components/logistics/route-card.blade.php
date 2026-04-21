{{--
  Karta „Trasa — przystanki i dystans” + osadzony GroundTransferSlot (stan u rodzica Livewire).
  @see components.logistics.trip-details-panel
--}}
@props([
    'summary' => null,
    'label' => 'Trasa — przystanki i dystans',
    'dateFrom' => '',
    'dateTo' => '',
    'selectedEmployeeIds' => [],
    'groundTransferConfig' => [],
    'vehicleId' => null,
    'vehicleSeats' => [],
    'baseLocationId' => null,
    'slotKey' => 'transfer-create',
    'wireKey' => 'transfer-create-gts',
    'contextLabel' => 'Transfer',
    'externalLegKind' => 'own',
])

@php
    $vehicleSeats = is_array($vehicleSeats) ? $vehicleSeats : [];
    $resolvedBaseLocationId = $baseLocationId ?? \App\Models\Location::getBase()?->id;
    $panelDriverEmployeeId = isset($vehicleSeats[0]) && empty($vehicleSeats[0]['external_driver']) && ! empty($vehicleSeats[0]['employee_id'])
        ? (int) $vehicleSeats[0]['employee_id']
        : null;
    $panelDriverIsExternal = isset($vehicleSeats[0]) && ! empty($vehicleSeats[0]['external_driver']);
@endphp

<x-ui.card {{ $attributes->class(['mb-4']) }} :label="$label">
    @once('logistics-route-card-styles')
        <style>
            .transfer-board-route-summary {
                color: #e2e8f0;
                line-height: 1.55;
                font-size: 0.9rem;
            }
            .transfer-board-route-summary .tbr-sep {
                color: #64748b;
                font-weight: 500;
            }
            .transfer-board-route-summary .tbr-em {
                color: #f8fafc;
                font-weight: 600;
            }
            .transfer-board-route-summary .tbr-meta {
                color: #cbd5e1;
                font-weight: 500;
            }
            .transfer-board-route-missing {
                border: 1px solid rgba(248, 113, 113, 0.45);
                background: rgba(127, 29, 29, 0.2);
                color: #fecaca;
                border-radius: 0.5rem;
                padding: 0.5rem 0.75rem;
                font-weight: 600;
                font-size: 0.875rem;
                margin-bottom: 0.75rem;
            }
            .transfer-board-route-hint {
                color: #94a3b8;
                line-height: 1.5;
            }
        </style>
    @endonce
    @if($summary)
        @php $s = $summary; @endphp
        <p class="transfer-board-route-summary mb-3">
            @if($s['km'] !== null)
                <span class="tbr-em">{{ number_format($s['km'], 1, ',', ' ') }} km</span>
            @else
                <span class="tbr-em">—</span>
            @endif
            @if(! empty($s['duration_label']))
                <span class="tbr-sep"> · </span><span class="tbr-em">{{ $s['duration_label'] }}</span>
            @endif
            <span class="tbr-sep"> · </span><span class="tbr-meta">Przystanki: <span class="tbr-em">{{ $s['stop_count'] }}</span></span>
            <span class="tbr-sep"> · </span><span class="tbr-meta">Start: <span class="tbr-em">{{ $s['start_label'] }}</span></span>
            <span class="tbr-sep"> · </span><span class="tbr-meta">Koniec: <span class="tbr-em">{{ $s['end_label'] }}</span></span>
        </p>
    @else
        <div class="transfer-board-route-missing mb-2">
            <i class="bi bi-signpost-split me-1"></i>Brak trasy
        </div>
        <p class="small transfer-board-route-hint mb-3">
            Przystanki dodajesz w modalu <strong class="text-light">Konfiguruj trasę</strong> (kolejność = kolejność przejazdu, notatki przy każdym punkcie, opcjonalnie km i minuty ręcznie).
            Pojazd z karty powyżej jest synchronizowany z tą konfiguracją przy zapisie transferu.
        </p>
    @endif
    <livewire:ground-transfer-slot
        :wire:key="$wireKey"
        :slot-key="$slotKey"
        :context-label="$contextLabel"
        :date-from="$dateFrom"
        :date-to="$dateTo"
        :selected-employee-ids="$selectedEmployeeIds"
        :initial-config="$groundTransferConfig"
        :external-leg-kind="$externalLegKind"
        :sync-vehicle-id="$vehicleId"
        :panel-driver-employee-id="$panelDriverEmployeeId"
        :panel-driver-is-external="$panelDriverIsExternal"
        :base-location-id="$resolvedBaseLocationId"
    />
</x-ui.card>
