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

    <x-ui.card label="Informacje podstawowe">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h5 class="fw-bold text-dark mb-2">Nazwa</h5>
                            <p class="text-dark">{{ $location->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5 class="fw-bold text-dark mb-2">Adres</h5>
                            <p class="text-dark">{{ $location->address }}</p>
                        </div>
                        @if($location->city)
                        <div class="col-md-6">
                            <h5 class="fw-bold text-dark mb-2">Miasto</h5>
                            <p class="text-dark">{{ $location->city }}</p>
                        </div>
                        @endif
                        <div class="col-md-6">
                            <h5 class="fw-bold text-dark mb-2">Baza</h5>
                            @if($location->is_base)
                                <x-ui.badge variant="success">Tak - Lokalizacja jest bazą</x-ui.badge>
                            @else
                                <span class="text-muted">Nie</span>
                            @endif
                        </div>
                        @if($location->postal_code)
                        <div class="col-md-6">
                            <h5 class="fw-bold text-dark mb-2">Kod pocztowy</h5>
                            <p class="text-dark">{{ $location->postal_code }}</p>
                        </div>
                        @endif
                        @if($location->contact_person)
                        <div class="col-md-6">
                            <h5 class="fw-bold text-dark mb-2">Osoba kontaktowa</h5>
                            <p class="text-dark">{{ $location->contact_person }}</p>
                        </div>
                        @endif
                        @if($location->phone)
                        <div class="col-md-6">
                            <h5 class="fw-bold text-dark mb-2">Telefon</h5>
                            <p class="text-dark">{{ $location->phone }}</p>
                        </div>
                        @endif
                        @if($location->email)
                        <div class="col-md-6">
                            <h5 class="fw-bold text-dark mb-2">Email</h5>
                            <p class="text-dark">{{ $location->email }}</p>
                        </div>
                        @endif
                        @if($location->description)
                        <div class="col-12">
                            <h5 class="fw-bold text-dark mb-2">Opis</h5>
                            <p class="text-dark">{{ $location->description }}</p>
                        </div>
                        @endif
                    </div>

        @if($location->projects->count() > 0)
        <div class="mt-4 pt-4 border-top">
            <h5 class="fw-bold text-dark mb-3">Projekty w tej lokalizacji ({{ $location->projects->count() }})</h5>
            <ul class="list-group">
                @foreach($location->projects as $project)
                    <li class="list-group-item">
                        <a href="{{ route('projects.show', $project) }}" class="text-decoration-none">
                            {{ $project->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        @endif
    </x-ui.card>
</x-app-layout>
