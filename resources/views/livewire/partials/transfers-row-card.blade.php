@props(['transfer'])

@php
    $uniqueParticipants = $transfer->participants
        ->filter(fn ($p) => $p->employee)
        ->unique('employee_id')
        ->values();

    $visualStatus = $transfer->getVisualStatus();
    $badgeVariant = match($visualStatus) {
        'oczekuje' => 'primary',
        'w trakcie' => 'warning',
        'zakończone' => 'success',
        'anulowany' => 'danger',
        default => 'accent'
    };

    $driverAdj = $transfer->driverAdjustments->first();
@endphp

<x-ui.card class="dt-card" wire:key="transfer-card-{{ $transfer->id }}">
    <div class="dt-card__title">
        <a href="{{ route('transfers.show', $transfer) }}" class="stretched-link">
            {{ $transfer->fromLocation?->name ?? '—' }} → {{ $transfer->toLocation?->name ?? '—' }}
        </a>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Data</span>
        <span class="dt-card__value">{{ $transfer->event_date->format('d.m.Y H:i') }}</span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Rodzaj</span>
        <span class="dt-card__value">
            @if($transfer->has_reassignment)
                <x-ui.badge variant="info">Przeniesienie</x-ui.badge>
            @else
                <x-ui.badge variant="secondary">Przejazd</x-ui.badge>
            @endif
        </span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Pojazd</span>
        <span class="dt-card__value">{{ $transfer->vehicle?->registration_number ?? '—' }}</span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Uczestnicy</span>
        <span class="dt-card__value">
            <x-ui.avatar-stack :employees="$uniqueParticipants->pluck('employee')" size="26px" />
        </span>
    </div>

    @if($driverAdj)
        <div class="dt-card__row">
            <span class="dt-card__label">Kierowca</span>
            <span class="dt-card__value">{{ $driverAdj->employee?->full_name }}</span>
        </div>
    @endif

    <div class="dt-card__row">
        <span class="dt-card__label">Status</span>
        <span class="dt-card__value">
            <x-ui.badge variant="{{ $badgeVariant }}">{{ ucfirst($visualStatus) }}</x-ui.badge>
        </span>
    </div>
</x-ui.card>
