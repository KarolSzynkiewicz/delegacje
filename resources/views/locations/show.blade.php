<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Lokalizacja: {{ $location->name }}</h2>
            <div class="d-flex gap-2">
                <x-edit-button href="{{ route('locations.edit', $location) }}" />
                <a href="{{ route('locations.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Powrót
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <div class="card shadow-sm border-0">
                <div class="card-body">
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
                                <span class="badge bg-success">Tak - Lokalizacja jest bazą</span>
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
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
