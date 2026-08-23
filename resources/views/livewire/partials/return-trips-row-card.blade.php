@props(['trip'])

@php
    $uniqueParticipants = $trip->participants
        ->filter(fn ($p) => $p->employee)
        ->unique('employee_id')
        ->values();

    if ($trip->status === \App\Enums\LogisticsEventStatus::CANCELLED) {
        $statusLabel = 'Anulowany';
        $badgeVariant = 'danger';
    } else {
        $endDate = $trip->end_date ?? $trip->event_date;
        if ($endDate->isPast()) {
            $statusLabel = 'Zakończony';
            $badgeVariant = 'secondary';
        } else {
            $statusLabel = 'Zaplanowany';
            $badgeVariant = 'info';
        }
    }

    $fromName = $trip->fromLocation?->name ?? '—';
    $fromAccommodationLocations = $trip->participants
        ->filter(fn ($p) => $p->assignment_type === 'accommodation_assignment' && $p->assignment?->accommodation?->location)
        ->map(fn ($p) => $p->assignment->accommodation->location)
        ->unique('id')
        ->values();

    if ($fromAccommodationLocations->isNotEmpty()) {
        $fromName = $fromAccommodationLocations->pluck('name')->join(', ');
    }
@endphp

<x-ui.card class="dt-card" wire:key="return-trip-card-{{ $trip->id }}">
    <div class="dt-card__title">
        <a href="{{ route('return-trips.show', $trip) }}" class="stretched-link">
            {{ $fromName }} → {{ $trip->toLocation->name }}
        </a>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Data</span>
        <span class="dt-card__value">{{ $trip->event_date->format('d.m.Y') }}</span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Pojazd</span>
        <span class="dt-card__value">{{ $trip->vehicle?->registration_number ?? '—' }}</span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Uczestnicy</span>
        <span class="dt-card__value">
            <x-ui.avatar-stack :employees="$uniqueParticipants->pluck('employee')" size="26px" />
        </span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Status</span>
        <span class="dt-card__value">
            <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
        </span>
    </div>
</x-ui.card>
