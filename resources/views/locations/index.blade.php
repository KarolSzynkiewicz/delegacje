<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Lokalizacje">
            <x-slot name="right">
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('locations.create') }}"
                    routeName="locations.create"
                    action="create"
                >
                    Dodaj Lokalizację
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if (session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif

    <x-ui.card>
        @if($locations->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Nazwa</th>
                            <th>Adres</th>
                            <th>Miasto</th>
                            <th>Kraj</th>
                            <th>Współrzędne</th>
                            <th>Baza</th>
                            <th>Kontakt</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($locations as $location)
                            <tr>
                                <td class="fw-medium">{{ $location->name }}</td>
                                <td>{{ $location->address }}</td>
                                <td>{{ $location->city ?? '-' }}</td>
                                <td>{{ $location->country ? $location->country->labelWithFlag() : '-' }}</td>
                                <td>
                                    @if($location->hasCoordinates())
                                        <div>
                                            <a 
                                                href="https://www.openstreetmap.org/?mlat={{ $location->latitude }}&mlon={{ $location->longitude }}&zoom=15" 
                                                target="_blank"
                                                class="text-decoration-none d-inline-flex align-items-center gap-1"
                                                title="Otwórz na mapie"
                                                style="color: var(--success); cursor: pointer;"
                                                onmouseover="this.style.opacity='0.8';"
                                                onmouseout="this.style.opacity='1';"
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
                                    @if($location->is_base)
                                        <x-ui.badge variant="success">Baza</x-ui.badge>
                                    @else
                                        <span class="text-muted">-</span>
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
                                        -
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
        @else
            <x-ui.empty-state 
                icon="inbox" 
                message="Brak lokalizacji w systemie."
            >
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('locations.create') }}"
                    routeName="locations.create"
                    action="create"
                >
                    Dodaj pierwszą lokalizację
                </x-ui.button>
            </x-ui.empty-state>
        @endif
    </x-ui.card>
</x-app-layout>
