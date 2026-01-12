<x-app-layout>
    <div class="row">
        <div class="col-lg-8">
            <x-ui.card label="Szczegóły Projektu: {{ $project->name }}">
                <dl class="row mb-0">
                    <div class="col-md-6 mb-3">
                        <dt class="fw-semibold mb-1">Nazwa:</dt>
                        <dd>{{ $project->name }}</dd>
                    </div>
                    <div class="col-md-6 mb-3">
                        <dt class="fw-semibold mb-1">Klient:</dt>
                        <dd>{{ $project->client_name ?? '-' }}</dd>
                    </div>
                    <div class="col-md-6 mb-3">
                        <dt class="fw-semibold mb-1">Status:</dt>
                        <dd>
                            @php
                                $badgeVariant = match($project->status) {
                                    'active' => 'success',
                                    'on_hold' => 'warning',
                                    'completed' => 'info',
                                    'cancelled' => 'danger',
                                    default => 'info'
                                };
                            @endphp
                            <x-ui.badge variant="{{ $badgeVariant }}">{{ ucfirst($project->status) }}</x-ui.badge>
                        </dd>
                    </div>
                    <div class="col-md-6 mb-3">
                        <dt class="fw-semibold mb-1">Budżet:</dt>
                        <dd>{{ $project->budget ? number_format($project->budget, 2) . ' PLN' : '-' }}</dd>
                    </div>
                    @if($project->location)
                    <div class="col-md-6 mb-3">
                        <dt class="fw-semibold mb-1">Lokalizacja:</dt>
                        <dd>
                            <i class="bi bi-geo-alt me-1"></i>{{ $project->location->name }}
                        </dd>
                    </div>
                    @endif
                    @if($project->description)
                    <div class="col-12 mb-3">
                        <dt class="fw-semibold mb-1">Opis:</dt>
                        <dd>{{ $project->description }}</dd>
                    </div>
                    @endif
                </dl>

                <div class="mt-4 pt-3 border-top">
                    <x-ui.button variant="primary" href="{{ route('projects.edit', $project) }}">
                        <i class="bi bi-pencil me-1"></i> Edytuj
                    </x-ui.button>
                    <x-ui.button variant="ghost" href="{{ route('projects.index') }}">
                        <i class="bi bi-arrow-left me-1"></i> Powrót
                    </x-ui.button>
                </div>
            </x-ui.card>

            @if($project->demand)
            <x-ui.card label="Zapotrzebowanie" class="mt-4">
                <dl class="row mb-0">
                    <div class="col-md-6 mb-2">
                        <dt class="fw-semibold">Liczba pracowników:</dt>
                        <dd>{{ $project->demand->required_workers_count }}</dd>
                    </div>
                    <div class="col-md-6 mb-2">
                        <dt class="fw-semibold">Od:</dt>
                        <dd>{{ $project->demand->start_date->format('Y-m-d') }}</dd>
                    </div>
                    <div class="col-md-6 mb-2">
                        <dt class="fw-semibold">Do:</dt>
                        <dd>{{ $project->demand->end_date ? $project->demand->end_date->format('Y-m-d') : 'Nieokreślone' }}</dd>
                    </div>
                </dl>
            </x-ui.card>
            @endif

            <x-ui.card label="Przypisani Pracownicy" class="mt-4">
                @if($project->assignments->count() > 0)
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Pracownik</th>
                                    <th>Rola</th>
                                    <th>Okres</th>
                                    <th>Status</th>
                                    <th class="text-end">Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($project->assignments as $assignment)
                                    <tr>
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
                                                {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : 'Bieżące' }}
                                            </small>
                                        </td>
                                        <td>
                                            @php
                                                $status = $assignment->status ?? \App\Enums\AssignmentStatus::ACTIVE;
                                                $statusValue = $status instanceof \App\Enums\AssignmentStatus ? $status->value : $status;
                                                $badgeVariant = match($statusValue) {
                                                    'active' => 'success',
                                                    'completed' => 'info',
                                                    'cancelled' => 'danger',
                                                    'in_transit' => 'warning',
                                                    'at_base' => 'info',
                                                    default => 'info'
                                                };
                                            @endphp
                                            <x-ui.badge variant="{{ $badgeVariant }}">{{ ucfirst($statusValue) }}</x-ui.badge>
                                        </td>
                                        <td class="text-end">
                                            <x-ui.button variant="ghost" href="{{ route('assignments.show', $assignment) }}">
                                                <i class="bi bi-eye"></i>
                                            </x-ui.button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-people text-muted"></i>
                        <p class="text-muted mt-2 mb-0">Brak przypisanych pracowników</p>
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
