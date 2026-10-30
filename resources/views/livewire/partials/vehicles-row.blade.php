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
    $brandModel = trim(($vehicle->brand ?? '').' '.($vehicle->model ?? ''));
    $projectLabel = $locationStatus['project_names']->join(', ');
    $houseLabel = $locationStatus['accommodation_names']->join(', ');
    $stationLabel = (string) ($locationStatus['stationing_location'] ?? '');
@endphp

<tr wire:key="vehicle-{{ $vehicle->id }}">
    <td>
        <x-ui.avatar
            :image-url="$vehicle->image_path ? $vehicle->image_url : null"
            :alt="$brandModel !== '' ? $brandModel : $vehicle->registration_number"
            :initials="substr($vehicle->registration_number, 0, 2)"
            size="50px"
            shape="rounded"
        />
    </td>
    <td class="vehicles-table__cell vehicles-table__cell--reg">
        <div class="fw-medium vehicles-table__truncate" title="{{ $vehicle->registration_number }}">
            {{ $vehicle->registration_number }}
        </div>
        @if($vehicle->isRetired())
            <x-ui.badge variant="danger">Wycofany</x-ui.badge>
        @endif
        <div class="d-md-none small text-muted mt-1 vehicles-table__truncate" title="{{ $brandModel }}">{{ $brandModel }}</div>
    </td>
    <td class="d-none d-md-table-cell vehicles-table__cell vehicles-table__cell--brand">
        <span class="vehicles-table__truncate" title="{{ $brandModel }}">{{ $brandModel }}</span>
    </td>
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
    <td class="text-center d-none d-lg-table-cell vehicles-table__cell vehicles-table__cell--wide">
        @if($locationStatus['in_transit'] || !$locationStatus['outside_base'])
            <span class="text-muted">─</span>
        @elseif($locationStatus['project_names']->isNotEmpty())
            <x-ui.badge variant="info" class="vehicles-table__badge" title="{{ $projectLabel }}">{{ $projectLabel }}</x-ui.badge>
        @else
            <x-ui.badge variant="danger">Brak</x-ui.badge>
        @endif
    </td>
    <td class="text-center d-none d-lg-table-cell vehicles-table__cell vehicles-table__cell--wide">
        @if($locationStatus['in_transit'] || !$locationStatus['outside_base'])
            <span class="text-muted">─</span>
        @elseif($locationStatus['accommodation_names']->isNotEmpty())
            <x-ui.badge variant="info" class="vehicles-table__badge" title="{{ $houseLabel }}">{{ $houseLabel }}</x-ui.badge>
        @else
            <x-ui.badge variant="danger">Brak</x-ui.badge>
        @endif
    </td>
    <td class="text-center d-none d-xl-table-cell vehicles-table__cell vehicles-table__cell--wide">
        @if($locationStatus['in_transit'])
            <span class="text-muted">─</span>
        @elseif($stationLabel !== '')
            <x-ui.badge variant="info" class="vehicles-table__badge" title="{{ $stationLabel }}">{{ $stationLabel }}</x-ui.badge>
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
    <td class="text-end vehicles-table__actions">
        <x-ui.button variant="ghost" href="{{ route('vehicles.show', $vehicle) }}" class="btn-sm">
            <i class="bi bi-eye"></i>
        </x-ui.button>
        <x-ui.button variant="ghost" href="{{ route('vehicles.edit', $vehicle) }}" class="btn-sm">
            <i class="bi bi-pencil"></i>
        </x-ui.button>
    </td>
</tr>
