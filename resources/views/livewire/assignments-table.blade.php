<div>
    <x-data-table :paginator="$assignments" :has-filters="$this->hasActiveFilters()">
        <x-slot:filters>
            <x-data-table-filters :count="$assignments->total()">
                @unless($this->isEmployeeScoped())
                    <x-data-table-search
                        wire:model.live.debounce.300ms="searchEmployee"
                        placeholder="Pracownik..."
                    />
                @endunless
                <x-data-table-search
                    wire:model.live.debounce.300ms="searchProject"
                    placeholder="Nazwa projektu..."
                />
                <select wire:model.live="status" class="form-select form-select-sm">
                    <option value="">Status: wszystkie</option>
                    <option value="active">Aktywne</option>
                    <option value="completed">Zakończone</option>
                </select>
                <select wire:model.live="projectFilter" class="form-select form-select-sm">
                    <option value="">Projekt: wszystkie</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </x-data-table-filters>
        </x-slot:filters>

        <x-slot:activeFilters>
            @if(! $this->isEmployeeScoped() && $searchEmployee)
                <x-data-table-filter-chip label="Pracownik: {{ $searchEmployee }}" wire:click="$set('searchEmployee', '')" />
            @endif
            @if($searchProject)
                <x-data-table-filter-chip label="Projekt: {{ $searchProject }}" wire:click="$set('searchProject', '')" />
            @endif
            @if($status)
                <x-data-table-filter-chip label="Status: {{ $status === 'active' ? 'Aktywne' : 'Zakończone' }}" wire:click="$set('status', '')" />
            @endif
            @if($projectFilter)
                <x-data-table-filter-chip label="Projekt: {{ $projects->firstWhere('id', (int) $projectFilter)?->name ?? $projectFilter }}" wire:click="$set('projectFilter', '')" />
            @endif
        </x-slot:activeFilters>

        <x-slot:head>
            <tr>
                <th class="text-start">Projekt</th>
                @unless($this->isEmployeeScoped())
                    <th class="text-start">Pracownik</th>
                @endunless
                <th class="text-start">Rola</th>
                <th class="text-start">Od – Do</th>
                <th class="text-start">Status</th>
                <th class="text-end">Akcje</th>
            </tr>
        </x-slot:head>
        <x-slot:body>
            @foreach($assignments as $assignment)
                @include('livewire.partials.assignments-row', ['assignment' => $assignment, 'hideEmployee' => $this->isEmployeeScoped()])
            @endforeach
        </x-slot:body>
        <x-slot:cards>
            @foreach($assignments as $assignment)
                @include('livewire.partials.assignments-row-card', ['assignment' => $assignment, 'hideEmployee' => $this->isEmployeeScoped()])
            @endforeach
        </x-slot:cards>
        <x-slot:empty>
            <x-ui.empty-state
                icon="person-check"
                :message="$this->hasActiveFilters() ? 'Brak przypisań spełniających kryteria.' : 'Brak przypisań do projektów.'"
                :has-filters="$this->hasActiveFilters()"
                clear-filters-action="wire:clearFilters"
            >
                @if($this->isEmployeeScoped())
                    <x-ui.button variant="primary" href="{{ route('project-assignments.create', ['employee_id' => $employeeId]) }}" class="btn-sm">
                        Dodaj przypisanie
                    </x-ui.button>
                @endif
            </x-ui.empty-state>
        </x-slot:empty>
    </x-data-table>
</div>
