@props(['accommodation', 'checkDate' => null])

@php
    $checkDate = $checkDate ?? now();
    $coveringLease = $accommodation->leaseCoveringDate($checkDate);
    $currentCount = $accommodation->occupied_count ?? $accommodation->getPeakOccupancy($checkDate, $checkDate);
    $isFull = $currentCount >= $accommodation->capacity;
    $isOverfilled = $currentCount > $accommodation->capacity;
    $tenure = $accommodation->currentTenure($checkDate);
@endphp

<tr wire:key="accommodation-{{ $accommodation->id }}">
    <td>
        <div class="d-flex align-items-center justify-content-center">
            <x-ui.avatar
                :image-url="$accommodation->image_path ? $accommodation->image_url : null"
                :alt="$accommodation->name"
                :initials="substr($accommodation->name, 0, 2)"
                size="50px"
                shape="rounded"
            />
        </div>
    </td>
    <td class="fw-medium">{{ $accommodation->name }}</td>
    <td>
        @if($accommodation->location)
            <a href="{{ route('locations.show', $accommodation->location) }}" class="text-decoration-none">
                <span class="fw-medium">{{ $accommodation->location->name }}</span>
                @if($accommodation->location->city)
                    <br><small class="text-muted">{{ $accommodation->location->city }}</small>
                @endif
            </a>
        @elseif($accommodation->address)
            <span class="text-muted small">{{ $accommodation->address }}</span>
        @else
            <span class="text-muted">—</span>
        @endif
    </td>
    <td class="d-none d-xl-table-cell">
        @if($accommodation->hasCoordinates())
            <a
                href="https://www.openstreetmap.org/?mlat={{ floatval($accommodation->latitude) }}&mlon={{ floatval($accommodation->longitude) }}&zoom=15"
                target="_blank"
                class="text-decoration-none d-inline-flex align-items-center gap-1"
                style="color: var(--success);"
            >
                <i class="bi bi-geo-alt-fill"></i>
                <small>{{ number_format((float) $accommodation->latitude, 4) }}, {{ number_format((float) $accommodation->longitude, 4) }}</small>
            </a>
        @else
            <small class="text-muted"><i class="bi bi-geo-alt"></i> Brak</small>
        @endif
    </td>
    <td>
        @if($tenure === 'rented')
            <x-ui.badge variant="info">Najem</x-ui.badge>
            @if($coveringLease?->end_date)
                <div class="small text-muted font-mono mt-1">do {{ $coveringLease->end_date->format('d.m.Y') }}</div>
            @endif
        @elseif($tenure === 'ended')
            <x-ui.badge variant="warning">Najem zakończony</x-ui.badge>
        @else
            <x-ui.badge variant="secondary">Własny</x-ui.badge>
        @endif
    </td>
    <td>
        <span class="small {{ $isOverfilled ? 'text-danger fw-bold' : ($isFull ? 'text-success fw-semibold' : 'text-muted') }}">
            {{ $currentCount }} / {{ $accommodation->capacity }} osób
        </span>
    </td>
    <td>
        @if($tenure === 'ended')
            <x-ui.badge variant="secondary">Poza użytkiem</x-ui.badge>
        @elseif($isOverfilled)
            <x-ui.badge variant="danger">Przepełnione</x-ui.badge>
        @elseif($isFull)
            <x-ui.badge variant="warning">Pełne</x-ui.badge>
        @else
            <x-ui.badge variant="success">Wolne miejsca</x-ui.badge>
        @endif
    </td>
    <td class="text-end">
        <x-action-buttons
            viewRoute="{{ route('accommodations.show', $accommodation) }}"
            editRoute="{{ route('accommodations.edit', $accommodation) }}"
            deleteRoute="{{ route('accommodations.destroy', $accommodation) }}"
        />
    </td>
</tr>
