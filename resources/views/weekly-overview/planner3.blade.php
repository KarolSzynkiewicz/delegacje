@php
    // Pobierz pierwszy projekt z danymi
    $projectData = $projectsWithStability[0] ?? null;
    $project = $projectData['project'] ?? null;
    $stability = $projectData['stability'] ?? null;
    
    if (!$project || !$stability || !$stability['has_data']) {
        $hasData = false;
    } else {
        $hasData = true;
        $requirementsSummary = $stability['requirements_summary'] ?? null;
        $assignedEmployees = $stability['assigned_employees'] ?? collect();
        $vehicles = $stability['vehicles'] ?? [];
        $accommodations = $stability['accommodations'] ?? [];
        $potentialIssues = $stability['potential_issues'] ?? [];
        
        // Oblicz procent realizacji
        $totalNeeded = $requirementsSummary['total_needed'] ?? 0;
        $totalAssigned = $requirementsSummary['total_assigned'] ?? null;
        $isStable = $requirementsSummary['is_stable'] ?? false;
        $percentage = null;
        if ($isStable && $totalAssigned !== null && $totalNeeded > 0) {
            $percentage = round(($totalAssigned / $totalNeeded) * 100, 0);
        }
        
        // Przygotuj dane dla pojazdów z pracownikami
        $vehiclesWithEmployees = [];
        foreach ($vehicles as $vehicleData) {
            $vehicle = $vehicleData['vehicle'];
            $employeesInVehicle = $assignedEmployees->filter(function($emp) use ($vehicle) {
                return $emp['vehicle_stable'] && $emp['vehicle'] && $emp['vehicle']->id === $vehicle->id;
            });
            $maxOccupancy = $vehicleData['max_occupancy'] ?? $employeesInVehicle->count();
            $vehiclesWithEmployees[] = [
                'vehicle' => $vehicle,
                'count' => $maxOccupancy . '/' . ($vehicle->capacity ?? '?'),
                'employees' => $employeesInVehicle->map(fn($e) => $e['employee']->full_name)->toArray(),
                'is_full' => $vehicle->capacity && $maxOccupancy >= $vehicle->capacity,
            ];
        }
        
        // Przygotuj dane dla mieszkań z pracownikami
        $accommodationsWithEmployees = [];
        foreach ($accommodations as $accommodationData) {
            $accommodation = $accommodationData['accommodation'];
            $employeesInAccommodation = $assignedEmployees->filter(function($emp) use ($accommodation) {
                return $emp['accommodation_stable'] && $emp['accommodation'] && $emp['accommodation']->id === $accommodation->id;
            });
            $maxOccupancy = $accommodationData['max_occupancy'] ?? $employeesInAccommodation->count();
            $accommodationsWithEmployees[] = [
                'accommodation' => $accommodation,
                'count' => $maxOccupancy . '/' . ($accommodation->capacity ?? '?'),
                'employees' => $employeesInAccommodation->map(fn($e) => $e['employee']->full_name)->toArray(),
                'is_full' => $accommodation->capacity && $maxOccupancy >= $accommodation->capacity,
            ];
        }
        
        // Sprawdź statusy
        $allHaveVehicle = $assignedEmployees->every(fn($e) => $e['vehicle_stable'] && $e['vehicle'] !== null);
        $allHaveAccommodation = $assignedEmployees->every(fn($e) => $e['accommodation_stable'] && $e['accommodation'] !== null);
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Przegląd przydziałów ekip</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-vh-100">

    <div class="dark-dashboard-wrapper">
        @if($hasData)
            <div class="container-xxl">
                <!-- Project Hero Section (Full Width) -->
                <div class="project-hero">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <x-ui.badge variant="success" class="mb-2">Aktywny Projekt</x-ui.badge>
                            <h1 class="mb-2">{{ $project->name }}</h1>
                            <p class="location mb-0 d-flex align-items-center gap-2">
                                <i class="bi bi-geo-alt-fill"></i>
                                {{ $project->location->name ?? 'Brak lokalizacji' }}
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            @if($percentage !== null)
                                <x-ui.card>
                                    <span class="card-label">Realizacja</span>
                                    <div class="stat-value">{{ $percentage }}%</div>
                                    <x-ui.progress value="{{ $percentage }}" max="100" />
                                </x-ui.card>
                            @endif
                            <div class="mt-3">
                                <x-ui.button variant="primary" href="{{ $navigation['nextUrl'] }}">
                                    Następny tydzień &rarr;
                                </x-ui.button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Główny Układ (Grid 8+4) -->
                <div class="row g-4">
                    <!-- LEWA KOLUMNA: Ekipa (col-xl-8) -->
                    <div class="col-12 col-xl-8">
                        <div class="dark-card">
                            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-white border-opacity-10">
                                <h3 class="fw-bold text-white mb-0 d-flex align-items-center gap-2">
                                    <i class="bi bi-lightning-charge text-warning"></i> Skład Ekipy
                                </h3>
                                <x-ui.button variant="ghost" href="{{ route('projects.demands.create', ['project' => $project, 'date_from' => $weeks[0]['start']->format('Y-m-d'), 'date_to' => $weeks[0]['end']->format('Y-m-d')]) }}" class="btn-sm">
                                    Edytuj zapotrzebowanie
                                </x-ui.button>
                            </div>

                            <!-- Kompaktowe Podsumowanie Ról -->
                            @if(!empty($requirementsSummary['role_details']))
                                <div class="row g-2 mb-4">
                                    @foreach($requirementsSummary['role_details'] as $roleDetail)
                                        @php
                                            $needed = $roleDetail['needed'];
                                            $assigned = $roleDetail['assigned'] ?? null;
                                            $assignedMin = $roleDetail['assigned_min'] ?? null;
                                            $assignedMax = $roleDetail['assigned_max'] ?? null;
                                            $isStable = $roleDetail['is_stable'] ?? false;
                                            $displayAssigned = $isStable && $assigned !== null ? $assigned : ($assignedMin . '-' . $assignedMax);
                                        @endphp
                                        <div class="col-6 col-sm-4 col-md-3">
                                            <div class="role-summary-tile">
                                                <div class="role-name">{{ $roleDetail['role']->name }}</div>
                                                <div class="role-count">{{ $displayAssigned }}/{{ $needed }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <!-- Tabela Osoby -->
                            @if($assignedEmployees->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th class="text-start">Pracownik</th>
                                                <th class="text-start">Rola</th>
                                                <th class="text-start">Pokrycie</th>
                                                <th class="text-end">Rotacja</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($assignedEmployees as $employeeData)
                                                @php
                                                    $employee = $employeeData['employee'];
                                                    $initials = substr($employee->first_name ?? '', 0, 1) . substr($employee->last_name ?? '', 0, 1);
                                                    $daysAssigned = $employeeData['stability']['days_assigned'] ?? 0;
                                                    $daysInWeek = $employeeData['stability']['days_in_week'] ?? 7;
                                                    $rotation = $daysAssigned - $daysInWeek;
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-3">
                                                            <div class="avatar">{{ $initials }}</div>
                                                            <span class="fw-medium">{{ $employee->full_name }}</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @if($employeeData['role_stable'] && $employeeData['role'])
                                                            <x-ui.badge variant="info">
                                                                {{ $employeeData['role']->name }}
                                                            </x-ui.badge>
                                                        @else
                                                            <x-ui.badge variant="warning">
                                                                Zmienne
                                                            </x-ui.badge>
                                                        @endif
                                                    </td>
                                                    <td class="text-slate-400 fst-italic">{{ $employeeData['date_range'] ?? '-' }}</td>
                                                    <td class="text-end">
                                                        @if($rotation < 0)
                                                            <span class="text-danger font-monospace">{{ $rotation }}</span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                            
                            <x-ui.button variant="ghost" href="{{ route('projects.assignments.create', ['project' => $project, 'date_from' => $weeks[0]['start']->format('Y-m-d'), 'date_to' => $weeks[0]['end']->format('Y-m-d')]) }}" class="w-100 mt-4 py-3 border-2 border-dashed">
                                <i class="bi bi-plus"></i> Dodaj osobę
                            </x-ui.button>
                        </div>
                    </div>

                    <!-- PRAWA KOLUMNA: Logistyka (col-xl-4) -->
                    <div class="col-12 col-xl-4">
                        <!-- Alerty systemowe na górze -->
                        <div class="mb-4">
                            @if(!empty($requirementsSummary['role_details']))
                                @foreach($requirementsSummary['role_details'] as $roleDetail)
                                    @if(($roleDetail['missing'] ?? 0) > 0)
                                        <div class="alert alert-danger mb-3">
                                            <i class="bi bi-shield-lock-fill text-danger fs-3"></i>
                                            <div>
                                                <div class="fw-bold text-danger">Alert Logistyczny</div>
                                                <div class="text-muted small">Za mało {{ Str::lower($roleDetail['role']->name) }}: {{ $roleDetail['missing'] }}</div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                            
                            @if($allHaveAccommodation)
                                <div class="alert alert-success mb-3">
                                    <i class="bi bi-check-circle-fill text-success fs-3"></i>
                                    <div>
                                        <div class="fw-bold text-success">Status OK</div>
                                        <div class="text-muted small">Wszyscy mają dom</div>
                                    </div>
                                </div>
                            @endif
                            
                            @if($allHaveVehicle)
                                <div class="alert alert-success mb-3">
                                    <i class="bi bi-check-circle-fill text-success fs-3"></i>
                                    <div>
                                        <div class="fw-bold text-success">Status OK</div>
                                        <div class="text-muted small">Wszyscy mają auto</div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Auta -->
                        <div class="dark-card mb-4">
                            <h3 class="fw-bold text-white mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-car-front text-info"></i> Flota Aut
                            </h3>
                            <div class="d-flex flex-column gap-2">
                                @forelse($vehiclesWithEmployees as $index => $vehicleData)
                                    <div class="dark-accordion-item">
                                        <button class="dark-accordion-button" type="button" aria-expanded="false">
                                            <div class="text-start">
                                                <div class="small text-info fw-bold mb-1">{{ $vehicleData['count'] }}</div>
                                                <div class="small fw-semibold">{{ $vehicleData['vehicle']->brand }} {{ $vehicleData['vehicle']->model }} {{ $vehicleData['vehicle']->registration_number }}</div>
                                            </div>
                                            <i class="bi bi-chevron-down"></i>
                                        </button>
                                        <div class="dark-accordion-content">
                                            @if(!empty($vehicleData['employees']))
                                                @foreach($vehicleData['employees'] as $employeeName)
                                                    <div class="small text-slate-400 d-flex align-items-center gap-2 mb-1">
                                                        <span style="width: 4px; height: 4px; background: #06b6d4; border-radius: 50%; display: inline-block;"></span> {{ $employeeName }}
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="small text-slate-400">Brak przypisanych osób</div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-slate-400 small mb-0">Brak pojazdów</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- Domy -->
                        <div class="dark-card">
                            <h3 class="fw-bold text-white mb-3 d-flex align-items-center gap-2">
                                <i class="bi bi-house text-success"></i> Zakwaterowanie
                            </h3>
                            <div class="d-flex flex-column gap-2">
                                @forelse($accommodationsWithEmployees as $index => $accommodationData)
                                    <div class="dark-accordion-item border-start-3 {{ $accommodationData['is_full'] ? 'border-success' : 'border-warning' }}">
                                        <button class="dark-accordion-button" type="button" aria-expanded="false">
                                            <div class="text-start">
                                                <div class="small fw-bold mb-1 {{ $accommodationData['is_full'] ? 'text-success' : 'text-warning' }} text-uppercase" style="font-size: 10px;">
                                                    @if($accommodationData['is_full']) Pełne @else Wolne miejsca @endif • {{ $accommodationData['count'] }}
                                                </div>
                                                <div class="small fw-semibold">{{ $accommodationData['accommodation']->name }}</div>
                                            </div>
                                            <i class="bi bi-chevron-down"></i>
                                        </button>
                                        <div class="dark-accordion-content">
                                            @if(!empty($accommodationData['employees']))
                                                @foreach($accommodationData['employees'] as $employeeName)
                                                    <div class="small text-slate-400 mb-1">{{ $employeeName }}</div>
                                                @endforeach
                                            @else
                                                <div class="small text-slate-400">Brak przypisanych osób</div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-slate-400 small mb-0">Brak mieszkań</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="container-xxl">
                <div class="dark-card text-center py-5">
                    <i class="bi bi-inbox text-slate-400" style="font-size: 4rem;"></i>
                    <p class="text-slate-400 fs-5 fw-medium mt-4 mb-4">Brak danych do wyświetlenia</p>
                    @if($project)
                        <x-ui.button variant="primary" href="{{ route('projects.demands.create', ['project' => $project, 'date_from' => $weeks[0]['start']->format('Y-m-d')]) }}">
                            <i class="bi bi-plus"></i> Dodaj zapotrzebowanie
                        </x-ui.button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    @livewireScripts
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.dark-accordion-button').forEach(button => {
                button.addEventListener('click', function() {
                    const content = this.nextElementSibling;
                    const isExpanded = this.getAttribute('aria-expanded') === 'true';
                    
                    // Zamknij wszystkie inne accordiony w tym samym kontenerze
                    const container = this.closest('.dark-card');
                    container.querySelectorAll('.dark-accordion-button').forEach(btn => {
                        if (btn !== this) {
                            btn.setAttribute('aria-expanded', 'false');
                            btn.nextElementSibling.classList.remove('show');
                        }
                    });
                    
                    // Toggle aktualnego
                    this.setAttribute('aria-expanded', !isExpanded);
                    if (!isExpanded) {
                        content.classList.add('show');
                    } else {
                        content.classList.remove('show');
                    }
                });
            });
        });
    </script>
</body>
</html>
