@props(['departure'])

@php
    $visualStatus = $departure->getVisualStatus();
    $badgeVariant = match($visualStatus) {
        'oczekuje' => 'primary',
        'w trakcie' => 'warning',
        'zakończone' => 'success',
        'anulowany' => 'danger',
        default => 'accent'
    };
@endphp

<x-ui.card class="dt-card" wire:key="departure-card-{{ $departure->id }}">
    <div class="dt-card__title">
        <a href="{{ route('departures.show', $departure) }}" class="stretched-link">
            {{ $departure->fromLocation->name }} → {{ $departure->toLocation->name }}
        </a>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Daty</span>
        <span class="dt-card__value">
            {{ $departure->event_date->format('d.m.Y') }}
            @if($departure->end_date)
                – {{ $departure->end_date->format('d.m.Y') }}
            @endif
        </span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Pojazd</span>
        <span class="dt-card__value">
            {{ $departure->vehicle?->registration_number ?? 'Transport publiczny' }}
        </span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Uczestnicy</span>
        <span class="dt-card__value">
            <x-ui.avatar-stack :employees="$departure->participants->pluck('employee')" size="26px" />
        </span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Status</span>
        <span class="dt-card__value">
            <x-ui.badge variant="{{ $badgeVariant }}">{{ ucfirst($visualStatus) }}</x-ui.badge>
        </span>
    </div>
</x-ui.card>
