@props(['rotation', 'hideEmployee' => false])

@php
    $status = $rotation->status;
    $today = now()->toDateString();

    if (empty($status) || $status !== 'cancelled') {
        if ($rotation->start_date->toDateString() > $today) {
            $status = 'scheduled';
        } elseif ($rotation->end_date->toDateString() < $today) {
            $status = 'completed';
        } else {
            $status = 'active';
        }
    }

    $badgeVariant = match($status) {
        'active' => 'success',
        'scheduled' => 'info',
        'completed' => 'accent',
        'cancelled' => 'danger',
        default => 'accent'
    };

    $badgeLabel = match($status) {
        'active' => 'Aktywna',
        'scheduled' => 'Zaplanowana',
        'completed' => 'Zakończona',
        'cancelled' => 'Anulowana',
        default => '-'
    };

    $days = $rotation->duration_days;
@endphp

<x-ui.card class="dt-card" wire:key="rotation-card-{{ $rotation->id }}">
    <div class="dt-card__title mb-2">
        @if($hideEmployee)
            {{ $rotation->start_date->format('d.m.Y') }} – {{ $rotation->end_date->format('d.m.Y') }}
        @else
            <x-employee-cell :employee="$rotation->employee" />
        @endif
        <a href="{{ route('employees.rotations.show', [$rotation->employee, $rotation]) }}" class="stretched-link visually-hidden">
            Szczegóły rotacji
        </a>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Okres</span>
        <span class="dt-card__value">{{ $rotation->start_date->format('d.m.Y') }} – {{ $rotation->end_date->format('d.m.Y') }}</span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Długość</span>
        <span class="dt-card__value">
            @if($days !== null)
                {{ $days }} {{ $days === 1 ? 'dzień' : 'dni' }}
            @else
                —
            @endif
        </span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Status</span>
        <span class="dt-card__value"><x-ui.badge variant="{{ $badgeVariant }}">{{ $badgeLabel }}</x-ui.badge></span>
    </div>

    @if($rotation->notes)
        <div class="dt-card__row">
            <span class="dt-card__label">Notatki</span>
            <span class="dt-card__value text-start">{{ Str::limit($rotation->notes, 80) }}</span>
        </div>
    @endif

    <div class="dt-card__actions">
        <x-ui.action-buttons
            editRoute="{{ route('employees.rotations.edit', [$rotation->employee, $rotation]) }}"
            deleteRoute="{{ route('employees.rotations.destroy', [$rotation->employee, $rotation]) }}"
            deleteMessage="Czy na pewno chcesz usunąć tę rotację?"
        />
    </div>
</x-ui.card>
