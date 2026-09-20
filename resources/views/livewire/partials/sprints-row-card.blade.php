@props(['sprint', 'canMutate' => false])

@php
    $categories = $sprint->categoryLabels();
@endphp

<x-ui.card class="dt-card" wire:key="sprint-card-{{ $sprint->id }}">
    <div class="dt-card__title">
        <a href="{{ route('sprints.show', $sprint) }}" class="stretched-link">{{ $sprint->name }}</a>
    </div>
    @if($sprint->goal)
        <p class="small text-muted mb-2">{{ Str::limit($sprint->goal, 100) }}</p>
    @endif

    <div class="dt-card__row">
        <span class="dt-card__label">Status</span>
        <span class="dt-card__value"><x-sprint.status-badge :sprint="$sprint" /></span>
    </div>
    <div class="dt-card__row">
        <span class="dt-card__label">Termin</span>
        <span class="dt-card__value">{{ $sprint->start_date?->format('d.m') }}–{{ $sprint->end_date?->format('d.m.Y') }}</span>
    </div>
    <div class="dt-card__row align-items-start">
        <span class="dt-card__label">Postęp</span>
        <span class="dt-card__value" style="position:relative; z-index:2;">
            <x-sprint.progress :sprint="$sprint" details="always" />
        </span>
    </div>
    <div class="dt-card__row">
        <span class="dt-card__label">Uczestnicy</span>
        <span class="dt-card__value"><x-ui.avatar-stack :users="$sprint->participants()" size="26px" :max="4" /></span>
    </div>
    @if($categories !== [])
        <div class="dt-card__row">
            <span class="dt-card__label">Kategorie</span>
            <span class="dt-card__value">
                <x-sprint.category-chips
                    :categories="$categories"
                    wrap-class="d-inline-flex flex-wrap gap-1 justify-content-end"
                />
            </span>
        </div>
    @endif

    <div class="dt-card__actions">
        @include('livewire.partials.sprint-row-actions', ['sprint' => $sprint, 'canMutate' => $canMutate])
    </div>
</x-ui.card>
