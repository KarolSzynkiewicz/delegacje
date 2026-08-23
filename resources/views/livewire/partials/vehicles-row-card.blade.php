@props(['vehicle', 'checkDate'])

@php
    $locationTracker = app(\App\Services\LocationTrackingService::class);
    $locationStatus = $locationTracker->getVehicleLocationStatus($vehicle, $checkDate);
    $condition = \App\Enums\VehicleCondition::tryFrom($vehicle->technical_condition);
@endphp

<x-ui.card class="dt-card" wire:key="vehicle-card-{{ $vehicle->id }}">
    <div class="d-flex align-items-start gap-3 mb-2">
        <x-ui.avatar
            :image-url="$vehicle->image_path ? $vehicle->image_url : null"
            :alt="$vehicle->registration_number"
            :initials="substr($vehicle->registration_number, 0, 2)"
            size="48px"
            shape="rounded"
        />
        <div class="flex-grow-1 min-width-0">
            <div class="dt-card__title mb-0">
                <a href="{{ route('vehicles.show', $vehicle) }}" class="stretched-link">{{ $vehicle->registration_number }}</a>
            </div>
            <div class="small text-muted">{{ trim(($vehicle->brand ?? '') . ' ' . ($vehicle->model ?? '')) }}</div>
        </div>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Stan techn.</span>
        <span class="dt-card__value">{{ $condition?->label() ?? $vehicle->technical_condition }}</span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Status</span>
        <span class="dt-card__value">
            @if($locationStatus['in_transit'])
                <x-ui.badge variant="warning">W podróży</x-ui.badge>
            @elseif(!$locationStatus['outside_base'])
                <x-ui.badge variant="success">Baza</x-ui.badge>
            @else
                <x-ui.badge variant="info">Poza bazą</x-ui.badge>
            @endif
        </span>
    </div>

    @if($locationStatus['capacity'] && !$locationStatus['in_transit'])
        <div class="dt-card__row">
            <span class="dt-card__label">Zapełnienie</span>
            <span class="dt-card__value">{{ $locationStatus['occupancy'] }}/{{ $locationStatus['capacity'] }}</span>
        </div>
    @endif

    <div class="dt-card__actions">
        <x-ui.button variant="ghost" href="{{ route('vehicles.edit', $vehicle) }}" class="btn-sm">
            <i class="bi bi-pencil"></i> Edytuj
        </x-ui.button>
    </div>
</x-ui.card>
