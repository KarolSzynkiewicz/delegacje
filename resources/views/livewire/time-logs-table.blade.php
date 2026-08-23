<div>
    <x-data-table-filters
        :count="$timeLogs->total()"
        :has-filters="(bool) ($employeeFilter || (!$isMineView && $projectFilter) || $dateFrom || $dateTo)"
        item-label="wpisów"
    >
        <x-slot:note>
            suma godzin: {{ number_format((float) ($totalHours ?? 0), 2, ',', ' ') }}h
        </x-slot:note>

        <x-slot:actions>
            @php
                $csvParams = array_filter([
                    'employee_id' => $employeeFilter ?: null,
                    'project_id'  => (!$isMineView && $projectFilter) ? $projectFilter : null,
                    'date_from'   => $dateFrom ?: null,
                    'date_to'     => $dateTo   ?: null,
                ]);
            @endphp
            <a href="{{ route('time-logs.export-csv', $csvParams) }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-download me-1"></i> Eksport CSV
            </a>
        </x-slot:actions>

        <div class="dt-filter-field">
            <label class="form-label small">
                <i class="bi bi-person me-1"></i> Pracownik
            </label>
            <select wire:model.live="employeeFilter" class="form-select">
                <option value="">Wszyscy pracownicy</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                @endforeach
            </select>
        </div>

        @if(!$isMineView)
            <div class="dt-filter-field">
                <label class="form-label small">
                    <i class="bi bi-folder me-1"></i> Projekt
                </label>
                <select wire:model.live="projectFilter" class="form-select">
                    <option value="">Wszystkie projekty</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="dt-filter-field">
            <label class="form-label small">
                <i class="bi bi-calendar-event me-1"></i> Data od
            </label>
            <input type="date" wire:model.live="dateFrom" class="form-control" max="{{ $dateTo ? $dateTo : '' }}">
        </div>

        <div class="dt-filter-field">
            <label class="form-label small">
                <i class="bi bi-calendar-event me-1"></i> Data do
            </label>
            <input type="date" wire:model.live="dateTo" class="form-control" min="{{ $dateFrom ? $dateFrom : '' }}">
        </div>
    </x-data-table-filters>

    <!-- Tabela -->
    <x-ui.card>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th class="text-start">Data</th>
                        <th class="text-start">Pracownik</th>
                        <th class="text-start">Projekt</th>
                        <th class="text-start">Godziny</th>
                        <th class="text-start">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($timeLogs as $timeLog)
                        <tr>
                            <td>{{ $timeLog->start_time->format('Y-m-d') }}</td>
                            <td>
                                <x-employee-cell :employee="$timeLog->projectAssignment->employee"  />
                            </td>
                            <td>{{ $timeLog->projectAssignment->project->name }}</td>
                            <td class="fw-semibold">{{ number_format($timeLog->hours_worked, 2) }}h</td>
                            <td>
                                @if(auth()->user()->hasPermission('time-logs.delete'))
                                    <x-ui.action-buttons
                                        viewRoute="{{ route('time-logs.show', $timeLog) }}"
                                        editRoute="{{ route('time-logs.edit', $timeLog) }}"
                                        deleteRoute="{{ route('time-logs.destroy', $timeLog) }}"
                                        deleteMessage="Czy na pewno chcesz usunąć ten wpis?"
                                    />
                                @else
                                    <x-ui.action-buttons
                                        viewRoute="{{ route('time-logs.show', $timeLog) }}"
                                        editRoute="{{ route('time-logs.edit', $timeLog) }}"
                                    />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-state 
                            icon="inbox"
                            :message="$employeeFilter || (!$isMineView && $projectFilter) || $dateFrom || $dateTo ? 'Brak wpisów spełniających kryteria wyszukiwania.' : 'Brak wpisów w systemie.'"
                            :has-filters="$employeeFilter || (!$isMineView && $projectFilter) || $dateFrom || $dateTo"
                            clear-filters-action="wire:clearFilters"
                            :in-table="true"
                            colspan="5"
                        />
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginacja -->
        @if($timeLogs->hasPages())
            <div class="mt-3 pt-3 border-top">
                {{ $timeLogs->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
