@props(['location'])

<x-ui.card class="dt-card" wire:key="location-card-{{ $location->id }}">
    <div class="dt-card__title">
        <a href="{{ route('locations.show', $location) }}" class="stretched-link">{{ $location->name }}</a>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Adres</span>
        <span class="dt-card__value">{{ $location->address }}</span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Miasto</span>
        <span class="dt-card__value">{{ $location->city ?? '—' }}</span>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Typ</span>
        <span class="dt-card__value">
            <span class="d-inline-flex flex-wrap gap-1 justify-content-end">
                @forelse ($location->purposes as $lp)
                    @php $pt = $lp->purpose; @endphp
                    @if($pt instanceof \App\Enums\LocationPurposeType)
                        <x-ui.badge variant="{{ $pt->badgeVariant() }}">{{ $pt->label() }}</x-ui.badge>
                    @else
                        <x-ui.badge variant="secondary">{{ $lp->purpose }}</x-ui.badge>
                    @endif
                @empty
                    <span class="text-muted">—</span>
                @endforelse
            </span>
        </span>
    </div>

    @if($location->hasCoordinates())
        <div class="dt-card__row">
            <span class="dt-card__label">Mapa</span>
            <span class="dt-card__value">
                <a
                    href="https://www.openstreetmap.org/?mlat={{ $location->latitude }}&mlon={{ $location->longitude }}&zoom=15"
                    target="_blank"
                    class="btn btn-sm btn-outline-secondary"
                >
                    <i class="bi bi-map"></i> Otwórz
                </a>
            </span>
        </div>
    @endif

    @if($location->contact_person || $location->phone || $location->email)
        <div class="dt-card__row">
            <span class="dt-card__label">Kontakt</span>
            <span class="dt-card__value">
                @if($location->contact_person)
                    <div>{{ $location->contact_person }}</div>
                @endif
                @if($location->phone)
                    <small class="text-muted d-block">{{ $location->phone }}</small>
                @endif
                @if($location->email)
                    <small class="text-muted d-block">{{ $location->email }}</small>
                @endif
            </span>
        </div>
    @endif

    <div class="dt-card__actions">
        <x-action-buttons
            editRoute="{{ route('locations.edit', $location) }}"
            deleteRoute="{{ route('locations.destroy', $location) }}"
            deleteMessage="Czy na pewno chcesz usunąć tę lokalizację?"
        />
    </div>
</x-ui.card>
