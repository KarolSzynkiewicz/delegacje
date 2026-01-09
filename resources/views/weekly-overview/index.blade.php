<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 text-dark mb-0">
            Przegląd przydziałów ekip – tygodniowy podgląd
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <!-- Nawigacja między tygodniami -->
            <div class="mb-4 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                @php
                    $currentWeek = $weeks[0];
                    $prevWeekStart = $currentWeek['start']->copy()->subWeek()->startOfWeek();
                    $nextWeekStart = $currentWeek['end']->copy()->addDay()->startOfWeek();
                @endphp
                
                <!-- Przycisk poprzedni tydzień -->
                <a href="{{ route('weekly-overview.index', ['start_date' => $prevWeekStart->format('Y-m-d')]) }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-chevron-left"></i>
                    <span>Poprzedni tydzień</span>
                </a>

                <!-- Aktualny tydzień -->
                <div class="text-center">
                    <h3 class="fs-5 fw-bold text-dark mb-0">
                        Tydzień {{ $currentWeek['number'] }}
                    </h3>
                    <p class="small text-muted mb-0">
                        {{ $currentWeek['start']->format('d.m.Y') }} – {{ $currentWeek['end']->format('d.m.Y') }}
                    </p>
                </div>

                <!-- Przycisk następny tydzień -->
                <a href="{{ route('weekly-overview.index', ['start_date' => $nextWeekStart->format('Y-m-d')]) }}" class="btn btn-primary d-flex align-items-center gap-2">
                    <span>Następny tydzień</span>
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>

            <!-- Tabela tygodniowa -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <colgroup>
                            <col style="width: 25%;">
                            <col style="width: 75%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="text-center fw-bold bg-light border-bottom-2">
                                    Projekt
                                </th>
                                <th class="text-center fw-bold bg-light border-bottom-2">
                                    <div>Tydzień {{ $weeks[0]['number'] }}</div>
                                    <div class="small fw-normal text-muted mt-1">{{ $weeks[0]['label'] }}</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-light bg-opacity-50">
                            @forelse($projects as $projectData)
                                @php
                                    $project = $projectData['project'];
                                @endphp
                                <tr>
                                    <td class="bg-white border-end-2 align-top">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge bg-primary rounded-circle me-2" style="width: 8px; height: 8px; padding: 0;"></span>
                                            <span class="fw-bold text-dark">{{ $project->name }}</span>
                                        </div>
                                        @if($project->location)
                                            <div class="small text-muted mb-3">
                                                <i class="bi bi-geo-alt"></i> {{ $project->location->name }}
                                            </div>
                                        @endif
                                        
                                        @php
                                            $weekData = $projectData['weeks_data'][0] ?? null;
                                            if ($weekData && isset($weekData['assigned_employees'])) {
                                                $employeesWithoutVehicle = $weekData['assigned_employees']->filter(function($employeeData) {
                                                    return empty($employeeData['vehicle']);
                                                });
                                                $employeesWithoutAccommodation = $weekData['assigned_employees']->filter(function($employeeData) {
                                                    return empty($employeeData['accommodation']);
                                                });
                                                $allHaveVehicle = $weekData['assigned_employees']->isNotEmpty() && $employeesWithoutVehicle->isEmpty();
                                                $allHaveAccommodation = $weekData['assigned_employees']->isNotEmpty() && $employeesWithoutAccommodation->isEmpty();
                                                
                                                // Missing and excess roles
                                                $missingRoles = [];
                                                $excessRoles = [];
                                                if (!empty($weekData['requirements_summary']['role_details'])) {
                                                    $missingRoles = array_filter($weekData['requirements_summary']['role_details'], function($roleDetail) {
                                                        return isset($roleDetail['missing']) && $roleDetail['missing'] > 0;
                                                    });
                                                    $excessRoles = array_filter($weekData['requirements_summary']['role_details'], function($roleDetail) {
                                                        return isset($roleDetail['excess']) && $roleDetail['excess'] > 0;
                                                    });
                                                }
                                            } else {
                                                $allHaveVehicle = false;
                                                $allHaveAccommodation = false;
                                                $missingRoles = [];
                                                $excessRoles = [];
                                            }
                                        @endphp
                                        
                                        {{-- Kafelek: Realizacja --}}
                                        @if($weekData && $weekData['has_data'])
                                            @php
                                                $totalNeeded = $weekData['requirements_summary']['total_needed'] ?? 0;
                                                $totalAssigned = $weekData['requirements_summary']['total_assigned'] ?? 0;
                                                $totalMissing = $weekData['requirements_summary']['total_missing'] ?? 0;
                                                $percentage = $totalNeeded > 0 ? round(($totalAssigned / $totalNeeded) * 100) : 0;
                                                
                                                // Kolory dla progress bar
                                                if ($percentage == 100) {
                                                    $progressClass = 'bg-success';
                                                } elseif ($percentage >= 70) {
                                                    $progressClass = 'bg-warning';
                                                } else {
                                                    $progressClass = 'bg-danger';
                                                }
                                                
                                                $textClass = $percentage == 100 ? 'text-success' : ($percentage >= 70 ? 'text-warning' : 'text-danger');
                                            @endphp
                                            <div class="bg-info bg-opacity-10 rounded p-2 border border-info mb-2">
                                                <h5 class="small fw-bold text-dark mb-2">Realizacja</h5>
                                                
                                                {{-- Progress bar z liczbą --}}
                                                <div class="mb-2">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="small fw-bold text-dark">{{ $totalAssigned }}/{{ $totalNeeded }}</span>
                                                        <span class="small fw-semibold {{ $textClass }}">
                                                            {{ $percentage }}%
                                                        </span>
                                                    </div>
                                                    <div class="progress" style="height: 8px;">
                                                        <div class="progress-bar {{ $progressClass }}" role="progressbar" style="width: {{ $percentage }}%" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                </div>
                                                
                                                {{-- Informacje tekstowe --}}
                                                <div>
                                                    @php
                                                        $totalExcess = $weekData['requirements_summary']['total_excess'] ?? 0;
                                                        $hasIssues = $totalMissing > 0 || $totalExcess > 0;
                                                    @endphp
                                                    @if(!$hasIssues)
                                                        <div class="small text-success fw-semibold">
                                                            <i class="bi bi-check-circle"></i> Wszystko OK – pełny skład
                                                        </div>
                                                    @else
                                                        {{-- Braki --}}
                                                        @if(!empty($missingRoles))
                                                            <div class="small text-warning mb-1">
                                                                @foreach($missingRoles as $roleDetail)
                                                                    <div>Za mało {{ Str::lower($roleDetail['role']->name) }}: {{ $roleDetail['missing'] }}</div>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                        {{-- Nadmiary --}}
                                                        @if(!empty($excessRoles))
                                                            <div class="small text-danger">
                                                                @foreach($excessRoles as $roleDetail)
                                                                    <div>Za dużo {{ Str::lower($roleDetail['role']->name) }}: +{{ $roleDetail['excess'] }}</div>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                        
                                        {{-- Kafelek: Domy --}}
                                        @if($weekData && $weekData['assigned_employees']->isNotEmpty())
                                            <div class="bg-success bg-opacity-10 rounded p-2 border border-success mb-2">
                                                <h5 class="small fw-bold text-dark mb-1">Domy</h5>
                                                @if($allHaveAccommodation)
                                                    <div class="small text-success fw-semibold">
                                                        <i class="bi bi-check-circle"></i> Wszyscy mają dom
                                                    </div>
                                                @else
                                                    <div class="small text-warning">
                                                        Brakuje {{ $employeesWithoutAccommodation->count() }} {{ $employeesWithoutAccommodation->count() == 1 ? 'domu' : 'domów' }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                        
                                        {{-- Kafelek: Auta --}}
                                        @if($weekData && $weekData['assigned_employees']->isNotEmpty())
                                            <div class="bg-primary bg-opacity-10 rounded p-2 border border-primary mb-2">
                                                <h5 class="small fw-bold text-dark mb-1">Auta</h5>
                                                @if($allHaveVehicle)
                                                    <div class="small text-success fw-semibold">
                                                        <i class="bi bi-check-circle"></i> Wszyscy mają auto
                                                    </div>
                                                @else
                                                    <div class="small text-warning fw-medium mb-1">
                                                        {{ $employeesWithoutVehicle->count() }} {{ $employeesWithoutVehicle->count() == 1 ? 'osobie brakuje auta' : 'osobom brakuje auta' }}
                                                    </div>
                                                    <div>
                                                        @foreach($employeesWithoutVehicle as $employeeData)
                                                            <div class="d-flex justify-content-between align-items-center small mb-1">
                                                                <span class="text-dark">
                                                                    <a href="{{ route('employees.show', $employeeData['employee']) }}" class="text-primary text-decoration-none">
                                                                        {{ $employeeData['employee']->full_name }}
                                                                    </a>
                                                                </span>
                                                                <a href="{{ route('employees.vehicles.create', ['employee' => $employeeData['employee'], 'date_from' => $weekData['week']['start']->format('Y-m-d'), 'date_to' => $weekData['week']['end']->format('Y-m-d')]) }}" 
                                                                   class="btn btn-sm btn-primary">
                                                                    <i class="bi bi-plus"></i> Przypisz auto
                                                                </a>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="bg-white align-top">
                                        <x-weekly-overview.project-week-tile 
                                            :weekData="$projectData['weeks_data'][0]" 
                                            :project="$project" 
                                        />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center py-5 text-muted">
                                        Brak projektów do wyświetlenia
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sekcje dodatkowe -->
            <div class="mt-4">
                <!-- Kończące się dokumenty -->
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h2 class="fs-3 fw-bold text-dark mb-4 text-center d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-exclamation-triangle text-danger"></i>
                            Kończące się dokumenty i ubezpieczenia
                        </h2>
                        <div class="row g-3">
                            <!-- TODO: Implementacja wyświetlania kończących się dokumentów -->
                            <div class="col-md-4">
                                <div class="card border-start border-danger border-4">
                                    <div class="card-body">
                                        <p class="text-dark fw-medium mb-0">Funkcjonalność w przygotowaniu</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<style>
    .border-end-2 {
        border-right-width: 2px !important;
    }
    
    .border-bottom-2 {
        border-bottom-width: 2px !important;
    }
</style>
