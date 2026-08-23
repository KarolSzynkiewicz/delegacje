<div>
    <x-data-table
        :paginator="$locations"
        :has-filters="$search !== '' || $purposeFilter !== ''"
    >
        <x-slot:filters>
            <x-data-table-filters :count="$locations->total()">
                <x-data-table-search
                    wire:model.live.debounce.300ms="search"
                    placeholder="Szukaj: nazwa, adres, miasto, kod…"
                    wide
                />
                <select wire:model.live="purposeFilter" class="form-select form-select-sm">
                    <option value="">Typ: wszystkie</option>
                    @foreach($purposeTypes as $pt)
                        <option value="{{ $pt->value }}">{{ $pt->label() }}</option>
                    @endforeach
                </select>
            </x-data-table-filters>
        </x-slot:filters>

        <x-slot:activeFilters>
            @if($search !== '')
                <x-data-table-filter-chip label="Szukaj: {{ $search }}" wire:click="$set('search', '')" />
            @endif
            @if($purposeFilter !== '')
                @php $purposeLabel = collect($purposeTypes)->first(fn ($pt) => $pt->value === $purposeFilter)?->label() ?? $purposeFilter; @endphp
                <x-data-table-filter-chip label="Typ: {{ $purposeLabel }}" wire:click="$set('purposeFilter', '')" />
            @endif
        </x-slot:activeFilters>

        <x-slot:head>
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
        </x-slot:head>

        <x-slot:body>
            @foreach ($locations as $location)
                @include('livewire.partials.locations-row', ['location' => $location])
            @endforeach
        </x-slot:body>

        <x-slot:cards>
            @foreach ($locations as $location)
                @include('livewire.partials.locations-row-card', ['location' => $location])
            @endforeach
        </x-slot:cards>

        <x-slot:empty>
            <x-ui.empty-state
                icon="inbox"
                message="Brak lokalizacji spełniających kryteria."
                :has-filters="$search !== '' || $purposeFilter !== ''"
                clear-filters-action="wire:clearFilters"
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
        </x-slot:empty>
    </x-data-table>
</div>
