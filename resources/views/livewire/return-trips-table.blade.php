<div>
    <x-data-table :paginator="$returnTrips" :has-filters="$this->hasLogisticsFilters()">
        <x-slot:filters>
            @include('livewire.partials.logistics-events-filters', [
                'total' => $returnTrips->total(),
                'vehicles' => $vehicles,
            ])
        </x-slot:filters>

        <x-slot:activeFilters>
            @include('livewire.partials.logistics-events-active-filters', ['vehicles' => $vehicles])
        </x-slot:activeFilters>

        <x-slot:head>
            <tr>
                <th class="text-nowrap">
                    <button type="button" wire:click="sortBy('id')" class="btn btn-link p-0 text-start text-decoration-none fw-semibold">
                        ID
                        @if($sortField === 'id')
                            <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-fill small"></i>
                        @endif
                    </button>
                </th>
                <th class="text-nowrap">
                    <button type="button" wire:click="sortBy('event_date')" class="btn btn-link p-0 text-start text-decoration-none fw-semibold">
                        Data
                        @if($sortField === 'event_date')
                            <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-fill small"></i>
                        @endif
                    </button>
                </th>
                <th>Pojazd</th>
                <th>Z</th>
                <th>Do</th>
                <th>Uczestnicy</th>
                <th>Status</th>
                <th class="text-nowrap">
                    <button type="button" wire:click="sortBy('created_at')" class="btn btn-link p-0 text-start text-decoration-none fw-semibold">
                        Utworzono
                        @if($sortField === 'created_at')
                            <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-fill small"></i>
                        @endif
                    </button>
                </th>
                <th>Akcje</th>
            </tr>
        </x-slot:head>
        <x-slot:body>
            @foreach ($returnTrips as $trip)
                @include('livewire.partials.return-trips-row', ['trip' => $trip])
            @endforeach
        </x-slot:body>
        <x-slot:cards>
            @foreach ($returnTrips as $trip)
                @include('livewire.partials.return-trips-row-card', ['trip' => $trip])
            @endforeach
        </x-slot:cards>
        <x-slot:empty>
            <x-ui.empty-state icon="inbox" message="Brak zjazdów w systemie." :has-filters="$this->hasLogisticsFilters()" clear-filters-action="wire:clearFilters">
                @unless($this->hasLogisticsFilters())
                    <x-ui.button variant="primary" href="{{ route('return-trips.create') }}" routeName="return-trips.create" action="create">
                        Utwórz pierwszy zjazd
                    </x-ui.button>
                @endunless
            </x-ui.empty-state>
        </x-slot:empty>
    </x-data-table>
</div>
