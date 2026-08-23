@props(['project', 'isMineView' => false])

@php
    $status = $project->status ?? \App\Enums\ProjectStatus::ACTIVE;
    $statusLabel = $status instanceof \App\Enums\ProjectStatus ? $status->label() : ucfirst($status);
    $badgeVariant = match(\App\Services\StatusColorService::getProjectStatusColor($status)) {
        'success' => 'success',
        'danger'  => 'danger',
        'warning' => 'warning',
        default   => 'info',
    };

    if ($project->isScheduled()) {
        $stateLabel   = 'Zaplanowany';
        $stateVariant = 'warning';
    } elseif ($project->isCurrentlyActive()) {
        $stateLabel   = 'Aktywny';
        $stateVariant = 'success';
    } elseif ($project->isPast()) {
        $stateLabel   = 'Zakończony';
        $stateVariant = 'info';
    } else {
        $stateLabel   = 'Brak dat';
        $stateVariant = 'accent';
    }

    $showRoute = $isMineView ? 'mine.projects.show' : 'projects.show';
@endphp

<x-ui.card class="dt-card" wire:key="project-card-{{ $project->id }}">
    <div class="dt-card__title">
        <a href="{{ route($showRoute, $project) }}" class="stretched-link">{{ $project->name }}</a>
    </div>

    @if($project->client_name)
        <div class="dt-card__row">
            <span class="dt-card__label">Klient</span>
            <span class="dt-card__value">{{ $project->client_name }}</span>
        </div>
    @endif

    <div class="dt-card__row">
        <span class="dt-card__label">Lokalizacja</span>
        <span class="dt-card__value">{{ $project->location?->name ?? '—' }}</span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Okres</span>
        <span class="dt-card__value">
            {{ $project->start_date?->format('d.m.Y') ?? '—' }}
            –
            {{ $project->end_date?->format('d.m.Y') ?? '—' }}
        </span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Stan</span>
        <span class="dt-card__value">
            <x-ui.badge variant="{{ $stateVariant }}">{{ $stateLabel }}</x-ui.badge>
        </span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Status</span>
        <span class="dt-card__value">
            <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
        </span>
    </div>

</x-ui.card>
