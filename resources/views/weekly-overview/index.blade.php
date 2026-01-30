<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Przegląd tygodniowy">
            <x-slot name="left">
                @if($projectId)
                    <x-ui.button variant="ghost" href="{{ route('weekly-overview.index', ['start_date' => $startDate->format('Y-m-d')]) }}" action="back" class="btn-sm">
                        Wyczyść filtry
                    </x-ui.button>
                @endif
            </x-slot>
            <x-slot name="right">
                <select id="project-search" class="form-select form-select-sm" style="width: 200px;" onchange="(function() { const baseUrl = '{{ route('weekly-overview.index') }}'; const params = new URLSearchParams(); params.set('start_date', '{{ $startDate->format('Y-m-d') }}'); if (this.value) { params.set('project_id', this.value); } window.location.href = baseUrl + '?' + params.toString(); }).call(this)">
                    <option value="">Wszystkie projekty</option>
                    @foreach($allProjects as $project)
                        <option value="{{ $project->id }}" {{ $projectId && $projectId == $project->id ? 'selected' : '' }}>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <!-- Nawigacja między tygodniami -->
    <div class="mb-4 d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <x-ui.button variant="ghost" href="{{ $navigation['prevUrl'] }}" action="back">
            Poprzedni tydzień
        </x-ui.button>

        <div class="text-center">
            <h3 class="fs-5 fw-bold mb-0">
                Tydzień {{ $navigation['current']['number'] }}
            </h3>
            <p class="small text-muted mb-0">
                {{ $navigation['current']['start']->format('d.m.Y') }} – {{ $navigation['current']['end']->format('d.m.Y') }}
            </p>
        </div>

        <x-ui.button variant="primary" href="{{ $navigation['nextUrl'] }}">
            Następny tydzień
        </x-ui.button>
    </div>

    <!-- Projekty -->
    @php
        // Pre-load all project assignments for all employees in vehicles/accommodations to avoid N+1 queries
        $weekStart = $weeks[0]['start'];
        $weekEnd = $weeks[0]['end'];
        $allEmployeeIds = collect();
        
        // Collect all employee IDs from vehicles and accommodations across all projects
        foreach ($projects as $projectData) {
            $weekData = $projectData['weeks_data'][0] ?? null;
            if ($weekData) {
                $vehicles = collect($weekData['vehicles'] ?? []);
                $accommodations = collect($weekData['accommodations'] ?? []);
                
                foreach ($vehicles as $vehicleData) {
                    if (isset($vehicleData['assignments'])) {
                        $allEmployeeIds = $allEmployeeIds->merge(collect($vehicleData['assignments'])->pluck('employee_id'));
                    }
                }
                
                foreach ($accommodations as $accommodationData) {
                    if (isset($accommodationData['assignments'])) {
                        $allEmployeeIds = $allEmployeeIds->merge(collect($accommodationData['assignments'])->pluck('employee_id'));
                    }
                }
            }
        }
        
        $allEmployeeIds = $allEmployeeIds->unique()->filter();
        
        // Pre-load all project assignments for these employees in one query
        $preloadedProjectAssignments = collect();
        if ($allEmployeeIds->isNotEmpty()) {
            $preloadedProjectAssignments = \App\Models\ProjectAssignment::whereIn('employee_id', $allEmployeeIds)
                ->where('is_cancelled', false)
                ->where(function($query) use ($weekStart, $weekEnd) {
                    $query->where(function($q) use ($weekStart, $weekEnd) {
                        $q->where('start_date', '<=', $weekEnd)
                          ->where(function($q2) use ($weekStart) {
                              $q2->whereNull('end_date')
                                 ->orWhere('end_date', '>=', $weekStart);
                          });
                    });
                })
                ->with('project')
                ->get()
                ->groupBy('employee_id');
        }
    @endphp
    
    @forelse($projects as $projectData)
        @php
            $project = $projectData['project'];
            $summary = $projectData['summary'] ?? null;
            $weekData = $projectData['weeks_data'][0] ?? null;
            $weekStart = $weeks[0]['start']->format('Y-m-d');
            $planner2Url = route('weekly-overview.planner2', ['start_date' => $weekStart, 'project_id' => $project->id]);
        @endphp

        <x-ui.card class="mb-4">
            <!-- Row1: Nazwa projektu/lokalizacja + statystyki -->
            <div class="mb-3">
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <span class="badge bg-primary rounded-circle" style="width: 8px; height: 8px; padding: 0;"></span>
                    <h3 class="fs-5 fw-bold mb-0 text-dark">
                        <a href="{{ route('projects.show', $project) }}" class="text-decoration-underline">{{ $project->name }}</a>
                    </h3>
                    
                    <!-- Przycisk Planer dzienny -->
                    <div class="flex-grow-1 text-center">
                        <x-ui.button variant="warning" href="{{ $planner2Url }}" action="view" class="btn-sm">
                            Zobacz planer dzienny
                        </x-ui.button>
                    </div>
                    
                    @if($weekData && $weekData['has_data'] && $summary)
                        @php
                            $reqSummary = $weekData['requirements_summary'] ?? [];
                            $totalNeeded = $reqSummary['total_needed'] ?? 0;
                            $totalAssigned = $reqSummary['total_assigned_max'] ?? $reqSummary['total_assigned'] ?? 0;
                            
                            // Licz osoby z autem i domem z przypisanych do projektu
                            $employeesWithoutVehicle = $summary->getEmployeesWithoutVehicle();
                            $employeesWithVehicle = $totalAssigned - $employeesWithoutVehicle->count();
                            
                            $employeesWithoutAccommodation = $summary->getEmployeesWithoutAccommodation();
                            $employeesWithAccommodation = $totalAssigned - $employeesWithoutAccommodation->count();
                            
                            $peopleProgress = $totalNeeded > 0 ? round(($totalAssigned / $totalNeeded) * 100) : 0;
                            $vehiclesProgress = $totalAssigned > 0 ? round(($employeesWithVehicle / $totalAssigned) * 100) : 0;
                            $accommodationsProgress = $totalAssigned > 0 ? round(($employeesWithAccommodation / $totalAssigned) * 100) : 0;
                        @endphp
                        
                        <div class="d-flex align-items-center gap-3 ms-auto flex-wrap">
                            <!-- Ludzie -->
                            <div class="d-flex align-items-center gap-2">
                                <x-tooltip title="Ilu jest przypisanych do projektu / na ilu było zapotrzebowanie">
                                    <i class="bi bi-people text-primary fs-5"></i>
                                </x-tooltip>
                                <div class="small">
                                    <div class="fw-semibold">{{ $totalAssigned }} / {{ $totalNeeded }}</div>
                                    <div class="mt-1" style="width: 60px;">
                                        <x-ui.progress value="{{ $peopleProgress }}" max="100" variant="{{ $peopleProgress == 100 ? 'success' : ($peopleProgress >= 70 ? 'warning' : 'danger') }}" />
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Auta -->
                            <div class="d-flex align-items-center gap-2">
                                <x-tooltip title="Ilu ma przypisane auto / z ilu przypisanych do projektu">
                                    <i class="bi bi-car-front text-info fs-5"></i>
                                </x-tooltip>
                                <div class="small">
                                    <div class="fw-semibold">{{ $employeesWithVehicle }} / {{ $totalAssigned }}</div>
                                    <div class="mt-1" style="width: 60px;">
                                        <x-ui.progress value="{{ $vehiclesProgress }}" max="100" variant="{{ $vehiclesProgress == 100 ? 'success' : ($vehiclesProgress >= 70 ? 'warning' : 'danger') }}" />
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Domy -->
                            <div class="d-flex align-items-center gap-2">
                                <x-tooltip title="Ilu ma przypisany dom / z ilu przypisanych do projektu">
                                     <i class="bi bi-house text-info fs-5"></i>
                                </x-tooltip>
                                <div class="small">
                                    <div class="fw-semibold">{{ $employeesWithAccommodation }} / {{ $totalAssigned }}</div>
                                    <div class="mt-1" style="width: 60px;">
                                        <x-ui.progress value="{{ $accommodationsProgress }}" max="100" variant="{{ $accommodationsProgress == 100 ? 'success' : ($accommodationsProgress >= 70 ? 'warning' : 'danger') }}" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                @if($project->location)
                    <div class="small text-muted">
                        <i class="bi bi-geo-alt"></i> {{ $project->location->name }}
                    </div>
                @endif
            </div>

            @if($weekData && $weekData['has_data'])
                <!-- Row3: Zapotrzebowanie, Auta w projekcie, Domy w projekcie -->
                <div class="row g-3 mb-4">
                    <!-- Zapotrzebowanie (1/3) -->
                    <div class="col-md-4">
                        <x-ui.card>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="card-label">Zapotrzebowanie</span>
                                <x-ui.button 
                                    variant="ghost" 
                                    href="{{ route('projects.demands.create', ['project' => $project, 'start_date' => $weeks[0]['start']->format('Y-m-d'), 'end_date' => $weeks[0]['end']->format('Y-m-d')]) }}" 
                                    class="btn-sm" 
                                    action="edit"
                                    title="Edytuj zapotrzebowanie"
                                >
                                    <i class="bi bi-pencil"></i>
                                </x-ui.button>
                            </div>
                            
                            @php
                                $reqSummary = $weekData['requirements_summary'] ?? [];
                                $totalNeeded = $reqSummary['total_needed'] ?? 0;
                                $totalAssigned = $reqSummary['total_assigned_max'] ?? $reqSummary['total_assigned'] ?? 0;
                                $roleDetails = $reqSummary['role_details'] ?? [];
                                $summary = new \App\ViewModels\WeeklyProjectSummary($weekData);
                            @endphp
                            
                            <!-- Tabelka zapotrzebowania -->
                            @if(!empty($roleDetails))
                                <div class="table-responsive mb-0">
                                    <table class="table table-sm mb-0" style="margin-bottom: 0 !important;">
                                        <thead>
                                            <tr>
                                                <th class="text-start small fw-bold" style="padding: 0.5rem;">Rola</th>
                                                <th class="text-center small fw-bold" style="padding: 0.5rem;">jest</th>
                                                <th class="text-center small fw-bold" style="padding: 0.5rem;">ma być</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($roleDetails as $roleDetail)
                                                @php
                                                    $needed = $roleDetail['needed'] ?? 0;
                                                    $assigned = $roleDetail['assigned'] ?? null;
                                                    $assignedMin = $roleDetail['assigned_min'] ?? $assigned;
                                                    $assignedMax = $roleDetail['assigned_max'] ?? $assigned;
                                                    $isStable = $roleDetail['is_stable'] ?? true;
                                                    $missing = $roleDetail['missing'] ?? 0;
                                                    $isComplete = $isStable && $assigned !== null && $assigned >= $needed;
                                                    $isPartial = $isStable && $assigned !== null && $assigned > 0 && $assigned < $needed;
                                                    $role = $roleDetail['role'] ?? null;
                                                @endphp
                                                <tr>
                                                    <td class="small" style="padding: 0.25rem;">
                                                        @if($role)
                                                            @php
                                                                $createUrl = route('project-assignments.create', [
                                                                    'project_id' => $project->id,
                                                                    'start_date' => $weeks[0]['start']->format('Y-m-d'),
                                                                    'end_date' => $weeks[0]['end']->format('Y-m-d'),
                                                                    'role_id' => $role->id
                                                                ]);
                                                            @endphp
                                                            <x-ui.clickable-badge 
                                                                variant="accent" 
                                                                :href="$createUrl"
                                                            >
                                                                {{ $role->name }}
                                                            </x-ui.clickable-badge>
                                                        @else
                                                            {{ Str::lower($roleDetail['role']->name ?? '-') }}
                                                        @endif
                                                    </td>
                                                    <td class="text-center small fw-semibold text-primary" style="padding: 0.25rem;">
                                                        @if($isStable && $assigned !== null)
                                                            {{ $assigned }}
                                                        @else
                                                            {{ $assignedMin }}-{{ $assignedMax }}
                                                        @endif
                                                    </td>
                                                    <td class="text-center small fw-semibold {{ $isComplete ? 'text-success' : ($isPartial ? 'text-warning' : 'text-danger') }}" style="padding: 0.25rem;">
                                                        {{ $needed }}
                                                    </td>
                                                </tr>
                                                @if($needed > 0)
                                                    @php
                                                        $roleProgress = $isStable && $assigned !== null 
                                                            ? round(($assigned / $needed) * 100) 
                                                            : ($assignedMax > 0 ? round(($assignedMax / $needed) * 100) : 0);
                                                        $roleProgressVariant = $roleProgress == 100 ? 'success' : ($roleProgress >= 70 ? 'warning' : 'danger');
                                                    @endphp
                                                    <tr>
                                                        <td colspan="3" style="padding: 0.25rem 0.5rem;">
                                                            <div style="width: 100%;">
                                                                <x-ui.progress 
                                                                    value="{{ min($roleProgress, 100) }}" 
                                                                    max="100" 
                                                                    variant="{{ $roleProgressVariant }}"
                                                                    style="height: 4px;"
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="small text-muted">Potrzebnych:</span>
                                        <x-ui.badge variant="info">{{ $totalNeeded }}</x-ui.badge>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="small text-muted">Przypisanych:</span>
                                        <x-ui.badge variant="accent">{{ $totalAssigned }}</x-ui.badge>
                                    </div>
                                    @if($totalMissing > 0)
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="small text-danger fw-bold">Brak:</span>
                                            <x-ui.badge variant="danger">{{ $totalMissing }}</x-ui.badge>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </x-ui.card>
                    </div>

                    <!-- Auta w projekcie (1/3) -->
                    <div class="col-md-4">
                        <x-ui.card label="Auta w projekcie">
                            @php
                                $vehicles = collect($weekData['vehicles'] ?? []);
                            @endphp
                            @if($vehicles->isNotEmpty())
                                <div class="d-flex flex-column gap-2">
                                    @foreach($vehicles->take(6) as $vehicleData)
                                        @php
                                            $usagePercentage = $vehicleData['usage_percentage'] ?? 0;
                                            $progressVariant = $usagePercentage == 100 ? 'success' : ($usagePercentage >= 70 ? 'warning' : 'danger');
                                        @endphp
                                        <x-ui.card>
                                            <!-- Wiersz 1: Nazwa auta i progress bar -->
                                            <div class="mb-3">
                                                <div class="small fw-semibold mb-2">
                                                    <a href="{{ route('vehicles.show', $vehicleData['vehicle']) }}" class="text-decoration-none">
                                                        {{ $vehicleData['vehicle_name'] }}
                                                    </a>
                                                </div>
                                                <div>
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="small text-muted">{{ $vehicleData['usage'] }}</span>
                                                        <span class="small fw-semibold">{{ $usagePercentage }}%</span>
                                                    </div>
                                                    <x-ui.progress value="{{ $usagePercentage }}" max="100" variant="{{ $progressVariant }}" />
                                                </div>
                                            </div>
                                            
                                            <!-- Wiersz 2: Zdjęcie auta (50%) i lista osób (50%) -->
                                            <div class="row g-2">
                                                <!-- Lewa połowa - zdjęcie auta -->
                                                <div class="col-6">
                                                    <a href="{{ route('vehicles.show', $vehicleData['vehicle']) }}" class="text-decoration-none" style="display: block;">
                                                        <div class="d-flex align-items-center justify-content-center w-100" style="height: 120px; background: var(--bg-card); padding: 8px; border-radius: 4px;">
                                                            @if($vehicleData['vehicle']->image_path)
                                                                <img src="{{ $vehicleData['vehicle']->image_url }}" 
                                                                     alt="{{ $vehicleData['vehicle_name'] }}" 
                                                                     style="max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain; border-radius: 4px;"
                                                                     onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2306b6d4\'%3E%3Cpath d=\'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2\'/%3E%3C/svg%3E';">
                                                            @else
                                                                <div class="bg-info bg-opacity-10 w-100 h-100 d-flex align-items-center justify-content-center" style="border-radius: 4px;">
                                                                    <i class="bi bi-car-front text-info" style="font-size: 3rem;"></i>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </a>
                                                </div>
                                                
                                                <!-- Prawa połowa - lista osób -->
                                                <div class="col-6">
                                                    @if(isset($vehicleData['assignments']) && $vehicleData['assignments']->isNotEmpty())
                                                        <ul class="list-unstyled mb-0 small">
                                                            @foreach($vehicleData['assignments'] as $assignment)
                                                                @php
                                                                    $position = $assignment->position ?? \App\Enums\VehiclePosition::PASSENGER;
                                                                    $positionValue = $position instanceof \App\Enums\VehiclePosition ? $position->value : $position;
                                                                    $isDriver = $positionValue === 'driver';
                                                                @endphp
                                                                <li class="mb-1">
                                                                    <div class="d-flex align-items-center gap-1">
                                                                        <a href="{{ route('vehicle-assignments.show', $assignment) }}" 
                                                                           class="text-decoration-none d-flex align-items-center gap-1">
                                                                            <i class="bi {{ $isDriver ? 'bi-car-front-fill text-success' : 'bi-person text-primary' }}"></i>
                                                                            <span class="{{ $isDriver ? 'text-success fw-semibold' : 'text-primary' }}">
                                                                                {{ $assignment->employee->full_name }}
                                                                            </span>
                                                                        </a>
                                                                    </div>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <p class="small text-muted mb-0">Brak przypisanych osób</p>
                                                    @endif
                                                    
                                                    <!-- Informacja o zjazdzie dla tego auta -->
                                                    @if(isset($vehicleData['return_trip']) && $vehicleData['return_trip'] !== null)
                                                        @php
                                                            $returnTrip = $vehicleData['return_trip'];
                                                        @endphp
                                                        <div class="mt-2 pt-2 border-top">
                                                            <div class="d-flex align-items-center gap-1 small">
                                                                <i class="bi bi-arrow-return-left text-warning"></i>
                                                                <a href="{{ route('return-trips.show', $returnTrip) }}" class="text-decoration-none text-dark fw-semibold">
                                                                    Zjazd: {{ $returnTrip->event_date->format('d.m.Y') }}
                                                                    @if($vehicleData['vehicle'])
                                                                        - {{ $vehicleData['vehicle']->registration_number }}
                                                                    @endif
                                                                </a>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <!-- Sekcja "Obsługuje:" -->
                                            @php
                                                // Pobierz employee IDs z assignments tego auta
                                                $vehicleEmployeeIds = collect($vehicleData['assignments'] ?? [])
                                                    ->pluck('employee_id')
                                                    ->unique()
                                                    ->filter();
                                                
                                                // Jeśli są pracownicy przypisani do auta, sprawdź ich projekty (używamy pre-loaded danych)
                                                $otherProjects = collect();
                                                if ($vehicleEmployeeIds->isNotEmpty()) {
                                                    // Użyj pre-loaded project assignments zamiast nowego zapytania
                                                    $projectAssignments = collect();
                                                    foreach ($vehicleEmployeeIds as $employeeId) {
                                                        if ($preloadedProjectAssignments->has($employeeId)) {
                                                            $projectAssignments = $projectAssignments->merge($preloadedProjectAssignments->get($employeeId));
                                                        }
                                                    }
                                                    
                                                    // Zbierz unikalne projekty (oprócz aktualnego)
                                                    $otherProjects = $projectAssignments
                                                        ->pluck('project')
                                                        ->filter()
                                                        ->unique('id')
                                                        ->filter(fn($p) => $p->id !== $project->id)
                                                        ->values();
                                                }
                                            @endphp
                                            
                                            @if($otherProjects->isNotEmpty())
                                                <hr class="my-2">
                                                <div class="small">
                                                    <span class="text-muted fw-semibold">Obsługuje również:</span>
                                                    <div class="mt-1">
                                                        @foreach($otherProjects as $otherProject)
                                                            <x-ui.clickable-badge variant="info" route="projects.show" :routeParams="['project' => $otherProject]" class="small me-2 mb-1">
                                                                {{ $otherProject->name }}
                                                            </x-ui.clickable-badge>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </x-ui.card>
                                    @endforeach
                                </div>
                                @if($vehicles->count() > 6)
                                    <div class="text-center mt-2">
                                        <x-ui.badge variant="info">+{{ $vehicles->count() - 6 }} więcej</x-ui.badge>
                                    </div>
                                @endif
                            @else
                                <p class="text-muted small mb-0">Brak aut w projekcie</p>
                            @endif
                        </x-ui.card>
                    </div>

                    <!-- Domy w projekcie (1/3) -->
                    <div class="col-md-4">
                        <x-ui.card label="Domy w projekcie">
                            @php
                                $accommodations = collect($weekData['accommodations'] ?? []);
                            @endphp
                            @if($accommodations->isNotEmpty())
                                <div class="d-flex flex-column gap-2">
                                    @foreach($accommodations->take(6) as $accommodationData)
                                        @php
                                            $accommodation = $accommodationData['accommodation'];
                                            $usagePercentage = $accommodationData['usage_percentage'] ?? 0;
                                            $progressVariant = $usagePercentage == 100 ? 'success' : ($usagePercentage >= 70 ? 'warning' : 'danger');
                                        @endphp
                                        <x-ui.card>
                                            <!-- Wiersz 1: Nazwa domu i progress bar -->
                                            <div class="mb-3">
                                                <div class="small fw-semibold mb-2">
                                                    <a href="{{ route('accommodations.show', $accommodation) }}" class="text-decoration-none">
                                                        {{ $accommodation->name }}
                                                    </a>
                                                </div>
                                                <div>
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="small text-muted">{{ $accommodationData['usage'] }}</span>
                                                        <span class="small fw-semibold">{{ $usagePercentage }}%</span>
                                                    </div>
                                                    <x-ui.progress value="{{ $usagePercentage }}" max="100" variant="{{ $progressVariant }}" />
                                                </div>
                                            </div>
                                            
                                            <!-- Wiersz 2: Zdjęcie domu (50%) i lista osób (50%) -->
                                            <div class="row g-2">
                                                <!-- Lewa połowa - zdjęcie domu -->
                                                <div class="col-6">
                                                    <a href="{{ route('accommodations.show', $accommodation) }}" class="text-decoration-none" style="display: block;">
                                                        <div class="d-flex align-items-center justify-content-center w-100" style="height: 120px; background: var(--bg-card); padding: 8px; border-radius: 4px;">
                                                            @if($accommodation->image_path)
                                                                <img src="{{ $accommodation->image_url }}" 
                                                                     alt="{{ $accommodation->name }}" 
                                                                     style="max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain; border-radius: 4px;"
                                                                     onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2310b981\'%3E%3Cpath d=\'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6\'/%3E%3C/svg%3E';">
                                                            @else
                                                                <div class="bg-success bg-opacity-10 w-100 h-100 d-flex align-items-center justify-content-center" style="border-radius: 4px;">
                                                                    <i class="bi bi-house text-success" style="font-size: 3rem;"></i>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </a>
                                                </div>
                                                
                                                <!-- Prawa połowa - lista osób -->
                                                <div class="col-6">
                                                    @if(isset($accommodationData['assignments']) && $accommodationData['assignments']->isNotEmpty())
                                                        <ul class="list-unstyled mb-0 small">
                                                            @foreach($accommodationData['assignments'] as $assignment)
                                                                <li class="mb-1">
                                                                    <div class="d-flex align-items-center gap-1">
                                                                        <a href="{{ route('accommodation-assignments.show', $assignment) }}" 
                                                                           class="text-decoration-none d-flex align-items-center gap-1">
                                                                            <i class="bi bi-house text-success"></i>
                                                                            <span class="text-primary">
                                                                                {{ $assignment->employee->full_name }}
                                                                            </span>
                                                                        </a>
                                                                    </div>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <p class="small text-muted mb-0">Brak przypisanych osób</p>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <!-- Sekcja "Obsługuje również:" -->
                                            @php
                                                // Pobierz employee IDs z assignments tego domu
                                                $accommodationEmployeeIds = collect($accommodationData['assignments'] ?? [])
                                                    ->pluck('employee_id')
                                                    ->unique()
                                                    ->filter();
                                                
                                                // Jeśli są pracownicy przypisani do domu, sprawdź ich projekty (używamy pre-loaded danych)
                                                $otherProjects = collect();
                                                if ($accommodationEmployeeIds->isNotEmpty()) {
                                                    // Użyj pre-loaded project assignments zamiast nowego zapytania
                                                    $projectAssignments = collect();
                                                    foreach ($accommodationEmployeeIds as $employeeId) {
                                                        if ($preloadedProjectAssignments->has($employeeId)) {
                                                            $projectAssignments = $projectAssignments->merge($preloadedProjectAssignments->get($employeeId));
                                                        }
                                                    }
                                                    
                                                    // Zbierz unikalne projekty (oprócz aktualnego)
                                                    $otherProjects = $projectAssignments
                                                        ->pluck('project')
                                                        ->filter()
                                                        ->unique('id')
                                                        ->filter(fn($p) => $p->id !== $project->id)
                                                        ->values();
                                                }
                                            @endphp
                                            
                                            @if($otherProjects->isNotEmpty())
                                                <hr class="my-2">
                                                <div class="small">
                                                    <span class="text-muted fw-semibold">Obsługuje również:</span>
                                                    <div class="mt-1">
                                                        @foreach($otherProjects as $otherProject)
                                                            <x-ui.clickable-badge variant="info" route="projects.show" :routeParams="['project' => $otherProject]" class="small me-2 mb-1">
                                                                {{ $otherProject->name }}
                                                            </x-ui.clickable-badge>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </x-ui.card>
                                    @endforeach
                                </div>
                                @if($accommodations->count() > 6)
                                    <div class="text-center mt-2">
                                        <x-ui.badge variant="info">+{{ $accommodations->count() - 6 }} więcej</x-ui.badge>
                                    </div>
                                @endif
                            @else
                                <p class="text-muted small mb-0">Brak domów w projekcie</p>
                            @endif
                        </x-ui.card>
                    </div>
                </div>

                <!-- Karta Alerty -->
                @if($weekData && $weekData['has_data'])
                    @php
                        $summary = new \App\ViewModels\WeeklyProjectSummary($weekData);
                        $hasBraki = $summary->getTotalMissing() > 0 || $summary->getEmployeesWithoutVehicle()->isNotEmpty() || $summary->getEmployeesWithoutAccommodation()->isNotEmpty();
                        $hasNadmiary = $summary->getTotalExcess() > 0 || $summary->getOvercrowdedAccommodations()->isNotEmpty() || $summary->getOvercrowdedVehicles()->isNotEmpty();
                    @endphp
                    
                    @if($hasBraki || $hasNadmiary || ($returnTrips->isNotEmpty() ?? false) || (isset($departures) && $departures->isNotEmpty()))
                        <x-ui.card label="Alerty" class="mt-4 mb-4">
                            <div class="row g-3">
                                <!-- Kolumna 1: Braki -->
                                <div class="col-md-3">
                                    @if($hasBraki)
                                        <x-ui.alert variant="danger" title="Braki">
                                            @if($summary->getTotalMissing() > 0)
                                                <div class="mb-1 small">
                                                    Brakuje {{ $summary->getTotalMissing() }} {{ $summary->getTotalMissing() == 1 ? 'osoby' : 'osób' }}
                                                </div>
                                            @endif
                                            @if($summary->getEmployeesWithoutVehicle()->isNotEmpty())
                                                <div class="mb-1 small">
                                                    {{ $summary->getEmployeesWithoutVehicle()->count() }} {{ $summary->getEmployeesWithoutVehicle()->count() == 1 ? 'osobie' : 'osobom' }} brakuje auta
                                                </div>
                                            @endif
                                            @if($summary->getEmployeesWithoutAccommodation()->isNotEmpty())
                                                <div class="mb-1 small">
                                                    {{ $summary->getEmployeesWithoutAccommodation()->count() }} {{ $summary->getEmployeesWithoutAccommodation()->count() == 1 ? 'osobie' : 'osobom' }} brakuje domu
                                                </div>
                                            @endif
                                        </x-ui.alert>
                                    @else
                                        <div class="small text-muted">Brak braków</div>
                                    @endif
                                </div>
                                
                                <!-- Kolumna 2: Nadmiary -->
                                <div class="col-md-3">
                                    @if($hasNadmiary)
                                        <x-ui.alert variant="warning" title="Nadmiary">
                                            @if($summary->getTotalExcess() > 0)
                                                <div class="mb-1 small">
                                                    Nadmiar {{ $summary->getTotalExcess() }} {{ $summary->getTotalExcess() == 1 ? 'osoby' : 'osób' }}
                                                </div>
                                            @endif
                                            @if($summary->getOvercrowdedVehicles()->isNotEmpty())
                                                <div class="mb-1 small">
                                                    {{ $summary->getOvercrowdedVehicles()->count() }} {{ $summary->getOvercrowdedVehicles()->count() == 1 ? 'auto' : 'aut' }} przepełnione
                                                </div>
                                            @endif
                                            @if($summary->getOvercrowdedAccommodations()->isNotEmpty())
                                                <div class="mb-1 small">
                                                    {{ $summary->getOvercrowdedAccommodations()->count() }} {{ $summary->getOvercrowdedAccommodations()->count() == 1 ? 'dom' : 'domów' }} przepełnionych
                                                </div>
                                            @endif
                                        </x-ui.alert>
                                    @else
                                        <div class="small text-muted">Brak nadmiarów</div>
                                    @endif
                                </div>
                                
                                <!-- Kolumna 3: Zjazdy -->
                                <div class="col-md-3">
                                    @if($returnTrips->isNotEmpty())
                                        <div class="small">
                                            <div class="fw-semibold text-warning mb-2">
                                                <i class="bi bi-arrow-return-left"></i> Zjazdy
                                            </div>
                                            <ul class="mb-0 small list-unstyled">
                                                @foreach($returnTrips as $returnTrip)
                                                    <li class="mb-1">
                                                        <a href="{{ route('return-trips.show', $returnTrip) }}" class="text-decoration-none">
                                                            <strong>{{ $returnTrip->event_date->format('d.m.Y') }}</strong>
                                                            @if($returnTrip->vehicle)
                                                                - {{ $returnTrip->vehicle->registration_number }}
                                                            @endif
                                                            @php
                                                                $uniqueParticipantsCount = $returnTrip->participants->pluck('employee_id')->unique()->count();
                                                            @endphp
                                                            @if($uniqueParticipantsCount > 0)
                                                                ({{ $uniqueParticipantsCount }} {{ $uniqueParticipantsCount == 1 ? 'osoba' : 'osób' }})
                                                            @endif
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @else
                                        <div class="small text-muted">Brak zjazdów</div>
                                    @endif
                                </div>
                                
                                <!-- Kolumna 4: Wyjazdy -->
                                <div class="col-md-3">
                                    @if(isset($departures) && $departures->isNotEmpty())
                                        <div class="small">
                                            <div class="fw-semibold text-warning mb-2">
                                                <i class="bi bi-arrow-right"></i> Wyjazdy
                                            </div>
                                            <ul class="mb-0 small list-unstyled">
                                                @foreach($departures as $departure)
                                                    <li class="mb-1">
                                                        <a href="{{ route('departures.show', $departure) }}" class="text-decoration-none">
                                                            <strong>{{ $departure->event_date->format('d.m.Y') }}</strong>
                                                            @if($departure->toLocation)
                                                                - {{ $departure->toLocation->name }}
                                                            @endif
                                                            @if($departure->vehicle)
                                                                - {{ $departure->vehicle->registration_number }}
                                                            @endif
                                                            @php
                                                                $uniqueParticipantsCount = $departure->participants->pluck('employee_id')->unique()->count();
                                                            @endphp
                                                            @if($uniqueParticipantsCount > 0)
                                                                ({{ $uniqueParticipantsCount }} {{ $uniqueParticipantsCount == 1 ? 'osoba' : 'osób' }})
                                                            @endif
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @else
                                        <div class="small text-muted">Brak wyjazdów</div>
                                    @endif
                                </div>
                            </div>
                        </x-ui.card>
                    @endif
                @endif

                <!-- Tabelka z ludźmi -->
                @if(isset($weekData['assigned_employees']) && $weekData['assigned_employees']->isNotEmpty())
                    <div class="mt-4">
                        <x-ui.table-header title="Przypisani pracownicy" titleClass="text-dark">
                            <x-ui.button variant="primary" href="{{ route('project-assignments.create', ['project_id' => $project->id, 'date_from' => $weeks[0]['start']->format('Y-m-d'), 'date_to' => $weeks[0]['end']->format('Y-m-d')]) }}" action="create" class="btn-sm">
                                Przypisz osoby
                            </x-ui.button>
                        </x-ui.table-header>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Pracownik</th>
                                        <th>Rola w projekcie</th>
                                        <th>Pokrycie</th>
                                        <th>Auto</th>
                                        <th>Dom</th>
                                        <th>Do rotacji</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($weekData['assigned_employees'] as $employeeData)
                                        @php
                                            $dateRange = $employeeData['date_range'] ?? 'cały tydzień';
                                            $isFullWeek = ($dateRange === 'cały tydzień' || $dateRange === 'pon-nie');
                                        @endphp
                                        <tr>
                                            <td>
                                                <x-employee-cell :employee="$employeeData['employee']"  />
                                            </td>
                                            <td>
                                                @if(isset($employeeData['role_stable']) && !$employeeData['role_stable'])
                                                    <x-ui.badge variant="warning" title="Rola zmienia się w trakcie tygodnia">
                                                        <i class="bi bi-arrow-left-right"></i> Zmienna
                                                    </x-ui.badge>
                                                @elseif(isset($employeeData['assignment']) && $employeeData['assignment'])
                                                    @php
                                                        $assignment = $employeeData['assignment'];
                                                        $editUrl = route('assignments.edit', ['project_assignment' => $assignment->id ?? $assignment]);
                                                    @endphp
                                                    <x-ui.clickable-badge variant="accent" :href="$editUrl">
                                                        {{ $employeeData['role']->name ?? '-' }}
                                                    </x-ui.clickable-badge>
                                                @else
                                                    <x-ui.badge variant="info">{{ $employeeData['role']->name ?? '-' }}</x-ui.badge>
                                                @endif
                                            </td>
                                            <td class="text-center {{ !$isFullWeek ? 'bg-danger bg-opacity-25' : '' }}">
                                                <span class="fw-semibold small">{{ $dateRange }}</span>
                                            </td>
                                            <td>
                                                @if(isset($employeeData['vehicle']) && $employeeData['vehicle'])
                                                    <x-ui.clickable-badge variant="success" route="vehicle-assignments.show" :routeParams="['vehicle_assignment' => $employeeData['vehicle_assignment']]" title="{{ $employeeData['vehicle']->brand }} {{ $employeeData['vehicle']->model }}">
                                                        <i class="bi bi-car-front"></i> {{ $employeeData['vehicle']->registration_number }}
                                                    </x-ui.clickable-badge>
                                                @elseif($employeeData['has_vehicle_in_week'] ?? false)
                                                    <x-ui.badge variant="success">
                                                        <i class="bi bi-car-front"></i> Tak
                                                    </x-ui.badge>
                                                @else
                                                    <x-ui.clickable-badge variant="danger" route="vehicle-assignments.create" :routeParams="['employee_id' => $employeeData['employee']->id, 'date_from' => $weeks[0]['start']->format('Y-m-d'), 'date_to' => $weeks[0]['end']->format('Y-m-d')]">
                                                        <i class="bi bi-x-circle"></i> Brak
                                                    </x-ui.clickable-badge>
                                                @endif
                                            </td>
                                            <td>
                                                @if(isset($employeeData['accommodation']) && $employeeData['accommodation'])
                                                    <x-ui.clickable-badge variant="info" route="accommodations.show" :routeParams="['accommodation' => $employeeData['accommodation']]">
                                                        <i class="bi bi-house"></i> {{ $employeeData['accommodation']->name }}
                                                    </x-ui.clickable-badge>
                                                @else
                                                    <x-ui.clickable-badge variant="danger" route="accommodation-assignments.create" :routeParams="['employee_id' => $employeeData['employee']->id, 'date_from' => $weeks[0]['start']->format('Y-m-d'), 'date_to' => $weeks[0]['end']->format('Y-m-d')]">
                                                        <i class="bi bi-x-circle"></i> Brak
                                                    </x-ui.clickable-badge>
                                                @endif
                                            </td>
                                            <td>
                                                @if(isset($employeeData['rotation']) && $employeeData['rotation'])
                                                    @php
                                                        $rotation = $employeeData['rotation']['rotation'] ?? null;
                                                        $startDate = $employeeData['rotation']['start_date'] ?? ($employeeData['rotation']->start_date ?? null);
                                                        $endDate = $employeeData['rotation']['end_date'] ?? ($employeeData['rotation']->end_date ?? null);
                                                        
                                                        // Oblicz liczbę dni
                                                        $daysCount = null;
                                                        if ($startDate && $endDate) {
                                                            $start = \Carbon\Carbon::parse($startDate);
                                                            $end = \Carbon\Carbon::parse($endDate);
                                                            $daysCount = $start->diffInDays($end) + 1; // +1 bo wliczamy oba dni
                                                        } elseif ($startDate) {
                                                            // Jeśli jest tylko startDate, oblicz do końca tygodnia
                                                            $start = \Carbon\Carbon::parse($startDate);
                                                            $weekEnd = $weeks[0]['end'];
                                                            $daysCount = $start->diffInDays($weekEnd) + 1;
                                                        } elseif ($endDate) {
                                                            // Jeśli jest tylko endDate, oblicz od początku tygodnia
                                                            $end = \Carbon\Carbon::parse($endDate);
                                                            $weekStart = $weeks[0]['start'];
                                                            $daysCount = $weekStart->diffInDays($end) + 1;
                                                        }
                                                    @endphp
                                                    @if($rotation)
                                                        <x-ui.clickable-badge variant="warning" route="employees.rotations.edit" :routeParams="['employee' => $employeeData['employee'], 'rotation' => $rotation]">
                                                            <i class="bi bi-arrow-repeat"></i> 
                                                            @if($daysCount !== null)
                                                                {{ $daysCount }} {{ $daysCount == 1 ? 'dzień' : 'dni' }}
                                                            @else
                                                                Rotacja
                                                            @endif
                                                        </x-ui.clickable-badge>
                                                    @else
                                                        <x-ui.badge variant="warning">
                                                            <i class="bi bi-arrow-repeat"></i> 
                                                            @if($daysCount !== null)
                                                                {{ $daysCount }} {{ $daysCount == 1 ? 'dzień' : 'dni' }}
                                                            @else
                                                                Rotacja
                                                            @endif
                                                        </x-ui.badge>
                                                    @endif
                                                @else
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="mt-4">
                        <x-ui.table-header title="Przypisani pracownicy" titleClass="text-dark">
                            <x-ui.button variant="primary" href="{{ route('project-assignments.create', ['project_id' => $project->id, 'date_from' => $weeks[0]['start']->format('Y-m-d'), 'date_to' => $weeks[0]['end']->format('Y-m-d')]) }}" action="create" class="btn-sm">
                                Przypisz osoby
                            </x-ui.button>
                        </x-ui.table-header>
                        <x-ui.empty-state 
                            icon="people"
                            message="Brak przypisanych pracowników w tym tygodniu."
                        />
                    </div>
                @endif

                <!-- Zadania projektu -->
                @php
                    $tasks = collect($weekData['tasks'] ?? [])
                        ->filter(fn($task) => in_array($task->status->value, [\App\Enums\TaskStatus::PENDING->value, \App\Enums\TaskStatus::IN_PROGRESS->value]));
                @endphp
                <div class="mt-4">
                    <x-ui.table-header title="Zadania projektu" titleClass="text-dark">
                        <x-ui.button variant="primary" href="{{ route('projects.show', $projectData['project']) }}?tab=tasks" action="create" class="btn-sm">
                            Dodaj zadanie
                        </x-ui.button>
                    </x-ui.table-header>
                    @if($tasks->isNotEmpty())
                        @include('components.project-tasks-list', ['tasks' => $tasks, 'project' => $projectData['project'], 'users' => $users ?? []])
                    @else
                        <x-ui.empty-state 
                            icon="list-check"
                            message="Brak zadań oczekujących lub w trakcie"
                        />
                    @endif
                </div>
            @else
                <x-ui.empty-state 
                    icon="folder"
                    message="Brak danych dla tego projektu w tym tygodniu."
                >
                    <x-ui.button 
                        variant="primary" 
                        href="{{ route('projects.demands.create', ['project' => $project, 'start_date' => $weeks[0]['start']->format('Y-m-d'), 'end_date' => $weeks[0]['end']->format('Y-m-d')]) }}"
                        action="create"
                    >
                        Dodaj zapotrzebowanie
                    </x-ui.button>
                </x-ui.empty-state>
            @endif
        </x-ui.card>
    @empty
        <x-ui.card>
            <x-ui.empty-state 
                icon="folder"
                message="Brak projektów do wyświetlenia."
            />
        </x-ui.card>
    @endforelse

    <!-- Sekcje dodatkowe -->
    <div class="mt-4">
        <!-- Kończące się dokumenty -->
        <x-ui.card>
            <h2 class="fs-3 fw-bold mb-4 text-center d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-exclamation-triangle text-danger"></i>
                Kończące się dokumenty i ubezpieczenia
            </h2>
            
            @php
                // Tworzymy listę pojazdów z informacją o typie (przegląd/OC)
                $vehiclesList = collect();
                foreach($expiringItems['vehicle_inspections'] as $vehicle) {
                    $vehiclesList->push([
                        'vehicle' => $vehicle,
                        'type' => 'inspection',
                        'date' => $vehicle->inspection_valid_to
                    ]);
                }
                foreach($expiringItems['vehicle_insurance'] as $vehicle) {
                    $vehiclesList->push([
                        'vehicle' => $vehicle,
                        'type' => 'insurance',
                        'date' => $vehicle->insurance_valid_to
                    ]);
                }
                $vehiclesList = $vehiclesList->sortBy('date');
                
                $hasExpiringItems = ($expiringItems['documents']->isNotEmpty() || 
                                    $vehiclesList->isNotEmpty() || 
                                    $expiringItems['accommodations']->isNotEmpty());
            @endphp
            
            @if($hasExpiringItems)
                <div x-data="{ 
                    showAllVehicles: false,
                    showAllAccommodations: false
                }">
                    <!-- Sekcja: Dokumenty -->
                    @if($expiringItems['documents']->isNotEmpty())
                        <div class="mb-4">
                            <h3 class="fs-4 fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-text text-info"></i>
                                Dokumenty ({{ $expiringItems['documents']->count() }})
                            </h3>
                            <div class="row g-3">
                                @foreach($expiringItems['documents']->take(6) as $document)
                                    <div class="col-md-4">
                                        <x-ui.card>
                                            <div class="d-flex align-items-start justify-content-between mb-2">
                                                <div class="flex-grow-1">
                                                    <h5 class="fw-bold mb-1">{{ $document->employee->full_name }}</h5>
                                                    <p class="mb-1 text-muted small">{{ $document->document->name ?? 'Dokument' }}</p>
                                                    @if($document->type)
                                                        <x-ui.badge variant="info" class="mb-2">{{ $document->type }}</x-ui.badge>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-calendar-event text-warning"></i>
                                                <span class="small">Ważny do: <strong>{{ $document->valid_to->format('d.m.Y') }}</strong></span>
                                            </div>
                                        </x-ui.card>
                                    </div>
                                @endforeach
                            </div>
                            @if($expiringItems['documents']->count() > 6)
                                <div class="text-center mt-3">
                                    <x-ui.button variant="outline-secondary" href="{{ route('employee-documents.index', ['filterStatus' => 'wygasa_wkrotce']) }}" class="btn-sm">
                                        Pokaż więcej ({{ $expiringItems['documents']->count() - 6 }})
                                    </x-ui.button>
                                </div>
                            @endif
                        </div>
                    @endif
                    
                    <!-- Sekcja: Auta -->
                    @if($vehiclesList->isNotEmpty())
                        <div class="mb-4">
                            <h3 class="fs-4 fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-car-front text-warning"></i>
                                Auta ({{ $vehiclesList->count() }})
                            </h3>
                            <div class="row g-3">
                                @foreach($vehiclesList->take(5) as $item)
                                    @php
                                        $vehicle = $item['vehicle'];
                                        $type = $item['type'];
                                        $date = $item['date'];
                                    @endphp
                                    <div class="col-md-4">
                                        <x-ui.card>
                                            <div class="d-flex align-items-start justify-content-between mb-2">
                                                <div class="flex-grow-1">
                                                    <h5 class="fw-bold mb-1">{{ $vehicle->registration_number }}</h5>
                                                    <p class="mb-1 text-muted small">{{ trim($vehicle->brand . ' ' . $vehicle->model) }}</p>
                                                    @if($type === 'inspection')
                                                        <x-ui.badge variant="warning" class="mb-2">Przegląd</x-ui.badge>
                                                    @else
                                                        <x-ui.badge variant="danger" class="mb-2">OC</x-ui.badge>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-calendar-event {{ $type === 'inspection' ? 'text-warning' : 'text-danger' }}"></i>
                                                <span class="small">
                                                    @if($type === 'inspection')
                                                        Przegląd ważny do:
                                                    @else
                                                        OC ważne do:
                                                    @endif
                                                    <strong>{{ $date->format('d.m.Y') }}</strong>
                                                </span>
                                            </div>
                                        </x-ui.card>
                                    </div>
                                @endforeach
                                
                                @if($vehiclesList->count() > 5)
                                    <template x-if="showAllVehicles">
                                        @foreach($vehiclesList->skip(5) as $item)
                                            @php
                                                $vehicle = $item['vehicle'];
                                                $type = $item['type'];
                                                $date = $item['date'];
                                            @endphp
                                            <div class="col-md-4">
                                                <x-ui.card>
                                                    <div class="d-flex align-items-start justify-content-between mb-2">
                                                        <div class="flex-grow-1">
                                                            <h5 class="fw-bold mb-1">{{ $vehicle->registration_number }}</h5>
                                                            <p class="mb-1 text-muted small">{{ trim($vehicle->brand . ' ' . $vehicle->model) }}</p>
                                                            @if($type === 'inspection')
                                                                <x-ui.badge variant="warning" class="mb-2">Przegląd</x-ui.badge>
                                                            @else
                                                                <x-ui.badge variant="danger" class="mb-2">OC</x-ui.badge>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="bi bi-calendar-event {{ $type === 'inspection' ? 'text-warning' : 'text-danger' }}"></i>
                                                        <span class="small">
                                                            @if($type === 'inspection')
                                                                Przegląd ważny do:
                                                            @else
                                                                OC ważne do:
                                                            @endif
                                                            <strong>{{ $date->format('d.m.Y') }}</strong>
                                                        </span>
                                                    </div>
                                                </x-ui.card>
                                            </div>
                                        @endforeach
                                    </template>
                                @endif
                            </div>
                            @if($vehiclesList->count() > 5)
                                <div class="text-center mt-3">
                                    <button @click="showAllVehicles = !showAllVehicles" class="btn btn-outline-secondary btn-sm">
                                        <span x-show="!showAllVehicles">Pokaż więcej ({{ $vehiclesList->count() - 5 }})</span>
                                        <span x-show="showAllVehicles">Pokaż mniej</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif
                    
                    <!-- Sekcja: Mieszkania -->
                    @if($expiringItems['accommodations']->isNotEmpty())
                        <div class="mb-4">
                            <h3 class="fs-4 fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-house text-danger"></i>
                                Mieszkania ({{ $expiringItems['accommodations']->count() }})
                            </h3>
                            <div class="row g-3">
                                @foreach($expiringItems['accommodations']->take(5) as $accommodation)
                                    <div class="col-md-4">
                                        <x-ui.card>
                                            <div class="d-flex align-items-start justify-content-between mb-2">
                                                <div class="flex-grow-1">
                                                    <h5 class="fw-bold mb-1">{{ $accommodation->name }}</h5>
                                                    <p class="mb-1 text-muted small">{{ $accommodation->address }}</p>
                                                    <x-ui.badge variant="danger" class="mb-2">Najem</x-ui.badge>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-calendar-event text-warning"></i>
                                                <span class="small">Ważny do: <strong>{{ $accommodation->lease_end_date->format('d.m.Y') }}</strong></span>
                                            </div>
                                        </x-ui.card>
                                    </div>
                                @endforeach
                                
                                @if($expiringItems['accommodations']->count() > 5)
                                    <template x-if="showAllAccommodations">
                                        @foreach($expiringItems['accommodations']->skip(5) as $accommodation)
                                            <div class="col-md-4">
                                                <x-ui.card>
                                                    <div class="d-flex align-items-start justify-content-between mb-2">
                                                        <div class="flex-grow-1">
                                                            <h5 class="fw-bold mb-1">{{ $accommodation->name }}</h5>
                                                            <p class="mb-1 text-muted small">{{ $accommodation->address }}</p>
                                                            <x-ui.badge variant="danger" class="mb-2">Najem</x-ui.badge>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="bi bi-calendar-event text-warning"></i>
                                                        <span class="small">Ważny do: <strong>{{ $accommodation->lease_end_date->format('d.m.Y') }}</strong></span>
                                                    </div>
                                                </x-ui.card>
                                            </div>
                                        @endforeach
                                    </template>
                                @endif
                            </div>
                            @if($expiringItems['accommodations']->count() > 5)
                                <div class="text-center mt-3">
                                    <button @click="showAllAccommodations = !showAllAccommodations" class="btn btn-outline-secondary btn-sm">
                                        <span x-show="!showAllAccommodations">Pokaż więcej ({{ $expiringItems['accommodations']->count() - 5 }})</span>
                                        <span x-show="showAllAccommodations">Pokaż mniej</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @else
                <div class="col-12">
                    <x-ui.card>
                        <p class="fw-medium mb-0 text-center text-muted">Brak dokumentów, ubezpieczeń ani najmów kończących się w tym miesiącu</p>
                    </x-ui.card>
                </div>
            @endif
        </x-ui.card>
    </div>

    <!-- Sekcja: Pracownicy bez projektu, ale z autem lub domem -->
    @if(isset($employeesWithoutProject) && $employeesWithoutProject->isNotEmpty())
        <div class="mt-4">
            <x-ui.alert variant="warning" title="Pracownicy bez projektu">
                <p class="mb-3">Następujący pracownicy mają przypisane auto lub dom, ale nie są przypisani do żadnego projektu w tym tygodniu:</p>
                <div class="row g-3">
                    @foreach($employeesWithoutProject as $employeeData)
                        @php
                            $employee = $employeeData['employee'];
                            $vehicles = $employeeData['vehicles'];
                            $accommodations = $employeeData['accommodations'];
                        @endphp
                        <div class="col-md-6 col-lg-4">
                            <x-ui.card>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <x-employee-cell :employee="$employee" />
                                </div>
                                @if($employee->roles->count() > 0)
                                    <div class="mb-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($employee->roles as $role)
                                                <x-ui.badge variant="accent">{{ $role->name }}</x-ui.badge>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                <div class="small">
                                    @if($vehicles->isNotEmpty())
                                        <div class="mb-1">
                                            <i class="bi bi-car-front text-primary"></i>
                                            <span class="text-muted">Auto:</span>
                                            @foreach($vehicles as $vehicle)
                                                <a href="{{ route('vehicles.show', $vehicle) }}" class="text-decoration-none">
                                                    {{ $vehicle->name }}
                                                </a>@if(!$loop->last), @endif
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($accommodations->isNotEmpty())
                                        <div>
                                            <i class="bi bi-house text-success"></i>
                                            <span class="text-muted">Dom:</span>
                                            @foreach($accommodations as $accommodation)
                                                <a href="{{ route('accommodations.show', $accommodation) }}" class="text-decoration-none">
                                                    {{ $accommodation->name }}
                                                </a>@if(!$loop->last), @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="mt-2">
                                    @if(isset($allProjects) && $allProjects->isNotEmpty())
                                        <div class="dropdown" style="position: relative; z-index: 9999;">
                                            <x-ui.button 
                                                variant="primary" 
                                                size="sm"
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false"
                                            >
                                                <i class="bi bi-person-check"></i> Przypisz projekt
                                            </x-ui.button>
                                            <ul class="dropdown-menu" style="background-color: var(--bg-card); opacity: 1; z-index: 9999; position: absolute;">
                                                @foreach($allProjects as $project)
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('project-assignments.create', ['project_id' => $project->id, 'employee_id' => $employee->id, 'start_date' => $weeks[0]['start']->format('Y-m-d'), 'end_date' => $weeks[0]['end']->format('Y-m-d')]) }}">
                                                            {{ $project->name }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @else
                                        <x-ui.button 
                                            variant="primary" 
                                            size="sm"
                                            href="{{ route('projects.index') }}"
                                        >
                                            <i class="bi bi-person-check"></i> Przypisz projekt
                                        </x-ui.button>
                                    @endif
                                </div>
                            </x-ui.card>
                        </div>
                    @endforeach
                </div>
            </x-ui.alert>
        </div>
    @endif

    <!-- Sekcja: Przyjazdy w tym tygodniu -->
    @if(isset($departures) && $departures->isNotEmpty())
        @php
            // Zbierz wszystkich unikalnych uczestników wyjazdów
            $departureEmployees = collect();
            foreach ($departures as $departure) {
                foreach ($departure->participants as $participant) {
                    if ($participant->employee && !$departureEmployees->contains('id', $participant->employee->id)) {
                        $departureEmployees->push([
                            'employee' => $participant->employee,
                            'departure' => $departure,
                        ]);
                    }
                }
            }
        @endphp
        
        @if($departureEmployees->isNotEmpty())
            <div class="mt-4">
                <x-ui.alert variant="warning" title="Przyjazdy w tym tygodniu">
                    <p class="mb-3">Następujący pracownicy przyjeżdżają w tym tygodniu i wymagają przypisania do projektu, mieszkania lub auta:</p>
                    <div class="row g-3">
                        @foreach($departureEmployees as $departureEmployeeData)
                            @php
                                $employee = $departureEmployeeData['employee'];
                                $departure = $departureEmployeeData['departure'];
                            @endphp
                            <div class="col-md-6 col-lg-4">
                                <x-ui.card>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <x-employee-cell :employee="$employee" />
                                    </div>
                                    @if($employee->roles->count() > 0)
                                        <div class="mb-2">
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($employee->roles as $role)
                                                    <x-ui.badge variant="accent">{{ $role->name }}</x-ui.badge>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    <div class="small mb-2">
                                        <div class="text-muted">
                                            <i class="bi bi-calendar-event"></i> 
                                            {{ $departure->event_date->format('d.m.Y') }}
                                            @if($departure->toLocation)
                                                - {{ $departure->toLocation->name }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @if(isset($allProjects) && $allProjects->isNotEmpty())
                                            <div class="dropdown" style="position: relative; z-index: 9999;">
                                                <x-ui.button 
                                                    variant="primary" 
                                                    size="sm"
                                                    data-bs-toggle="dropdown"
                                                    aria-expanded="false"
                                                >
                                                    <i class="bi bi-person-check"></i> Projekt
                                                </x-ui.button>
                                                <ul class="dropdown-menu" style="background-color: var(--bg-card); opacity: 1; z-index: 9999; position: absolute;">
                                                    @foreach($allProjects as $project)
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('project-assignments.create', ['project_id' => $project->id, 'employee_id' => $employee->id, 'start_date' => $weeks[0]['start']->format('Y-m-d'), 'end_date' => $weeks[0]['end']->format('Y-m-d')]) }}">
                                                                {{ $project->name }}
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @else
                                            <x-ui.button 
                                                variant="primary" 
                                                size="sm"
                                                href="{{ route('projects.index') }}"
                                            >
                                                <i class="bi bi-person-check"></i> Projekt
                                            </x-ui.button>
                                        @endif
                                        <x-ui.button 
                                            variant="success" 
                                            size="sm"
                                            href="{{ route('accommodation-assignments.create', ['employee_id' => $employee->id, 'date_from' => $weeks[0]['start']->format('Y-m-d'), 'date_to' => $weeks[0]['end']->format('Y-m-d')]) }}"
                                        >
                                            <i class="bi bi-house"></i> Dom
                                        </x-ui.button>
                                        <x-ui.button 
                                            variant="info" 
                                            size="sm"
                                            href="{{ route('vehicle-assignments.create', ['employee_id' => $employee->id, 'date_from' => $weeks[0]['start']->format('Y-m-d'), 'date_to' => $weeks[0]['end']->format('Y-m-d')]) }}"
                                        >
                                            <i class="bi bi-car-front"></i> Auto
                                        </x-ui.button>
                                    </div>
                                </x-ui.card>
                            </div>
                        @endforeach
                    </div>
                </x-ui.alert>
            </div>
        @endif
    @endif

</x-app-layout>
