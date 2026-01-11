<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Planer Tygodnia 2</h2>
            <div class="d-flex gap-2 align-items-center">
                <a href="{{ $navigation['prevUrl'] }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-chevron-left"></i> Poprzedni tydzień
                </a>
                <span class="fw-semibold">{{ $navigation['current']['label'] }}</span>
                <a href="{{ $navigation['nextUrl'] }}" class="btn btn-outline-secondary btn-sm">
                    Następny tydzień <i class="bi bi-chevron-right"></i>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            @forelse($projectsWithCalendar as $projectData)
                @php
                    $project = $projectData['project'];
                    $calendar = $projectData['calendar'] ?? null;
                    $weekData = $projectData['weeks_data'][0] ?? null;
                @endphp
                
                @php
                    $employees = is_array($calendar['employees'] ?? []) ? collect($calendar['employees'] ?? []) : ($calendar['employees'] ?? collect());
                @endphp
                @if($calendar)
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <div class="row">
                                <!-- Lewa kolumna: Projekt -->
                                <div class="col-md-3 border-end">
                                    <h4 class="fw-bold text-dark mb-3">
                                        <a href="{{ route('projects.show', $project) }}" class="text-decoration-none text-dark">
                                            {{ $project->name }}
                                        </a>
                                    </h4>
                                    
                                    @if($project->location)
                                        <p class="text-muted small mb-3">
                                            <i class="bi bi-geo-alt"></i> {{ $project->location->name }}
                                        </p>
                                    @endif
                                    
                                    @if($weekData)
                                        @php
                                            $weekStart = $weekData['week']['start'] ?? null;
                                            $weekEnd = $weekData['week']['end'] ?? null;
                                            $demandCreateUrl = route('projects.demands.create', $project);
                                            if ($weekStart && $weekEnd) {
                                                $demandCreateUrl .= '?date_from=' . $weekStart->format('Y-m-d') . '&date_to=' . $weekEnd->format('Y-m-d');
                                            }
                                            $hasDemands = isset($weekData['demands']) && $weekData['demands']->isNotEmpty();
                                            $totalNeeded = $weekData['requirements_summary']['total_needed'] ?? 0;
                                        @endphp
                                        <div class="mb-3">
                                            @if(!$hasDemands || $totalNeeded == 0)
                                                <div class="mb-2">
                                                    <a href="{{ $demandCreateUrl }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-plus-circle"></i> Dodaj zapotrzebowanie
                                                    </a>
                                                </div>
                                            @else
                                                <h6 class="fw-semibold small mb-2">
                                                    <a href="{{ $demandCreateUrl }}" class="text-decoration-none text-dark">
                                                        Zapotrzebowanie
                                                    </a>
                                                </h6>
                                                <div class="small">
                                                    <div>Potrzebnych: <strong>{{ $totalNeeded }}</strong></div>
                                                    <div>Przypisanych: <strong class="text-primary">{{ $weekData['requirements_summary']['total_assigned'] ?? 0 }}</strong></div>
                                                    @if(($weekData['requirements_summary']['total_missing'] ?? 0) > 0)
                                                        <div class="text-danger">Brakuje: <strong>{{ $weekData['requirements_summary']['total_missing'] }}</strong></div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                        
                                        @if(isset($weekData['vehicles']) && (is_array($weekData['vehicles']) ? count($weekData['vehicles']) > 0 : $weekData['vehicles']->isNotEmpty()))
                                            <div class="mb-3">
                                                <h6 class="fw-semibold small mb-2">Auta</h6>
                                                <div class="small">
                                                    @foreach($weekData['vehicles']->take(3) as $vehicleData)
                                                        @php
                                                            $vehicle = $vehicleData['vehicle'] ?? null;
                                                        @endphp
                                                        <div class="mb-2 d-flex align-items-center gap-2">
                                                            @if($vehicle && $vehicle->image_url)
                                                                <a href="{{ route('vehicles.show', $vehicle) }}" class="text-decoration-none d-block">
                                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 72px; height: 72px; overflow: hidden;">
                                                                        <img src="{{ $vehicle->image_url }}" 
                                                                             alt="{{ $vehicleData['vehicle_name'] }}" 
                                                                             class="rounded" 
                                                                             style="width: 100%; height: 100%; object-fit: contain;">
                                                                    </div>
                                                                </a>
                                                            @else
                                                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 72px; height: 72px;">
                                                                    <i class="bi bi-car-front text-info fs-4"></i>
                                                                </div>
                                                            @endif
                                                            <div class="flex-grow-1">
                                                                <div>{{ $vehicleData['vehicle_name'] }}</div>
                                                                <div class="text-muted small">({{ $vehicleData['usage'] }})</div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                    @if($weekData['vehicles']->count() > 3)
                                                        <div class="text-muted">+{{ $weekData['vehicles']->count() - 3 }} więcej</div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                        
                                        @if(isset($weekData['accommodations']) && (is_array($weekData['accommodations']) ? count($weekData['accommodations']) > 0 : $weekData['accommodations']->isNotEmpty()))
                                            <div class="mb-3">
                                                <h6 class="fw-semibold small mb-2">Domy</h6>
                                                <div class="small">
                                                    @foreach($weekData['accommodations']->take(3) as $accommodationData)
                                                        @php
                                                            $accommodation = $accommodationData['accommodation'] ?? null;
                                                        @endphp
                                                        <div class="mb-2 d-flex align-items-center gap-2">
                                                            @if($accommodation && $accommodation->image_url)
                                                                <a href="{{ route('accommodations.show', $accommodation) }}" class="text-decoration-none d-block">
                                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 72px; height: 72px; overflow: hidden;">
                                                                        <img src="{{ $accommodation->image_url }}" 
                                                                             alt="{{ $accommodation->name }}" 
                                                                             class="rounded" 
                                                                             style="width: 100%; height: 100%; object-fit: contain;">
                                                                    </div>
                                                                </a>
                                                            @else
                                                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 72px; height: 72px;">
                                                                    <i class="bi bi-house text-success fs-4"></i>
                                                                </div>
                                                            @endif
                                                            <div class="flex-grow-1">
                                                                <div>{{ $accommodation->name }}</div>
                                                                <div class="text-muted small">({{ $accommodationData['usage'] }})</div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                    @if($weekData['accommodations']->count() > 3)
                                                        <div class="text-muted">+{{ $weekData['accommodations']->count() - 3 }} więcej</div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                                
                                <!-- Prawa kolumna: Tabela kalendarza -->
                                <div class="col-md-9">
                                    @php
                                        $dailyDemands = $calendar['daily_demands'] ?? [];
                                        // Get all unique roles from daily demands with first demand for each
                                        $allRoles = collect();
                                        $roleDemands = [];
                                        foreach ($dailyDemands as $dayData) {
                                            foreach ($dayData as $roleId => $roleData) {
                                                if (!$allRoles->contains('id', $roleId)) {
                                                    $allRoles->push($roleData['role']);
                                                    if (isset($roleData['first_demand'])) {
                                                        $roleDemands[$roleId] = $roleData['first_demand'];
                                                    }
                                                }
                                            }
                                        }
                                    @endphp
                                    
                                    <!-- Tabela zapotrzebowania i pracowników -->
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-start" style="width: 200px;">Pracownik</th>
                                                    @foreach($calendar['days'] as $day)
                                                        <th class="text-center" style="min-width: 120px;">
                                                            <div class="fw-bold">{{ $day['day_name_short'] }}</div>
                                                            <div class="small text-muted">{{ $day['day_number'] }}</div>
                                                        </th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @if($allRoles->isNotEmpty())
                                                    <!-- Wiersze zapotrzebowania -->
                                                    @foreach($allRoles as $role)
                                                        @php
                                                            $firstDemand = $roleDemands[$role->id] ?? null;
                                                        @endphp
                                                        <tr>
                                                            <td class="bg-light align-middle fw-semibold">
                                                                @if($firstDemand)
                                                                    <a href="{{ route('demands.edit', $firstDemand) }}" class="text-decoration-none text-dark">
                                                                        {{ $role->name }}
                                                                    </a>
                                                                @else
                                                                    {{ $role->name }}
                                                                @endif
                                                            </td>
                                                            @foreach($calendar['days'] as $day)
                                                                @php
                                                                    $dayKey = $day['date']->format('Y-m-d');
                                                                    $dayDemand = $dailyDemands[$dayKey][$role->id] ?? null;
                                                                    $required = $dayDemand['required'] ?? 0;
                                                                    $assigned = $dayDemand['assigned'] ?? 0;
                                                                @endphp
                                                                <td class="text-center align-middle bg-light">
                                                                    @if($required > 0 || $assigned > 0)
                                                                        <div class="small">
                                                                            <span class="text-primary fw-semibold">{{ $assigned }}</span>
                                                                            <span class="text-muted">/</span>
                                                                            <span class="text-dark">{{ $required }}</span>
                                                                        </div>
                                                                    @else
                                                                        <span class="text-muted">-</span>
                                                                    @endif
                                                                </td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                    <tr class="table-secondary">
                                                        <td class="fw-bold">Razem</td>
                                                        @foreach($calendar['days'] as $day)
                                                            @php
                                                                $dayKey = $day['date']->format('Y-m-d');
                                                                $dayTotalRequired = 0;
                                                                $dayTotalAssigned = 0;
                                                                foreach ($allRoles as $role) {
                                                                    $dayDemand = $dailyDemands[$dayKey][$role->id] ?? null;
                                                                    if ($dayDemand) {
                                                                        $dayTotalRequired += $dayDemand['required'] ?? 0;
                                                                        $dayTotalAssigned += $dayDemand['assigned'] ?? 0;
                                                                    }
                                                                }
                                                            @endphp
                                                            <td class="text-center align-middle">
                                                                <div class="small fw-semibold">
                                                                    <span class="text-primary">{{ $dayTotalAssigned }}</span>
                                                                    <span class="text-muted">/</span>
                                                                    <span class="text-dark">{{ $dayTotalRequired }}</span>
                                                                </div>
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                @endif
                                                @if($employees->isEmpty())
                                                    <tr>
                                                        <td colspan="{{ count($calendar['days']) + 1 }}" class="text-center py-4 text-muted">
                                                            <i class="bi bi-info-circle"></i> Brak przypisań w tym tygodniu
                                                        </td>
                                                    </tr>
                                                @else
                                                    @foreach($employees as $employeeData)
                                                    <tr>
                                                        <td class="bg-light align-middle">
                                                            <div class="d-flex align-items-center gap-2">
                                                                @if($employeeData['employee']->image_path)
                                                                    <img src="{{ $employeeData['employee']->image_url }}" 
                                                                         alt="{{ $employeeData['employee']->full_name }}" 
                                                                         class="rounded-circle" 
                                                                         style="width: 32px; height: 32px; object-fit: cover;">
                                                                @else
                                                                    <div class="bg-warning bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" 
                                                                         style="width: 32px; height: 32px;">
                                                                        <span class="text-warning fw-semibold small">
                                                                            {{ substr($employeeData['employee']->first_name, 0, 1) }}{{ substr($employeeData['employee']->last_name, 0, 1) }}
                                                                        </span>
                                                                    </div>
                                                                @endif
                                                                <div>
                                                                    <div class="fw-semibold">
                                                                        <a href="{{ route('employees.show', $employeeData['employee']) }}" 
                                                                           class="text-decoration-none text-dark">
                                                                            {{ $employeeData['employee']->full_name }}
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        @foreach($employeeData['daily_data'] as $dayData)
                                                            @php
                                                                $hasAnyAssignment = ($dayData['project'] ?? null) || ($dayData['accommodation'] ?? null) || ($dayData['vehicle'] ?? null);
                                                                $hasProject = ($dayData['project'] ?? null) !== null;
                                                                $hasAccommodation = ($dayData['accommodation'] ?? null) !== null;
                                                                $hasVehicle = ($dayData['vehicle'] ?? null) !== null;
                                                            @endphp
                                                            <td class="text-center align-middle p-2" 
                                                                style="min-height: 80px; {{ !$hasAnyAssignment ? 'background-color: #f8f9fa;' : 'background-color: #e7f3ff;' }}">
                                                                @if($hasAnyAssignment)
                                                                    <div class="d-flex flex-column gap-1 small">
                                                                        <!-- Projekt - zawsze pokazuj jeśli jest jakiekolwiek przypisanie -->
                                                                        @if($hasProject)
                                                                            @php
                                                                                $roleName = $dayData['project_assignment']?->role?->name ?? 'Brak roli';
                                                                                $projectTitle = $dayData['project']->name . ($roleName !== 'Brak roli' ? ' (' . $roleName . ')' : '');
                                                                                $projectAssignment = $dayData['project_assignment'];
                                                                            @endphp
                                                                            <div class="text-primary fw-semibold" title="{{ $projectTitle }}">
                                                                                <i class="bi bi-briefcase-fill"></i>
                                                                                @if($projectAssignment)
                                                                                    <a href="{{ route('assignments.edit', $projectAssignment) }}" class="text-decoration-none text-primary">
                                                                                        <span class="d-none d-lg-inline">{{ $roleName !== 'Brak roli' ? Str::limit($roleName, 12) : Str::limit($dayData['project']->name, 12) }}</span>
                                                                                    </a>
                                                                                @else
                                                                                    <span class="d-none d-lg-inline">{{ $roleName !== 'Brak roli' ? Str::limit($roleName, 12) : Str::limit($dayData['project']->name, 12) }}</span>
                                                                                @endif
                                                                            </div>
                                                                        @else
                                                                            <div class="text-danger fw-bold" title="Brak projektu">
                                                                                <i class="bi bi-briefcase"></i>
                                                                                <span class="d-none d-lg-inline">⚠️ Brak</span>
                                                                            </div>
                                                                        @endif
                                                                        
                                                                        <!-- Mieszkanie - zawsze pokazuj jeśli jest jakiekolwiek przypisanie -->
                                                                        @if($hasAccommodation)
                                                                            @php
                                                                                $occupancy = $dayData['accommodation_occupancy'] ?? 0;
                                                                                $capacity = $dayData['accommodation_capacity'] ?? null;
                                                                                $accommodationTitle = '';
                                                                                if ($occupancy > 0 && $capacity !== null) {
                                                                                    $accommodationTitle = $occupancy . '/' . $capacity . ' osób';
                                                                                } elseif ($occupancy > 0) {
                                                                                    $accommodationTitle = $occupancy . ' ' . ($occupancy == 1 ? 'osoba' : ($occupancy < 5 ? 'osoby' : 'osób'));
                                                                                }
                                                                                $accommodationAssignment = $dayData['accommodation_assignment'] ?? null;
                                                                            @endphp
                                                                            <div class="text-success" title="{{ $accommodationTitle }}">
                                                                                <i class="bi bi-house-fill"></i>
                                                                                @if($accommodationAssignment)
                                                                                    <a href="{{ route('accommodation-assignments.edit', $accommodationAssignment) }}" class="text-decoration-none text-success">
                                                                                        <span class="d-none d-lg-inline">{{ Str::limit($dayData['accommodation']->name, 12) }}</span>
                                                                                    </a>
                                                                                @else
                                                                                    <span class="d-none d-lg-inline">{{ Str::limit($dayData['accommodation']->name, 12) }}</span>
                                                                                @endif
                                                                            </div>
                                                                        @else
                                                                            <div class="text-danger fw-bold" title="Brak domu">
                                                                                <i class="bi bi-house-exclamation"></i>
                                                                                <span class="d-none d-lg-inline">⚠️ Brak</span>
                                                                            </div>
                                                                        @endif
                                                                        
                                                                        <!-- Auto - zawsze pokazuj jeśli jest jakiekolwiek przypisanie -->
                                                                        @if($hasVehicle)
                                                                            @php
                                                                                $position = $dayData['vehicle_assignment']?->position ?? null;
                                                                                $positionText = '';
                                                                                if ($position instanceof \App\Enums\VehiclePosition) {
                                                                                    $positionText = $position->label();
                                                                                }
                                                                                $occupancy = $dayData['vehicle_occupancy'] ?? 0;
                                                                                $capacity = $dayData['vehicle_capacity'] ?? null;
                                                                                $vehicleTitle = '';
                                                                                if ($positionText) {
                                                                                    $vehicleTitle = $positionText;
                                                                                    if ($occupancy > 0 && $capacity !== null) {
                                                                                        $vehicleTitle .= ', ' . $occupancy . '/' . $capacity . ' osób';
                                                                                    } elseif ($occupancy > 0) {
                                                                                        $vehicleTitle .= ', ' . $occupancy . ' ' . ($occupancy == 1 ? 'osoba' : ($occupancy < 5 ? 'osoby' : 'osób'));
                                                                                    }
                                                                                } elseif ($occupancy > 0 && $capacity !== null) {
                                                                                    $vehicleTitle = $occupancy . '/' . $capacity . ' osób';
                                                                                } elseif ($occupancy > 0) {
                                                                                    $vehicleTitle = $occupancy . ' ' . ($occupancy == 1 ? 'osoba' : ($occupancy < 5 ? 'osoby' : 'osób'));
                                                                                }
                                                                                $vehicleAssignment = $dayData['vehicle_assignment'] ?? null;
                                                                            @endphp
                                                                            <div class="text-info" title="{{ $vehicleTitle }}">
                                                                                <i class="bi bi-car-front-fill"></i>
                                                                                @if($vehicleAssignment)
                                                                                    <a href="{{ route('vehicle-assignments.edit', $vehicleAssignment) }}" class="text-decoration-none text-info">
                                                                                        <span class="d-none d-lg-inline">{{ Str::limit($dayData['vehicle']->registration_number, 10) }}</span>
                                                                                    </a>
                                                                                @else
                                                                                    <span class="d-none d-lg-inline">{{ Str::limit($dayData['vehicle']->registration_number, 10) }}</span>
                                                                                @endif
                                                                            </div>
                                                                        @else
                                                                            <div class="text-danger fw-bold" title="Brak auta">
                                                                                <i class="bi bi-car-front-slash"></i>
                                                                                <span class="d-none d-lg-inline">⚠️ Brak</span>
                                                                            </div>
                                                                        @endif
                                                                        
                                                                        @if($dayData['return_trip'])
                                                                            @php
                                                                                $returnTrip = $dayData['return_trip'];
                                                                                $returnVehicle = $returnTrip->vehicle;
                                                                                $returnParticipants = $returnTrip->participants;
                                                                                
                                                                                // Build detailed tooltip
                                                                                $tooltipParts = ['Zjazd'];
                                                                                
                                                                                if ($returnVehicle) {
                                                                                    $vehicleName = trim($returnVehicle->brand . ' ' . $returnVehicle->model . ' ' . $returnVehicle->registration_number);
                                                                                    $tooltipParts[] = 'Auto: ' . $vehicleName;
                                                                                } else {
                                                                                    $tooltipParts[] = 'Auto: Brak auta';
                                                                                }
                                                                                
                                                                                if ($returnParticipants && $returnParticipants->isNotEmpty()) {
                                                                                    $participantNames = $returnParticipants->map(function($participant) {
                                                                                        return $participant->employee ? $participant->employee->full_name : null;
                                                                                    })->filter()->values()->join(', ');
                                                                                    if ($participantNames) {
                                                                                        $tooltipParts[] = 'Z kim: ' . $participantNames;
                                                                                    }
                                                                                }
                                                                                
                                                                                $returnTitle = implode(' | ', $tooltipParts);
                                                                            @endphp
                                                                            <div class="text-warning fw-semibold" title="{{ $returnTitle }}">
                                                                                <i class="bi bi-arrow-return-left"></i>
                                                                                <span class="d-none d-lg-inline">Zjazd</span>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                    @endforeach
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @empty
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        <p class="text-muted">Brak projektów z przypisaniami w tym tygodniu.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
