<div>
    <x-data-table :paginator="$assignments" :has-filters="$this->hasActiveFilters()">
        <x-slot:filters>
            <x-data-table-filters :count="$assignments->total()">
                @unless($this->isEmployeeScoped())
                    <x-data-table-search wire:model.live.debounce.300ms="searchEmployee" placeholder="Pracownik..." />
                @endunless
                <x-data-table-search wire:model.live.debounce.300ms="searchAccommodation" placeholder="Mieszkanie, adres..." />
                <select wire:model.live="statusFilter" class="form-select form-select-sm">
                    <option value="">Status: wszystkie</option>
                    <option value="active">Aktywne</option>
                    <option value="scheduled">Przyszłe</option>
                    <option value="completed">Zakończone</option>
                </select>
            </x-data-table-filters>
        </x-slot:filters>
        <x-slot:activeFilters>
            @if(! $this->isEmployeeScoped() && $searchEmployee)
                <x-data-table-filter-chip label="Pracownik: {{ $searchEmployee }}" wire:click="$set('searchEmployee', '')" />
            @endif
            @if($searchAccommodation)
                <x-data-table-filter-chip label="Mieszkanie: {{ $searchAccommodation }}" wire:click="$set('searchAccommodation', '')" />
            @endif
            @if($statusFilter)
                <x-data-table-filter-chip label="Status: {{ $statusFilter }}" wire:click="$set('statusFilter', '')" />
            @endif
        </x-slot:activeFilters>
        <x-slot:head>
            <tr>
                @unless($this->isEmployeeScoped())
                    <th class="text-start">Pracownik</th>
                @endunless
                <th class="text-start">Mieszkanie</th>
                <th class="text-start">Od – Do</th>
                <th class="text-start">Status</th>
                <th class="text-end">Akcje</th>
            </tr>
        </x-slot:head>
        <x-slot:body>
            @foreach($assignments as $assignment)
                @include('livewire.partials.accommodation-assignments-row', ['assignment' => $assignment, 'hideEmployee' => $this->isEmployeeScoped()])
            @endforeach
        </x-slot:body>
        <x-slot:cards>
            @foreach($assignments as $assignment)
                @include('livewire.partials.accommodation-assignments-row-card', ['assignment' => $assignment, 'hideEmployee' => $this->isEmployeeScoped()])
            @endforeach
        </x-slot:cards>
        <x-slot:empty>
            <x-ui.empty-state
                icon="house"
                :message="$this->hasActiveFilters() ? 'Brak przypisań spełniających kryteria.' : 'Brak przypisań do domów.'"
                :has-filters="$this->hasActiveFilters()"
                clear-filters-action="wire:clearFilters"
            >
                @if($this->isEmployeeScoped())
                    <x-ui.button variant="primary" href="{{ route('accommodation-assignments.create', ['employee_id' => $employeeId]) }}" class="btn-sm">Dodaj przypisanie</x-ui.button>
                @endif
            </x-ui.empty-state>
        </x-slot:empty>
    </x-data-table>
</div>
