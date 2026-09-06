<div>
    <x-data-table :paginator="$accommodations" :has-filters="$this->hasExtraFilters()">
        <x-slot:filters>
            <x-data-table-filters :count="$accommodations->total()">
                @if($statusDate)
                    <x-slot:note>
                        stan na {{ $checkDate->format('d.m.Y') }}
                    </x-slot:note>
                @endif
                <x-data-table-search
                    wire:model.live.debounce.300ms="search"
                    placeholder="Nazwa, adres, miasto..."
                />
                <input
                    type="date"
                    wire:model.live="statusDate"
                    class="form-control form-control-sm"
                    title="Stan na dzień"
                    aria-label="Stan na dzień"
                >
                <select wire:model.live="tenureFilter" class="form-select form-select-sm" aria-label="Najem">
                    <option value="current">W użyciu (własne + najem)</option>
                    <option value="owned">Własne</option>
                    <option value="rented">Aktywny najem</option>
                    <option value="ended">Najem zakończony</option>
                    <option value="all">Najem: wszystkie</option>
                </select>
                <select wire:model.live="statusFilter" class="form-select form-select-sm" aria-label="Obłożenie">
                    <option value="">Obłożenie: wszystkie</option>
                    <option value="available">Wolne miejsca</option>
                    <option value="full">Pełne</option>
                    <option value="overfilled">Przepełnione</option>
                </select>
            </x-data-table-filters>
        </x-slot:filters>

        <x-slot:activeFilters>
            @if($search)
                <x-data-table-filter-chip label="Szukaj: {{ $search }}" wire:click="$set('search', '')" />
            @endif
            @if($statusDate)
                <x-data-table-filter-chip label="Stan na: {{ $checkDate->format('d.m.Y') }}" wire:click="$set('statusDate', '')" />
            @endif
            @if($tenureFilter === 'owned')
                <x-data-table-filter-chip label="Najem: własne" wire:click="$set('tenureFilter', 'current')" />
            @elseif($tenureFilter === 'rented')
                <x-data-table-filter-chip label="Najem: aktywny" wire:click="$set('tenureFilter', 'current')" />
            @elseif($tenureFilter === 'ended')
                <x-data-table-filter-chip label="Najem: zakończony" wire:click="$set('tenureFilter', 'current')" />
            @elseif($tenureFilter === 'all')
                <x-data-table-filter-chip label="Najem: wszystkie" wire:click="$set('tenureFilter', 'current')" />
            @endif
            @if($statusFilter === 'full')
                <x-data-table-filter-chip label="Obłożenie: pełne" wire:click="$set('statusFilter', '')" />
            @elseif($statusFilter === 'available')
                <x-data-table-filter-chip label="Obłożenie: wolne miejsca" wire:click="$set('statusFilter', '')" />
            @elseif($statusFilter === 'overfilled')
                <x-data-table-filter-chip label="Obłożenie: przepełnione" wire:click="$set('statusFilter', '')" />
            @endif
        </x-slot:activeFilters>

        <x-slot:head>
            <tr>
                <th class="text-start">Zdjęcie</th>
                <x-livewire.sortable-header field="name" :sortField="$sortField" :sortDirection="$sortDirection">Nazwa</x-livewire.sortable-header>
                <th class="text-start">Lokalizacja</th>
                <th class="text-start d-none d-xl-table-cell">Współrzędne</th>
                <th class="text-start">Najem</th>
                <th class="text-start">Pojemność</th>
                <th class="text-start">Obłożenie</th>
                <th class="text-end">Akcje</th>
            </tr>
        </x-slot:head>
        <x-slot:body>
            @foreach ($accommodations as $accommodation)
                @include('livewire.partials.accommodations-row', ['accommodation' => $accommodation, 'checkDate' => $checkDate])
            @endforeach
        </x-slot:body>
        <x-slot:cards>
            @foreach ($accommodations as $accommodation)
                @include('livewire.partials.accommodations-row-card', ['accommodation' => $accommodation, 'checkDate' => $checkDate])
            @endforeach
        </x-slot:cards>
        <x-slot:empty>
            <x-ui.empty-state
                icon="house-x"
                :message="$this->hasExtraFilters() ? 'Brak mieszkań spełniających kryteria' : 'Brak mieszkań w użyciu — własne albo z aktywnym najmem'"
                :has-filters="$this->hasExtraFilters()"
                clear-filters-action="wire:clearFilters"
            />
        </x-slot:empty>
    </x-data-table>
</div>
