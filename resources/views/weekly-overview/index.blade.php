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
                <select id="project-search" class="form-select w-100 ms-md-auto" style="max-width: min(320px, 100%);" onchange="(function() { const baseUrl = '{{ route('weekly-overview.index') }}'; const params = new URLSearchParams(); params.set('start_date', '{{ $startDate->format('Y-m-d') }}'); if (this.value) { params.set('project_id', this.value); } window.location.href = baseUrl + '?' + params.toString(); }).call(this)">
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

    <!-- Komunikaty sukcesu/błędu -->
    @if(session('success'))
        <x-ui.alert variant="success" title="Sukces" dismissible class="mb-3">
            {{ session('success') }}
        </x-ui.alert>
    @endif

    @if(session('error'))
        <x-ui.alert variant="danger" title="Błąd" dismissible class="mb-3">
            {{ session('error') }}
        </x-ui.alert>
    @endif

    @if($errors->any())
        <x-ui.alert variant="danger" title="Błędy walidacji" dismissible class="mb-3">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <!-- Nawigacja między tygodniami -->
    <x-ui.period-nav class="cl-reveal mb-4">
        <x-slot name="prev">
            <x-ui.button variant="ghost" href="{{ $navigation['prevUrl'] }}" class="w-100">
                <i class="bi bi-chevron-left"></i>
                <span>Poprzedni tydzień</span>
            </x-ui.button>
        </x-slot>
        <div>
            <h3 class="fs-5 fw-bold mb-0">
                Tydzień {{ $navigation['current']['number'] }}
            </h3>
            <p class="small text-muted mb-0">
                {{ $navigation['current']['start']->format('d.m.Y') }} – {{ $navigation['current']['end']->format('d.m.Y') }}
            </p>
        </div>
        <x-slot name="next">
            <x-ui.button variant="primary" href="{{ $navigation['nextUrl'] }}" class="w-100">
                <span>Następny tydzień</span>
                <i class="bi bi-chevron-right"></i>
            </x-ui.button>
        </x-slot>
    </x-ui.period-nav>

    @include('weekly-overview.partials.week-summary', [
        'returnTrips' => $returnTrips,
        'allDepartures' => $allDepartures,
        'allTransfers' => $allTransfers,
        'employeesInFieldCount' => $employeesInFieldCount,
        'employeesNeededCount' => $employeesNeededCount,
        'employeesInFieldByProject' => $employeesInFieldByProject,
        'rotationsEnding' => $rotationsEnding,
        'rotationHorizonLabel' => $rotationHorizonLabel,
        'housingOccupied' => $housingOccupied,
        'housingCapacity' => $housingCapacity,
        'housingHouses' => $housingHouses,
        'vehicleOccupied' => $vehicleOccupied,
        'vehicleCapacity' => $vehicleCapacity,
        'fieldVehicles' => $fieldVehicles,
        'benchWithRotation' => $benchWithRotation,
        'benchWithoutRotation' => $benchWithoutRotation,
        'benchWithPeople' => $benchWithPeople,
        'benchWithoutPeople' => $benchWithoutPeople,
        'benchDayLabel' => $benchDayLabel,
        'toSendCount' => $toSendCount,
        'toSendNextDemand' => $toSendNextDemand,
        'toSendStaying' => $toSendStaying,
        'toSendDemandByProject' => $toSendDemandByProject,
        'toSendStayingPeople' => $toSendStayingPeople,
        'toSendLeavingPeople' => $toSendLeavingPeople,
    ])

    <div class="mb-4 cl-reveal" id="week-comments">
        <x-comments
            :commentable="$planningWeek"
            label="Komentarze tygodnia"
            input-label="Dodaj komentarz do tygodnia"
            button-text="Dodaj komentarz"
        />
    </div>

    @include('weekly-overview.partials.staffing-demand-matrix', [
        'staffingDemandMatrix' => $staffingDemandMatrix,
    ])

    <x-ui.card reveal class="mb-4" id="deadline-radar">
        @include('weekly-overview.partials.deadline-radar', [
            'deadlineRadar' => $deadlineRadar,
        ])
    </x-ui.card>

    <!-- Sekcja: Pracownicy bez projektu, ale z autem lub domem -->
    @if(isset($employeesWithoutProject) && $employeesWithoutProject->isNotEmpty())
        <div class="mt-4">
            <x-ui.alert variant="warning" title="Pracownicy bez projektu">
                <p class="mb-3">Następujący pracownicy mają przypisane auto lub dom, ale nie są przypisani do żadnego projektu w tym tygodniu:</p>
                <div class="row g-3">
                    @foreach($employeesWithoutProject as $employeeData)
                        @php
                            $employee = $employeeData['employee'];
                            $vehicleAssignments = $employeeData['vehicle_assignments'];
                            $accommodationAssignments = $employeeData['accommodation_assignments'];
                        @endphp
                        <div class="col-md-6 col-lg-4">
                            <x-ui.card reveal>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <x-employee-cell :employee="$employee" />
                                </div>
                                                @if($employee->roles->count() > 0)
                                    <div class="mb-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($employee->roles as $role)
                                                <x-role-seniority-badge :role="$role" />
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                <div class="small">
                                    @if($vehicleAssignments->isNotEmpty())
                                        <div class="mb-1">
                                            <i class="bi bi-car-front text-primary"></i>
                                            <span class="text-muted">Auto:</span>
                                            @foreach($vehicleAssignments as $assignment)
                                                @if($assignment->vehicle)
                                                    <a href="{{ route('vehicle-assignments.show', $assignment) }}" class="text-decoration-none" title="Przejdź do przypisania">
                                                        {{ $assignment->vehicle->registration_number }}
                                                        @if($assignment->vehicle->brand || $assignment->vehicle->model)
                                                            ({{ $assignment->vehicle->brand }} {{ $assignment->vehicle->model }})
                                                        @endif
                                                    </a>@if(!$loop->last), @endif
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($accommodationAssignments->isNotEmpty())
                                        <div>
                                            <i class="bi bi-house text-success"></i>
                                            <span class="text-muted">Dom:</span>
                                            @foreach($accommodationAssignments as $assignment)
                                                @if($assignment->accommodation)
                                                    <a href="{{ route('accommodation-assignments.show', $assignment) }}" class="text-decoration-none" title="Przejdź do przypisania">
                                                        {{ $assignment->accommodation->name }}
                                                    </a>@if(!$loop->last), @endif
                                                @endif
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

    <!-- Sekcja: Zwolnieni pracownicy z wiszącymi przypisaniami -->
    @if(isset($terminatedEmployeesWithAssignments) && $terminatedEmployeesWithAssignments->isNotEmpty())
        <div class="mt-4">
            <x-ui.alert variant="danger" title="Zwolnieni z aktywnymi przypisaniami">
                <p class="mb-3">Następujący pracownicy są zwolnieni, ale nadal mają przypisania w tym tygodniu — zamknij lub usuń te przypisania:</p>
                <div class="row g-3">
                    @foreach($terminatedEmployeesWithAssignments as $employeeData)
                        @php
                            $employee = $employeeData['employee'];
                            $projectAssignments = $employeeData['project_assignments'];
                            $vehicleAssignments = $employeeData['vehicle_assignments'];
                            $accommodationAssignments = $employeeData['accommodation_assignments'];
                        @endphp
                        <div class="col-md-6 col-lg-4">
                            <x-ui.card reveal>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <x-employee-cell :employee="$employee" />
                                    <x-ui.badge variant="danger">Zwolniony</x-ui.badge>
                                </div>
                                @if($employee->terminated_at)
                                    <div class="small text-muted mb-2">
                                        Od {{ $employee->terminated_at->format('Y-m-d') }}
                                        @if($employee->termination_reason)
                                            · {{ $employee->termination_reason->label() }}
                                        @endif
                                    </div>
                                @endif
                                <div class="small">
                                    @if($projectAssignments->isNotEmpty())
                                        <div class="mb-1">
                                            <i class="bi bi-briefcase text-danger"></i>
                                            <span class="text-muted">Projekt:</span>
                                            @foreach($projectAssignments as $assignment)
                                                <a href="{{ route('project-assignments.show', $assignment) }}" class="text-decoration-none" title="Przejdź do przypisania">
                                                    {{ $assignment->project?->name ?? '—' }}
                                                    @if($assignment->role)
                                                        ({{ $assignment->role->name }})
                                                    @endif
                                                </a>@if(!$loop->last), @endif
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($vehicleAssignments->isNotEmpty())
                                        <div class="mb-1">
                                            <i class="bi bi-car-front text-primary"></i>
                                            <span class="text-muted">Auto:</span>
                                            @foreach($vehicleAssignments as $assignment)
                                                @if($assignment->vehicle)
                                                    <a href="{{ route('vehicle-assignments.show', $assignment) }}" class="text-decoration-none" title="Przejdź do przypisania">
                                                        {{ $assignment->vehicle->registration_number }}
                                                    </a>@if(!$loop->last), @endif
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($accommodationAssignments->isNotEmpty())
                                        <div>
                                            <i class="bi bi-house text-success"></i>
                                            <span class="text-muted">Dom:</span>
                                            @foreach($accommodationAssignments as $assignment)
                                                @if($assignment->accommodation)
                                                    <a href="{{ route('accommodation-assignments.show', $assignment) }}" class="text-decoration-none" title="Przejdź do przypisania">
                                                        {{ $assignment->accommodation->name }}
                                                    </a>@if(!$loop->last), @endif
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="mt-2">
                                    <x-ui.button
                                        variant="ghost"
                                        size="sm"
                                        href="{{ route('employees.show', $employee) }}"
                                    >
                                        <i class="bi bi-person"></i> Kartoteka
                                    </x-ui.button>
                                </div>
                            </x-ui.card>
                        </div>
                    @endforeach
                </div>
            </x-ui.alert>
        </div>
    @endif

    <!-- Projekty -->
    @forelse($projects as $projectData)
        @php
            $project = $projectData['project'];
            $summary = $projectData['summary'] ?? null;
            $weekData = $projectData['weeks_data'][0] ?? null;
            $weekStart = $weeks[0]['start']->format('Y-m-d');
            $planner2Url = route('weekly-overview.planner2', ['start_date' => $weekStart, 'project_id' => $project->id]);
        @endphp

        <x-ui.card reveal class="mb-4">
            <!-- Row1: Nazwa projektu/lokalizacja + statystyki -->
            <div class="mb-3">
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <span class="badge bg-primary rounded-circle" style="width: 8px; height: 8px; padding: 0;"></span>
                    <h3 class="fs-5 fw-bold mb-0 text-dark">
                        <a href="{{ route('projects.show', $project) }}" class="text-decoration-underline">{{ $project->name }}</a>
                    </h3>
                    @php
                        $weekSiteLeads = $weekData['site_leads'] ?? collect();
                    @endphp
                    @if($weekSiteLeads->isNotEmpty())
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            @foreach($weekSiteLeads as $siteLead)
                                @if($siteLead->employee)
                                    <span class="d-inline-flex align-items-center gap-1">
                                        <x-project-site-lead-badge />
                                        <a href="{{ route('employees.show', $siteLead->employee) }}" class="small text-decoration-none">
                                            {{ $siteLead->employee->full_name }}
                                        </a>
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    @endif
                    
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
                                @livewire('project-staffing-delta', [
                                    'projectId' => $project->id,
                                    'weekStart' => $weeks[0]['start']->toDateString(),
                                    'projectName' => $project->name,
                                ], key('staffing-delta-'.$project->id.'-'.$weeks[0]['start']->toDateString()))
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
                <!-- Zapotrzebowanie, Auta w projekcie, Domy w projekcie (jedna pod drugą) -->
                <div class="row g-3 mb-4">
                    <!-- Zapotrzebowanie -->
                    <div class="col-12">
                        <x-ui.card>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="card-label">Zapotrzebowanie</span>
                                <x-ui.button 
                                    variant="ghost" 
                                    href="{{ route('projects.demands.create', ['project' => $project, 'start_date' => $weeks[0]['start']->format('Y-m-d'), 'end_date' => $weeks[0]['end']->format('Y-m-d')]) }}" 
                                    class="btn-sm" 
                                    action="edit"
                                    title="Edytuj zapotrzebowanie"
                                />
                            </div>
                            
                            @php
                                $reqSummary = $weekData['requirements_summary'] ?? [];
                                $totalNeeded = $reqSummary['total_needed'] ?? 0;
                                $roleDetails = $reqSummary['role_details'] ?? [];

                                $demandPalette = [
                                    '#8b5cf6', '#10b981', '#f59e0b', '#ec4899', '#64748b',
                                    '#3b82f6', '#06b6d4', '#f97316', '#84cc16', '#14b8a6',
                                ];
                                $demandChartItems = collect($roleDetails)
                                    ->filter(fn ($roleDetail) => ($roleDetail['needed'] ?? 0) > 0)
                                    ->values()
                                    ->map(function ($roleDetail, $index) use ($demandPalette) {
                                        $role = $roleDetail['role'] ?? null;

                                        return [
                                            'label' => $role?->name ?? '—',
                                            'value' => (int) ($roleDetail['needed'] ?? 0),
                                            'role_id' => $role?->id,
                                            'color' => $demandPalette[$index % count($demandPalette)],
                                        ];
                                    });
                                $demandChartLabels = $demandChartItems->pluck('label')->all();
                                $demandChartValues = $demandChartItems->pluck('value')->all();
                                $demandChartColors = $demandChartItems->pluck('color')->all();

                                $senioritySliceMeta = [
                                    4 => ['label' => '4 Ekspert', 'color' => '#a855f7'],
                                    3 => ['label' => '3 Fachowiec', 'color' => '#10b981'],
                                    2 => ['label' => '2 Podstawowy', 'color' => '#3b82f6'],
                                    1 => ['label' => '1 Przyuczenie', 'color' => '#f59e0b'],
                                    0 => ['label' => '? Nieustalone', 'color' => '#64748b'],
                                ];

                                $fulfillItems = collect($roleDetails)
                                    ->filter(fn ($rd) => ($rd['needed'] ?? 0) > 0)
                                    ->values()
                                    ->map(function ($rd) use ($senioritySliceMeta) {
                                        $needed = (int) ($rd['needed'] ?? 0);
                                        $mix = ($rd['seniority_mix'] ?? []) + [4 => 0, 3 => 0, 2 => 0, 1 => 0, 0 => 0];
                                        $assigned = (int) array_sum($mix);
                                        $empty = max(0, $needed - $assigned);
                                        $role = $rd['role'] ?? null;

                                        $legend = [];
                                        $chartSlices = [];
                                        foreach ([4, 3, 2, 1, 0] as $level) {
                                            $count = (int) $mix[$level];
                                            if ($count <= 0) {
                                                continue;
                                            }
                                            $meta = $senioritySliceMeta[$level];
                                            $slice = [
                                                'label' => $meta['label'],
                                                'value' => $count,
                                                'color' => $meta['color'],
                                                'gap' => false,
                                            ];
                                            $legend[] = $slice;
                                            $chartSlices[] = $slice;
                                        }
                                        if ($empty > 0) {
                                            $chartSlices[] = [
                                                'label' => 'Brak',
                                                'value' => $empty,
                                                'color' => 'rgba(255,255,255,0.08)',
                                                'gap' => true,
                                            ];
                                        }

                                        $isStable = $rd['is_stable'] ?? true;
                                        $assignedMin = (int) ($rd['assigned_min'] ?? $assigned);
                                        $assignedMax = (int) ($rd['assigned_max'] ?? $assigned);
                                        $centerLabel = $isStable
                                            ? $assignedMax.'/'.$needed
                                            : $assignedMin.'–'.$assignedMax.'/'.$needed;

                                        return [
                                            'label' => $role?->name ?? '—',
                                            'role_id' => $role?->id,
                                            'center' => $centerLabel,
                                            'legend' => $legend,
                                            'chart_labels' => collect($chartSlices)->pluck('label')->all(),
                                            'chart_values' => collect($chartSlices)->pluck('value')->all(),
                                            'chart_colors' => collect($chartSlices)->pluck('color')->all(),
                                            'chart_gaps' => collect($chartSlices)->pluck('gap')->all(),
                                        ];
                                    });
                            @endphp

                            @if(!empty($demandChartLabels))
                                <div class="row g-4 align-items-start">
                                    <div class="col-12 col-lg-5">
                                        <div class="wo-demand">
                                            <div class="wo-demand__donut">
                                                <canvas
                                                    class="wo-demand-chart"
                                                    style="width:100%;height:100%;"
                                                    data-labels='@json($demandChartLabels)'
                                                    data-values='@json($demandChartValues)'
                                                    data-colors='@json($demandChartColors)'
                                                ></canvas>
                                                <div class="wo-demand__center">
                                                    <div class="wo-demand__total">{{ $totalNeeded }}</div>
                                                    <div class="wo-demand__caption">potrzebnych</div>
                                                </div>
                                            </div>
                                            <ul class="wo-demand__legend list-unstyled mb-0">
                                                @foreach($demandChartItems as $item)
                                                    @php
                                                        $legendUrl = $item['role_id']
                                                            ? route('project-assignments.create', [
                                                                'project_id' => $project->id,
                                                                'start_date' => $weeks[0]['start']->format('Y-m-d'),
                                                                'end_date'   => $weeks[0]['end']->format('Y-m-d'),
                                                                'role_id'    => $item['role_id'],
                                                            ])
                                                            : null;
                                                    @endphp
                                                    <li>
                                                        <span style="background:{{ $item['color'] }};"></span>
                                                        @if($legendUrl)
                                                            <a href="{{ $legendUrl }}" class="text-decoration-none" style="color:inherit;">{{ $item['label'] }}</a>
                                                        @else
                                                            {{ $item['label'] }}
                                                        @endif
                                                        <em>{{ $item['value'] }}</em>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-7">
                                        <div class="wo-fulfill__heading">Wykonanie</div>
                                        <div class="wo-fulfill">
                                            @foreach($fulfillItems as $fulfill)
                                                @php
                                                    $fulfillUrl = $fulfill['role_id']
                                                        ? route('project-assignments.create', [
                                                            'project_id' => $project->id,
                                                            'start_date' => $weeks[0]['start']->format('Y-m-d'),
                                                            'end_date'   => $weeks[0]['end']->format('Y-m-d'),
                                                            'role_id'    => $fulfill['role_id'],
                                                        ])
                                                        : null;
                                                @endphp
                                                <div class="wo-fulfill__item">
                                                    <div class="wo-fulfill__donut">
                                                        <canvas
                                                            class="wo-fulfill-chart"
                                                            style="width:100%;height:100%;"
                                                            data-labels='@json($fulfill['chart_labels'])'
                                                            data-values='@json($fulfill['chart_values'])'
                                                            data-colors='@json($fulfill['chart_colors'])'
                                                            data-gaps='@json($fulfill['chart_gaps'])'
                                                        ></canvas>
                                                        <div class="wo-fulfill__center">
                                                            <div class="wo-fulfill__total">{{ $fulfill['center'] }}</div>
                                                            @if($fulfillUrl)
                                                                <a href="{{ $fulfillUrl }}" class="wo-fulfill__caption text-decoration-none" data-tip="{{ $fulfill['label'] }}">{{ Str::limit($fulfill['label'], 16) }}</a>
                                                            @else
                                                                <div class="wo-fulfill__caption" data-tip="{{ $fulfill['label'] }}">{{ Str::limit($fulfill['label'], 16) }}</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <ul class="wo-fulfill__legend list-unstyled mb-0">
                                                        @forelse($fulfill['legend'] as $slice)
                                                            <li>
                                                                <span style="background:{{ $slice['color'] }};"></span>
                                                                {{ $slice['label'] }}
                                                                <em>{{ $slice['value'] }}</em>
                                                            </li>
                                                        @empty
                                                            <li class="text-muted">Brak przypisań</li>
                                                        @endforelse
                                                    </ul>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @else
                                <p class="text-muted small mb-0">Brak zapotrzebowania na role</p>
                            @endif
                        </x-ui.card>
                    </div>

                    <!-- Auta w projekcie -->
                    <div class="col-12">
                        <x-ui.card label="Auta w projekcie">
                            @php
                                $projectVehicles = collect($weekData['vehicles'] ?? []);
                            @endphp
                            @if($projectVehicles->isNotEmpty())
                                <div class="row g-3" data-cl-reveal-items>
                                    @foreach($projectVehicles->take(9) as $vehicleData)
                                        @include('weekly-overview.partials.vehicle-card', [
                                            'vehicleData' => $vehicleData,
                                            'project' => $project,
                                            'preloadedProjectAssignments' => $preloadedProjectAssignments,
                                        ])
                                    @endforeach
                                </div>
                                @if($projectVehicles->count() > 9)
                                    <div class="text-center mt-2">
                                        <x-ui.badge variant="info">+{{ $projectVehicles->count() - 9 }} więcej</x-ui.badge>
                                    </div>
                                @endif
                            @else
                                <p class="text-muted small mb-0">Brak aut w projekcie</p>
                            @endif

                            @php
                                $serviceRepairs = collect($weekData['service_repairs'] ?? []);
                            @endphp
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex align-items-center gap-1 mb-2">
                                    <i class="bi bi-tools text-warning"></i>
                                    <span class="card-label">Pojazdy w serwisie</span>
                                </div>
                                @if($serviceRepairs->isNotEmpty())
                                    <ul class="list-unstyled mb-0 small" data-cl-reveal-items>
                                        @foreach($serviceRepairs as $repairData)
                                            @php
                                                $repair = $repairData['repair'];
                                            @endphp
                                            <li class="mb-2">
                                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                                    @if($repairData['vehicle'])
                                                        <a href="{{ route('vehicles.show', $repairData['vehicle']) }}" class="text-decoration-none fw-semibold">
                                                            <i class="bi bi-car-front"></i> {{ $repairData['vehicle']->registration_number }}
                                                        </a>
                                                    @else
                                                        <span class="fw-semibold">{{ $repairData['vehicle_name'] }}</span>
                                                    @endif
                                                    <a href="{{ route('vehicle-repairs.show', $repair) }}" class="text-decoration-none">
                                                        <x-ui.badge variant="{{ $repair->status_badge_variant }}">{{ $repair->status_label }}</x-ui.badge>
                                                    </a>
                                                </div>
                                                <div class="text-muted">
                                                    {{ $repair->action_type->label() }}
                                                    &middot;
                                                    {{ $repair->start_date->format('d.m') }}@if($repair->end_date) – {{ $repair->end_date->format('d.m') }}@endif
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted small mb-0">Brak pojazdów w serwisie w tym tygodniu</p>
                                @endif
                            </div>
                        </x-ui.card>
                    </div>

                    <!-- Domy w projekcie -->
                    <div class="col-12">
                        <x-ui.card label="Domy w projekcie">
                            @php
                                $projectAccommodations = collect($weekData['accommodations'] ?? []);
                            @endphp
                            @if($projectAccommodations->isNotEmpty())
                                <div class="row g-3" data-cl-reveal-items>
                                    @foreach($projectAccommodations->take(9) as $accommodationData)
                                        @php $accommodation = $accommodationData['accommodation']; @endphp
                                        @include('weekly-overview.partials.accommodation-card', [
                                            'accommodationData' => $accommodationData,
                                            'accommodation' => $accommodation,
                                            'project' => $project,
                                            'preloadedProjectAssignments' => $preloadedProjectAssignments,
                                        ])
                                    @endforeach
                                </div>
                                @if($projectAccommodations->count() > 9)
                                    <div class="text-center mt-2">
                                        <x-ui.badge variant="info">+{{ $projectAccommodations->count() - 9 }} więcej</x-ui.badge>
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
                    
                    @if($hasBraki || $hasNadmiary)
                        <x-ui.card label="Alerty" class="mt-4 mb-4">
                            <div class="row g-3">
                                <!-- Kolumna 1: Braki -->
                                <div class="col-md-6">
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
                                <div class="col-md-6">
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
                            </div>
                        </x-ui.card>
                    @endif
                @endif

                <!-- Tabelka z ludźmi -->
                @php
                    $assignedList = isset($weekData['assigned_employees']) ? $weekData['assigned_employees'] : collect();
                    $assignedCollapseId = 'weekly-assigned-p'.$project->id.'-w'.$weeks[0]['start']->format('Ymd');
                    $assignedEurRates = $assignedList
                        ->map(fn ($row) => $row['hourly_rate'] ?? null)
                        ->filter(fn ($rate) => is_array($rate)
                            && isset($rate['amount'], $rate['currency'])
                            && strtoupper((string) $rate['currency']) === 'EUR');
                    $assignedAvgRateLabel = $assignedEurRates->isNotEmpty()
                        ? \App\Models\EmployeeRate::formatCompactLabel(
                            (float) $assignedEurRates->avg(fn ($rate) => (float) $rate['amount']),
                            'EUR'
                        )
                        : null;
                @endphp
                <div class="mt-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h5 class="mb-0 text-dark">Przypisani pracownicy</h5>
                            @if($assignedList->isNotEmpty())
                                <span class="badge rounded-pill text-bg-secondary">{{ $assignedList->count() }}</span>
                                <span
                                    class="badge rounded-pill wo-assigned-avg-rate {{ $assignedAvgRateLabel ? '' : 'wo-assigned-avg-rate--empty' }}"
                                    title="{{ $assignedAvgRateLabel
                                        ? 'Średnia stawka EUR przypisanych ('.$assignedEurRates->count().'/'.$assignedList->count().' w euro)'
                                        : 'Brak stawek w EUR u przypisanych w tym tygodniu (inne waluty nie wchodzą do średniej)' }}"
                                >
                                    śr. {{ $assignedAvgRateLabel ?? '—' }}
                                </span>
                            @endif
                            <button
                                type="button"
                                class="weekly-overview-assigned-toggle btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1"
                                data-bs-toggle="collapse"
                                data-bs-target="#{{ $assignedCollapseId }}"
                                aria-expanded="true"
                                aria-controls="{{ $assignedCollapseId }}"
                                title="Pokaż lub ukryj listę pracowników"
                            >
                                <i class="bi bi-chevron-down collapse-chevron"></i>
                                <span class="small">Lista</span>
                            </button>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <x-ui.button variant="primary" href="{{ route('project-assignments.create', ['project_id' => $project->id, 'date_from' => $weeks[0]['start']->format('Y-m-d'), 'date_to' => $weeks[0]['end']->format('Y-m-d')]) }}" action="create" class="btn-sm">
                                Przypisz osoby
                            </x-ui.button>
                            <x-ui.button variant="success" href="{{ route('departures.create-v2', ['departure_date' => $weeks[0]['start']->format('Y-m-d'), 'end_date' => $weeks[0]['end']->format('Y-m-d')]) }}" action="create" class="btn-sm">
                                Utwórz wyjazd
                            </x-ui.button>
                        </div>
                    </div>

                    <div class="collapse show" id="{{ $assignedCollapseId }}">
                        @include('weekly-overview.partials.site-lead-panel')
                        @if($assignedList->isNotEmpty())
                        @php
                            $crewRows = $assignedList
                                ->sortBy(function ($row) {
                                    $lead = ($row['is_site_lead'] ?? false) ? '0' : '1';
                                    $name = mb_strtolower($row['employee']->last_name.' '.$row['employee']->first_name);

                                    return $lead.'-'.$name;
                                })
                                ->values();
                        @endphp
                        <div class="table-responsive">
                            <table
                                class="table align-middle weekly-overview-assigned-table"
                                data-cl-reveal-rows
                                aria-label="Przypisani pracownicy"
                            >
                                <thead>
                                    <tr>
                                        <th class="wo-sortable" data-sort-dir="asc">
                                            <button type="button" class="wo-sort-btn">
                                                <span>Pracownik</span>
                                                <i class="bi bi-chevron-up wo-sort-icon"></i>
                                            </button>
                                        </th>
                                        <th class="wo-sortable">
                                            <button type="button" class="wo-sort-btn">
                                                <span>Rola w projekcie</span>
                                                <i class="bi bi-chevron-expand wo-sort-icon text-muted"></i>
                                            </button>
                                        </th>
                                        <th class="wo-sortable">
                                            <button type="button" class="wo-sort-btn">
                                                <span>Pokrycie</span>
                                                <i class="bi bi-chevron-expand wo-sort-icon text-muted"></i>
                                            </button>
                                        </th>
                                        <th class="wo-sortable">
                                            <button type="button" class="wo-sort-btn">
                                                <span>Auto</span>
                                                <i class="bi bi-chevron-expand wo-sort-icon text-muted"></i>
                                            </button>
                                        </th>
                                        <th class="wo-sortable">
                                            <button type="button" class="wo-sort-btn">
                                                <span>Dom</span>
                                                <i class="bi bi-chevron-expand wo-sort-icon text-muted"></i>
                                            </button>
                                        </th>
                                        <th class="wo-sortable">
                                            <button type="button" class="wo-sort-btn">
                                                <span>Do rotacji</span>
                                                <i class="bi bi-chevron-expand wo-sort-icon text-muted"></i>
                                            </button>
                                        </th>
                                        <th class="wo-sortable">
                                            <button type="button" class="wo-sort-btn">
                                                <span>Stawka</span>
                                                <i class="bi bi-chevron-expand wo-sort-icon text-muted"></i>
                                            </button>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($crewRows as $employeeData)
                                        @include('weekly-overview.partials.assigned-employee-row')
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <x-ui.empty-state 
                            icon="people"
                            message="Brak przypisanych pracowników w tym tygodniu."
                        />
                        @endif
                    </div>
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
        <x-ui.card reveal>
            <x-ui.empty-state 
                icon="folder"
                message="Brak projektów do wyświetlenia."
            />
        </x-ui.card>
    @endforelse

    @push('scripts')
    <script>
        (function () {
            const PALETTE = [
                '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                '#06b6d4', '#f97316', '#84cc16', '#ec4899', '#14b8a6',
                '#a855f7', '#64748b', '#0ea5e9', '#d97706', '#16a34a',
            ];

            function mkColor(i) { return PALETTE[i % PALETTE.length]; }
            function mkAlpha(hex, a) {
                const r = parseInt(hex.slice(1, 3), 16);
                const g = parseInt(hex.slice(3, 5), 16);
                const b = parseInt(hex.slice(5, 7), 16);
                return `rgba(${r},${g},${b},${a})`;
            }

            function loadChartJs() {
                return new Promise(resolve => {
                    if (window.Chart) { resolve(); return; }
                    const s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
                    s.onload = resolve;
                    s.onerror = resolve;
                    document.head.appendChild(s);
                });
            }

            function whenVisible(el, callback) {
                if (!el || typeof callback !== 'function') return;
                if (el.dataset.woChartReady === '1') return;
                const run = () => {
                    if (el.dataset.woChartReady === '1') return;
                    el.dataset.woChartReady = '1';
                    callback();
                };
                if (typeof IntersectionObserver === 'undefined') {
                    run();
                    return;
                }
                const io = new IntersectionObserver((entries) => {
                    if (!entries.some((entry) => entry.isIntersecting)) return;
                    io.disconnect();
                    run();
                }, { threshold: 0.2, rootMargin: '0px 0px -6% 0px' });
                io.observe(el);
            }

            function initDemandTrendChart(canvas) {
                if (!window.Chart || !canvas) return;

                let payload = null;
                try {
                    payload = JSON.parse(canvas.dataset.chart || '{}');
                } catch (_) {
                    return;
                }
                const labels = payload.labels || [];
                const needed = payload.needed || [];
                const assigned = payload.assigned || [];
                const projects = payload.projects || [];
                if (!labels.length) return;

                const wrap = canvas.closest('.wo-demand-trend');
                const layerList = wrap ? wrap.querySelector('#wo-demand-trend-layers') : null;
                const gapFill = {
                    target: '-1',
                    above: 'rgba(59, 130, 246, 0.08)',
                    below: 'rgba(168, 85, 247, 0.32)',
                };
                const projectPalette = [
                    '#94a3b8', '#38bdf8', '#a78bfa', '#34d399', '#fbbf24',
                    '#fb7185', '#22d3ee', '#c084fc', '#4ade80', '#f472b6',
                ];

                const layerOn = {
                    needed: true,
                    assigned: true,
                    gap: true,
                };
                projects.forEach((_, i) => { layerOn['project:' + i] = true; });

                let hoverKey = null;
                let hoverPointIndex = null;

                const pointLabelPlugin = {
                    id: 'woDemandTrendLabels',
                    afterDatasetsDraw(chartInstance) {
                        const { ctx } = chartInstance;
                        ctx.save();
                        chartInstance.data.datasets.forEach((dataset, datasetIndex) => {
                            if (dataset.woOn === false) return;
                            const showAll = !!dataset.woPointLabels;
                            const showHover = dataset.woKind === 'project'
                                && hoverKey === dataset.woKey
                                && dataset.woOn !== false;
                            if (!showAll && !showHover) return;

                            const meta = chartInstance.getDatasetMeta(datasetIndex);
                            meta.data.forEach((point, index) => {
                                const value = dataset.data[index];
                                if (value == null || Number.isNaN(value)) return;
                                // Projekty: liczby tylko przy hover (cała seria albo podkreślony punkt).
                                if (showHover && !showAll) {
                                    if (hoverPointIndex != null && index !== hoverPointIndex) return;
                                }
                                const emphasize = showHover && (hoverPointIndex == null || index === hoverPointIndex);
                                const above = dataset.woLabelSide !== 'below';
                                ctx.font = emphasize
                                    ? "700 12px 'JetBrains Mono', 'Space Grotesk', monospace"
                                    : "600 11px 'JetBrains Mono', 'Space Grotesk', monospace";
                                ctx.fillStyle = dataset.woBorderOn || dataset.borderColor || '#fff';
                                ctx.textAlign = 'center';
                                ctx.textBaseline = above ? 'bottom' : 'top';
                                ctx.fillText(String(value), point.x, point.y + (above ? -9 : 9));
                            });
                        });
                        ctx.restore();
                    },
                };

                const projectDatasets = projects.map((project, i) => {
                    const color = projectPalette[i % projectPalette.length];
                    const r = parseInt(color.slice(1, 3), 16);
                    const g = parseInt(color.slice(3, 5), 16);
                    const b = parseInt(color.slice(5, 7), 16);
                    return {
                        label: project.label,
                        data: project.data || [],
                        borderColor: color,
                        backgroundColor: 'transparent',
                        borderWidth: 1.75,
                        pointRadius: 3.5,
                        pointHoverRadius: 6,
                        pointHitRadius: 18,
                        pointBackgroundColor: color,
                        pointBorderColor: '#0b1220',
                        pointBorderWidth: 1.5,
                        tension: 0.25,
                        fill: false,
                        order: 3,
                        woKey: 'project:' + i,
                        woKind: 'project',
                        woOn: true,
                        woLabelSide: 'above',
                        woBorderOn: color,
                        woBorderOff: `rgba(${r},${g},${b},0.14)`,
                        woBorderDim: `rgba(${r},${g},${b},0.28)`,
                    };
                });

                /** Najbliższy punkt w promieniu — puste miejsce nie przełącza „losowej” warstwy. */
                const pickNearest = (event, maxDist = 16) => {
                    const hits = chart.getElementsAtEventForMode(
                        event,
                        'nearest',
                        { intersect: false, axis: 'xy' },
                        false
                    );
                    if (!hits.length || event.x == null || event.y == null) {
                        return { key: null, index: null };
                    }

                    let bestDs = null;
                    let bestIndex = null;
                    let bestDist = Infinity;
                    hits.forEach((hit) => {
                        const ds = chart.data.datasets[hit.datasetIndex];
                        if (!ds?.woKey) return;
                        const el = hit.element;
                        if (!el || el.x == null || el.y == null) return;
                        const dist = Math.hypot(el.x - event.x, el.y - event.y);
                        if (dist > maxDist || dist > bestDist) return;
                        if (dist === bestDist && bestDs && ds.woOn === false && bestDs.woOn !== false) return;
                        // Przy bliskim remisie wolimy projekt (kropki pod sumami).
                        if (
                            dist === bestDist
                            && bestDs
                            && bestDs.woKind !== 'project'
                            && ds.woKind === 'project'
                        ) {
                            bestDs = ds;
                            bestIndex = hit.index;
                            return;
                        }
                        bestDist = dist;
                        bestDs = ds;
                        bestIndex = hit.index;
                    });
                    return { key: bestDs?.woKey || null, index: bestIndex };
                };

                const chart = new Chart(canvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            ...projectDatasets,
                            {
                                label: 'Popyt razem',
                                data: needed,
                                borderColor: '#f1f5f9',
                                backgroundColor: '#f1f5f9',
                                borderWidth: 2.5,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointHitRadius: 14,
                                pointBackgroundColor: '#0b1220',
                                pointBorderColor: '#f1f5f9',
                                pointBorderWidth: 2,
                                tension: 0.28,
                                fill: false,
                                order: 1,
                                woPointLabels: true,
                                woLabelSide: 'above',
                                woKey: 'needed',
                                woKind: 'needed',
                                woOn: true,
                            },
                            {
                                label: 'Przypisani',
                                data: assigned,
                                borderColor: '#60a5fa',
                                backgroundColor: '#60a5fa',
                                borderWidth: 2.25,
                                borderDash: [6, 5],
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointHitRadius: 14,
                                pointBackgroundColor: '#0b1220',
                                pointBorderColor: '#60a5fa',
                                pointBorderWidth: 2,
                                tension: 0.28,
                                fill: gapFill,
                                order: 2,
                                woPointLabels: true,
                                woLabelSide: 'below',
                                woKey: 'assigned',
                                woKind: 'assigned',
                                woOn: true,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        animation: { duration: 450 },
                        onHover(event) {
                            const picked = pickNearest(event, 20);
                            const changed = picked.key !== hoverKey || picked.index !== hoverPointIndex;
                            hoverKey = picked.key;
                            hoverPointIndex = picked.index;
                            if (changed) {
                                syncLayerHover();
                                emphasizeHover();
                            }
                            canvas.style.cursor = picked.key ? 'pointer' : 'crosshair';
                        },
                        onClick(event) {
                            const picked = pickNearest(event, 16);
                            if (!picked.key) return;
                            setLayer(picked.key, !layerOn[picked.key]);
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: { enabled: false },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: {
                                    color: 'rgba(148, 163, 184, 0.85)',
                                    font: { size: 11, family: "'Space Grotesk', system-ui, sans-serif" },
                                },
                            },
                            y: {
                                beginAtZero: true,
                                grace: '12%',
                                grid: { color: 'rgba(148, 163, 184, 0.12)' },
                                ticks: {
                                    color: 'rgba(148, 163, 184, 0.75)',
                                    precision: 0,
                                    font: { size: 11, family: "'JetBrains Mono', monospace" },
                                },
                            },
                        },
                    },
                    plugins: [pointLabelPlugin],
                });

                const buildLayerList = () => {
                    if (!layerList) return;
                    const items = [
                        { key: 'needed', label: 'Popyt razem', swatch: '#f1f5f9', kind: 'line' },
                        { key: 'assigned', label: 'Przypisani', swatch: '#60a5fa', kind: 'dash' },
                        { key: 'gap', label: 'Luka', swatch: 'rgba(168,85,247,0.55)', kind: 'gap' },
                        ...projects.map((project, i) => ({
                            key: 'project:' + i,
                            label: project.label,
                            swatch: projectPalette[i % projectPalette.length],
                            kind: 'line',
                        })),
                    ];
                    layerList.innerHTML = items.map((item) => {
                        const swatchClass = item.kind === 'dash'
                            ? 'wo-demand-trend__layer-swatch--dash'
                            : (item.kind === 'gap'
                                ? 'wo-demand-trend__layer-swatch--gap'
                                : 'wo-demand-trend__layer-swatch--line');
                        const swatchStyle = item.kind === 'dash'
                            ? `border-top-color:${item.swatch};`
                            : `background:${item.swatch};`;
                        const safeId = 'wo-layer-' + String(item.key).replace(/[^a-zA-Z0-9_-]/g, '-');
                        return `
                        <li>
                            <label class="form-check form-check-compact wo-demand-trend__layer" data-wo-layer="${item.key}" for="${safeId}">
                                <input type="checkbox" class="form-check-input" id="${safeId}" data-wo-layer-check="${item.key}" checked>
                                <span class="wo-demand-trend__layer-swatch ${swatchClass}" style="${swatchStyle}" aria-hidden="true"></span>
                                <span class="wo-demand-trend__layer-label" title="${item.label.replace(/"/g, '&quot;')}">${item.label}</span>
                            </label>
                        </li>`;
                    }).join('');

                    layerList.querySelectorAll('[data-wo-layer-check]').forEach((input) => {
                        input.addEventListener('change', () => {
                            setLayer(input.dataset.woLayerCheck, input.checked);
                        });
                    });
                    layerList.querySelectorAll('[data-wo-layer]').forEach((row) => {
                        row.addEventListener('mouseenter', () => {
                            hoverKey = row.dataset.woLayer;
                            hoverPointIndex = null; // cała seria projektu
                            syncLayerHover();
                            emphasizeHover();
                        });
                        row.addEventListener('mouseleave', () => {
                            if (hoverKey === row.dataset.woLayer) {
                                hoverKey = null;
                                hoverPointIndex = null;
                                syncLayerHover();
                                emphasizeHover();
                            }
                        });
                    });
                };

                const syncLayerHover = () => {
                    if (!layerList) return;
                    layerList.querySelectorAll('[data-wo-layer]').forEach((row) => {
                        row.classList.toggle('is-hover', row.dataset.woLayer === hoverKey);
                    });
                };

                const syncLayerChecks = () => {
                    if (!layerList) return;
                    layerList.querySelectorAll('[data-wo-layer]').forEach((row) => {
                        const key = row.dataset.woLayer;
                        const on = !!layerOn[key];
                        row.classList.toggle('is-off', !on);
                        const input = row.querySelector('[data-wo-layer-check]');
                        if (input && input.checked !== on) input.checked = on;
                    });
                };

                const emphasizeHover = () => {
                    const hoveringProject = hoverKey && hoverKey.startsWith('project:');
                    chart.data.datasets.forEach((dataset) => {
                        if (dataset.woKind === 'project') {
                            const on = dataset.woOn !== false;
                            const isHover = hoverKey === dataset.woKey;
                            dataset.borderWidth = !on ? 1 : (isHover ? 3 : 1.75);
                            dataset.pointRadius = !on ? 0 : (isHover ? 5 : 3.5);
                            dataset.pointHoverRadius = isHover ? 7 : 6;
                            if (!on) {
                                dataset.borderColor = dataset.woBorderOff;
                                dataset.pointBackgroundColor = 'transparent';
                                dataset.pointBorderColor = 'transparent';
                            } else if (isHover) {
                                dataset.borderColor = dataset.woBorderOn;
                                dataset.pointBackgroundColor = dataset.woBorderOn;
                                dataset.pointBorderColor = '#0b1220';
                            } else if (hoveringProject) {
                                dataset.borderColor = dataset.woBorderDim;
                                dataset.pointBackgroundColor = dataset.woBorderDim;
                                dataset.pointBorderColor = 'rgba(11, 18, 32, 0.5)';
                            } else {
                                dataset.borderColor = dataset.woBorderOn;
                                dataset.pointBackgroundColor = dataset.woBorderOn;
                                dataset.pointBorderColor = '#0b1220';
                            }
                        } else if (dataset.woKind === 'needed' || dataset.woKind === 'assigned') {
                            const isHover = hoverKey === dataset.woKey;
                            const base = dataset.woKind === 'needed' ? 2.5 : 2.25;
                            dataset.borderWidth = dataset.woOn === false ? 1.5 : (isHover ? 3.25 : base);
                        }
                    });
                    chart.update('none');
                };

                const applyLayers = () => {
                    chart.data.datasets.forEach((dataset) => {
                        if (dataset.woKind === 'needed') {
                            dataset.woOn = layerOn.needed;
                            dataset.borderColor = layerOn.needed ? '#f1f5f9' : 'rgba(241, 245, 249, 0.12)';
                            dataset.pointBorderColor = dataset.borderColor;
                            dataset.pointBackgroundColor = layerOn.needed ? '#0b1220' : 'transparent';
                            dataset.pointRadius = layerOn.needed ? 4 : 0;
                            dataset.backgroundColor = layerOn.needed ? '#f1f5f9' : 'transparent';
                        } else if (dataset.woKind === 'assigned') {
                            dataset.woOn = layerOn.assigned;
                            dataset.borderColor = layerOn.assigned ? '#60a5fa' : 'rgba(96, 165, 250, 0.12)';
                            dataset.pointBorderColor = dataset.borderColor;
                            dataset.pointBackgroundColor = layerOn.assigned ? '#0b1220' : 'transparent';
                            dataset.pointRadius = layerOn.assigned ? 4 : 0;
                            dataset.backgroundColor = layerOn.assigned ? '#60a5fa' : 'transparent';
                            dataset.fill = layerOn.gap && layerOn.needed && layerOn.assigned ? gapFill : false;
                        } else if (dataset.woKind === 'project') {
                            const on = !!layerOn[dataset.woKey];
                            dataset.woOn = on;
                            dataset.borderColor = on ? dataset.woBorderOn : dataset.woBorderOff;
                            dataset.borderWidth = on ? 1.75 : 1;
                            dataset.pointRadius = on ? 3.5 : 0;
                            dataset.pointBackgroundColor = on ? dataset.woBorderOn : 'transparent';
                            dataset.pointBorderColor = on ? '#0b1220' : 'transparent';
                        }
                    });
                    syncLayerChecks();
                    emphasizeHover();
                };

                const setLayer = (key, on) => {
                    if (!(key in layerOn)) return;
                    layerOn[key] = !!on;
                    applyLayers();
                };

                canvas.addEventListener('mouseleave', () => {
                    hoverKey = null;
                    hoverPointIndex = null;
                    syncLayerHover();
                    emphasizeHover();
                });

                buildLayerList();
                applyLayers();
                // Lekki stagger listy warstw — ten sam globalny cl-reveal.
                if (layerList && window.clRevealScan) {
                    delete layerList.dataset.clItemsPrepared;
                    delete layerList.dataset.clItemsDone;
                    window.clRevealScan(wrap || document);
                }
            }

            function initDemandDonut(canvas) {
                if (!window.Chart || !canvas) return;
                const labels = JSON.parse(canvas.dataset.labels || '[]');
                const values = JSON.parse(canvas.dataset.values || '[]');
                const colors = JSON.parse(canvas.dataset.colors || '[]');
                if (!labels.length) return;
                const chartColors = colors.length ? colors : labels.map((_, i) => mkColor(i));
                new Chart(canvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels,
                        datasets: [{
                            data: values,
                            backgroundColor: chartColors.map(c => mkAlpha(c, 0.55)),
                            borderColor: chartColors,
                            borderWidth: 2,
                            hoverOffset: 4,
                        }],
                    },
                    options: {
                        responsive: false,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        animation: { animateRotate: true, duration: 850 },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: ctx => ` ${ctx.label}: ${ctx.raw} potrzebnych`,
                                },
                            },
                        },
                    },
                });
            }

            function initFulfillDonut(canvas) {
                if (!window.Chart || !canvas) return;
                const labels = JSON.parse(canvas.dataset.labels || '[]');
                const values = JSON.parse(canvas.dataset.values || '[]');
                const colors = JSON.parse(canvas.dataset.colors || '[]');
                const gaps = JSON.parse(canvas.dataset.gaps || '[]');
                if (!values.length) return;
                new Chart(canvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels,
                        datasets: [{
                            data: values,
                            backgroundColor: colors.map((c, i) => gaps[i] ? c : mkAlpha(c, 0.7)),
                            borderColor: colors.map((c, i) => gaps[i] ? 'transparent' : c),
                            borderWidth: colors.map((_, i) => gaps[i] ? 0 : 2),
                            hoverOffset: colors.map((_, i) => gaps[i] ? 0 : 4),
                        }],
                    },
                    options: {
                        responsive: false,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        animation: { animateRotate: true, duration: 850 },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                filter: (item) => !gaps[item.dataIndex],
                                callbacks: {
                                    label: ctx => ` ${ctx.label}: ${ctx.raw}`,
                                },
                            },
                        },
                    },
                });
            }

            function initDemandCharts() {
                if (!window.Chart) return;

                Chart.defaults.color = 'rgba(255,255,255,0.45)';
                Chart.defaults.borderColor = 'rgba(255,255,255,0.08)';
                Chart.defaults.font.family = "'Space Grotesk', system-ui, sans-serif";
                Chart.defaults.font.size = 11;

                // Animacje startują dopiero gdy wykres wchodzi w viewport.
                whenVisible(document.getElementById('wo-demand-trend-chart'), () => {
                    initDemandTrendChart(document.getElementById('wo-demand-trend-chart'));
                });
                document.querySelectorAll('.wo-demand-chart').forEach((canvas) => {
                    whenVisible(canvas, () => initDemandDonut(canvas));
                });
                document.querySelectorAll('.wo-fulfill-chart').forEach((canvas) => {
                    whenVisible(canvas, () => initFulfillDonut(canvas));
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });

                loadChartJs().then(initDemandCharts);
            });
        })();
    </script>

    <script>
        (function () {
            function getSortValue(cell) {
                if (cell.dataset && 'sortValue' in cell.dataset) {
                    return cell.dataset.sortValue;
                }
                return (cell.textContent || '').trim();
            }

            function compareValues(a, b) {
                var na = parseFloat(String(a).replace(',', '.'));
                var nb = parseFloat(String(b).replace(',', '.'));
                var numericPattern = /^-?\d+([.,]\d+)?$/;
                var bothNumeric = a !== '' && b !== '' && numericPattern.test(a) && numericPattern.test(b) && !isNaN(na) && !isNaN(nb);
                if (bothNumeric) {
                    return na - nb;
                }
                return String(a).localeCompare(String(b), 'pl', { sensitivity: 'base' });
            }

            function updateIcons(table, activeTh, direction) {
                table.querySelectorAll('th.wo-sortable').forEach(function (th) {
                    th.removeAttribute('data-sort-dir');
                    var icon = th.querySelector('.wo-sort-icon');
                    if (icon) {
                        icon.className = 'bi bi-chevron-expand wo-sort-icon text-muted';
                    }
                });

                activeTh.setAttribute('data-sort-dir', direction);
                var activeIcon = activeTh.querySelector('.wo-sort-icon');
                if (activeIcon) {
                    activeIcon.className = direction === 'asc'
                        ? 'bi bi-chevron-up wo-sort-icon'
                        : 'bi bi-chevron-down wo-sort-icon';
                }
            }

            function sortTable(table, colIndex, direction) {
                var tbody = table.tBodies[0];
                if (!tbody) return;

                var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'))
                    .filter(function (tr) { return tr.cells.length > colIndex; });

                rows.sort(function (r1, r2) {
                    var v1 = getSortValue(r1.cells[colIndex]);
                    var v2 = getSortValue(r2.cells[colIndex]);
                    var cmp = compareValues(v1, v2);
                    return direction === 'asc' ? cmp : -cmp;
                });

                rows.forEach(function (row) { tbody.appendChild(row); });
            }

            document.addEventListener('click', function (e) {
                var th = e.target.closest('th.wo-sortable');
                if (!th) return;

                var table = th.closest('table');
                if (!table) return;

                var colIndex = Array.prototype.indexOf.call(th.parentElement.children, th);
                var nextDir = th.getAttribute('data-sort-dir') === 'asc' ? 'desc' : 'asc';

                sortTable(table, colIndex, nextDir);
                updateIcons(table, th, nextDir);
            });
        })();
    </script>
    @endpush

</x-app-layout>
