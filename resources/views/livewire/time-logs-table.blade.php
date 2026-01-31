<div>
    <!-- Statystyki i Filtry -->
    <x-ui.card class="mb-4">
        <!-- Statystyki -->
        <div class="mb-4 pb-3 border-top border-bottom">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h3 class="fs-5 fw-semibold mb-1">Ewidencja Godzin</h3>
                    <p class="small text-muted mb-0">
                        @if($employeeFilter || (!$isMineView && $projectFilter) || $dateFrom || $dateTo)
                            Znaleziono: <span class="fw-semibold">{{ $timeLogs->total() }}</span> wpisów
                        @else
                            Łącznie: <span class="fw-semibold">{{ $timeLogs->total() }}</span> wpisów
                        @endif
                    </p>
                </div>
                <x-ui.button 
                    variant="ghost" 
                    wire:click="clearFilters" 
                    class="btn-sm"
                    :disabled="!($employeeFilter || (!$isMineView && $projectFilter) || $dateFrom || $dateTo)"
                >
                    <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                </x-ui.button>
            </div>
        </div>

        <!-- Filtry -->
        <div class="row g-3">
            <!-- Pracownik -->
            <div class="col-md-6 col-lg-3">
                <label class="form-label small">
                    <i class="bi bi-person me-1"></i> Pracownik
                </label>
                <select wire:model.live="employeeFilter" class="form-control">
                    <option value="">Wszyscy pracownicy</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Projekt (ukryty w widoku /mine/*) -->
            @if(!$isMineView)
                <div class="col-md-6 col-lg-3">
                    <label class="form-label small">
                        <i class="bi bi-folder me-1"></i> Projekt
                    </label>
                    <select wire:model.live="projectFilter" class="form-control">
                        <option value="">Wszystkie projekty</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- Data od -->
            <div class="col-md-6 col-lg-3">
                <label class="form-label small">
                    <i class="bi bi-calendar-event me-1"></i> Data od
                </label>
                <input 
                    type="date" 
                    wire:model.live="dateFrom" 
                    class="form-control"
                    max="{{ $dateTo ? $dateTo : '' }}"
                >
            </div>

            <!-- Data do -->
            <div class="col-md-6 col-lg-3">
                <label class="form-label small">
                    <i class="bi bi-calendar-event me-1"></i> Data do
                </label>
                <input 
                    type="date" 
                    wire:model.live="dateTo" 
                    class="form-control"
                    min="{{ $dateFrom ? $dateFrom : '' }}"
                >
            </div>
        </div>
    </x-ui.card>

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
