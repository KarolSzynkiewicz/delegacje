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
