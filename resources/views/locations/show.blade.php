<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Lokalizacja: {{ $location->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('locations.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('locations.edit', $location) }}"
                    routeName="locations.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <x-ui.card label="Informacje podstawowe">
                <x-ui.detail-list>
                    <x-ui.detail-item label="Nazwa">{{ $location->name }}</x-ui.detail-item>
                    <x-ui.detail-item label="Adres">{{ $location->address }}</x-ui.detail-item>
                    @if($location->city)
                    <x-ui.detail-item label="Miasto">{{ $location->city }}</x-ui.detail-item>
                    @endif
                    @if($location->postal_code)
                    <x-ui.detail-item label="Kod pocztowy">{{ $location->postal_code }}</x-ui.detail-item>
                    @endif
                    <x-ui.detail-item label="Baza">
                        @if($location->is_base)
                            <x-ui.badge variant="success">Tak - Lokalizacja jest bazą</x-ui.badge>
                        @else
                            <span class="text-muted">Nie</span>
                        @endif
                    </x-ui.detail-item>
                    @if($location->contact_person)
                    <x-ui.detail-item label="Osoba kontaktowa">{{ $location->contact_person }}</x-ui.detail-item>
                    @endif
                    @if($location->phone)
                    <x-ui.detail-item label="Telefon">{{ $location->phone }}</x-ui.detail-item>
                    @endif
                    @if($location->email)
                    <x-ui.detail-item label="Email">{{ $location->email }}</x-ui.detail-item>
                    @endif
                    @if($location->description)
                    <x-ui.detail-item label="Opis" fullWidth>{{ $location->description }}</x-ui.detail-item>
                    @endif
                </x-ui.detail-list>
            </x-ui.card>

            @if($location->projects->count() > 0)
            <x-ui.card label="Projekty w tej lokalizacji ({{ $location->projects->count() }})" class="mt-4">
                <ul class="list-group-ui">
                    @foreach($location->projects as $project)
                        <li class="list-group-item-ui">
                            <a href="{{ route('projects.show', $project) }}" class="list-group-item-action-ui d-flex align-items-center justify-content-between text-decoration-none">
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">{{ $project->name }}</div>
                                    @if($project->client_name)
                                        <div class="small text-muted mt-1">{{ $project->client_name }}</div>
                                    @endif
                                </div>
                                <i class="bi bi-arrow-right text-muted"></i>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
            @endif
        </div>
    </div>
</x-app-layout>
