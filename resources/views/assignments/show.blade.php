<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Szczegóły Przypisania">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('project-assignments.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('assignments.edit', $assignment) }}"
                    routeName="assignments.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <x-ui.card label="Szczegóły Przypisania">
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
                            <x-ui.badge variant="info">{{ $assignment->role->name }}</x-ui.badge>
                        </dd>
                    </div>
                    <div class="col-md-6 mb-3">
                        <dt class="fw-semibold mb-1">Status:</dt>
                        <dd>
                            @php
                                $status = $assignment->status ?? \App\Enums\AssignmentStatus::ACTIVE;
                                $statusValue = $status instanceof \App\Enums\AssignmentStatus ? $status->value : $status;
                                $statusLabel = $status instanceof \App\Enums\AssignmentStatus ? $status->label() : ucfirst($status);
                                
                                $badgeVariant = match($statusValue) {
                                    'active' => 'success',
                                    'completed' => 'info',
                                    'cancelled' => 'danger',
                                    'in_transit' => 'warning',
                                    'at_base' => 'info',
                                    default => 'info'
                                };
                            @endphp
                            <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
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

            </x-ui.card>
        </div>
    </div>
</x-app-layout>
