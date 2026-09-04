@props(['assignment', 'hideEmployee' => false])

<x-ui.card class="dt-card" wire:key="accommodation-assignment-card-{{ $assignment->id }}">
    <div class="dt-card__title mb-2">
        {{ $assignment->accommodation->name }}
        <a href="{{ route('accommodation-assignments.show', $assignment) }}" class="stretched-link visually-hidden">Szczegóły</a>
    </div>
    @unless($hideEmployee)
        <div class="dt-card__row">
            <span class="dt-card__label">Pracownik</span>
            <span class="dt-card__value">{{ $assignment->employee->full_name }}</span>
        </div>
    @endunless
    <div class="dt-card__row">
        <span class="dt-card__label">Okres</span>
        <span class="dt-card__value">{{ $assignment->start_date->format('d.m.Y') }} – {{ $assignment->end_date ? $assignment->end_date->format('d.m.Y') : '...' }}</span>
    </div>
</x-ui.card>
