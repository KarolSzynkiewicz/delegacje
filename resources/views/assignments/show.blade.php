<x-app-layout>
    <div class="row justify-content-center">
        <div class="col-lg-10">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom">
                        <h2 class="h4 fw-semibold text-dark mb-0">Szczegóły Przypisania</h2>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <div class="col-md-6 mb-3">
                                <dt class="fw-semibold mb-1">Pracownik:</dt>
                                <dd>
                                    <a href="{{ route('employees.show', $assignment->employee) }}" class="text-primary text-decoration-none">
                                        {{ $assignment->employee->full_name }}
                                    </a>
                                </dd>
                            </div>
                            <div class="col-md-6 mb-3">
                                <dt class="fw-semibold mb-1">Projekt:</dt>
                                <dd>
                                    <a href="{{ route('projects.show', $assignment->project) }}" class="text-primary text-decoration-none">
                                        {{ $assignment->project->name }}
                                    </a>
                                </dd>
                            </div>
                            <div class="col-md-6 mb-3">
                                <dt class="fw-semibold mb-1">Rola:</dt>
                                <dd>
                                    <span class="badge bg-secondary">{{ $assignment->role->name }}</span>
                                </dd>
                            </div>
                            <div class="col-md-6 mb-3">
                                <dt class="fw-semibold mb-1">Status:</dt>
                                <dd>
                                    @php
                                        $status = $assignment->status ?? \App\Enums\AssignmentStatus::ACTIVE;
                                        $statusValue = $status instanceof \App\Enums\AssignmentStatus ? $status->value : $status;
                                        $statusLabel = $status instanceof \App\Enums\AssignmentStatus ? $status->label() : ucfirst($status);
                                        
                                        $badgeClass = match($statusValue) {
                                            'active' => 'bg-success',
                                            'completed' => 'bg-primary',
                                            'cancelled' => 'bg-danger',
                                            'in_transit' => 'bg-warning',
                                            'at_base' => 'bg-secondary',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                                </dd>
                            </div>
                            <div class="col-md-6 mb-3">
                                <dt class="fw-semibold mb-1">Data Rozpoczęcia:</dt>
                                <dd>{{ $assignment->start_date->format('Y-m-d') }}</dd>
                            </div>
                            <div class="col-md-6 mb-3">
                                <dt class="fw-semibold mb-1">Data Zakończenia:</dt>
                                <dd>{{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : 'Bieżące' }}</dd>
                            </div>
                            @if($assignment->notes)
                            <div class="col-12 mb-3">
                                <dt class="fw-semibold mb-1">Uwagi:</dt>
                                <dd>{{ $assignment->notes }}</dd>
                            </div>
                            @endif
                        </dl>

                        <div class="mt-4 pt-3 border-top">
                            <a href="{{ route('assignments.edit', $assignment) }}" class="btn btn-primary me-2">
                                <i class="bi bi-pencil me-1"></i> Edytuj
                            </a>
                            <a href="{{ route('project-assignments.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Powrót
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
