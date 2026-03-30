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
                    @if($location->country)
                    <x-ui.detail-item label="Kraj">{{ $location->country->labelWithFlag() }}</x-ui.detail-item>
                    @endif
                    @if($location->hasCoordinates())
                    <x-ui.detail-item label="Współrzędne geograficzne">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span>
                                <i class="bi bi-geo-alt-fill text-success"></i>
                                {{ number_format((float)$location->latitude, 8) }}, {{ number_format((float)$location->longitude, 8) }}
                            </span>
                            <a 
                                href="https://www.openstreetmap.org/?mlat={{ $location->latitude }}&mlon={{ $location->longitude }}&zoom=15" 
                                target="_blank"
                                class="btn btn-sm btn-outline-secondary"
                                title="Otwórz na mapie"
                            >
                                <i class="bi bi-map"></i> Otwórz na mapie
                            </a>
                        </div>
                    </x-ui.detail-item>
                    @endif
                    <x-ui.detail-item label="Typy / cele lokalizacji">
                        @if($location->purposes->isNotEmpty())
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($location->purposes as $p)
                                    @php $pt = $p->purpose; @endphp
                                    @if($pt instanceof \App\Enums\LocationPurposeType)
                                        <x-ui.badge variant="{{ $pt->badgeVariant() }}">{{ $pt->label() }}</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="secondary">{{ $p->purpose }}</x-ui.badge>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <span class="text-muted">Brak przypisanych typów</span>
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

            @php $locationAccommodations = $location->accommodations()->orderBy('lease_start_date', 'desc')->get(); @endphp
            @if($locationAccommodations->isNotEmpty())
            <x-ui.card label="Wynajmy w tej lokalizacji ({{ $locationAccommodations->count() }})" class="mt-4">
                <ul class="list-group-ui">
                    @foreach($locationAccommodations as $acc)
                        <li class="list-group-item-ui">
                            <a href="{{ route('accommodations.show', $acc) }}" class="list-group-item-action-ui d-flex align-items-center justify-content-between text-decoration-none">
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">{{ $acc->name }}</div>
                                    <div class="small text-muted mt-1">
                                        @if($acc->lease_start_date)
                                            {{ $acc->lease_start_date->format('d.m.Y') }}
                                            @if($acc->lease_end_date) – {{ $acc->lease_end_date->format('d.m.Y') }} @endif
                                        @else
                                            Brak dat najmu
                                        @endif
                                        · {{ $acc->capacity }} os.
                                    </div>
                                </div>
                                <i class="bi bi-arrow-right text-muted"></i>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
            @endif

            @php $locationRepairs = $location->vehicleRepairs()->with('vehicle')->orderBy('start_date', 'desc')->get(); @endphp
            @if($locationRepairs->isNotEmpty())
            <x-ui.card label="Naprawy / serwis w tym warsztacie ({{ $locationRepairs->count() }})" class="mt-4">
                <ul class="list-group-ui">
                    @foreach($locationRepairs as $repair)
                        <li class="list-group-item-ui">
                            <a href="{{ route('vehicle-repairs.show', $repair) }}" class="list-group-item-action-ui d-flex align-items-center justify-content-between text-decoration-none">
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">
                                        {{ $repair->vehicle->registration_number ?? '—' }}
                                        <span class="ms-2 text-muted small">{{ $repair->action_type?->label() }}</span>
                                    </div>
                                    <div class="small text-muted mt-1">
                                        {{ $repair->start_date?->format('d.m.Y') }}
                                        @if($repair->end_date) – {{ $repair->end_date->format('d.m.Y') }} @endif
                                    </div>
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
