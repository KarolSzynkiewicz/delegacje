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

    $fromAccommodationLocations = $trip->participants
        ->filter(fn ($p) => $p->assignment_type === 'accommodation_assignment' && $p->assignment?->accommodation?->location)
        ->map(fn ($p) => $p->assignment->accommodation->location)
        ->unique('id')
        ->values();
@endphp

<tr wire:key="return-trip-{{ $trip->id }}">
    <td class="text-muted small">{{ $trip->id }}</td>
    <td>{{ $trip->event_date->format('Y-m-d') }}</td>
    <td>{{ $trip->vehicle?->registration_number ?? '—' }}</td>
    <td>
        @if($fromAccommodationLocations->isNotEmpty())
            <div class="d-flex flex-column gap-1">
                @foreach($fromAccommodationLocations->take(3) as $loc)
                    <div class="small">
                        <span class="fw-semibold">{{ $loc->name }}</span>
                        @if($loc->city)
                            <span class="text-muted">({{ $loc->city }})</span>
                        @endif
                    </div>
                @endforeach
                @if($fromAccommodationLocations->count() > 3)
                    <small class="text-muted">+{{ $fromAccommodationLocations->count() - 3 }} więcej</small>
                @endif
            </div>
        @else
            {{ $trip->fromLocation?->name ?? '—' }}
        @endif
    </td>
    <td>{{ $trip->toLocation->name }}</td>
    <td>
        <x-ui.avatar-stack :employees="$uniqueParticipants->pluck('employee')" />
    </td>
    <td>
        <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
    </td>
    <td>
        <small class="text-muted">{{ $trip->created_at?->format('d.m.Y H:i') ?? '—' }}</small>
    </td>
    <td>
        <x-action-buttons viewRoute="{{ route('return-trips.show', $trip) }}" />
    </td>
</tr>
