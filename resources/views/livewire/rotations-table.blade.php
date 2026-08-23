<div>
    @if(session('success'))
        <x-ui.alert variant="success" dismissible class="mb-3">{{ session('success') }}</x-ui.alert>
    @endif

    <x-data-table :paginator="$rotations" :has-filters="(bool) ($search || $statusFilter)">
        <x-slot:filters>
            <x-data-table-filters :count="$rotations->total()">
                <x-data-table-search
                    wire:model.live.debounce.300ms="search"
                    placeholder="Imię lub nazwisko..."
                />
                <select wire:model.live="statusFilter" class="form-select form-select-sm">
                    <option value="">Status: wszystkie</option>
                    <option value="scheduled">Zaplanowana</option>
                    <option value="active">Aktywna</option>
                    <option value="completed">Zakończona</option>
                    <option value="cancelled">Anulowana</option>
                </select>
            </x-data-table-filters>
        </x-slot:filters>

        <x-slot:activeFilters>
            @if($search !== '')
                <x-data-table-filter-chip label="Szukaj: {{ $search }}" wire:click="$set('search', '')" />
            @endif
            @if($statusFilter !== '')
                @php
                    $rotationStatusLabels = [
                        'scheduled' => 'Zaplanowana',
                        'active' => 'Aktywna',
                        'completed' => 'Zakończona',
                        'cancelled' => 'Anulowana',
                    ];
                @endphp
                <x-data-table-filter-chip
                    label="Status: {{ $rotationStatusLabels[$statusFilter] ?? $statusFilter }}"
                    wire:click="$set('statusFilter', '')"
                />
            @endif
        </x-slot:activeFilters>

        <x-slot:head>
            <tr>
                <x-livewire.sortable-header field="employee_id" :sortField="$sortField" :sortDirection="$sortDirection">Pracownik</x-livewire.sortable-header>
                <x-livewire.sortable-header field="start_date" :sortField="$sortField" :sortDirection="$sortDirection">Data rozpoczęcia</x-livewire.sortable-header>
                <x-livewire.sortable-header field="end_date" :sortField="$sortField" :sortDirection="$sortDirection">Data zakończenia</x-livewire.sortable-header>
                <th class="text-end text-nowrap">Długość</th>
                <th class="text-start">Status</th>
                <th class="text-start">Notatki</th>
                <th class="text-start">Akcje</th>
            </tr>
        </x-slot:head>
        <x-slot:body>
            @foreach($rotations as $rotation)
                @include('livewire.partials.rotations-row', ['rotation' => $rotation])
            @endforeach
        </x-slot:body>
        <x-slot:cards>
            @foreach($rotations as $rotation)
                @include('livewire.partials.rotations-row-card', ['rotation' => $rotation])
            @endforeach
        </x-slot:cards>
        <x-slot:empty>
            <x-ui.empty-state
                icon="inbox"
                :message="$search || $statusFilter ? 'Nie znaleziono rotacji spełniających kryteria wyszukiwania.' : 'Brak rotacji w systemie.'"
                :has-filters="(bool) ($search || $statusFilter)"
                clear-filters-action="wire:clearFilters"
            >
                @if(empty($search) && empty($statusFilter))
                    <x-ui.button variant="primary" href="{{ route('rotations.create') }}">
                        <i class="bi bi-plus-circle"></i> Dodaj pierwszą rotację
                    </x-ui.button>
                @endif
            </x-ui.empty-state>
        </x-slot:empty>
    </x-data-table>
</div>
