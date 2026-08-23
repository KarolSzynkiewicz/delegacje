<div>
    <x-data-table :paginator="$vehicles" :has-filters="(bool) ($search || $conditionFilter || $statusFilter || $locationFilter || $statusDate)">
        <x-slot:filters>
            <x-data-table-filters :count="$vehicles->total()">
                @if($statusDate)
                    <x-slot:note>
                        stan na {{ \Carbon\Carbon::parse($statusDate)->format('d.m.Y') }}
                    </x-slot:note>
                @endif
                <x-data-table-search
                    wire:model.live.debounce.300ms="search"
                    placeholder="Nr rej., marka..."
                />
                <input type="date" wire:model.live="statusDate" class="form-control form-control-sm" title="Stan na dzień">
                <select wire:model.live="conditionFilter" class="form-select form-select-sm">
                    <option value="">Stan: wszystkie</option>
                    @foreach(\App\Enums\VehicleCondition::cases() as $condition)
                        <option value="{{ $condition->value }}">{{ $condition->label() }}</option>
                    @endforeach
                </select>
                <select wire:model.live="statusFilter" class="form-select form-select-sm">
                    <option value="">Status: wszystkie</option>
                    <option value="occupied">Zajęty</option>
                    <option value="available">Wolny</option>
                </select>
                <select wire:model.live="locationFilter" class="form-select form-select-sm">
                    <option value="">Lokalizacja: wszystkie</option>
                    <option value="base">Baza</option>
                    <option value="field">W terenie</option>
                    <option value="transit">W podróży</option>
                </select>
            </x-data-table-filters>
        </x-slot:filters>

        <x-slot:activeFilters>
            @if($search)
                <x-data-table-filter-chip label="Szukaj: {{ $search }}" wire:click="$set('search', '')" />
            @endif
            @if($statusDate)
                <x-data-table-filter-chip label="Stan na: {{ \Carbon\Carbon::parse($statusDate)->format('d.m.Y') }}" wire:click="$set('statusDate', '')" />
            @endif
            @if($conditionFilter)
                @php $condLabel = \App\Enums\VehicleCondition::tryFrom($conditionFilter)?->label() ?? $conditionFilter; @endphp
                <x-data-table-filter-chip label="Stan techniczny: {{ $condLabel }}" wire:click="$set('conditionFilter', '')" />
            @endif
            @if($statusFilter === 'occupied')
                <x-data-table-filter-chip label="Status: zajęty" wire:click="$set('statusFilter', '')" />
            @elseif($statusFilter === 'available')
                <x-data-table-filter-chip label="Status: wolny" wire:click="$set('statusFilter', '')" />
            @endif
            @if($locationFilter === 'base')
                <x-data-table-filter-chip label="Lokalizacja: baza" wire:click="$set('locationFilter', '')" />
            @elseif($locationFilter === 'field')
                <x-data-table-filter-chip label="Lokalizacja: w terenie" wire:click="$set('locationFilter', '')" />
            @elseif($locationFilter === 'transit')
                <x-data-table-filter-chip label="Lokalizacja: w podróży" wire:click="$set('locationFilter', '')" />
            @endif
        </x-slot:activeFilters>

        <x-slot:head>
            <tr>
                <th class="text-start">Zdjęcie</th>
                <x-livewire.sortable-header field="registration_number" :sortField="$sortField" :sortDirection="$sortDirection">Nr Rejestracyjny</x-livewire.sortable-header>
                <th class="text-start d-none d-md-table-cell">Marka i Model</th>
                <th class="text-start">Stan</th>
                <th class="text-center">Status</th>
                <th class="text-center d-none d-lg-table-cell">Projekty</th>
                <th class="text-center d-none d-lg-table-cell">Domy</th>
                <th class="text-center d-none d-xl-table-cell">Stacjonowanie</th>
                <th class="text-center">Zapełnienie</th>
                <th class="text-end">Akcje</th>
            </tr>
        </x-slot:head>
        <x-slot:body>
            @foreach ($vehicles as $vehicle)
                @include('livewire.partials.vehicles-row', ['vehicle' => $vehicle, 'checkDate' => $checkDate])
            @endforeach
        </x-slot:body>
        <x-slot:cards>
            @foreach ($vehicles as $vehicle)
                @include('livewire.partials.vehicles-row-card', ['vehicle' => $vehicle, 'checkDate' => $checkDate])
            @endforeach
        </x-slot:cards>
        <x-slot:empty>
            <x-ui.empty-state
                icon="car-front"
                :message="$search || $conditionFilter || $statusFilter || $locationFilter || $statusDate ? 'Brak pojazdów spełniających kryteria' : 'Brak pojazdów'"
                :has-filters="(bool) ($search || $conditionFilter || $statusFilter || $locationFilter || $statusDate)"
                clear-filters-action="wire:clearFilters"
            />
        </x-slot:empty>
    </x-data-table>
</div>
