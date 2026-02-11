<div>
    <x-ui.card class="mb-4">     
        
          <!-- Search bar -->
            <div class="row">
                <div class="col-md-4">
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="searchEmployee" 
                        placeholder="Szukaj pracownika..."
                        class="form-control form-control-sm">
                </div>
              
                <div class="col-md-4">
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="searchProject" 
                        placeholder="Szukaj projektu..."
                        class="form-control form-control-sm">
                </div>

                <div class="col-md-4">
                    <select 
                        wire:model.live="status" 
                        class="form-control form-select-sm">
                        <option value="">Wszystkie</option>
                        <option value="active">Aktywne</option>
                        <option value="completed">Zakończone</option>
                        <option value="cancelled">Anulowane</option>
                    </select>
                </div>
            </div>
    </x-ui.card>
 <!-- not here?? -->
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
        <!-- Table for each project -->
        <x-ui.card class="mb-3">
            <div class="card-header">
                <h5 class="mb-0 fw-semibold">
                    <a href="{{ route('projects.show', $project) }}" class="text-decoration-none text-dark">
                        <i class="bi bi-folder me-2"></i>{{ $project->name }}
                    </a>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pracownik</th>
                            <th>Rola</th>
                            <th>Od - Do</th>
                            <th>Status</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($projectAssignments->sortBy('start_date') as $assignment)
                            <tr wire:key="assignment-{{ $assignment->id }}">
                                <td>
                                    <x-employee-cell :employee="$assignment->employee" />
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
                                    <!-- Status badge not here -->
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
        </x-ui.card>
        </div>
    @endforeach

    @if($assignments->hasPages())
        <div class="mt-3">
            {{ $assignments->links() }}
        </div>
    @endif

    @if($assignments->isEmpty())
        <x-ui.empty-state 
            icon="inbox"
            message="Brak przypisań"
        />
    @endif
</div>
