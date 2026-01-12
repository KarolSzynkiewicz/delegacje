<div>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="fs-5 fw-semibold text-dark mb-0">Filtry</h3>
                <x-ui.button variant="ghost" wire:click="clearFilters" class="btn-sm">
                    <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                </x-ui.button>
            </div>
            
            <div class="row g-3">
                <!-- Pracownik -->
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Pracownik</label>
                    <input type="text" wire:model.live.debounce.300ms="searchEmployee" 
                        placeholder="Szukaj pracownika..."
                        class="form-control form-control-sm">
                </div>

                <!-- Projekt -->
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Projekt</label>
                    <input type="text" wire:model.live.debounce.300ms="searchProject" 
                        placeholder="Szukaj projektu..."
                        class="form-control form-control-sm">
                </div>

                <!-- Status -->
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Status</label>
                    <select wire:model.live="status" class="form-select form-select-sm">
                        <option value="">Wszystkie</option>
                        <option value="active">Aktywne</option>
                        <option value="completed">Zakończone</option>
                        <option value="cancelled">Anulowane</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    @php
        $groupedAssignments = $assignments->groupBy(function($assignment) {
            return $assignment->project->id;
        });
    @endphp

    @foreach($groupedAssignments as $projectId => $projectAssignments)
        <div wire:key="project-group-{{ $projectId }}">
        @php
            $project = $projectAssignments->first()->project;
        @endphp
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-light">
                <h5 class="mb-0 fw-semibold">
                    <a href="{{ route('projects.show', $project) }}" class="text-decoration-none text-dark">
                        <i class="bi bi-folder me-2"></i>{{ $project->name }}
                    </a>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-start">Pracownik</th>
                                <th class="text-start">Rola</th>
                                <th class="text-start">Od - Do</th>
                                <th class="text-start">Status</th>
                                <th class="text-start">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($projectAssignments->sortBy('start_date') as $assignment)
                                <tr wire:key="assignment-{{ $assignment->id }}">
                                    <td>
                                        <a href="{{ route('employees.show', $assignment->employee) }}" class="text-primary text-decoration-none">
                                            {{ $assignment->employee->full_name }}
                                        </a>
                                    </td>
                                    <td>
                                        <x-ui.badge variant="info">{{ $assignment->role->name }}</x-ui.badge>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $assignment->start_date->format('Y-m-d') }} - 
                                            {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '...' }}
                                        </small>
                                    </td>
                                    <td>
                                        @php
                                            $status = $assignment->status ?? \App\Enums\AssignmentStatus::ACTIVE;
                                            $statusValue = $status instanceof \App\Enums\AssignmentStatus ? $status->value : $status;
                                            $statusLabel = $status instanceof \App\Enums\AssignmentStatus ? $status->label() : ucfirst($status);
                                            
                                            $colorType = \App\Services\StatusColorService::getAssignmentStatusColor($status);
                                        @endphp
                                        @php
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
                                    <td>
                                        <x-action-buttons
                                            viewRoute="{{ route('assignments.show', $assignment) }}"
                                            editRoute="{{ route('assignments.edit', $assignment) }}"
                                            deleteRoute="{{ route('assignments.destroy', $assignment) }}"
                                            deleteMessage="Czy na pewno chcesz usunąć to przypisanie?"
                                        />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>
    @endforeach

    @if($assignments->hasPages())
        <div class="mt-3">
            {{ $assignments->links() }}
        </div>
    @endif

    @if($assignments->isEmpty())
        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-5">
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p class="text-muted small fw-medium mb-0">Brak przypisań</p>
                </div>
            </div>
        </div>
    @endif
</div>
