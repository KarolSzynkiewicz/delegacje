@props(['accommodation'])

@php
    $currentCount = $accommodation->currentAssignments()->count();
    $isFull = $currentCount >= $accommodation->capacity;
    $isOverfilled = $currentCount > $accommodation->capacity;
@endphp

<x-ui.card class="dt-card" wire:key="accommodation-card-{{ $accommodation->id }}">
    <div class="d-flex align-items-start gap-3 mb-2">
        <x-ui.avatar
            :image-url="$accommodation->image_path ? $accommodation->image_url : null"
            :alt="$accommodation->name"
            :initials="substr($accommodation->name, 0, 2)"
            size="48px"
            shape="rounded"
        />
        <div class="dt-card__title mb-0 flex-grow-1">
            <a href="{{ route('accommodations.show', $accommodation) }}" class="stretched-link">{{ $accommodation->name }}</a>
        </div>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Lokalizacja</span>
        <span class="dt-card__value">
            @if($accommodation->location)
                {{ $accommodation->location->name }}
            @elseif($accommodation->address)
                {{ $accommodation->address }}
            @else
                —
            @endif
        </span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Pojemność</span>
        <span class="dt-card__value {{ $isOverfilled ? 'text-danger' : '' }}">
            {{ $currentCount }} / {{ $accommodation->capacity }}
        </span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Status</span>
        <span class="dt-card__value">
            @if($isOverfilled)
                <x-ui.badge variant="danger">Przepełnione</x-ui.badge>
            @elseif($isFull)
                <x-ui.badge variant="warning">Pełne</x-ui.badge>
            @else
                <x-ui.badge variant="success">Wolne miejsca</x-ui.badge>
            @endif
        </span>
    </div>

    <div class="dt-card__actions">
        <x-action-buttons
            editRoute="{{ route('accommodations.edit', $accommodation) }}"
            deleteRoute="{{ route('accommodations.destroy', $accommodation) }}"
        />
    </div>
</x-ui.card>
