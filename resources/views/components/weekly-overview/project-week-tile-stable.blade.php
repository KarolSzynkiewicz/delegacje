@props(['stability', 'project', 'weekStart', 'weekEnd'])

@if($stability && $stability['has_data'])
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <!-- Zapotrzebowanie -->
            <div class="mb-3">
                <div class="bg-info bg-opacity-10 rounded p-2 border border-info">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                        <h4 class="small fw-bold text-dark mb-0">Zapotrzebowanie</h4>
                        <a href="{{ route('projects.demands.create', ['project' => $project, 'date_from' => $weekStart->format('Y-m-d'), 'date_to' => $weekEnd->format('Y-m-d')]) }}" 
                           class="btn btn-sm btn-info">
                            <i class="bi bi-pencil"></i>
                            Edytuj
                        </a>
                    </div>
                    
                    <!-- Tabelka zapotrzebowania -->
                    @if(!empty($stability['requirements_summary']['role_details']))
                        <div class="mb-2 table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-start small fw-bold text-dark">Rola</th>
                                        <th class="text-center small fw-bold text-dark">Potrzebnych</th>
                                        <th class="text-center small fw-bold text-dark">Przypisanych</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($stability['requirements_summary']['role_details'] as $roleDetail)
                                        @php
                                            $needed = $roleDetail['needed'];
                                            $assigned = $roleDetail['assigned'];
                                            $assignedMin = $roleDetail['assigned_min'] ?? null;
                                            $assignedMax = $roleDetail['assigned_max'] ?? null;
                                            $isStable = $roleDetail['is_stable'] ?? false;
                                            $isComplete = $isStable && $assigned !== null && $assigned >= $needed;
                                            $isPartial = $isStable && $assigned !== null && $assigned > 0 && $assigned < $needed;
                                        @endphp
                                        <tr>
                                            <td class="small text-dark">{{ Str::lower($roleDetail['role']->name) }}</td>
                                            <td class="text-center small fw-semibold {{ $isComplete ? 'text-success' : ($isPartial ? 'text-warning' : 'text-danger') }}">
                                                {{ $needed }}
                                            </td>
                                            <td class="text-center small fw-semibold text-primary">
                                                @if($isStable && $assigned !== null)
                                                    {{ $assigned }}
                                                @else
                                                    {{ $assignedMin }}-{{ $assignedMax }}
                                                    <span class="badge bg-warning ms-1" title="Pokrycie zmienia się w trakcie tygodnia">
                                                        <i class="bi bi-arrow-left-right"></i>
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="table-secondary fw-bold">
                                        <td class="small text-dark">łącznie</td>
                                        <td class="text-center small text-dark">{{ $stability['requirements_summary']['total_needed'] }}</td>
                                        <td class="text-center small text-dark">
                                            @if($stability['requirements_summary']['is_stable'] && $stability['requirements_summary']['total_assigned'] !== null)
                                                {{ $stability['requirements_summary']['total_assigned'] }}
                                            @else
                                                {{ $stability['requirements_summary']['total_assigned_min'] }}-{{ $stability['requirements_summary']['total_assigned_max'] }}
                                                <span class="badge bg-warning ms-1">
                                                    <i class="bi bi-arrow-left-right"></i>
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Osoby w projekcie -->
            <div class="mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h4 class="fw-bold text-dark mb-2 d-flex align-items-center gap-1 small">
                            <i class="bi bi-people text-warning"></i>
                            Osoby
                        </h4>
                        @if($stability['assigned_employees']->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table mb-2">
                                    <thead>
                                        <tr>
                                            <th class="text-start small fw-bold text-dark">Pracownik</th>
                                            <th class="text-start small fw-bold text-dark">Rola w projekcie</th>
                                            <th class="text-center small fw-bold text-dark">Pokrycie</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($stability['assigned_employees'] as $employeeData)
                                            <tr>
                                                <td>
                                                    <x-employee-cell :employee="$employeeData['employee']"  />
                                                </td>
                                                <td>
                                                    @if($employeeData['role_stable'] && $employeeData['role'])
                                                        <span class="badge bg-primary">{{ $employeeData['role']->name }}</span>
                                                    @else
                                                        <span class="badge bg-warning" title="Rola zmienia się w trakcie tygodnia">
                                                            <i class="bi bi-arrow-left-right"></i> Zmienne
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <span class="text-dark fw-semibold">
                                                        {{ $employeeData['date_range'] }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-2">
                                <a href="{{ route('project-assignments.create', ['project_id' => $project->id, 'date_from' => $weekStart->format('Y-m-d'), 'date_to' => $weekEnd->format('Y-m-d')]) }}" class="btn btn-sm btn-primary w-100">
                                    <i class="bi bi-plus"></i>
                                    {{ $stability['assigned_employees']->count() > 0 ? 'Dodaj' : 'Przypisz' }}
                                </a>
                            </div>
                        @else
                            <div class="text-center py-4 text-muted small">
                                Brak osób
                            </div>
                            <div class="mt-2">
                                <a href="{{ route('project-assignments.create', ['project_id' => $project->id, 'date_from' => $weekStart->format('Y-m-d'), 'date_to' => $weekEnd->format('Y-m-d')]) }}" class="btn btn-sm btn-primary w-100">
                                    <i class="bi bi-plus"></i>
                                    Przypisz osoby
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Potencjalne problemy -->
            @if(!empty($stability['potential_issues']))
                <div class="alert alert-warning mb-3">
                    <h5 class="alert-heading small fw-bold mb-2">
                        <i class="bi bi-exclamation-triangle"></i>
                        Potencjalne problemy – sprawdź dni
                    </h5>
                    <ul class="mb-0 small">
                        @foreach($stability['potential_issues'] as $issue)
                            <li>
                                {{ $issue['message'] }}
                                <a href="{{ route('weekly-overview.planner2', ['start_date' => $weekStart->format('Y-m-d'), 'project_id' => $project->id]) }}" 
                                   class="text-decoration-none">
                                    Sprawdź dni →
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
@else
    <div class="card shadow-sm border-0">
        <div class="card-body text-center py-5">
            <div class="mb-4">
                <i class="bi bi-file-earmark-text text-muted" style="font-size: 4rem;"></i>
            </div>
            <p class="text-muted fs-5 fw-medium mb-4">Brak prac w tym tygodniu</p>
            <x-ui.button variant="success" href="{{ route('projects.demands.create', ['project' => $project->id, 'date_from' => $weekStart->format('Y-m-d'), 'date_to' => $weekEnd->format('Y-m-d')]) }}">
                <i class="bi bi-plus"></i>
                Dodaj zapotrzebowanie
            </x-ui.button>
        </div>
    </div>
@endif
