<div>
    <x-data-table :paginator="$projects" :has-filters="(bool) ($search || $statusFilter || $locationFilter)">
        <x-slot:filters>
            <x-data-table-filters :count="$projects->total()">
                <x-data-table-search
                    wire:model.live.debounce.500ms="search"
                    placeholder="Nazwa projektu lub klient..."
                />
                <select wire:model.live.debounce.300ms="statusFilter" class="form-select form-select-sm">
                    <option value="">Status: wszystkie</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
                <select wire:model.live.debounce.300ms="locationFilter" class="form-select form-select-sm">
                    <option value="">Lokalizacja: wszystkie</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </x-data-table-filters>
        </x-slot:filters>

        <x-slot:activeFilters>
            @if($search)
                <x-data-table-filter-chip label="Szukaj: {{ $search }}" wire:click="$set('search', '')" />
            @endif
            @if($statusFilter)
                @php $statusLabel = collect($statuses)->first(fn ($s) => $s->value === $statusFilter)?->label() ?? $statusFilter; @endphp
                <x-data-table-filter-chip label="Status: {{ $statusLabel }}" wire:click="$set('statusFilter', '')" />
            @endif
            @if($locationFilter)
                @php $locLabel = $locations->firstWhere('id', (int) $locationFilter)?->name ?? $locationFilter; @endphp
                <x-data-table-filter-chip label="Lokalizacja: {{ $locLabel }}" wire:click="$set('locationFilter', '')" />
            @endif
        </x-slot:activeFilters>

        <x-slot:head>
            <tr>
                <x-livewire.sortable-header field="name" :sortField="$sortField" :sortDirection="$sortDirection">Nazwa</x-livewire.sortable-header>
                <th class="text-start d-none d-md-table-cell">Klient</th>
                <th class="text-start">Lokalizacja</th>
                <x-livewire.sortable-header field="start_date" :sortField="$sortField" :sortDirection="$sortDirection" class="text-start">Data od</x-livewire.sortable-header>
                <x-livewire.sortable-header field="end_date" :sortField="$sortField" :sortDirection="$sortDirection" class="text-start">Data do</x-livewire.sortable-header>
                <th class="text-start">Stan</th>
                <x-livewire.sortable-header field="status" :sortField="$sortField" :sortDirection="$sortDirection">Status</x-livewire.sortable-header>
                <th class="text-end">Akcje</th>
            </tr>
        </x-slot:head>
        <x-slot:body>
            @foreach ($projects as $project)
                @include('livewire.partials.projects-row', ['project' => $project, 'isMineView' => $isMineView])
            @endforeach
        </x-slot:body>
        <x-slot:cards>
            @foreach ($projects as $project)
                @include('livewire.partials.projects-row-card', ['project' => $project, 'isMineView' => $isMineView])
            @endforeach
        </x-slot:cards>
        <x-slot:empty>
            <x-ui.empty-state
                icon="folder-x"
                :message="$search || $statusFilter || $locationFilter ? 'Brak projektów spełniających kryteria wyszukiwania' : 'Brak projektów'"
                :has-filters="(bool) ($search || $statusFilter || $locationFilter)"
                clear-filters-action="wire:clearFilters"
            />
        </x-slot:empty>
    </x-data-table>
</div>
