@props(['weekData', 'project'])

@if($weekData['has_data'])
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <!-- Zapotrzebowanie -->
            <div class="mb-3">
                <div class="bg-info bg-opacity-10 rounded p-2 border border-info">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                        <h4 class="small fw-bold text-dark mb-0">Zapotrzebowanie</h4>
                        <a href="{{ route('projects.demands.create', ['project' => $project, 'date_from' => $weekData['week']['start']->format('Y-m-d'), 'date_to' => $weekData['week']['end']->format('Y-m-d')]) }}" 
                           class="btn btn-sm btn-info">
                            <i class="bi bi-pencil"></i>
                            Edytuj
                        </a>
                    </div>
                    
                    <!-- Tabelka zapotrzebowania -->
                    @if(!empty($weekData['requirements_summary']['role_details']))
                        <div class="mb-2 table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start small fw-bold text-dark">Rola</th>
                                        <th class="text-center small fw-bold text-dark">Potrzebnych</th>
                                        <th class="text-center small fw-bold text-dark">Przypisanych</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($weekData['requirements_summary']['role_details'] as $roleDetail)
                                        @php
                                            $needed = $roleDetail['needed'];
                                            $assigned = $roleDetail['assigned'];
                                            $isComplete = $assigned >= $needed;
                                            $isPartial = $assigned > 0 && $assigned < $needed;
                                        @endphp
                                        <tr>
                                            <td class="small text-dark">{{ Str::lower($roleDetail['role']->name) }}</td>
                                            <td class="text-center small fw-semibold {{ $isComplete ? 'text-success' : ($isPartial ? 'text-warning' : 'text-danger') }}">
                                                {{ $needed }}
                                            </td>
                                            <td class="text-center small fw-semibold text-primary">
                                                {{ $assigned }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="table-secondary fw-bold">
                                        <td class="small text-dark">łącznie</td>
                                        <td class="text-center small text-dark">{{ $weekData['requirements_summary']['total_needed'] }}</td>
                                        <td class="text-center small text-dark">{{ $weekData['requirements_summary']['total_assigned'] }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="mb-2 table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start small fw-bold text-dark">Rola</th>
                                        <th class="text-center small fw-bold text-dark">Potrzebnych</th>
                                        <th class="text-center small fw-bold text-dark">Przypisanych</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="table-secondary fw-bold">
                                        <td class="small text-dark">łącznie</td>
                                        <td class="text-center small text-dark">{{ $weekData['requirements_summary']['total_needed'] }}</td>
                                        <td class="text-center small text-dark">{{ $weekData['requirements_summary']['total_assigned'] }}</td>
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
                        @if($weekData['assigned_employees']->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-2">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-start small fw-bold text-dark">Zdjęcie</th>
                                            <th class="text-start small fw-bold text-dark">Imię i nazwisko</th>
                                            <th class="text-start small fw-bold text-dark">Rola w projekcie</th>
                                            <th class="text-center small fw-bold text-dark">Pokrycie</th>
                                            <th class="text-start small fw-bold text-dark">Do rotacji</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($weekData['assigned_employees'] as $employeeData)
                                            <tr>
                                                <td>
                                                    @if($employeeData['employee']->image_path)
                                                        <img src="{{ $employeeData['employee']->image_url }}" alt="{{ $employeeData['employee']->full_name }}" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                                                    @else
                                                        <div class="bg-warning bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                            <span class="text-warning fw-semibold small">{{ substr($employeeData['employee']->first_name, 0, 1) }}{{ substr($employeeData['employee']->last_name, 0, 1) }}</span>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('employees.show', $employeeData['employee']) }}" class="fw-semibold text-dark text-decoration-none">{{ $employeeData['employee']->full_name }}</a>
                                                </td>
                                                <td>
                                                    @if(isset($employeeData['assignment']))
                                                        <a href="{{ route('assignments.show', $employeeData['assignment']) }}" class="text-decoration-none">
                                                            <span class="badge bg-primary">{{ $employeeData['role']->name ?? '-' }}</span>
                                                        </a>
                                                    @else
                                                        <span class="badge bg-secondary">{{ $employeeData['role']->name ?? '-' }}</span>
                                                    @endif
                                                </td>
                                                @php
                                                    $dateRange = $employeeData['date_range'] ?? 'cały tydzień';
                                                    $isFullWeek = ($dateRange === 'cały tydzień' || $dateRange === 'pon-nie');
                                                    $shouldHighlight = !$isFullWeek;
                                                @endphp
                                                <td class="text-center {{ $shouldHighlight ? 'bg-danger bg-opacity-25' : '' }}">
                                                    <span class="text-dark fw-semibold">
                                                        {{ $dateRange }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($employeeData['rotation'] ?? null)
                                                        @if($employeeData['rotation']['days_left'] >= 0)
                                                            <span class="text-primary fw-semibold">{{ $employeeData['rotation']['days_left'] }}</span>
                                                        @else
                                                            <span class="text-danger fw-semibold">{{ abs($employeeData['rotation']['days_left']) }}</span>
                                                        @endif
                                                    @else
                                                        <span class="text-muted fst-italic">Brak rotacji</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-2">
                                @if(isset($weekData['week']) && isset($weekData['week']['start']) && isset($weekData['week']['end']))
                                    <a href="{{ route('projects.assignments.create', ['project' => $project->id, 'date_from' => $weekData['week']['start']->format('Y-m-d'), 'date_to' => $weekData['week']['end']->format('Y-m-d')]) }}" class="btn btn-sm btn-primary w-100">
                                        <i class="bi bi-plus"></i>
                                        {{ $weekData['assigned_employees']->count() > 0 ? 'Dodaj' : 'Przypisz' }}
                                    </a>
                                @else
                                    <a href="{{ route('projects.assignments.create', $project) }}" class="btn btn-sm btn-primary w-100">
                                        <i class="bi bi-plus"></i>
                                        {{ $weekData['assigned_employees']->count() > 0 ? 'Dodaj' : 'Przypisz' }}
                                    </a>
                                @endif
                            </div>
                        @else
                            <div class="text-center py-4 text-muted small">
                                Brak osób
                            </div>
                            <div class="mt-2">
                                @if(isset($weekData['week']) && isset($weekData['week']['start']) && isset($weekData['week']['end']))
                                    <a href="{{ route('projects.assignments.create', ['project' => $project->id, 'date_from' => $weekData['week']['start']->format('Y-m-d'), 'date_to' => $weekData['week']['end']->format('Y-m-d')]) }}" class="btn btn-sm btn-primary w-100">
                                        <i class="bi bi-plus"></i>
                                        Przypisz osoby
                                    </a>
                                @else
                                    <a href="{{ route('projects.assignments.create', $project) }}" class="btn btn-sm btn-primary w-100">
                                        <i class="bi bi-plus"></i>
                                        Przypisz osoby
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Auta i Domy - w stylu Album -->
            <div class="row g-3 mb-3">
                <!-- Auta -->
                <div class="col-12">
                    <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-car-front text-info"></i>
                        Auta
                    </h5>
                    @if($weekData['vehicles']->isNotEmpty())
                        <div class="row g-3 mb-3">
                            @foreach($weekData['vehicles']->take(6) as $vehicleData)
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="card shadow-sm h-100">
                                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 120px; overflow: hidden;">
                                            @if($vehicleData['vehicle']->image_path)
                                                <img src="{{ $vehicleData['vehicle']->image_url }}" 
                                                     alt="{{ $vehicleData['vehicle_name'] }}" 
                                                     class="w-100 h-100 object-fit-cover"
                                                     onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2306b6d4\'%3E%3Cpath d=\'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2\'/%3E%3C/svg%3E';">
                                            @else
                                                <div class="bg-info bg-opacity-10 w-100 h-100 d-flex align-items-center justify-content-center">
                                                    <i class="bi bi-car-front text-info" style="font-size: 3rem;"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="card-body d-flex flex-column">
                                            <h6 class="card-title">
                                                <a href="{{ route('vehicles.show', $vehicleData['vehicle']) }}" class="text-decoration-none text-dark">
                                                    {{ $vehicleData['vehicle_name'] }}
                                                </a>
                                            </h6>
                                            <p class="card-text small text-muted mb-2">
                                                {{ $vehicleData['usage'] }}
                                                @if($vehicleData['driver'])
                                                    <br><span class="text-success"><i class="bi bi-person-check"></i> <a href="{{ route('employees.show', $vehicleData['driver']) }}" class="text-success text-decoration-none">{{ $vehicleData['driver']->full_name }}</a></span>
                                                @else
                                                    <br><span class="text-danger"><i class="bi bi-exclamation-circle"></i> Brak kierowcy</span>
                                                @endif
                                            </p>
                                            @if(isset($vehicleData['assignments']) && $vehicleData['assignments']->count() > 0)
                                                <div class="mt-auto" wire:ignore.self>
                                                    <button class="btn btn-sm btn-outline-primary w-100" type="button" data-bs-toggle="collapse" data-bs-target="#vehicle-{{ $vehicleData['vehicle']->id }}-assignments" aria-expanded="false">
                                                        <i class="bi bi-people"></i> Osoby ({{ $vehicleData['assignments']->count() }})
                                                    </button>
                                                    <div class="collapse mt-2" id="vehicle-{{ $vehicleData['vehicle']->id }}-assignments">
                                                        <ul class="list-unstyled mb-0 small">
                                                            @foreach($vehicleData['assignments'] as $assignment)
                                                                @php
                                                                    $position = $assignment->position ?? \App\Enums\VehiclePosition::PASSENGER;
                                                                    $positionValue = $position instanceof \App\Enums\VehiclePosition ? $position->value : $position;
                                                                    $isDriver = $positionValue === 'driver';
                                                                @endphp
                                                                <li class="mb-1">
                                                                    <a href="{{ route('vehicle-assignments.show', $assignment) }}" 
                                                                       class="text-decoration-none {{ $isDriver ? 'text-success fw-semibold' : 'text-primary' }}">
                                                                        <i class="bi {{ $isDriver ? 'bi-car-front-fill' : 'bi-person' }}"></i>
                                                                        {{ $assignment->employee->full_name }}
                                                                        @if($isDriver)
                                                                            <span class="badge bg-success ms-1">Kierowca</span>
                                                                        @endif
                                                                    </a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($weekData['vehicles']->count() > 6)
                            <div class="text-center">
                                <span class="badge bg-secondary">+{{ $weekData['vehicles']->count() - 6 }} więcej</span>
                            </div>
                        @endif
                    @endif
                    
                    <!-- Bez auta -->
                    @php
                        $employeesWithoutVehicle = $weekData['assigned_employees']->filter(function($employeeData) {
                            return empty($employeeData['vehicle']);
                        });
                    @endphp
                    @if($employeesWithoutVehicle->isNotEmpty())
                        <div class="alert alert-danger mb-0">
                            <h6 class="alert-heading small fw-bold mb-2">
                                <i class="bi bi-exclamation-triangle"></i>
                                Bez auta ({{ $employeesWithoutVehicle->count() }})
                            </h6>
                            <div class="row g-2">
                                @foreach($employeesWithoutVehicle as $employeeData)
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center gap-2 p-2 bg-danger bg-opacity-10 rounded border border-danger">
                                            @if($employeeData['employee']->image_path)
                                                <img src="{{ $employeeData['employee']->image_url }}" alt="{{ $employeeData['employee']->full_name }}" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                                            @else
                                                <div class="bg-warning bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                    <span class="text-warning fw-semibold small">{{ substr($employeeData['employee']->first_name, 0, 1) }}{{ substr($employeeData['employee']->last_name, 0, 1) }}</span>
                                                </div>
                                            @endif
                                            <div class="flex-grow-1">
                                                <a href="{{ route('employees.show', $employeeData['employee']) }}" class="fw-semibold text-dark text-decoration-none d-block">{{ $employeeData['employee']->full_name }}</a>
                                                <span class="badge bg-secondary small">{{ $employeeData['role']->name }}</span>
                                            </div>
                                            @php
                                                $employee = $employeeData['employee'];
                                                $url = route('employees.vehicles.create', $employee->id);
                                                if (isset($weekData['week']) && isset($weekData['week']['start']) && isset($weekData['week']['end'])) {
                                                    $url .= '?date_from=' . $weekData['week']['start']->format('Y-m-d') . '&date_to=' . $weekData['week']['end']->format('Y-m-d');
                                                }
                                            @endphp
                                            <a href="{{ $url }}" class="btn btn-sm btn-primary">
                                                <i class="bi bi-plus"></i>
                                                Auto
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @elseif($weekData['vehicles']->isEmpty() && $weekData['assigned_employees']->isEmpty())
                        <div class="text-center py-4 text-muted small">
                            Brak aut
                        </div>
                    @endif
                </div>

                <!-- Domy -->
                @if($weekData['accommodations']->isNotEmpty() || $weekData['assigned_employees']->isNotEmpty())
                    <div class="col-12">
                        <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-house text-success"></i>
                            Domy
                        </h5>
                        @if($weekData['accommodations']->isNotEmpty())
                            <div class="row g-3 mb-3">
                                @foreach($weekData['accommodations'] as $accommodationData)
                                    @php
                                        $accommodation = $accommodationData['accommodation'];
                                        $employeeCount = $accommodationData['employee_count'];
                                        $capacity = $accommodationData['capacity'];
                                        $usagePercentage = $accommodationData['usage_percentage'];
                                        $isOverfilled = $employeeCount > $capacity;
                                        $isFull = $employeeCount == $capacity;
                                    @endphp
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="card shadow-sm h-100">
                                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 120px; overflow: hidden;">
                                                @if($accommodation->image_path)
                                                    <img src="{{ $accommodation->image_url }}" 
                                                         alt="{{ $accommodation->name }}" 
                                                         class="w-100 h-100 object-fit-cover"
                                                         onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2310b981\'%3E%3Cpath d=\'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6\'/%3E%3C/svg%3E';">
                                                @else
                                                    <div class="bg-success bg-opacity-10 w-100 h-100 d-flex align-items-center justify-content-center">
                                                        <i class="bi bi-house text-success" style="font-size: 3rem;"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="card-body d-flex flex-column">
                                                <h6 class="card-title">
                                                    <a href="{{ route('accommodations.show', $accommodation) }}" class="text-decoration-none text-dark">
                                                        {{ $accommodation->name }}
                                                    </a>
                                                </h6>
                                                <p class="card-text small text-muted mb-2">
                                                    {{ $accommodationData['usage'] }}
                                                    <br>
                                                    @if($isOverfilled)
                                                        <span class="badge bg-danger">Przepełnione</span>
                                                    @elseif($isFull)
                                                        <span class="badge bg-warning">Pełne</span>
                                                    @else
                                                        <span class="badge bg-success">Wolne miejsca</span>
                                                    @endif
                                                </p>
                                                @if(isset($accommodationData['assignments']) && $accommodationData['assignments']->count() > 0)
                                                    <div class="mt-auto" wire:ignore.self>
                                                        <button class="btn btn-sm btn-outline-primary w-100" type="button" data-bs-toggle="collapse" data-bs-target="#accommodation-{{ $accommodation->id }}-assignments" aria-expanded="false">
                                                            <i class="bi bi-people"></i> Osoby ({{ $accommodationData['assignments']->count() }})
                                                        </button>
                                                        <div class="collapse mt-2" id="accommodation-{{ $accommodation->id }}-assignments">
                                                            <ul class="list-unstyled mb-0 small">
                                                                @foreach($accommodationData['assignments'] as $assignment)
                                                                    <li class="mb-1">
                                                                        <a href="{{ route('accommodation-assignments.show', $assignment) }}" 
                                                                           class="text-decoration-none text-primary">
                                                                            <i class="bi bi-house"></i>
                                                                            {{ $assignment->employee->full_name }}
                                                                        </a>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        
                        <!-- Bez domu -->
                        @php
                            $employeesWithoutAccommodation = $weekData['assigned_employees']->filter(function($employeeData) {
                                return empty($employeeData['accommodation']);
                            });
                        @endphp
                        @if($employeesWithoutAccommodation->isNotEmpty())
                            <div class="alert alert-danger mb-0">
                                <h6 class="alert-heading small fw-bold mb-2">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    Bez domu ({{ $employeesWithoutAccommodation->count() }})
                                </h6>
                                <div class="row g-2">
                                    @foreach($employeesWithoutAccommodation as $employeeData)
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center gap-2 p-2 bg-danger bg-opacity-10 rounded border border-danger">
                                                @if($employeeData['employee']->image_path)
                                                    <img src="{{ $employeeData['employee']->image_url }}" alt="{{ $employeeData['employee']->full_name }}" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                                                @else
                                                    <div class="bg-warning bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                        <span class="text-warning fw-semibold small">{{ substr($employeeData['employee']->first_name, 0, 1) }}{{ substr($employeeData['employee']->last_name, 0, 1) }}</span>
                                                    </div>
                                                @endif
                                                <div class="flex-grow-1">
                                                    <a href="{{ route('employees.show', $employeeData['employee']) }}" class="fw-semibold text-dark text-decoration-none d-block">{{ $employeeData['employee']->full_name }}</a>
                                                    <span class="badge bg-secondary small">{{ $employeeData['role']->name }}</span>
                                                </div>
                                                @php
                                                    $employee = $employeeData['employee'];
                                                    $url = route('employees.accommodations.create', $employee->id);
                                                    if (isset($weekData['week']) && isset($weekData['week']['start']) && isset($weekData['week']['end'])) {
                                                        $url .= '?date_from=' . $weekData['week']['start']->format('Y-m-d') . '&date_to=' . $weekData['week']['end']->format('Y-m-d');
                                                    }
                                                @endphp
                                                <a href="{{ $url }}" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-plus"></i>
                                                    Dom
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Dropdown z pełną listą osób (opcjonalnie) -->
            @if($weekData['assigned_employees']->count() > 5)
                <x-weekly-overview.assigned-employees 
                    :assignedEmployees="$weekData['assigned_employees']"
                />
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
            @if(isset($weekData['week']) && isset($weekData['week']['start']) && isset($weekData['week']['end']))
                <a href="{{ route('projects.demands.create', ['project' => $project->id, 'date_from' => $weekData['week']['start']->format('Y-m-d'), 'date_to' => $weekData['week']['end']->format('Y-m-d')]) }}" 
                   class="btn btn-success">
                    <i class="bi bi-plus"></i>
                    Dodaj zapotrzebowanie
                </a>
            @else
                <a href="{{ route('projects.demands.create', $project) }}" 
                   class="btn btn-success">
                    <i class="bi bi-plus"></i>
                    Dodaj zapotrzebowanie
                </a>
            @endif
        </div>
    </div>
@endif
