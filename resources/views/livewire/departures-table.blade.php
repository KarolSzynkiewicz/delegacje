<div>
    <x-data-table :paginator="$departures" :has-filters="$this->hasLogisticsFilters()">
        <x-slot:filters>
            @include('livewire.partials.logistics-events-filters', [
                'total' => $departures->total(),
                'vehicles' => $vehicles,
            ])
        </x-slot:filters>

        <x-slot:activeFilters>
            @include('livewire.partials.logistics-events-active-filters', ['vehicles' => $vehicles])
        </x-slot:activeFilters>

        <x-slot:head>
            <tr>
                <th class="text-start text-nowrap">
                    <button type="button" wire:click="sortBy('id')" class="btn btn-link p-0 text-start text-decoration-none fw-semibold">
                        ID
                        @if($sortField === 'id')
                            <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-fill small"></i>
                        @endif
                    </button>
                </th>
                <th class="text-start text-nowrap">
                    <button type="button" wire:click="sortBy('event_date')" class="btn btn-link p-0 text-start text-decoration-none fw-semibold">
                        Daty
                        @if($sortField === 'event_date')
                            <i class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-fill small"></i>
                        @endif
                    </button>
                </th>
                <th class="text-start">Trasa</th>
                <th class="text-start">Pojazd</th>
                <th class="text-start">Uczestnicy</th>
                <th class="text-start">Status</th>
                <th class="text-end">Akcje</th>
            </tr>
        </x-slot:head>
        <x-slot:body>
            @foreach ($departures as $departure)
                @include('livewire.partials.departures-row', ['departure' => $departure])
            @endforeach
        </x-slot:body>
        <x-slot:cards>
            @foreach ($departures as $departure)
                @include('livewire.partials.departures-row-card', ['departure' => $departure])
            @endforeach
        </x-slot:cards>
        <x-slot:empty>
            <x-ui.empty-state icon="airplane" message="Brak wyjazdów" :has-filters="$this->hasLogisticsFilters()" clear-filters-action="wire:clearFilters" />
        </x-slot:empty>
    </x-data-table>
</div>
