<x-app-layout>
    <div class="row">
        <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h2 class="h4 fw-semibold text-dark mb-0">Szczegóły Projektu: {{ $project->name }}</h2>
                    </div>
                    <div class="card-body">
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
                                        $badgeClass = match($project->status) {
                                            'active' => 'bg-success',
                                            'on_hold' => 'bg-warning',
                                            'completed' => 'bg-primary',
                                            'cancelled' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ ucfirst($project->status) }}</span>
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
                            <a href="{{ route('projects.edit', $project) }}" class="btn btn-primary me-2">
                                <i class="bi bi-pencil me-1"></i> Edytuj
                            </a>
                            <a href="{{ route('projects.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Powrót
                            </a>
                        </div>
                    </div>
                </div>

                @if($project->demand)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h3 class="h5 fw-semibold mb-0">Zapotrzebowanie</h3>
                    </div>
                    <div class="card-body">
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
                    </div>
                </div>
                @endif

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom">
                        <h3 class="h5 fw-semibold mb-0">Przypisani Pracownicy</h3>
                    </div>
                    <div class="card-body">
                        @if($project->assignments->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Pracownik</th>
                                            <th>Rola</th>
                                            <th>Okres</th>
                                            <th>Status</th>
                                            <th>Akcje</th>
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
                                                    <span class="badge bg-secondary">{{ $assignment->role->name }}</span>
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
                                                        $badgeClass = match($statusValue) {
                                                            'active' => 'bg-success',
                                                            'completed' => 'bg-primary',
                                                            'cancelled' => 'bg-danger',
                                                            'in_transit' => 'bg-warning',
                                                            'at_base' => 'bg-secondary',
                                                            default => 'bg-secondary'
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $badgeClass }}">{{ ucfirst($statusValue) }}</span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('assignments.show', $assignment) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2 mb-0">Brak przypisanych pracowników</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
