@props(['vehicle', 'checkDate'])

@php
    $locationTracker = app(\App\Services\LocationTrackingService::class);
    $locationStatus = $locationTracker->getVehicleLocationStatus($vehicle, $checkDate);
    $condition = \App\Enums\VehicleCondition::tryFrom($vehicle->technical_condition);
    $colorType = \App\Services\StatusColorService::getVehicleConditionColor($vehicle->technical_condition);
    $conditionVariant = match($colorType) {
        'success' => 'success',
        'danger' => 'danger',
        'warning' => 'warning',
        'info' => 'info',
        default => 'info'
    };
@endphp

<tr wire:key="vehicle-{{ $vehicle->id }}">
    <td>
        <x-ui.avatar
            :image-url="$vehicle->image_path ? $vehicle->image_url : null"
            :alt="($vehicle->brand ?? '') . ' ' . ($vehicle->model ?? '')"
            :initials="substr($vehicle->registration_number, 0, 2)"
            size="50px"
            shape="rounded"
        />
    </td>
    <td>
        <div class="fw-medium">{{ $vehicle->registration_number }}</div>
        <div class="d-md-none small text-muted mt-1">{{ trim(($vehicle->brand ?? '') . ' ' . ($vehicle->model ?? '')) }}</div>
    </td>
    <td class="d-none d-md-table-cell">{{ trim(($vehicle->brand ?? '') . ' ' . ($vehicle->model ?? '')) }}</td>
    <td><x-ui.badge variant="{{ $conditionVariant }}">{{ $condition?->label() ?? $vehicle->technical_condition }}</x-ui.badge></td>
    <td class="text-center">
        @if($locationStatus['in_transit'])
            <x-ui.badge variant="warning">W podróży</x-ui.badge>
        @elseif(!$locationStatus['outside_base'])
            <x-ui.badge variant="success">Baza</x-ui.badge>
        @else
            <x-ui.badge variant="info">Poza bazą</x-ui.badge>
        @endif
    </td>
    <td class="text-center d-none d-lg-table-cell">
        @if($locationStatus['in_transit'] || !$locationStatus['outside_base'])
            <span class="text-muted">─</span>
        @elseif($locationStatus['project_names']->isNotEmpty())
            <x-ui.badge variant="info">{{ $locationStatus['project_names']->join(', ') }}</x-ui.badge>
        @else
            <x-ui.badge variant="danger">Brak</x-ui.badge>
        @endif
    </td>
    <td class="text-center d-none d-lg-table-cell">
        @if($locationStatus['in_transit'] || !$locationStatus['outside_base'])
            <span class="text-muted">─</span>
        @elseif($locationStatus['accommodation_names']->isNotEmpty())
            <x-ui.badge variant="info">{{ $locationStatus['accommodation_names']->join(', ') }}</x-ui.badge>
        @else
            <x-ui.badge variant="danger">Brak</x-ui.badge>
        @endif
    </td>
    <td class="text-center d-none d-xl-table-cell">
        @if($locationStatus['in_transit'])
            <span class="text-muted">─</span>
        @elseif($locationStatus['stationing_location'])
            <x-ui.badge variant="info">{{ $locationStatus['stationing_location'] }}</x-ui.badge>
        @else
            <span class="text-muted">─</span>
        @endif
    </td>
    <td class="text-center">
        @if($locationStatus['in_transit'])
            <span class="text-muted">─</span>
        @elseif($locationStatus['capacity'])
            @php
                $occupancyText = "{$locationStatus['occupancy']}/{$locationStatus['capacity']}";
                $percentage = $locationStatus['occupancy_percentage'];
                $occVariant = match(true) {
                    $percentage >= 100 => 'danger',
                    $percentage >= 80 => 'warning',
                    default => 'success'
                };
            @endphp
            <x-ui.badge variant="{{ $occVariant }}">{{ $occupancyText }}</x-ui.badge>
        @else
            <x-ui.badge variant="info">{{ $locationStatus['occupancy'] }}</x-ui.badge>
        @endif
    </td>
    <td class="text-end">
        <x-ui.button variant="ghost" href="{{ route('vehicles.show', $vehicle) }}" class="btn-sm">
            <i class="bi bi-eye"></i>
        </x-ui.button>
        <x-ui.button variant="ghost" href="{{ route('vehicles.edit', $vehicle) }}" class="btn-sm">
            <i class="bi bi-pencil"></i>
        </x-ui.button>
    </td>
</tr>
