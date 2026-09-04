@props(['assignment', 'hideEmployee' => false])

@php
    $vehicle = $assignment->vehicle;
    $position = $assignment->position ?? \App\Enums\VehiclePosition::PASSENGER;
    $positionValue = $position instanceof \App\Enums\VehiclePosition ? $position->value : $position;
    $isDriver = $positionValue === 'driver';
@endphp

<x-ui.card class="dt-card" wire:key="vehicle-assignment-card-{{ $assignment->id }}">
    <div class="dt-card__title mb-2">
        {{ $vehicle->registration_number }}
        <a href="{{ route('vehicle-assignments.show', $assignment) }}" class="stretched-link visually-hidden">Szczegóły</a>
    </div>
    @unless($hideEmployee)
        <div class="dt-card__row">
            <span class="dt-card__label">Pracownik</span>
            <span class="dt-card__value">{{ $assignment->employee->full_name }}</span>
        </div>
    @endunless
    <div class="dt-card__row">
        <span class="dt-card__label">Rola</span>
        <span class="dt-card__value"><x-ui.badge variant="{{ $isDriver ? 'success' : 'info' }}">{{ $isDriver ? 'Kierowca' : 'Pasażer' }}</x-ui.badge></span>
    </div>
    <div class="dt-card__row">
        <span class="dt-card__label">Okres</span>
        <span class="dt-card__value">{{ $assignment->start_date->format('d.m.Y') }} – {{ $assignment->end_date ? $assignment->end_date->format('d.m.Y') : '...' }}</span>
    </div>
</x-ui.card>
