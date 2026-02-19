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
                    href="{{ route('project-assignments.edit', $assignment) }}"
                    routeName="assignments.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if (session('success'))
        <x-ui.alert variant="success" dismissible class="mb-3">
            {{ session('success') }}
        </x-ui.alert>
    @endif

    @if (session('error'))
        <x-ui.alert variant="danger" dismissible class="mb-3">
            {{ session('error') }}
        </x-ui.alert>
    @endif

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
                    @if($assignment->logisticsEvent)
                    <div class="col-12 mb-3">
                        <dt class="fw-semibold mb-1">Powiązany wyjazd:</dt>
                        <dd>
                            <a href="{{ route('departures.show', $assignment->logisticsEvent) }}" 
                               class="text-primary text-decoration-none d-inline-flex align-items-center gap-1"
                               target="_blank">
                                <i class="bi bi-link-45deg"></i>
                                Wyjazd #{{ $assignment->logisticsEvent->id }}
                                @if($assignment->logisticsEvent->toLocation)
                                    - {{ $assignment->logisticsEvent->toLocation->name }}
                                @endif
                                @if($assignment->logisticsEvent->event_date)
                                    ({{ $assignment->logisticsEvent->event_date->format('d.m.Y') }}
                                    @if($assignment->logisticsEvent->end_date)
                                        - {{ $assignment->logisticsEvent->end_date->format('d.m.Y') }}
                                    @endif
                                    )
                                @endif
                            </a>
                        </dd>
                    </div>
                    @endif
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
