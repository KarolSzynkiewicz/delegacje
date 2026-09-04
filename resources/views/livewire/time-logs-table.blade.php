<div>
    <x-data-table :paginator="$timeLogs" :has-filters="$this->hasActiveFilters()">
        <x-slot:filters>
            <x-data-table-filters :count="$timeLogs->total()">
                <x-slot:note>
                    suma: {{ number_format((float) ($totalHours ?? 0), 2, ',', ' ') }}h
                </x-slot:note>
                <x-slot:actions>
                    @php
                        $csvParams = array_filter([
                            'employee_id' => $this->isEmployeeScoped() ? $employeeId : ($employeeFilter ?: null),
                            'project_id' => (! $isMineView && $projectFilter) ? $projectFilter : null,
                            'date_from' => $dateFrom ?: null,
                            'date_to' => $dateTo ?: null,
                        ]);
                    @endphp
                    <a href="{{ route('time-logs.export-csv', $csvParams) }}" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-download me-1"></i> Eksport CSV
                    </a>
                </x-slot:actions>

                @unless($this->isEmployeeScoped())
                    <select wire:model.live="employeeFilter" class="form-select form-select-sm">
                        <option value="">Pracownik: wszyscy</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                        @endforeach
                    </select>
                @endunless

                @if(! $isMineView)
                    <select wire:model.live="projectFilter" class="form-select form-select-sm">
                        <option value="">Projekt: wszystkie</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                @endif

                <input type="date" wire:model.live="dateFrom" class="form-control form-control-sm" max="{{ $dateTo ?: '' }}" placeholder="Od">
                <input type="date" wire:model.live="dateTo" class="form-control form-control-sm" min="{{ $dateFrom ?: '' }}" placeholder="Do">
            </x-data-table-filters>
        </x-slot:filters>

        <x-slot:activeFilters>
            @if(! $this->isEmployeeScoped() && $employeeFilter)
                <x-data-table-filter-chip label="Pracownik: {{ $employees->firstWhere('id', (int) $employeeFilter)?->full_name ?? $employeeFilter }}" wire:click="$set('employeeFilter', '')" />
            @endif
            @if(! $isMineView && $projectFilter)
                <x-data-table-filter-chip label="Projekt: {{ $projects->firstWhere('id', (int) $projectFilter)?->name ?? $projectFilter }}" wire:click="$set('projectFilter', '')" />
            @endif
            @if($dateFrom)
                <x-data-table-filter-chip label="Od: {{ $dateFrom }}" wire:click="$set('dateFrom', '')" />
            @endif
            @if($dateTo)
                <x-data-table-filter-chip label="Do: {{ $dateTo }}" wire:click="$set('dateTo', '')" />
            @endif
        </x-slot:activeFilters>

        <x-slot:head>
            <tr>
                <th class="text-start">Data</th>
                @unless($this->isEmployeeScoped())
                    <th class="text-start">Pracownik</th>
                @endunless
                <th class="text-start">Projekt</th>
                <th class="text-start">Godziny</th>
                <th class="text-start">Notatki</th>
                <th class="text-end">Akcje</th>
            </tr>
        </x-slot:head>
        <x-slot:body>
            @foreach($timeLogs as $timeLog)
                @include('livewire.partials.time-logs-row', ['timeLog' => $timeLog, 'hideEmployee' => $this->isEmployeeScoped()])
            @endforeach
        </x-slot:body>
        <x-slot:cards>
            @foreach($timeLogs as $timeLog)
                @include('livewire.partials.time-logs-row-card', ['timeLog' => $timeLog, 'hideEmployee' => $this->isEmployeeScoped()])
            @endforeach
        </x-slot:cards>
        <x-slot:empty>
            <x-ui.empty-state
                icon="clock"
                :message="$this->hasActiveFilters() ? 'Brak wpisów spełniających kryteria.' : 'Brak wpisów godzin.'"
                :has-filters="$this->hasActiveFilters()"
                clear-filters-action="wire:clearFilters"
            />
        </x-slot:empty>
    </x-data-table>
</div>
