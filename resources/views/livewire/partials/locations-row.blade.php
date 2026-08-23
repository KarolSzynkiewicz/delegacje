@props(['location'])

<tr wire:key="location-{{ $location->id }}">
    <td class="fw-medium">{{ $location->name }}</td>
    <td>{{ $location->address }}</td>
    <td>{{ $location->city ?? '—' }}</td>
    <td>
        <div class="d-flex flex-wrap gap-1">
            @forelse ($location->purposes as $lp)
                @php $pt = $lp->purpose; @endphp
                @if($pt instanceof \App\Enums\LocationPurposeType)
                    <x-ui.badge variant="{{ $pt->badgeVariant() }}">{{ $pt->label() }}</x-ui.badge>
                @else
                    <x-ui.badge variant="secondary">{{ $lp->purpose }}</x-ui.badge>
                @endif
            @empty
                <span class="text-muted small">—</span>
            @endforelse
        </div>
    </td>
    <td>
        @if($location->hasCoordinates())
            <div>
                <a
                    href="https://www.openstreetmap.org/?mlat={{ $location->latitude }}&mlon={{ $location->longitude }}&zoom=15"
                    target="_blank"
                    class="text-decoration-none d-inline-flex align-items-center gap-1"
                    title="Otwórz na mapie"
                    style="color: var(--success); cursor: pointer;"
                >
                    <i class="bi bi-geo-alt-fill"></i>
                    <small>
                        {{ number_format((float) $location->latitude, 6) }}, {{ number_format((float) $location->longitude, 6) }}
                    </small>
                </a>
            </div>
            <div class="mt-1">
                <a
                    href="https://www.openstreetmap.org/?mlat={{ $location->latitude }}&mlon={{ $location->longitude }}&zoom=15"
                    target="_blank"
                    class="btn btn-sm btn-outline-secondary"
                    style="font-size: 0.75rem; padding: 0.25rem 0.5rem;"
                    title="Otwórz na mapie"
                >
                    <i class="bi bi-map"></i> Mapa
                </a>
            </div>
        @else
            <small class="text-muted">
                <i class="bi bi-geo-alt"></i> Brak
            </small>
        @endif
    </td>
    <td>
        @if($location->contact_person)
            <div>{{ $location->contact_person }}</div>
            @if($location->phone)
                <small class="text-muted d-block">{{ $location->phone }}</small>
            @endif
            @if($location->email)
                <small class="text-muted d-block">{{ $location->email }}</small>
            @endif
        @else
            —
        @endif
    </td>
    <td>
        <x-action-buttons
            viewRoute="{{ route('locations.show', $location) }}"
            editRoute="{{ route('locations.edit', $location) }}"
            deleteRoute="{{ route('locations.destroy', $location) }}"
            deleteMessage="Czy na pewno chcesz usunąć tę lokalizację?"
        />
    </td>
</tr>
