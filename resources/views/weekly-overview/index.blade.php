<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Przegląd tygodniowy">
            <x-slot name="left">
                @if($projectId)
                    <x-ui.button variant="ghost" href="{{ route('weekly-overview.index', ['start_date' => $startDate->format('Y-m-d')]) }}" action="back" class="btn-sm">
                        Wyczyść filtry
                    </x-ui.button>
                @endif
                @if(isset($projects) && count($projects) > 0)
                    @php
                        $firstProject = $projects[0]['project'];
                        $firstWeekStart = $weeks[0]['start']->format('Y-m-d');
                        $firstPlanner2Url = route('weekly-overview.planner2', ['start_date' => $firstWeekStart, 'project_id' => $firstProject->id]);
                    @endphp
                    <x-ui.button variant="primary" href="{{ $firstPlanner2Url }}" action="view" class="btn-sm">
                        Zobacz planer dzienny
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
                    
                    @if($weekData && $weekData['has_data'] && $summary)
                        @php
                            $reqSummary = $weekData['requirements_summary'] ?? [];
                            $totalNeeded = $reqSummary['total_needed'] ?? 0;
                            $totalAssigned = $reqSummary['total_assigned_max'] ?? $reqSummary['total_assigned'] ?? 0;
                            
                            $vehicles = collect($weekData['vehicles'] ?? []);
                            $vehiclesCount = $vehicles->count();
                            $employeesWithoutVehicle = $summary->getEmployeesWithoutVehicle();
                            $vehiclesNeeded = $employeesWithoutVehicle->count();
                            
                            $accommodations = collect($weekData['accommodations'] ?? []);
                            $accommodationsCount = $accommodations->count();
                            $employeesWithoutAccommodation = $summary->getEmployeesWithoutAccommodation();
                            $accommodationsNeeded = $employeesWithoutAccommodation->count();
                            
                            $peopleProgress = $totalNeeded > 0 ? round(($totalAssigned / $totalNeeded) * 100) : 0;
                            $vehiclesProgress = ($vehiclesNeeded + $vehiclesCount) > 0 ? round(($vehiclesCount / ($vehiclesNeeded + $vehiclesCount)) * 100) : 0;
                            $accommodationsProgress = ($accommodationsNeeded + $accommodationsCount) > 0 ? round(($accommodationsCount / ($accommodationsNeeded + $accommodationsCount)) * 100) : 0;
                        @endphp
                        
                        <div class="d-flex align-items-center gap-3 ms-auto flex-wrap">
                            <!-- Ludzie -->
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-people text-primary fs-5"></i>
                                <div class="small">
                                    <div class="fw-semibold">{{ $totalAssigned }} / {{ $totalNeeded }}</div>
                                    <div class="mt-1" style="width: 60px;">
                                        <x-ui.progress value="{{ $peopleProgress }}" max="100" variant="{{ $peopleProgress == 100 ? 'success' : ($peopleProgress >= 70 ? 'warning' : 'danger') }}" />
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Auta -->
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-car-front text-info fs-5"></i>
                                <div class="small">
                                    <div class="fw-semibold">{{ $vehiclesCount }} / {{ $vehiclesNeeded + $vehiclesCount }}</div>
                                    <div class="mt-1" style="width: 60px;">
                                        <x-ui.progress value="{{ $vehiclesProgress }}" max="100" variant="{{ $vehiclesProgress == 100 ? 'success' : ($vehiclesProgress >= 70 ? 'warning' : 'danger') }}" />
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Domy -->
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-house text-success fs-5"></i>
                                <div class="small">
                                    <div class="fw-semibold">{{ $accommodationsCount }} / {{ $accommodationsNeeded + $accommodationsCount }}</div>
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
                        <x-ui.card label="Zapotrzebowanie">
                            @php
                                $reqSummary = $weekData['requirements_summary'] ?? [];
                                $totalNeeded = $reqSummary['total_needed'] ?? 0;
                                $totalAssigned = $reqSummary['total_assigned_max'] ?? $reqSummary['total_assigned'] ?? 0;
                                $roleDetails = $reqSummary['role_details'] ?? [];
                                $summary = new \App\ViewModels\WeeklyProjectSummary($weekData);
                            @endphp
                            
                            <!-- Braki i Nadmiary -->
                            @php
                                $hasBraki = $summary->getTotalMissing() > 0 || $summary->getEmployeesWithoutVehicle()->isNotEmpty() || $summary->getEmployeesWithoutAccommodation()->isNotEmpty();
                                $hasNadmiary = $summary->getTotalExcess() > 0 || $summary->getOvercrowdedAccommodations()->isNotEmpty() || $summary->getOvercrowdedVehicles()->isNotEmpty();
                            @endphp
                            
                            @if($hasBraki || $hasNadmiary)
                                <div class="mb-3">
                                    <!-- Braki -->
                                    @if($hasBraki)
                                        <x-ui.alert variant="danger" title="Braki" class="mb-2">
                                            @if($summary->getTotalMissing() > 0)
                                                <div class="mb-1">
                                                    <x-ui.badge variant="danger">
                                                        <i class="bi bi-exclamation-circle"></i> Brakuje {{ $summary->getTotalMissing() }} {{ $summary->getTotalMissing() == 1 ? 'osoby' : 'osób' }}
                                                    </x-ui.badge>
                                                </div>
                                            @endif
                                            @if($summary->getEmployeesWithoutVehicle()->isNotEmpty())
                                                <div class="mb-1">
                                                    <x-ui.badge variant="danger">
                                                        <i class="bi bi-exclamation-circle text-white"></i> {{ $summary->getEmployeesWithoutVehicle()->count() }} {{ $summary->getEmployeesWithoutVehicle()->count() == 1 ? 'osobie' : 'osobom' }} brakuje auta
                                                    </x-ui.badge>
                                                </div>
                                            @endif
                                            @if($summary->getEmployeesWithoutAccommodation()->isNotEmpty())
                                                <div class="mb-1">
                                                    <x-ui.badge variant="danger">
                                                        <i class="bi bi-exclamation-circle text-white"></i> {{ $summary->getEmployeesWithoutAccommodation()->count() }} {{ $summary->getEmployeesWithoutAccommodation()->count() == 1 ? 'osobie' : 'osobom' }} brakuje domu
                                                    </x-ui.badge>
                                                </div>
                                            @endif
                                        </x-ui.alert>
                                    @endif
                                    
                                    <!-- Nadmiary -->
                                    @if($hasNadmiary)
                                        <x-ui.alert variant="warning" title="Nadmiary" class="mb-2">
                                            @if($summary->getTotalExcess() > 0)
                                                <div class="mb-1">
                                                    <x-ui.badge variant="warning">
                                                        <i class="bi bi-exclamation-circle"></i> Nadmiar {{ $summary->getTotalExcess() }} {{ $summary->getTotalExcess() == 1 ? 'osoby' : 'osób' }}
                                                    </x-ui.badge>
                                                </div>
                                            @endif
                                            @if($summary->getOvercrowdedVehicles()->isNotEmpty())
                                                <div class="mb-1">
                                                    <x-ui.badge variant="warning">
                                                        <i class="bi bi-exclamation-circle"></i> {{ $summary->getOvercrowdedVehicles()->count() }} {{ $summary->getOvercrowdedVehicles()->count() == 1 ? 'auto' : 'aut' }} przepełnione
                                                    </x-ui.badge>
                                                </div>
                                            @endif
                                            @if($summary->getOvercrowdedAccommodations()->isNotEmpty())
                                                <div class="mb-1">
                                                    <x-ui.badge variant="warning">
                                                        <i class="bi bi-exclamation-circle"></i> {{ $summary->getOvercrowdedAccommodations()->count() }} {{ $summary->getOvercrowdedAccommodations()->count() == 1 ? 'dom' : 'domów' }} przepełnionych
                                                    </x-ui.badge>
                                                </div>
                                            @endif
                                        </x-ui.alert>
                                    @endif
                                </div>
                            @endif
                            
                            <!-- Tabelka zapotrzebowania -->
                            @if(!empty($roleDetails))
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th class="text-start small fw-bold">Rola</th>
                                                <th class="text-center small fw-bold">Przypisanych</th>
                                                <th class="text-center small fw-bold">Potrzebnych</th>
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
                                                @endphp
                                                <tr>
                                                    <td class="small">
                                                        {{ Str::lower($roleDetail['role']->name ?? '-') }}
                                                    </td>
                                                    <td class="text-center small fw-semibold text-primary">
                                                        @if($isStable && $assigned !== null)
                                                            {{ $assigned }}
                                                        @else
                                                            {{ $assignedMin }}-{{ $assignedMax }}
                                                        @endif
                                                    </td>
                                                    <td class="text-center small fw-semibold {{ $isComplete ? 'text-success' : ($isPartial ? 'text-warning' : 'text-danger') }}">
                                                        {{ $needed }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <tr class="fw-bold border-top">
                                                <td class="small">łącznie</td>
                                                <td class="text-center small">{{ $totalAssigned }}</td>
                                                <td class="text-center small">{{ $totalNeeded }}</td>
                                            </tr>
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
                            
                            <div>
                                <x-ui.button variant="ghost" href="{{ route('projects.demands.create', ['project' => $project, 'start_date' => $weeks[0]['start']->format('Y-m-d'), 'end_date' => $weeks[0]['end']->format('Y-m-d')]) }}" class="w-100 btn-sm" action="edit">
                                    Edytuj zapotrzebowanie
                                </x-ui.button>
                            </div>
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
                                                                    <a href="{{ route('vehicle-assignments.show', $assignment) }}" 
                                                                       class="text-decoration-none d-flex align-items-center gap-1">
                                                                        <i class="bi {{ $isDriver ? 'bi-car-front-fill text-success' : 'bi-person text-primary' }}"></i>
                                                                        <span class="{{ $isDriver ? 'text-success fw-semibold' : 'text-primary' }}">
                                                                            {{ $assignment->employee->full_name }}
                                                                        </span>
                                                                    </a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <p class="small text-muted mb-0">Brak przypisanych osób</p>
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
                                                
                                                // Jeśli są pracownicy przypisani do auta, sprawdź ich projekty
                                                $otherProjects = collect();
                                                if ($vehicleEmployeeIds->isNotEmpty()) {
                                                    $weekStart = $weeks[0]['start'] ?? now()->startOfWeek();
                                                    $weekEnd = $weeks[0]['end'] ?? now()->endOfWeek();
                                                    
                                                    // Pobierz project assignments dla tych pracowników w tym okresie
                                                    $projectAssignments = \App\Models\ProjectAssignment::whereIn('employee_id', $vehicleEmployeeIds)
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
                                                        ->get();
                                                    
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
                                                            <a href="{{ route('projects.show', $otherProject) }}" class="text-decoration-none d-inline-block me-2 mb-1">
                                                                <x-ui.badge variant="info" class="small">
                                                                    {{ $otherProject->name }}
                                                                </x-ui.badge>
                                                            </a>
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
                                                                    <a href="{{ route('accommodation-assignments.show', $assignment) }}" 
                                                                       class="text-decoration-none d-flex align-items-center gap-1">
                                                                        <i class="bi bi-house text-success"></i>
                                                                        <span class="text-primary">
                                                                            {{ $assignment->employee->full_name }}
                                                                        </span>
                                                                    </a>
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
                                                
                                                // Jeśli są pracownicy przypisani do domu, sprawdź ich projekty
                                                $otherProjects = collect();
                                                if ($accommodationEmployeeIds->isNotEmpty()) {
                                                    $weekStart = $weeks[0]['start'] ?? now()->startOfWeek();
                                                    $weekEnd = $weeks[0]['end'] ?? now()->endOfWeek();
                                                    
                                                    // Pobierz project assignments dla tych pracowników w tym okresie
                                                    $projectAssignments = \App\Models\ProjectAssignment::whereIn('employee_id', $accommodationEmployeeIds)
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
                                                        ->get();
                                                    
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
                                                            <a href="{{ route('projects.show', $otherProject) }}" class="text-decoration-none d-inline-block me-2 mb-1">
                                                                <x-ui.badge variant="info" class="small">
                                                                    {{ $otherProject->name }}
                                                                </x-ui.badge>
                                                            </a>
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

                <!-- Tabelka z ludźmi -->
                @if(isset($weekData['assigned_employees']) && $weekData['assigned_employees']->isNotEmpty())
                    <div class="mt-4">
                        <x-ui.table-header title="Przypisani pracownicy" titleClass="text-dark">
                            <x-ui.button variant="primary" href="{{ route('projects.assignments.create', ['project' => $project, 'date_from' => $weeks[0]['start']->format('Y-m-d'), 'date_to' => $weeks[0]['end']->format('Y-m-d')]) }}" action="create" class="btn-sm">
                                Przypisz osoby
                            </x-ui.button>
                        </x-ui.table-header>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Zdjęcie</th>
                                        <th>Imię i nazwisko</th>
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
                                                <x-ui.avatar 
                                                    :image-url="$employeeData['employee']->image_path ? $employeeData['employee']->image_url : null"
                                                    :alt="$employeeData['employee']->full_name"
                                                    :initials="substr($employeeData['employee']->first_name, 0, 1) . substr($employeeData['employee']->last_name, 0, 1)"
                                                    size="40px"
                                                    shape="circle"
                                                />
                                            </td>
                                            <td>
                                                <a href="{{ route('employees.show', $employeeData['employee']) }}" class="fw-semibold text-decoration-none">
                                                    {{ $employeeData['employee']->full_name }}
                                                </a>
                                            </td>
                                            <td>
                                                @if(isset($employeeData['role_stable']) && !$employeeData['role_stable'])
                                                    <x-ui.badge variant="warning" title="Rola zmienia się w trakcie tygodnia">
                                                        <i class="bi bi-arrow-left-right"></i> Zmienna
                                                    </x-ui.badge>
                                                @elseif(isset($employeeData['assignment']))
                                                    <a href="{{ route('assignments.show', $employeeData['assignment']) }}" class="text-decoration-none">
                                                        <x-ui.badge variant="accent">{{ $employeeData['role']->name ?? '-' }}</x-ui.badge>
                                                    </a>
                                                @else
                                                    <x-ui.badge variant="info">{{ $employeeData['role']->name ?? '-' }}</x-ui.badge>
                                                @endif
                                            </td>
                                            <td class="text-center {{ !$isFullWeek ? 'bg-danger bg-opacity-25' : '' }}">
                                                <span class="fw-semibold small">{{ $dateRange }}</span>
                                            </td>
                                            <td>
                                                @if($employeeData['has_vehicle_in_week'] ?? false)
                                                    @if(isset($employeeData['vehicle']) && $employeeData['vehicle'])
                                                        <a href="{{ route('vehicle-assignments.show', $employeeData['vehicle_assignment']) }}" class="text-decoration-none">
                                                            <x-ui.badge variant="success" title="{{ $employeeData['vehicle']->brand }} {{ $employeeData['vehicle']->model }}">
                                                                <i class="bi bi-car-front"></i> {{ $employeeData['vehicle']->registration_number }}
                                                            </x-ui.badge>
                                                        </a>
                                                    @else
                                                        <x-ui.badge variant="success">
                                                            <i class="bi bi-car-front"></i> Tak
                                                        </x-ui.badge>
                                                    @endif
                                                @else
                                                    <a href="{{ route('employees.vehicles.create', ['employee' => $employeeData['employee'], 'date_from' => $weeks[0]['start']->format('Y-m-d'), 'date_to' => $weeks[0]['end']->format('Y-m-d')]) }}" class="text-decoration-none">
                                                        <x-ui.badge variant="danger">
                                                            <i class="bi bi-x-circle"></i> Brak
                                                        </x-ui.badge>
                                                    </a>
                                                @endif
                                            </td>
                                            <td>
                                                @if(isset($employeeData['accommodation']) && $employeeData['accommodation'])
                                                    <a href="{{ route('accommodations.show', $employeeData['accommodation']) }}" class="text-decoration-none">
                                                        <x-ui.badge variant="info">
                                                            <i class="bi bi-house"></i> {{ $employeeData['accommodation']->name }}
                                                        </x-ui.badge>
                                                    </a>
                                                @else
                                                    <a href="{{ route('employees.accommodations.create', ['employee' => $employeeData['employee'], 'date_from' => $weeks[0]['start']->format('Y-m-d'), 'date_to' => $weeks[0]['end']->format('Y-m-d')]) }}" class="text-decoration-none">
                                                        <x-ui.badge variant="danger">
                                                            <i class="bi bi-x-circle"></i> Brak
                                                        </x-ui.badge>
                                                    </a>
                                                @endif
                                            </td>
                                            <td>
                                                @if(isset($employeeData['rotation']) && $employeeData['rotation'])
                                                    <x-ui.badge variant="warning">
                                                        <i class="bi bi-arrow-repeat"></i> 
                                                        @if(isset($employeeData['rotation']['start_date']))
                                                            {{ \Carbon\Carbon::parse($employeeData['rotation']['start_date'])->format('d.m.Y') }} - {{ \Carbon\Carbon::parse($employeeData['rotation']['end_date'])->format('d.m.Y') }}
                                                        @elseif(isset($employeeData['rotation']->start_date))
                                                            {{ $employeeData['rotation']->start_date->format('d.m.Y') }} - {{ $employeeData['rotation']->end_date->format('d.m.Y') }}
                                                        @else
                                                            {{ \Carbon\Carbon::parse($employeeData['rotation']['end_date'])->format('d.m.Y') }}
                                                        @endif
                                                    </x-ui.badge>
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
                            <x-ui.button variant="primary" href="{{ route('projects.assignments.create', ['project' => $project, 'date_from' => $weeks[0]['start']->format('Y-m-d'), 'date_to' => $weeks[0]['end']->format('Y-m-d')]) }}" action="create" class="btn-sm">
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
                        <x-ui.button variant="primary" href="{{ route('projects.show.tasks', $projectData['project']) }}" action="create" class="btn-sm">
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
            <div class="row g-3">
                <!-- TODO: Implementacja wyświetlania kończących się dokumentów -->
                <div class="col-md-4">
                    <x-ui.card>
                        <p class="fw-medium mb-0">Funkcjonalność w przygotowaniu</p>
                    </x-ui.card>
                </div>
            </div>
        </x-ui.card>
    </div>
</x-app-layout>
