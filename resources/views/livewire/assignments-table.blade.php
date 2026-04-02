<div>
    <x-ui.card class="mb-4">
        @if($searchEmployee || $searchProject || $searchRole || $projectFilter || $status || $dateFrom || $dateTo)
            <div class="d-flex justify-content-end mb-3">
                <x-ui.button variant="ghost" wire:click="clearFilters">
                    <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                </x-ui.button>
            </div>
        @endif
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1 text-muted">Pracownik</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="searchEmployee"
                    placeholder="Szukaj pracownika..."
                    class="form-control">
            </div>

            <div class="col-md-3">
                <label class="form-label small mb-1 text-muted">Nazwa projektu</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="searchProject"
                    placeholder="Fragment nazwy..."
                    class="form-control">
            </div>

            <div class="col-md-3">
                <label class="form-label small mb-1 text-muted">Status</label>
                <select
                    wire:model.live.debounce.300ms="status"
                    class="form-select">
                    <option value="">Wszystkie</option>
                    <option value="active">Aktywne</option>
                    <option value="completed">Zakończone</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small mb-1 text-muted">Projekt</label>
                <select
                    wire:model.live.debounce.300ms="projectFilter"
                    class="form-select">
                    <option value="">Wszystkie projekty</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card>
        @if($assignments->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Projekt</th>
                            <th>Pracownik</th>
                            <th>Rola</th>
                            <th>Od - Do</th>
                            <th>Status</th>
                            <th class="text-end">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignments as $assignment)
                            <tr wire:key="assignment-{{ $assignment->id }}">
                                <td>
                                    <a href="{{ route('projects.show', $assignment->project) }}" class="text-decoration-none fw-medium">
                                        <i class="bi bi-folder me-1 text-muted"></i>{{ $assignment->project->name }}
                                    </a>
                                </td>
                                <td>
                                    <x-employee-cell :employee="$assignment->employee" />
                                </td>
                                <td>
                                    <x-ui.badge variant="info">{{ $assignment->role->name }}</x-ui.badge>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $assignment->start_date->format('Y-m-d') }}
                                        –
                                        {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '...' }}
                                    </small>
                                </td>
                                <td>
                                    @php
                                        $status = $assignment->status ?? \App\Enums\AssignmentStatus::ACTIVE;
                                        $statusLabel = $status instanceof \App\Enums\AssignmentStatus ? $status->label() : ucfirst($status);
                                        $colorType = \App\Services\StatusColorService::getAssignmentStatusColor($status);
                                        $badgeVariant = match($colorType) {
                                            'success' => 'success',
                                            'danger' => 'danger',
                                            'warning' => 'warning',
                                            'info' => 'info',
                                            'secondary' => 'info',
                                            default => 'info'
                                        };
                                    @endphp
                                    <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
                                </td>
                                <td class="text-end">
                                    <x-action-buttons
                                        viewRoute="{{ route('project-assignments.show', $assignment) }}"
                                        editRoute="{{ route('project-assignments.edit', $assignment) }}"
                                        deleteRoute="{{ route('project-assignments.destroy', $assignment) }}"
                                        deleteMessage="Czy na pewno chcesz usunąć to przypisanie?"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($assignments->hasPages())
                <div class="mt-3 pt-3 border-top">
                    {{ $assignments->links() }}
                </div>
            @endif
        @else
            <x-ui.empty-state
                icon="inbox"
                message="Brak przypisań"
            />
        @endif
    </x-ui.card>
</div>
