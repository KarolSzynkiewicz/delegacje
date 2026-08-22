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
    <x-ui.period-nav>
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
        'transferEvents' => $transferEvents,
        'employeesInFieldCount' => $employeesInFieldCount,
        'employeesInFieldByProject' => $employeesInFieldByProject,
        'expiringItems' => $expiringItems,
        'projectsEndingThisMonth' => $projectsEndingThisMonth,
    ])

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
                            <x-ui.card>
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
                                $totalAssignedMin = $reqSummary['total_assigned_min'] ?? ($reqSummary['total_assigned'] ?? 0);
                                $totalAssignedMax = $reqSummary['total_assigned_max'] ?? ($reqSummary['total_assigned'] ?? 0);
                                $isAssignedStable = $reqSummary['is_stable'] ?? ($totalAssignedMin === $totalAssignedMax);
                                $roleDetails = $reqSummary['role_details'] ?? [];
                                $summary = new \App\ViewModels\WeeklyProjectSummary($weekData);

                                if ($isAssignedStable) {
                                    $centerAssignedLabel = (string) (int) $totalAssignedMax;
                                } else {
                                    $centerAvg = ($totalAssignedMin + $totalAssignedMax) / 2;
                                    $centerAssignedLabel = fmod($centerAvg, 1) === 0.0
                                        ? (string) (int) $centerAvg
                                        : rtrim(rtrim(number_format($centerAvg, 1, '.', ''), '0'), '.');
                                }

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

                                // Gauge data per role (assigned vs needed)
                                $gaugeItems = collect($roleDetails)
                                    ->filter(fn ($rd) => ($rd['needed'] ?? 0) > 0)
                                    ->values()
                                    ->map(function ($rd, $index) use ($demandPalette) {
                                        $needed = (int) ($rd['needed'] ?? 0);
                                        $assigned = $rd['assigned'] ?? null;
                                        $assignedMin = (int) ($rd['assigned_min'] ?? ($assigned ?? 0));
                                        $assignedMax = (int) ($rd['assigned_max'] ?? ($assigned ?? 0));
                                        $isStable = $rd['is_stable'] ?? ($assignedMin === $assignedMax);
                                        $assignedVal = $assigned !== null
                                            ? (float) $assigned
                                            : (float) ($assignedMin + $assignedMax) / 2;
                                        $pct = $needed > 0 ? min(round(($assignedVal / $needed) * 100), 200) : 0;
                                        $color = $pct >= 100 ? '#10b981' : ($pct >= 70 ? '#f59e0b' : '#ef4444');
                                        $role = $rd['role'] ?? null;
                                        $countLabel = $isStable
                                            ? $assignedMax . '/' . $needed
                                            : $assignedMin . '-' . $assignedMax . '/' . $needed;

                                        return [
                                            'label'       => $role?->name ?? '—',
                                            'role_id'     => $role?->id,
                                            'assigned'    => $assignedVal,
                                            'needed'      => $needed,
                                            'count_label' => $countLabel,
                                            'pct'         => $pct,
                                            'color'       => $color,
                                            'bg_color'    => $demandPalette[$index % count($demandPalette)],
                                        ];
                                    });
                            @endphp

                            @if(!empty($demandChartLabels))
                                <div class="row g-4 align-items-start">
                                    {{-- Lewa: donut + legenda --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-center gap-3 flex-wrap">
                                            {{-- Donut --}}
                                            <div style="position:relative;width:180px;height:180px;flex-shrink:0;">
                                                <canvas
                                                    class="wo-demand-chart"
                                                    style="width:100%;height:100%;"
                                                    data-labels='@json($demandChartLabels)'
                                                    data-values='@json($demandChartValues)'
                                                    data-colors='@json($demandChartColors)'
                                                ></canvas>
                                                <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none;z-index:2;line-height:1.2;">
                                                    <div class="fw-bold" style="font-size:1.6rem;">{{ $centerAssignedLabel }}</div>
                                                    <div class="text-muted" style="font-size:0.75rem;">z {{ $totalNeeded }} potrzeb</div>
                                                </div>
                                            </div>
                                            {{-- Legenda --}}
                                            <ul class="list-unstyled mb-0" style="flex:1;min-width:130px;">
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
                                                    <li class="d-flex align-items-center gap-2" style="padding:0.3rem 0;">
                                                        <span style="width:12px;height:12px;border-radius:3px;background:{{ $item['color'] }};flex-shrink:0;display:inline-block;border:1px solid rgba(255,255,255,0.3);"></span>
                                                        @if($legendUrl)
                                                            <a href="{{ $legendUrl }}" class="text-decoration-none small" style="color:var(--text-main);">
                                                                {{ $item['label'] }} <span style="color:var(--text-muted);">{{ $item['value'] }}</span>
                                                            </a>
                                                        @else
                                                            <span class="small">{{ $item['label'] }} <span style="color:var(--text-muted);">{{ $item['value'] }}</span></span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>

                                    {{-- Prawa: Wykonanie (gauge'e na rolę) --}}
                                    <div class="col-12 col-md-6">
                                        <div class="small text-muted fw-semibold mb-3" style="text-transform:uppercase;letter-spacing:.05em;font-size:0.7rem;">Wykonanie</div>
                                        <div class="d-flex flex-wrap gap-3">
                                            @foreach($gaugeItems as $gauge)
                                                @php
                                                    $gaugeUrl = $gauge['role_id']
                                                        ? route('project-assignments.create', [
                                                            'project_id' => $project->id,
                                                            'start_date' => $weeks[0]['start']->format('Y-m-d'),
                                                            'end_date'   => $weeks[0]['end']->format('Y-m-d'),
                                                            'role_id'    => $gauge['role_id'],
                                                        ])
                                                        : null;
                                                    $gaugeLabel = Str::limit($gauge['label'], 12);
                                                @endphp
                                                <div class="text-center" style="width:80px;">
                                                    <div style="position:relative;width:80px;height:80px;margin:0 auto;">
                                                        <canvas
                                                            class="wo-gauge-chart"
                                                            style="width:100%;height:100%;"
                                                            data-pct="{{ $gauge['pct'] }}"
                                                            data-color="{{ $gauge['color'] }}"
                                                        ></canvas>
                                                        <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none;z-index:2;line-height:1.1;">
                                                            <span class="fw-bold" style="font-size:0.82rem;color:{{ $gauge['color'] }};">{{ $gauge['pct'] }}%</span>
                                                        </div>
                                                    </div>
                                                    @if($gaugeUrl)
                                                        <a href="{{ $gaugeUrl }}" class="text-decoration-none d-block mt-1 small text-truncate" style="max-width:80px;color:var(--text-muted);font-size:0.72rem;" title="{{ $gauge['label'] }}">{{ $gaugeLabel }}</a>
                                                    @else
                                                        <div class="small text-muted text-truncate mt-1" style="font-size:0.72rem;" title="{{ $gauge['label'] }}">{{ $gaugeLabel }}</div>
                                                    @endif
                                                    <div class="small fw-semibold mt-1" style="font-size:0.75rem;color:{{ $gauge['color'] }};">{{ $gauge['count_label'] }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <span class="small text-muted">Przypisanych: </span>
                                        <span class="fw-semibold">{{ $centerAssignedLabel }}</span>
                                        <span class="small text-muted"> z {{ $totalNeeded }} potrzeb</span>
                                    </div>
                                    <p class="text-muted small mb-0">Brak zapotrzebowania na role</p>
                                </div>
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
                                <div class="row g-3">
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
                                    <ul class="list-unstyled mb-0 small">
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
                                <div class="row g-3">
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
                @endphp
                <div class="mt-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h5 class="mb-0 text-dark">Przypisani pracownicy</h5>
                            @if($assignedList->isNotEmpty())
                                <span class="badge rounded-pill text-bg-secondary">{{ $assignedList->count() }}</span>
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
                        @if($assignedList->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table align-middle weekly-overview-assigned-table">
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
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($assignedList as $employeeData)
                                        @php
                                            $dateRange = $employeeData['date_range'] ?? 'cały tydzień';
                                            $isFullWeek = ($dateRange === 'cały tydzień' || $dateRange === 'pon-nie');
                                        @endphp
                                        <tr>
                                            <td data-label="Pracownik" data-sort-value="{{ mb_strtolower($employeeData['employee']->last_name.' '.$employeeData['employee']->first_name) }}">
                                                <x-employee-cell :employee="$employeeData['employee']"  />
                                            </td>
                                            <td data-label="Rola w projekcie" data-sort-value="{{ mb_strtolower($employeeData['role']->name ?? '') }}">
                                                @if(isset($employeeData['role_stable']) && !$employeeData['role_stable'])
                                                    <x-ui.badge variant="warning" title="Rola zmienia się w trakcie tygodnia">
                                                        <i class="bi bi-arrow-left-right"></i> Zmienna
                                                    </x-ui.badge>
                                                @elseif(isset($employeeData['assignment']) && $employeeData['assignment'])
                                                    @php
                                                        $assignment = $employeeData['assignment'];
                                                        $assignmentId = is_object($assignment) ? $assignment->id : $assignment;
                                                        $editUrl = route('project-assignments.edit', $assignmentId);
                                                        $roleName = $employeeData['role']->name ?? '-';
                                                        $roleDisplay = Str::limit($roleName, 24);
                                                    @endphp
                                                    <x-ui.clickable-badge variant="accent" :href="$editUrl" title="{{ $roleName }}" class="wo-cell-truncate">
                                                        {{ $roleDisplay }}
                                                    </x-ui.clickable-badge>
                                                @else
                                                    @php
                                                        $roleName = $employeeData['role']->name ?? '-';
                                                        $roleDisplay = Str::limit($roleName, 24);
                                                    @endphp
                                                    <x-ui.badge variant="info" title="{{ $roleName }}" class="wo-cell-truncate">{{ $roleDisplay }}</x-ui.badge>
                                                @endif
                                            </td>
                                            <td class="text-center {{ !$isFullWeek ? 'bg-danger bg-opacity-25' : '' }}" data-label="Pokrycie" data-sort-value="{{ $isFullWeek ? 1 : 0 }}">
                                                <span class="fw-semibold small">{{ $dateRange }}</span>
                                            </td>
                                            <td data-label="Auto" data-sort-value="{{ mb_strtolower($employeeData['vehicle']->registration_number ?? '') }}">
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
                                            <td data-label="Dom" data-sort-value="{{ mb_strtolower($employeeData['accommodation']->name ?? '') }}">
                                                @if(isset($employeeData['accommodation']) && $employeeData['accommodation'])
                                                    @php
                                                        $accommodationName = $employeeData['accommodation']->name;
                                                        $accommodationDisplay = Str::limit($accommodationName, 32);
                                                    @endphp
                                                    <x-ui.clickable-badge variant="info" route="accommodation-assignments.show" :routeParams="['accommodation_assignment' => $employeeData['accommodation_assignment']]" title="{{ $accommodationName }}" class="wo-cell-truncate">
                                                        <i class="bi bi-house"></i> {{ $accommodationDisplay }}
                                                    </x-ui.clickable-badge>
                                                @else
                                                    <x-ui.clickable-badge variant="danger" route="accommodation-assignments.create" :routeParams="['employee_id' => $employeeData['employee']->id, 'date_from' => $weeks[0]['start']->format('Y-m-d'), 'date_to' => $weeks[0]['end']->format('Y-m-d')]">
                                                        <i class="bi bi-x-circle"></i> Brak
                                                    </x-ui.clickable-badge>
                                                @endif
                                            </td>
                                            <td data-label="Do rotacji" data-sort-value="{{ $employeeData['rotation']['days_left'] ?? 999999 }}">
                                                @if(isset($employeeData['rotation']) && $employeeData['rotation'])
                                                    @php
                                                        $rotation = $employeeData['rotation']['rotation'] ?? null;
                                                        $rotationId = $employeeData['rotation']['id'] ?? null;
                                                        $daysLeft = $employeeData['rotation']['days_left'] ?? null;
                                                        $employee = $employeeData['employee'];
                                                    @endphp
                                                    @if($rotation && $daysLeft !== null)
                                                        @if($rotationId)
                                                            <x-ui.clickable-badge variant="warning" route="employees.rotations.edit" :routeParams="['employee' => $employee, 'rotation' => $rotation]">
                                                                <i class="bi bi-arrow-repeat"></i> 
                                                                @if($daysLeft >= 0)
                                                                    {{ $daysLeft }} {{ $daysLeft == 1 ? 'dzień' : 'dni' }}
                                                                @else
                                                                    {{ abs($daysLeft) }} {{ abs($daysLeft) == 1 ? 'dzień' : 'dni' }} temu
                                                                @endif
                                                            </x-ui.clickable-badge>
                                                        @else
                                                            <x-ui.badge variant="warning">
                                                                <i class="bi bi-arrow-repeat"></i> 
                                                                @if($daysLeft >= 0)
                                                                    {{ $daysLeft }} {{ $daysLeft == 1 ? 'dzień' : 'dni' }}
                                                                @else
                                                                    {{ abs($daysLeft) }} {{ abs($daysLeft) == 1 ? 'dzień' : 'dni' }} temu
                                                                @endif
                                                            </x-ui.badge>
                                                        @endif
                                                    @else
                                                        <x-ui.badge variant="warning">
                                                            <i class="bi bi-arrow-repeat"></i> Rotacja
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
        <x-ui.card>
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

            function initDemandCharts() {
                if (!window.Chart) return;

                Chart.defaults.color = 'rgba(255,255,255,0.45)';
                Chart.defaults.borderColor = 'rgba(255,255,255,0.08)';
                Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
                Chart.defaults.font.size = 11;

                // Main demand donut
                document.querySelectorAll('.wo-demand-chart').forEach(canvas => {
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
                });

                // Per-role gauge donuts
                document.querySelectorAll('.wo-gauge-chart').forEach(canvas => {
                    const pct   = parseFloat(canvas.dataset.pct) || 0;
                    const color = canvas.dataset.color || '#10b981';
                    const filled  = Math.min(pct, 100);
                    const rest    = Math.max(0, 100 - filled);
                    new Chart(canvas.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            datasets: [{
                                data: [filled, rest],
                                backgroundColor: [mkAlpha(color, 0.75), 'rgba(255,255,255,0.07)'],
                                borderColor:     [color,                 'transparent'],
                                borderWidth: [2, 0],
                            }],
                        },
                        options: {
                            responsive: false,
                            maintainAspectRatio: false,
                            cutout: '72%',
                            plugins: { legend: { display: false }, tooltip: { enabled: false } },
                            events: [],
                        },
                    });
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
