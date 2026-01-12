<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <!-- Przycisk poprzedni miesiąc -->
            <x-ui.button variant="ghost" href="{{ route('time-logs.monthly-grid', ['month' => $prevMonth]) }}" class="btn-sm">
                <i class="bi bi-chevron-left"></i>
                <span>Poprzedni miesiąc</span>
            </x-ui.button>

            <h2 class="fw-semibold fs-4 mb-0">
                Ewidencja Godzin – Widok Miesięczny
            </h2>

            <!-- Przycisk następny miesiąc -->
            <x-ui.button variant="primary" href="{{ route('time-logs.monthly-grid', ['month' => $nextMonth]) }}" class="btn-sm">
                <span>Następny miesiąc</span>
                <i class="bi bi-chevron-right"></i>
            </x-ui.button>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <!-- Informacje o miesiącu - pod headerem -->
            <div class="text-center mb-4">
                <div class="fw-bold mb-1">
                    {{ $currentDate->locale('pl')->translatedFormat('F Y') }}
                </div>
                <div class="small text-muted">
                    {{ $monthStart->format('d.m.Y') }} – {{ $monthEnd->format('d.m.Y') }}
                </div>
            </div>

            <!-- Komunikaty -->
            @if(session('success'))
                <x-ui.alert variant="success" title="Sukces" class="mb-3">
                    {{ session('success') }}
                </x-ui.alert>
            @endif

            @if(session('error'))
                <x-ui.alert variant="danger" title="Błąd" class="mb-3">
                    {{ session('error') }}
                </x-ui.alert>
            @endif

            @if($errors->any())
                <x-ui.alert variant="danger" title="Błędy walidacji" class="mb-3">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-ui.alert>
            @endif

            <!-- Formularz do zapisu -->
            <form method="POST" action="{{ route('time-logs.bulk-update') }}" id="timeLogsForm">
                @csrf

                <!-- Tabela miesięczna -->
                <x-ui.card class="mb-4">
                    <div class="table-responsive" style="max-height: 80vh; overflow-y: auto;">
                        <table class="table mb-0" id="timeLogsGrid">
                        <thead class="sticky-top">
                            <tr>
                                <th class="text-center fw-bold" style="min-width: 200px; position: sticky; left: 0; z-index: 10; background: var(--bg-card);">
                                    Projekt / Osoba
                                </th>
                                @foreach($days as $day)
                                    <th class="text-center fw-bold {{ $day['isWeekend'] ? 'weekend-header' : '' }}" style="min-width: 60px; background: var(--bg-card);">
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
                                <tr class="project-header-row">
                                    <td class="fw-bold border-end-2" style="position: sticky; left: 0; z-index: 5; background-color: inherit;">
                                        <div class="d-flex align-items-center">
                                            <span class="project-dot me-2"></span>
                                            <span>{{ $project->name }}</span>
                                        </div>
                                        @if($project->location)
                                            <div class="small text-muted">
                                                <i class="bi bi-geo-alt"></i> {{ $project->location->name }}
                                            </div>
                                        @endif
                                    </td>
                                    @foreach($days as $day)
                                        <td class="text-center {{ $day['isWeekend'] ? 'weekend-cell' : '' }}"></td>
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
                                        <td class="ps-4 border-end-2" style="position: sticky; left: 0; z-index: 5; background: var(--bg-card);">
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
                                                
                                                // If we have a time log, use its assignment (even if assignment was deleted)
                                                if ($timeLog && isset($timeLog['assignment_id'])) {
                                                    $assignmentId = $timeLog['assignment_id'];
                                                    
                                                    // Check if this assignment still exists in active assignments
                                                    $assignmentExists = false;
                                                    foreach ($assignmentData['assignments'] as $ass) {
                                                        if ($ass->id == $assignmentId) {
                                                            $assignmentExists = true;
                                                            break;
                                                        }
                                                    }
                                                    
                                                    // If assignment doesn't exist, we still show hours but field is disabled
                                                    if (!$assignmentExists) {
                                                        $isInAssignment = false; // This will make field disabled
                                                    }
                                                }
                                                
                                                // If no assignment from time log, find assignment for this day
                                                if (!$assignmentId) {
                                                    foreach ($assignmentData['assignments'] as $ass) {
                                                        $assStart = Carbon\Carbon::parse($ass->start_date);
                                                        $assEnd = $ass->end_date ? Carbon\Carbon::parse($ass->end_date) : $monthEnd;
                                                        if ($date->between($assStart, $assEnd)) {
                                                            $assignmentId = $ass->id;
                                                            break;
                                                        }
                                                    }
                                                }
                                                
                                                // If no assignment found, use first one as fallback (but only if we're in assignment period)
                                                if (!$assignmentId && !empty($assignmentData['assignments']) && $isInAssignment) {
                                                    $assignmentId = $assignmentData['assignments'][0]->id;
                                                }
                                            @endphp
                                            <td class="p-0 {{ $day['isWeekend'] ? 'weekend-cell' : '' }} {{ !$isInAssignment ? 'disabled-cell' : '' }}">
                                                @if($isInAssignment && $assignmentId)
                                                    {{-- Aktywne przypisanie - pole edytowalne --}}
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
                                                        class="form-control form-control-sm text-center time-input" 
                                                        value="{{ $hours }}"
                                                        min="0" 
                                                        max="24" 
                                                        step="0.5"
                                                        placeholder="0"
                                                    >
                                                @elseif($hours && $timeLog && isset($timeLog['assignment_id']))
                                                    {{-- Godziny zaksiegowane, ale przypisanie usunięte - pole zablokowane z wartością --}}
                                                    <input 
                                                        type="number" 
                                                        class="form-control form-control-sm text-center time-input disabled-input" 
                                                        value="{{ $hours }}"
                                                        readonly
                                                        tabindex="-1"
                                                        title="Przypisanie zostało usunięte, ale godziny są zaksiegowane"
                                                    >
                                                @else
                                                    {{-- Brak przypisania i brak godzin - pole puste i zablokowane --}}
                                                    <input 
                                                        type="number" 
                                                        class="form-control form-control-sm text-center time-input disabled-input" 
                                                        value=""
                                                        readonly
                                                        tabindex="-1"
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
                </x-ui.card>
                
                <!-- Przycisk zapisu - pod kartą tabelki, z prawej -->
                <div class="d-flex justify-content-end mb-4">
                    <x-ui.button variant="primary" type="submit">
                        <i class="bi bi-check-lg"></i> Zapisz zmiany
                    </x-ui.button>
                </div>
            </form>

            <!-- Legenda -->
            <x-ui.card>
                <h5 class="fw-bold mb-3">Legenda:</h5>
                <div class="d-flex flex-wrap gap-4">
                    <div class="d-flex align-items-center gap-2">
                        <div class="legend-disabled" style="width: 30px; height: 20px;"></div>
                        <span class="small">Brak przypisania / poza okresem</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="legend-weekend" style="width: 30px; height: 20px;"></div>
                        <span class="small">Weekend</span>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>

</x-app-layout>
