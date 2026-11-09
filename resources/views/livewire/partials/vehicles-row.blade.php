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

    $contextParts = [];
    if ($locationStatus['in_transit']) {
        $contextParts[] = 'W podróży';
    } elseif (! $locationStatus['outside_base']) {
        $contextParts[] = 'Baza';
    } else {
        if ($locationStatus['project_names']->isNotEmpty()) {
            $contextParts[] = $locationStatus['project_names']->join(', ');
        }
        if ($locationStatus['accommodation_names']->isNotEmpty()) {
            $contextParts[] = $locationStatus['accommodation_names']->join(', ');
        }
        if (! empty($locationStatus['stationing_location'])) {
            $contextParts[] = (string) $locationStatus['stationing_location'];
        }
        if ($contextParts === []) {
            $contextParts[] = 'Poza bazą';
        }
    }
    $contextLabel = implode(' · ', $contextParts);
@endphp

<tr wire:key="vehicle-{{ $vehicle->id }}">
    <td class="vehicles-table__photo">
        <x-ui.avatar
            :image-url="$vehicle->image_path ? $vehicle->image_url : null"
            :alt="$brandModel !== '' ? $brandModel : $vehicle->registration_number"
            :initials="substr($vehicle->registration_number, 0, 2)"
            size="40px"
            shape="rounded"
        />
    </td>
    <td class="vehicles-table__cell vehicles-table__cell--main">
        <a href="{{ route('vehicles.show', $vehicle) }}" class="table-link fw-semibold vehicles-table__truncate" title="{{ $vehicle->registration_number }}">
            {{ $vehicle->registration_number }}
        </a>
        @if($vehicle->isRetired())
            <x-ui.badge variant="danger">Wycofany</x-ui.badge>
        @endif
        @if($brandModel !== '')
            <div class="small text-muted vehicles-table__truncate" title="{{ $brandModel }}">{{ $brandModel }}</div>
        @endif
    </td>
    <td>
        <x-ui.badge variant="{{ $conditionVariant }}">{{ $condition?->label() ?? $vehicle->technical_condition }}</x-ui.badge>
    </td>
    <td class="text-center">
        @if($locationStatus['in_transit'])
            <x-ui.badge variant="warning">W podróży</x-ui.badge>
        @elseif(!$locationStatus['outside_base'])
            <x-ui.badge variant="success">Baza</x-ui.badge>
        @else
            <x-ui.badge variant="info">Poza bazą</x-ui.badge>
        @endif
    </td>
    <td class="d-none d-lg-table-cell vehicles-table__cell vehicles-table__cell--context">
        <span class="vehicles-table__truncate text-muted small" title="{{ $contextLabel }}">{{ $contextLabel }}</span>
    </td>
    <td class="text-center d-none d-md-table-cell">
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
        <x-ui.button variant="ghost" href="{{ route('vehicles.edit', $vehicle) }}" class="btn-sm" title="Edytuj">
            <i class="bi bi-pencil"></i>
        </x-ui.button>
    </td>
</tr>
