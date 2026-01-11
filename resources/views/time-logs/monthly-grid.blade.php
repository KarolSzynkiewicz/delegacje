<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 text-dark mb-0">
            Ewidencja Godzin – Widok Miesięczny
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <!-- Nawigacja między miesiącami -->
            <div class="mb-4 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <!-- Przycisk poprzedni miesiąc -->
                <a href="{{ route('time-logs.monthly-grid', ['month' => $prevMonth]) }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-chevron-left"></i>
                    <span>Poprzedni miesiąc</span>
                </a>

                <!-- Aktualny miesiąc -->
                <div class="text-center">
                    <h3 class="fs-5 fw-bold text-dark mb-0">
                        {{ $currentDate->locale('pl')->translatedFormat('F Y') }}
                    </h3>
                    <p class="small text-muted mb-0">
                        {{ $monthStart->format('d.m.Y') }} – {{ $monthEnd->format('d.m.Y') }}
                    </p>
                </div>

                <!-- Przycisk następny miesiąc -->
                <a href="{{ route('time-logs.monthly-grid', ['month' => $nextMonth]) }}" class="btn btn-primary d-flex align-items-center gap-2">
                    <span>Następny miesiąc</span>
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>

            <!-- Komunikaty -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Formularz do zapisu -->
            <form method="POST" action="{{ route('time-logs.bulk-update') }}" id="timeLogsForm">
                @csrf
                
                <!-- Przycisk zapisu -->
                <div class="mb-3 d-flex justify-content-end">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save"></i> Zapisz zmiany
                    </button>
                </div>

                <!-- Tabela miesięczna -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="table-responsive" style="max-height: 80vh; overflow-y: auto;">
                        <table class="table table-bordered mb-0" id="timeLogsGrid">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th class="text-center fw-bold bg-light border-bottom-2" style="min-width: 200px; position: sticky; left: 0; z-index: 10;">
                                    Projekt / Osoba
                                </th>
                                @foreach($days as $day)
                                    <th class="text-center fw-bold bg-light border-bottom-2 {{ $day['isWeekend'] ? 'bg-warning bg-opacity-25' : '' }}" style="min-width: 60px;">
                                        <div>{{ $day['number'] }}</div>
                                        <div class="small fw-normal text-muted">{{ $day['date']->format('D') }}</div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projectsData as $projectData)
                                @php
                                    $project = $projectData['project'];
                                    $assignments = $projectData['assignments'];
                                @endphp
                                
                                <!-- Nagłówek projektu -->
                                <tr class="bg-primary bg-opacity-10">
                                    <td class="fw-bold border-end-2" style="position: sticky; left: 0; z-index: 5; background-color: inherit;">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-primary rounded-circle me-2" style="width: 8px; height: 8px; padding: 0;"></span>
                                            <span>{{ $project->name }}</span>
                                        </div>
                                        @if($project->location)
                                            <div class="small text-muted">
                                                <i class="bi bi-geo-alt"></i> {{ $project->location->name }}
                                            </div>
                                        @endif
                                    </td>
                                    @foreach($days as $day)
                                        <td class="text-center {{ $day['isWeekend'] ? 'bg-warning bg-opacity-25' : '' }}"></td>
                                    @endforeach
                                </tr>

                                <!-- Osoby w projekcie -->
                                @foreach($assignments as $assignmentData)
                                    @php
                                        $employee = $assignmentData['employee'];
                                        $timeLogs = $assignmentData['timeLogs'];
                                        $daysInAssignment = $assignmentData['daysInAssignment'];
                                    @endphp
                                    <tr>
                                        <td class="ps-4 border-end-2" style="position: sticky; left: 0; z-index: 5; background-color: white;">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-person me-2"></i>
                                                <span>{{ $employee->first_name }} {{ $employee->last_name }}</span>
                                            </div>
                                        </td>
                                        @foreach($days as $day)
                                            @php
                                                $dayNumber = $day['number'];
                                                $date = $day['date'];
                                                $isInAssignment = isset($daysInAssignment[$dayNumber]);
                                                $timeLog = $timeLogs[$dayNumber] ?? null;
                                                $hours = $timeLog['hours'] ?? '';
                                                
                                                // Find assignment for this day
                                                $assignmentId = null;
                                                foreach ($assignmentData['assignments'] as $ass) {
                                                    $assStart = Carbon\Carbon::parse($ass->start_date);
                                                    $assEnd = $ass->end_date ? Carbon\Carbon::parse($ass->end_date) : $monthEnd;
                                                    if ($date->between($assStart, $assEnd)) {
                                                        $assignmentId = $ass->id;
                                                        break;
                                                    }
                                                }
                                                
                                                // If we have a time log, use its assignment
                                                if ($timeLog && isset($timeLog['assignment_id'])) {
                                                    $assignmentId = $timeLog['assignment_id'];
                                                }
                                                
                                                // If no assignment found, use first one as fallback
                                                if (!$assignmentId && !empty($assignmentData['assignments'])) {
                                                    $assignmentId = $assignmentData['assignments'][0]->id;
                                                }
                                            @endphp
                                            <td class="p-0 {{ $day['isWeekend'] ? 'bg-warning bg-opacity-25' : '' }} {{ !$isInAssignment ? 'bg-secondary' : '' }}">
                                                @if($isInAssignment && $assignmentId)
                                                    <input 
                                                        type="hidden" 
                                                        name="entries[{{ $assignmentId }}_{{ $date->format('Y-m-d') }}][assignment_id]" 
                                                        value="{{ $assignmentId }}"
                                                    >
                                                    <input 
                                                        type="hidden" 
                                                        name="entries[{{ $assignmentId }}_{{ $date->format('Y-m-d') }}][date]" 
                                                        value="{{ $date->format('Y-m-d') }}"
                                                    >
                                                    <input 
                                                        type="number" 
                                                        name="entries[{{ $assignmentId }}_{{ $date->format('Y-m-d') }}][hours]"
                                                        class="form-control form-control-sm text-center time-input border-0" 
                                                        value="{{ $hours }}"
                                                        min="0" 
                                                        max="24" 
                                                        step="0.5"
                                                        placeholder="0"
                                                        style="background-color: white;"
                                                    >
                                                @else
                                                    <input 
                                                        type="number" 
                                                        class="form-control form-control-sm text-center time-input border-0" 
                                                        value=""
                                                        readonly
                                                        tabindex="-1"
                                                        style="background-color: #adb5bd !important; color: #495057 !important; cursor: not-allowed;"
                                                    >
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="{{ count($days) + 1 }}" class="text-center py-5 text-muted">
                                        Brak projektów z przypisaniami w tym miesiącu
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            </form>

            <!-- Legenda -->
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Legenda:</h5>
                    <div class="d-flex flex-wrap gap-4">
                        <div class="d-flex align-items-center gap-2">
                            <div style="width: 30px; height: 20px; border: 1px solid #dee2e6; background-color: #adb5bd;"></div>
                            <span class="small">Brak przypisania / poza okresem</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-warning bg-opacity-25" style="width: 30px; height: 20px; border: 1px solid #dee2e6;"></div>
                            <span class="small">Weekend</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
