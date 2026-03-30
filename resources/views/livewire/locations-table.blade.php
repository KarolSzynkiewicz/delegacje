<div>
    <x-ui.card class="mb-4">
        <div class="mb-4 pb-3 border-top border-bottom">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h3 class="fs-5 fw-semibold mb-1">Lokalizacje</h3>
                    <p class="small text-muted mb-0">
                        @if($search !== '' || $purposeFilter !== '')
                            Znaleziono: <span class="fw-semibold">{{ $locations->total() }}</span> lokalizacji
                        @else
                            Łącznie: <span class="fw-semibold">{{ $locations->total() }}</span> lokalizacji
                        @endif
                    </p>
                </div>
                @if($search !== '' || $purposeFilter !== '')
                    <x-ui.button variant="ghost" wire:click="clearFilters" class="btn-sm">
                        <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                    </x-ui.button>
                @endif
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label small">
                    <i class="bi bi-search me-1"></i> Szukaj
                </label>
                <div class="position-relative">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Nazwa, adres, miasto, kod…"
                        class="form-control ps-5"
                        autocomplete="off"
                    >
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label small">
                    <i class="bi bi-funnel me-1"></i> Typ lokalizacji
                </label>
                <select wire:model.live="purposeFilter" class="form-select">
                    <option value="">Wszystkie typy</option>
                    @foreach($purposeTypes as $pt)
                        <option value="{{ $pt->value }}">{{ $pt->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card>
        @if($locations->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>
                                <button type="button" wire:click="sortBy('name')" class="btn btn-link p-0 text-start text-decoration-none fw-semibold">
                                    Nazwa
                                    @if($sortField === 'name')
                                        <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-fill small"></i>
                                    @endif
                                </button>
                            </th>
                            <th>
                                <button type="button" wire:click="sortBy('address')" class="btn btn-link p-0 text-start text-decoration-none fw-semibold">
                                    Adres
                                    @if($sortField === 'address')
                                        <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-fill small"></i>
                                    @endif
                                </button>
                            </th>
                            <th>
                                <button type="button" wire:click="sortBy('city')" class="btn btn-link p-0 text-start text-decoration-none fw-semibold">
                                    Miasto
                                    @if($sortField === 'city')
                                        <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-fill small"></i>
                                    @endif
                                </button>
                            </th>
                            <th>Typ</th>
                            <th>Współrzędne</th>
                            <th>Kontakt</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($locations as $location)
                            <tr>
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
                                                    {{ number_format((float)$location->latitude, 6) }}, {{ number_format((float)$location->longitude, 6) }}
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
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $locations->links() }}
            </div>
        @else
            <x-ui.empty-state
                icon="inbox"
                message="Brak lokalizacji spełniających kryteria."
            >
                @if($search !== '' || $purposeFilter !== '')
                    <x-ui.button variant="ghost" wire:click="clearFilters">
                        Wyczyść filtry
                    </x-ui.button>
                @else
                    <x-ui.button
                        variant="primary"
                        href="{{ route('locations.create') }}"
                        routeName="locations.create"
                        action="create"
                    >
                        Dodaj pierwszą lokalizację
                    </x-ui.button>
                @endif
            </x-ui.empty-state>
        @endif
    </x-ui.card>
</div>
