<div>
    <!-- Statystyki i Filtry -->
    <x-ui.card class="mb-4">
        <!-- Statystyki -->
        <div class="mb-4 pb-3 border-top border-bottom">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h3 class="fs-5 fw-semibold mb-1">Oceny Pracowników</h3>
                    <p class="small text-muted mb-0">
                        @if($search || $employeeFilter)
                            Znaleziono: <span class="fw-semibold">{{ $evaluations->total() }}</span> ocen
                        @else
                            Łącznie: <span class="fw-semibold">{{ $evaluations->total() }}</span> ocen
                        @endif
                    </p>
                </div>
                @if($search || $employeeFilter)
                    <x-ui.button variant="ghost" wire:click="clearFilters" class="btn-sm">
                        <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                    </x-ui.button>
                @endif
            </div>
        </div>

        <!-- Filtry -->
        <div class="row g-3">
            <!-- Pracownik -->
            <div class="col-md-6">
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

            <!-- Wyszukiwanie -->
            <div class="col-md-6">
                <label class="form-label small">
                    <i class="bi bi-search me-1"></i> Szukaj w uwagach
                </label>
                <div class="position-relative">
                    <input type="text" wire:model.live.debounce.300ms="search" 
                        placeholder="Szukaj w uwagach..."
                        class="form-control ps-5">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                </div>
            </div>
        </div>
    </x-ui.card>

    <!-- Tabela -->
    <x-ui.card>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <x-livewire.sortable-header field="created_at" :sortField="$sortField" :sortDirection="$sortDirection">
                            Data
                        </x-livewire.sortable-header>
                        <x-livewire.sortable-header field="employee_id" :sortField="$sortField" :sortDirection="$sortDirection">
                            Pracownik
                        </x-livewire.sortable-header>
                        <th class="text-center">Zaangażowanie</th>
                        <th class="text-center">Umiejętności</th>
                        <th class="text-center">Porządek</th>
                        <th class="text-center">Zachowanie</th>
                        <th class="text-center">Średnia</th>
                        <th class="text-start">Oceniający</th>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($evaluations as $evaluation)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-calendar me-2 text-muted"></i>
                                    {{ $evaluation->created_at->format('Y-m-d H:i') }}
                                </div>
                            </td>
                            <td>
                                <x-employee-cell :employee="$evaluation->employee" />
                            </td>
                            <td class="text-center">
                                <x-ui.badge variant="{{ $evaluation->engagement >= 7 ? 'success' : ($evaluation->engagement >= 4 ? 'warning' : 'danger') }}">
                                    {{ $evaluation->engagement }}/10
                                </x-ui.badge>
                            </td>
                            <td class="text-center">
                                <x-ui.badge variant="{{ $evaluation->skills >= 7 ? 'success' : ($evaluation->skills >= 4 ? 'warning' : 'danger') }}">
                                    {{ $evaluation->skills }}/10
                                </x-ui.badge>
                            </td>
                            <td class="text-center">
                                <x-ui.badge variant="{{ $evaluation->orderliness >= 7 ? 'success' : ($evaluation->orderliness >= 4 ? 'warning' : 'danger') }}">
                                    {{ $evaluation->orderliness }}/10
                                </x-ui.badge>
                            </td>
                            <td class="text-center">
                                <x-ui.badge variant="{{ $evaluation->behavior >= 7 ? 'success' : ($evaluation->behavior >= 4 ? 'warning' : 'danger') }}">
                                    {{ $evaluation->behavior }}/10
                                </x-ui.badge>
                            </td>
                            <td class="text-center">
                                <strong>{{ number_format($evaluation->average_score, 2) }}/10</strong>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-circle me-2 text-muted"></i>
                                    {{ $evaluation->createdBy->name ?? '-' }}
                                </div>
                            </td>
                            <td class="text-end">
                                <x-action-buttons
                                    viewRoute="{{ route('employee-evaluations.show', $evaluation) }}"
                                    editRoute="{{ route('employee-evaluations.edit', $evaluation) }}"
                                    deleteRoute="{{ route('employee-evaluations.destroy', $evaluation) }}"
                                />
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-state 
                            icon="star"
                            :message="$search || $employeeFilter ? 'Brak ocen spełniających kryteria wyszukiwania' : 'Brak ocen'"
                            :has-filters="$search || $employeeFilter"
                            clear-filters-action="wire:clearFilters"
                            :in-table="true"
                            colspan="9"
                        />
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginacja -->
        @if($evaluations->hasPages())
            <div class="mt-3 pt-3 border-top">
                {{ $evaluations->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
