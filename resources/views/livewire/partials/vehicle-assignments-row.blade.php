@props(['assignment', 'hideEmployee' => false])

@php
    $vehicle = $assignment->vehicle;
    $position = $assignment->position ?? \App\Enums\VehiclePosition::PASSENGER;
    $positionValue = $position instanceof \App\Enums\VehiclePosition ? $position->value : $position;
    $isDriver = $positionValue === 'driver';
@endphp

<tr wire:key="vehicle-assignment-{{ $assignment->id }}">
    <td class="fw-semibold">
        {{ $vehicle->registration_number }}
        @if($vehicle->brand || $vehicle->model)
            <span class="text-muted small">({{ trim($vehicle->brand.' '.$vehicle->model) }})</span>
        @endif
    </td>
    @unless($hideEmployee)
        <td><x-employee-cell :employee="$assignment->employee" /></td>
    @endunless
    <td>
        <x-ui.badge variant="{{ $isDriver ? 'success' : 'info' }}">{{ $isDriver ? 'Kierowca' : 'Pasażer' }}</x-ui.badge>
    </td>
    <td>
        <small class="text-muted">
            {{ $assignment->start_date->format('Y-m-d') }}
            – {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '...' }}
        </small>
    </td>
    <td>
        @if($assignment->isScheduled())
            <x-ui.badge variant="info">Przyszłe</x-ui.badge>
        @elseif($assignment->isActive())
            <x-ui.badge variant="success">Aktywne</x-ui.badge>
        @else
            <x-ui.badge variant="accent">Zakończone</x-ui.badge>
        @endif
    </td>
    <td class="text-end">
        <x-ui.action-buttons
            viewRoute="{{ route('vehicle-assignments.show', $assignment) }}"
            editRoute="{{ route('vehicle-assignments.edit', $assignment) }}"
            deleteRoute="{{ route('vehicle-assignments.destroy', $assignment) }}"
            deleteMessage="Czy na pewno chcesz usunąć to przypisanie pojazdu?"
        />
    </td>
</tr>
