@props(['weekData', 'project'])

@if($weekData['has_data'])
    <x-ui.card class="mb-3">
            <!-- Zapotrzebowanie -->
            <div class="mb-3">
                <x-ui.card label="Zapotrzebowanie">
                    <div class="d-flex justify-content-end mb-2">
                        <x-ui.button variant="ghost" href="{{ route('projects.demands.create', ['project' => $project, 'date_from' => $weekData['week']['start']->format('Y-m-d'), 'date_to' => $weekData['week']['end']->format('Y-m-d')]) }}" class="btn-sm">
                            <i class="bi bi-pencil"></i>
                            Edytuj
                        </x-ui.button>
                    </div>
                    
                    <!-- Tabelka zapotrzebowania -->
                    @if(!empty($weekData['requirements_summary']['role_details']))
                        <div class="mb-2 table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="text-start small fw-bold">Rola</th>
                                        <th class="text-center small fw-bold">Potrzebnych</th>
                                        <th class="text-center small fw-bold">Przypisanych</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($weekData['requirements_summary']['role_details'] as $roleDetail)
                                        @php
                                            $needed = $roleDetail['needed'];
                                            $assigned = $roleDetail['assigned'];
                                            $assignedMin = $roleDetail['assigned_min'] ?? $assigned;
                                            $assignedMax = $roleDetail['assigned_max'] ?? $assigned;
                                            $isStable = $roleDetail['is_stable'] ?? true;
                                            $isComplete = $isStable && $assigned !== null && $assigned >= $needed;
                                            $isPartial = $isStable && $assigned !== null && $assigned > 0 && $assigned < $needed;
                                        @endphp
                                        <tr>
                                            <td class="small">{{ Str::lower($roleDetail['role']->name) }}</td>
                                            <td class="text-center small fw-semibold {{ $isComplete ? 'text-success' : ($isPartial ? 'text-warning' : 'text-danger') }}">
                                                {{ $needed }}
                                            </td>
                                            <td class="text-center small fw-semibold text-primary">
                                                {{ $assignedMax }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="fw-bold">
                                        <td class="small">łącznie</td>
                                        <td class="text-center small">{{ $weekData['requirements_summary']['total_needed'] }}</td>
                                        <td class="text-center small">
                                            {{ $weekData['requirements_summary']['total_assigned_max'] ?? 0 }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="mb-2 table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="text-start small fw-bold">Rola</th>
                                        <th class="text-center small fw-bold">Potrzebnych</th>
                                        <th class="text-center small fw-bold">Przypisanych</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="fw-bold">
                                        <td class="small">łącznie</td>
                                        <td class="text-center small">{{ $weekData['requirements_summary']['total_needed'] }}</td>
                                        <td class="text-center small">{{ $weekData['requirements_summary']['total_assigned'] }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-ui.card>
            </div>

            <!-- Osoby w projekcie -->
            <div class="mb-3">
                <x-ui.card>
                        <h4 class="fw-bold mb-2 d-flex align-items-center gap-1 small">
                            <i class="bi bi-people text-warning"></i>
                            <span class="text-warning">Osoby</span>
                        </h4>
                        @if($weekData['assigned_employees']->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th class="text-start small fw-bold">Zdjęcie</th>
                                            <th class="text-start small fw-bold">Imię i nazwisko</th>
                                            <th class="text-start small fw-bold">Rola w projekcie</th>
                                            <th class="text-center small fw-bold">Pokrycie</th>
                                            <th class="text-start small fw-bold">Auto</th>
                                            <th class="text-start small fw-bold">Do rotacji</th>
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
                                                    <a href="{{ route('employees.show', $employeeData['employee']) }}" class="fw-semibold text-decoration-none">{{ $employeeData['employee']->full_name }}</a>
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
                                                @php
                                                    $dateRange = $employeeData['date_range'] ?? 'cały tydzień';
                                                    $isFullWeek = ($dateRange === 'cały tydzień' || $dateRange === 'pon-nie');
                                                    $shouldHighlight = !$isFullWeek;
                                                @endphp
                                                <td class="text-center {{ $shouldHighlight ? 'bg-danger bg-opacity-25' : '' }}">
                                                    <span class="fw-semibold">
                                                        {{ $dateRange }}
                                                    </span>
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
                                                        <div class="d-flex align-items-center gap-1">
                                                            <x-ui.badge variant="danger" title="Brak przypisanego auta">
                                                                <i class="bi bi-x-circle"></i> Brak
                                                            </x-ui.badge>
                                                            <a href="{{ route('employees.vehicles.create', ['employee' => $employeeData['employee']->id, 'start_date' => $weekData['week']['start']->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-primary p-0 px-1" style="font-size: 0.7rem;" title="Przypisz auto">
                                                                <i class="bi bi-plus"></i>
                                                            </a>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($employeeData['rotation'] ?? null)
                                                        @php
                                                            $rotationId = $employeeData['rotation']['id'] ?? null;
                                                            $daysLeft = $employeeData['rotation']['days_left'] ?? 0;
                                                            $employee = $employeeData['employee'];
                                                        @endphp
                                                        @if($rotationId)
                                                            <a href="{{ route('employees.rotations.show', ['employee' => $employee->id, 'rotation' => $rotationId]) }}" 
                                                               class="text-decoration-none {{ $daysLeft >= 0 ? 'text-primary' : 'text-danger' }} fw-semibold">
                                                                {{ $daysLeft }}
                                                            </a>
                                                        @else
                                                            <span class="{{ $daysLeft >= 0 ? 'text-primary' : 'text-danger' }} fw-semibold">{{ $daysLeft }}</span>
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
                                    <x-ui.button variant="primary" href="{{ route('projects.assignments.create', ['project' => $project->id, 'date_from' => $weekData['week']['start']->format('Y-m-d'), 'date_to' => $weekData['week']['end']->format('Y-m-d')]) }}" class="w-100 btn-sm">
                                        <i class="bi bi-plus"></i>
                                        {{ $weekData['assigned_employees']->count() > 0 ? 'Dodaj' : 'Przypisz' }}
                                    </x-ui.button>
                                @else
                                    <x-ui.button variant="primary" href="{{ route('projects.assignments.create', $project) }}" class="w-100 btn-sm">
                                        <i class="bi bi-plus"></i>
                                        {{ $weekData['assigned_employees']->count() > 0 ? 'Dodaj' : 'Przypisz' }}
                                    </x-ui.button>
                                @endif
                            </div>
                        @else
                            <div class="text-center py-4 text-muted small">
                                Brak osób
                            </div>
                            <div class="mt-2">
                                @if(isset($weekData['week']) && isset($weekData['week']['start']) && isset($weekData['week']['end']))
                                    <x-ui.button variant="primary" href="{{ route('projects.assignments.create', ['project' => $project->id, 'date_from' => $weekData['week']['start']->format('Y-m-d'), 'date_to' => $weekData['week']['end']->format('Y-m-d')]) }}" class="w-100 btn-sm">
                                        <i class="bi bi-plus"></i>
                                        Przypisz osoby
                                    </x-ui.button>
                                @else
                                    <x-ui.button variant="primary" href="{{ route('projects.assignments.create', $project) }}" class="w-100 btn-sm">
                                        <i class="bi bi-plus"></i>
                                        Przypisz osoby
                                    </x-ui.button>
                                @endif
                            </div>
                        @endif
                </x-ui.card>
            </div>

            <!-- Auta i Domy - w stylu Album -->
            <div class="row g-3 mb-3">
                <!-- Auta -->
                <div class="col-12">
                    <x-ui.card label="Auta">
                        @if($weekData['vehicles']->isNotEmpty())
                        <div class="row g-3 mb-3">
                            @foreach($weekData['vehicles']->take(6) as $vehicleData)
                                <div class="col-6 col-md-4 col-lg-3">
                                    <a href="{{ route('vehicles.show', $vehicleData['vehicle']) }}" class="text-decoration-none" style="display: block;">
                                        <x-ui.card class="h-100 vehicle-card-clickable">
                                            <div class="card-img-top d-flex align-items-center justify-content-center" style="height: 120px; background: var(--bg-card); padding: 8px;">
                                                @if($vehicleData['vehicle']->image_path)
                                                    <img src="{{ $vehicleData['vehicle']->image_url }}" 
                                                         alt="{{ $vehicleData['vehicle_name'] }}" 
                                                         style="max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain; border-radius: 8px;"
                                                         onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2306b6d4\'%3E%3Cpath d=\'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2\'/%3E%3C/svg%3E';">
                                                @else
                                                    <div class="bg-info bg-opacity-10 w-100 h-100 d-flex align-items-center justify-content-center" style="border-radius: 8px;">
                                                        <i class="bi bi-car-front text-info" style="font-size: 3rem;"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="d-flex flex-column">
                                                <h6 class="card-title mb-0">
                                                    {{ $vehicleData['vehicle_name'] }}
                                                </h6>
                                            <p class="card-text small text-muted mb-2">
                                                {{ $vehicleData['usage'] }}
                                                @if($vehicleData['driver'])
                                                    <br><span class="text-success fw-semibold"><i class="bi bi-car-front-fill"></i> <a href="{{ route('employees.show', $vehicleData['driver']) }}" class="text-success text-decoration-none">{{ $vehicleData['driver']->full_name }}</a></span>
                                                @else
                                                    <br><span class="text-danger fw-semibold"><i class="bi bi-car-front-fill"></i> Brak kierowcy</span>
                                                @endif
                                            </p>
                                            @if(isset($vehicleData['return_trip']) && $vehicleData['return_trip'] && isset($vehicleData['return_trip_assignments']) && $vehicleData['return_trip_assignments']->isNotEmpty())
                                                <div class="alert alert-info mb-2">
                                                    <i class="bi bi-arrow-down-circle"></i>
                                                    <div>
                                                        <div class="fw-bold">Zjazd: {{ $vehicleData['return_trip']->event_date->format('d.m.Y') }}</div>
                                                        <div class="text-muted small">
                                                            @foreach($vehicleData['return_trip_assignments'] as $returnAssignment)
                                                                <a href="{{ route('vehicle-assignments.show', $returnAssignment) }}" class="text-decoration-none">
                                                                    {{ $returnAssignment->employee->full_name }}
                                                                </a>@if(!$loop->last), @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            @if(isset($vehicleData['assignments']) && $vehicleData['assignments']->count() > 0)
                                                <div class="mt-auto" wire:ignore.self onclick="event.stopPropagation();">
                                                    <button class="btn-link w-100" type="button" data-bs-toggle="collapse" data-bs-target="#vehicle-{{ $vehicleData['vehicle']->id }}-assignments" aria-expanded="false" style="background: none; border: 1px solid var(--glass-border); color: var(--text-main); padding: 0.375rem 0.75rem; border-radius: 6px;">
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
                                                                            <x-ui.badge variant="success" class="ms-1">Kierowca</x-ui.badge>
                                                                        @endif
                                                                    </a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        </x-ui.card>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                        @if($weekData['vehicles']->count() > 6)
                            <div class="text-center">
                                <x-ui.badge variant="info">+{{ $weekData['vehicles']->count() - 6 }} więcej</x-ui.badge>
                            </div>
                        @endif
                    @endif
                    </x-ui.card>
                </div>

                <!-- Domy -->
                @if($weekData['accommodations']->isNotEmpty() || $weekData['assigned_employees']->isNotEmpty())
                    <div class="col-12">
                        <x-ui.card label="Domy">
                        @if($weekData['accommodations']->isNotEmpty())
                            <div class="row g-3 mb-3">
                                @foreach($weekData['accommodations'] as $accommodationData)
                                    @php
                                        $accommodation = $accommodationData['accommodation'];
                                    @endphp
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <a href="{{ route('accommodations.show', $accommodation) }}" class="text-decoration-none" style="display: block;">
                                            <x-ui.card class="h-100 accommodation-card-clickable">
                                                <div class="card-img-top d-flex align-items-center justify-content-center" style="height: 120px; background: var(--bg-card); padding: 8px;">
                                                    @if($accommodation->image_path)
                                                        <img src="{{ $accommodation->image_url }}" 
                                                             alt="{{ $accommodation->name }}" 
                                                             style="max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain; border-radius: 8px;"
                                                             onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2310b981\'%3E%3Cpath d=\'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6\'/%3E%3C/svg%3E';">
                                                    @else
                                                        <div class="bg-success bg-opacity-10 w-100 h-100 d-flex align-items-center justify-content-center" style="border-radius: 8px;">
                                                            <i class="bi bi-house text-success" style="font-size: 3rem;"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <h6 class="card-title mb-0">
                                                        {{ $accommodation->name }}
                                                    </h6>
                                                <p class="card-text small text-muted mb-2">
                                                    {{ $accommodationData['usage'] }}
                                                </p>
                                                @if(isset($accommodationData['assignments']) && $accommodationData['assignments']->count() > 0)
                                                    <div class="mt-auto" wire:ignore.self onclick="event.stopPropagation();">
                                                        <button class="btn-link w-100" type="button" data-bs-toggle="collapse" data-bs-target="#accommodation-{{ $accommodation->id }}-assignments" aria-expanded="false" style="background: none; border: 1px solid var(--glass-border); color: var(--text-main); padding: 0.375rem 0.75rem; border-radius: 6px;">
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
                                            </x-ui.card>
                                        </a>
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
                                <i class="bi bi-shield-lock-fill text-danger fs-3"></i>
                                <div>
                                    <div class="fw-bold text-danger">Alert Logistyczny</div>
                                    <div class="text-muted small">Bez domu ({{ $employeesWithoutAccommodation->count() }})</div>
                                </div>
                            </div>
                            <div class="mt-2">
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
                                                    <a href="{{ route('employees.show', $employeeData['employee']) }}" class="fw-semibold text-decoration-none d-block">{{ $employeeData['employee']->full_name }}</a>
                                                    <x-ui.badge variant="info" class="small">{{ $employeeData['role']->name }}</x-ui.badge>
                                                </div>
                                                @php
                                                    $employee = $employeeData['employee'];
                                                    $url = route('employees.accommodations.create', $employee->id);
                                                    if (isset($weekData['week']) && isset($weekData['week']['start']) && isset($weekData['week']['end'])) {
                                                        $url .= '?date_from=' . $weekData['week']['start']->format('Y-m-d') . '&date_to=' . $weekData['week']['end']->format('Y-m-d');
                                                    }
                                                @endphp
                                                <x-ui.button variant="primary" href="{{ $url }}" class="btn-sm">
                                                    <i class="bi bi-plus"></i>
                                                    Dom
                                                </x-ui.button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        </x-ui.card>
                    </div>
                @endif
            </div>

            <!-- Dropdown z pełną listą osób (opcjonalnie) -->
            @if($weekData['assigned_employees']->count() > 5)
                <x-weekly-overview.assigned-employees 
                    :assignedEmployees="$weekData['assigned_employees']"
                />
            @endif
    </x-ui.card>
@else
    <x-ui.card>
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="bi bi-file-earmark-text text-muted" style="font-size: 4rem;"></i>
            </div>
            <p class="text-muted fs-5 fw-medium mb-4">Brak prac w tym tygodniu</p>
            @if(isset($weekData['week']) && isset($weekData['week']['start']) && isset($weekData['week']['end']))
                <x-ui.button variant="primary" href="{{ route('projects.demands.create', ['project' => $project->id, 'date_from' => $weekData['week']['start']->format('Y-m-d'), 'date_to' => $weekData['week']['end']->format('Y-m-d')]) }}">
                    <i class="bi bi-plus"></i>
                    Dodaj zapotrzebowanie
                </x-ui.button>
            @else
                <x-ui.button variant="primary" href="{{ route('projects.demands.create', $project) }}">
                    <i class="bi bi-plus"></i>
                    Dodaj zapotrzebowanie
                </x-ui.button>
            @endif
        </div>
    </x-ui.card>
@endif
