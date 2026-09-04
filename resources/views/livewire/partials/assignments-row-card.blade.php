@props(['assignment', 'hideEmployee' => false])

@php
    $status = $assignment->status ?? \App\Enums\AssignmentStatus::ACTIVE;
    $statusLabel = $status instanceof \App\Enums\AssignmentStatus ? $status->label() : ucfirst($status);
    $colorType = \App\Services\StatusColorService::getAssignmentStatusColor($status);
    $badgeVariant = match ($colorType) {
        'success' => 'success',
        'danger' => 'danger',
        'warning' => 'warning',
        'info' => 'info',
        default => 'info',
    };
@endphp

<x-ui.card class="dt-card" wire:key="assignment-card-{{ $assignment->id }}">
    <div class="dt-card__title mb-2">
        {{ $assignment->project->name }}
        <a href="{{ route('project-assignments.show', $assignment) }}" class="stretched-link visually-hidden">Szczegóły przypisania</a>
    </div>

    @unless($hideEmployee)
        <div class="dt-card__row">
            <span class="dt-card__label">Pracownik</span>
            <span class="dt-card__value">{{ $assignment->employee->full_name }}</span>
        </div>
    @endunless

    <div class="dt-card__row">
        <span class="dt-card__label">Rola</span>
        <span class="dt-card__value"><x-ui.badge variant="info">{{ $assignment->role->name }}</x-ui.badge></span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Okres</span>
        <span class="dt-card__value">
            {{ $assignment->start_date->format('d.m.Y') }}
            –
            {{ $assignment->end_date ? $assignment->end_date->format('d.m.Y') : '...' }}
        </span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Status</span>
        <span class="dt-card__value"><x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge></span>
    </div>
</x-ui.card>
