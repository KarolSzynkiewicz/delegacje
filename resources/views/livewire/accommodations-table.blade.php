<div>
    <x-data-table :paginator="$accommodations" :has-filters="(bool) ($search || $statusFilter)">
        <x-slot:filters>
            <x-data-table-filters :count="$accommodations->total()">
                <x-data-table-search
                    wire:model.live.debounce.300ms="search"
                    placeholder="Nazwa, adres, miasto..."
                />
                <select wire:model.live="statusFilter" class="form-select form-select-sm">
                    <option value="">Status: wszystkie</option>
                    <option value="full">Pełne</option>
                    <option value="available">Wolne miejsca</option>
                </select>
            </x-data-table-filters>
        </x-slot:filters>

        <x-slot:activeFilters>
            @if($search)
                <x-data-table-filter-chip label="Szukaj: {{ $search }}" wire:click="$set('search', '')" />
            @endif
            @if($statusFilter === 'full')
                <x-data-table-filter-chip label="Status: pełne" wire:click="$set('statusFilter', '')" />
            @elseif($statusFilter === 'available')
                <x-data-table-filter-chip label="Status: wolne miejsca" wire:click="$set('statusFilter', '')" />
            @endif
        </x-slot:activeFilters>

        <x-slot:head>
            <tr>
                <th class="text-start">Zdjęcie</th>
                <x-livewire.sortable-header field="name" :sortField="$sortField" :sortDirection="$sortDirection">Nazwa</x-livewire.sortable-header>
                <th class="text-start">Lokalizacja</th>
                <th class="text-start">Współrzędne</th>
                <th class="text-start">Pojemność</th>
                <th class="text-start">Status</th>
                <th class="text-end">Akcje</th>
            </tr>
        </x-slot:head>
        <x-slot:body>
            @foreach ($accommodations as $accommodation)
                @include('livewire.partials.accommodations-row', ['accommodation' => $accommodation])
            @endforeach
        </x-slot:body>
        <x-slot:cards>
            @foreach ($accommodations as $accommodation)
                @include('livewire.partials.accommodations-row-card', ['accommodation' => $accommodation])
            @endforeach
        </x-slot:cards>
        <x-slot:empty>
            <x-ui.empty-state
                icon="house-x"
                :message="$search || $statusFilter ? 'Brak mieszkań spełniających kryteria' : 'Brak mieszkań'"
                :has-filters="(bool) ($search || $statusFilter)"
                clear-filters-action="wire:clearFilters"
            />
        </x-slot:empty>
    </x-data-table>
</div>
