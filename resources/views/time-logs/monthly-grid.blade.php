<x-app-layout :edge-to-edge="true">
    {{-- Tylko ten widok: pełna szerokość ekranu (wyjście poza ewentualny wąski main). Bez zmian globalnego app.css. --}}
    <style>
        /* Mobile-first: bez 100vw (unikamy poziomego scrolla całej strony), tabela przewijana wewnątrz karty */
        .time-logs-monthly-grid-root {
            width: 100%;
            max-width: 100%;
            margin-left: 0;
            margin-right: 0;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
            box-sizing: border-box;
        }
        .time-logs-monthly-grid-root .monthly-grid-table-card {
            width: 100%;
            max-width: 100%;
        }
        .time-logs-monthly-grid-root .monthly-grid-table-scroll {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: visible;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-x: contain;
            scrollbar-gutter: stable;
        }
        .time-logs-monthly-grid-root #timeLogsGrid {
            border-collapse: collapse;
            table-layout: auto;
            width: max-content;
            min-width: 100%;
        }
        .time-logs-monthly-grid-root #timeLogsGrid colgroup col {
            width: auto;
        }
        .time-logs-monthly-grid-root #timeLogsGrid th:first-child,
        .time-logs-monthly-grid-root #timeLogsGrid td:first-child {
            width: min(38vw, 152px);
            min-width: min(38vw, 152px);
            max-width: min(38vw, 152px);
            box-sizing: border-box;
            overflow: hidden;
            font-size: 0.8rem;
        }
        .time-logs-monthly-grid-root #timeLogsGrid thead th:first-child {
            position: sticky;
            left: 0;
            z-index: 12;
            background: var(--bg-card) !important;
            box-shadow: 6px 0 12px -6px rgba(0, 0, 0, 0.45);
        }
        .time-logs-monthly-grid-root #timeLogsGrid tbody td:first-child {
            position: sticky;
            left: 0;
            z-index: 6;
            background: var(--bg-card) !important;
            box-shadow: 6px 0 12px -6px rgba(0, 0, 0, 0.35);
        }
        .time-logs-monthly-grid-root #timeLogsGrid .project-header-row td:first-child {
            background: rgba(59, 130, 246, 0.1) !important;
            z-index: 7;
            word-break: break-word;
        }
        .time-logs-monthly-grid-root #timeLogsGrid thead th:not(:first-child),
        .time-logs-monthly-grid-root #timeLogsGrid tbody td:not(:first-child) {
            min-width: 46px;
            width: 46px;
            max-width: 46px;
            padding: 2px 3px !important;
            vertical-align: middle;
        }
        .time-logs-monthly-grid-root #timeLogsGrid thead th:not(:first-child) {
            font-size: 0.65rem;
            line-height: 1.15;
            padding-top: 5px !important;
            padding-bottom: 5px !important;
        }
        .time-logs-monthly-grid-root #timeLogsGrid .monthly-grid-day-dow {
            font-size: 0.55rem;
            opacity: 0.85;
        }
        .time-logs-monthly-grid-root #timeLogsGrid input.time-input,
        .time-logs-monthly-grid-root #timeLogsGrid input.disabled-input {
            padding: 4px 2px !important;
            font-size: 0.8rem;
            min-width: 0 !important;
            min-height: 40px;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }
        .time-logs-monthly-grid-root #timeLogsGrid tbody td.ps-4 {
            padding-left: 0.5rem !important;
        }
        .time-logs-monthly-grid-root .monthly-grid-project-select {
            max-width: 100%;
        }

        @media (min-width: 768px) {
            .time-logs-monthly-grid-root {
                width: 100vw;
                max-width: 100vw;
                margin-left: calc(50% - 50vw);
                margin-right: calc(50% - 50vw);
                padding-left: 1rem;
                padding-right: 1rem;
            }
            .time-logs-monthly-grid-root .monthly-grid-table-card {
                max-width: none;
            }
            /*
              table-layout: fixed + stała pierwsza kolumna: bez tego pierwsza kolumna pochłaniała całą szerokość,
              a wąskie kolumny dni były ścinane do paska po prawej.
            */
            .time-logs-monthly-grid-root #timeLogsGrid {
                table-layout: fixed;
                width: 100%;
                min-width: 0;
            }
            .time-logs-monthly-grid-root #timeLogsGrid colgroup col:first-child {
                width: 260px;
            }
            .time-logs-monthly-grid-root #timeLogsGrid th:first-child,
            .time-logs-monthly-grid-root #timeLogsGrid td:first-child {
                width: 260px;
                min-width: 260px;
                max-width: 260px;
                font-size: inherit;
            }
            .time-logs-monthly-grid-root #timeLogsGrid thead th:not(:first-child),
            .time-logs-monthly-grid-root #timeLogsGrid tbody td:not(:first-child) {
                min-width: 0;
                width: auto;
                max-width: none;
                padding: 3px 4px !important;
            }
            .time-logs-monthly-grid-root #timeLogsGrid thead th:not(:first-child) {
                font-size: 0.7rem;
                line-height: 1.2;
                padding-top: 6px !important;
                padding-bottom: 6px !important;
            }
            .time-logs-monthly-grid-root #timeLogsGrid .monthly-grid-day-dow {
                font-size: 0.6rem;
            }
            .time-logs-monthly-grid-root #timeLogsGrid input.time-input,
            .time-logs-monthly-grid-root #timeLogsGrid input.disabled-input {
                padding: 3px 4px !important;
                font-size: 0.75rem;
                min-height: 0;
            }
            .time-logs-monthly-grid-root #timeLogsGrid tbody td.ps-4 {
                padding-left: 1.5rem !important;
            }
            .time-logs-monthly-grid-root .monthly-grid-project-select {
                min-width: 280px;
                max-width: min(420px, 100%);
            }
        }
    </style>

    <x-slot name="header">
        @php
            $monthlyGridRoute = isset($isMineRoute) && $isMineRoute ? 'mine.time-logs.monthly-grid' : 'time-logs.monthly-grid';
            $selectedProjectParam = isset($selectedProjectId) && $selectedProjectId ? ['project_id' => $selectedProjectId] : [];
            $selectedUserParam = isset($userPage) && $userPage ? ['user_page' => $userPage] : [];
        @endphp

        @if(!($isMineRoute ?? false))
            <x-ui.period-nav class="mb-0">
                <x-slot name="prev">
                    <x-ui.button variant="ghost" href="{{ route($monthlyGridRoute, array_merge(['month' => $prevMonth], $selectedProjectParam, $selectedUserParam)) }}" class="btn-sm w-100">
                        <i class="bi bi-chevron-left"></i>
                        <span>Poprzedni miesiąc</span>
                    </x-ui.button>
                </x-slot>
                <h2 class="fw-semibold fs-4 mb-0">
                    Ewidencja Godzin – Widok Miesięczny
                </h2>
                <x-slot name="next">
                    <x-ui.button variant="primary" href="{{ route($monthlyGridRoute, array_merge(['month' => $nextMonth], $selectedProjectParam, $selectedUserParam)) }}" class="btn-sm w-100">
                        <span>Następny miesiąc</span>
                        <i class="bi bi-chevron-right"></i>
                    </x-ui.button>
                </x-slot>
            </x-ui.period-nav>
        @else
            <div class="text-center mb-0">
                <h2 class="fw-semibold fs-4 mb-0">
                    Ewidencja Godzin Zespołu – Widok Miesięczny
                </h2>
            </div>
        @endif
    </x-slot>

    <div class="time-logs-monthly-grid-root">
    <div class="py-2">
                <!-- Wybór projektu -->
                @php
                    $selectedProjectParamBody = isset($selectedProjectId) && $selectedProjectId ? ['project_id' => $selectedProjectId] : [];
                    $selectedUserParamBody = isset($userPage) && $userPage ? ['user_page' => $userPage] : [];
                @endphp

                @if(isset($availableProjects) && $availableProjects && count($availableProjects) > 0)
                    <div class="mb-4 d-flex justify-content-stretch justify-content-md-end gap-3 align-items-stretch align-items-md-center flex-column flex-md-row">
                        <div class="text-center text-md-end">
                            <div class="small text-muted mb-1">Projekt</div>
                            <div class="fw-semibold">
                                @if($selectedProjectId)
                                    Wybór: {{ $availableProjects->firstWhere('id', $selectedProjectId)->name ?? '-' }}
                                @else
                                    Wybór: -
                                @endif
                            </div>
                        </div>
                        <select
                            class="form-select monthly-grid-project-select w-100"
                            onchange="if(this.value) { window.location.href = this.value; }"
                        >
                            @foreach($availableProjects as $project)
                                @php
                                    $href = route($monthlyGridRoute, array_merge(['month' => $currentDate->format('Y-m'), 'project_id' => $project->id], $selectedUserParamBody));
                                @endphp
                                <option
                                    value="{{ $href }}"
                                    {{ ($selectedProjectId ?? null) === $project->id ? 'selected' : '' }}
                                >
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Paginacja: 10 userów na stronę -->
                @php
                    $userPerPage = isset($userPerPage) ? (int) $userPerPage : 10;
                    $userPage = isset($userPage) ? (int) $userPage : 1;
                    if ($userPerPage < 1) $userPerPage = 10;
                    if ($userPage < 1) $userPage = 1;
                    $userOffset = ($userPage - 1) * $userPerPage;

                    $totalUsersForPagination = 0;
                    if (!empty($projectsData) && isset($projectsData[0]['assignments'])) {
                        $totalUsersForPagination = count($projectsData[0]['assignments']);
                    }
                    $totalUserPages = $userPerPage > 0 ? (int) ceil($totalUsersForPagination / $userPerPage) : 1;
                    if ($totalUserPages < 1) $totalUserPages = 1;
                @endphp

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

            @if(session(key: 'error'))
                <x-ui.alert variant="danger" title="Błąd" class="mb-3">
                    <div class="mb-2">{{ session('error') }}</div>
                    @if(session('bulkErrors'))
                        <div class="mt-3">
                            <strong>Szczegóły błędów:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach(session('bulkErrors') as $error)
                                    <li>
                                        <strong>Data {{ $error['date'] ?? 'nieznana' }}:</strong> 
                                        {{ $error['message'] ?? 'Nieznany błąd' }}
                                        @if(isset($error['assignment_id']))
                                            (Assignment ID: {{ $error['assignment_id'] }})
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
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
                <div class="card mb-4 monthly-grid-table-card">
                    <p class="d-md-none small text-muted mb-0 px-3 pt-3 pb-2 border-bottom border-secondary border-opacity-10">
                        <i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>
                        Przewiń tabelę w poziomie, aby zobaczyć wszystkie dni miesiąca.
                    </p>
                    <div class="table-responsive monthly-grid-table-scroll">
                        <table class="table mb-0" id="timeLogsGrid">
                        <colgroup>
                            <col>
                            @foreach($days as $_day)
                                <col>
                            @endforeach
                        </colgroup>
                        <thead class="sticky-top">
                            <tr>
                                <th class="text-center fw-bold">
                                    Projekt / Osoba
                                </th>
                                @foreach($days as $day)
                                    @php
                                        $isToday = $day['date']->isToday();
                                        $dayTitle = $day['date']->locale('pl')->translatedFormat('l, j F Y');
                                    @endphp
                                    <th
                                        class="text-center fw-bold {{ $day['isWeekend'] ? 'weekend-header' : '' }} {{ $isToday ? 'today-header' : '' }}"
                                        title="{{ $dayTitle }}"
                                    >
                                        <div class="monthly-grid-day-num">{{ $day['number'] }}</div>
                                        <div class="monthly-grid-day-dow text-muted">{{ $day['date']->format('D') }}</div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projectsData as $projectData)
                                @php
                                    $project = $projectData['project'];
                                    $assignmentsAll = $projectData['assignments'];
                                    $assignments = array_slice($assignmentsAll, $userOffset, $userPerPage);
                                @endphp
                                
                                <!-- Nagłówek projektu -->
                                <tr class="project-header-row">
                                    <td class="fw-bold border-end-2">
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
                                        @php
                                            $isToday = $day['date']->isToday();
                                        @endphp
                                        <td class="text-center {{ $day['isWeekend'] ? 'weekend-cell' : '' }} {{ $isToday ? 'today-cell' : '' }}"></td>
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
                                        <td class="ps-4 border-end-2">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-person me-2"></i>
                                                <span>{{ $employee->last_name }}, {{ $employee->first_name }}</span>
                                            </div>
                                        </td>
                                        @foreach($days as $day)
                                            @php
                                                $dayNumber = $day['number'];
                                                $date = $day['date'];
                                                $isToday = $date->isToday();
                                                $isInAssignment = isset($daysInAssignment[$dayNumber]);
                                                $timeLog = $timeLogs[$dayNumber] ?? null;
                                                $hours = $timeLog['hours'] ?? '';

                                                // UI: wyświetlaj godziny jako `H:MM` zamiast `5.75`.
                                                // Wymuszamy skoki co 15 minut (zaokrąglenie do najbliższego kwadransa).
                                                $hoursText = '';
                                                if ($hours !== '' && $hours !== null) {
                                                    $hoursFloat = is_numeric($hours) ? (float) $hours : (float) str_replace(',', '.', (string) $hours);
                                                    $totalMinutes = (int) round($hoursFloat * 60);
                                                    $roundedMinutes = (int) (round($totalMinutes / 15) * 15);
                                                    if ($roundedMinutes < 0) {
                                                        $roundedMinutes = 0;
                                                    }
                                                    $hPart = intdiv($roundedMinutes, 60);
                                                    $mPart = $roundedMinutes % 60;
                                                    $hoursText = $roundedMinutes === 0 ? '' : ($hPart . ':' . str_pad((string) $mPart, 2, '0', STR_PAD_LEFT));
                                                }
                                                
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
                                                        $assStart = Carbon\Carbon::parse($ass->start_date)->startOfDay();
                                                        $assEnd = $ass->end_date ? Carbon\Carbon::parse($ass->end_date)->startOfDay() : $monthEnd->startOfDay();
                                                        $dateDay = $date->copy()->startOfDay();
                                                        // Check if date is within assignment period (inclusive - both start and end dates are allowed)
                                                        if ($dateDay->gte($assStart) && $dateDay->lte($assEnd)) {
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
                                            <td class="p-0 {{ $day['isWeekend'] ? 'weekend-cell' : '' }} {{ $isToday ? 'today-cell' : '' }} {{ !$isInAssignment ? 'disabled-cell' : '' }}">
                                                @if($isInAssignment && $assignmentId)
                                                    {{-- Aktywne przypisanie - pole edytowalne.
                                                         Uwaga: trzymamy tylko 1 input na komórkę, żeby nie przekraczać `max_input_vars` na dużych siatkach. --}}
                                                    <input
                                                        type="text"
                                                        name="entries[{{ $assignmentId }}][{{ $date->format('Y-m-d') }}]"
                                                        class="form-control form-control-sm text-center time-input"
                                                        value="{{ $hoursText }}"
                                                        inputmode="numeric"
                                                        placeholder=""
                                                    >
                                                @elseif($hours && $timeLog && isset($timeLog['assignment_id']))
                                                    {{-- Godziny zaksiegowane, ale przypisanie usunięte - pole zablokowane z wartością --}}
                                                    <input 
                                                        type="text"
                                                        class="form-control form-control-sm text-center time-input disabled-input" 
                                                        value="{{ $hoursText }}"
                                                        readonly
                                                        tabindex="-1"
                                                        title="Przypisanie zostało usunięte, ale godziny są zaksiegowane"
                                                    >
                                                @else
                                                    {{-- Brak przypisania i brak godzin - pole puste i zablokowane --}}
                                                    <input 
                                                        type="text"
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
                </div>

                @if(!empty($projectsData) && $totalUsersForPagination > 0 && $totalUserPages > 1)
                    <div class="mb-3 d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-between gap-3">
                        <div class="small text-muted text-center text-sm-start">
                            Strona <span class="fw-semibold">{{ $userPage }}</span> z <span class="fw-semibold">{{ $totalUserPages }}</span> (użytkownicy {{ $userOffset + 1 }}–{{ min($userOffset + $userPerPage, $totalUsersForPagination) }})
                        </div>
                        <div class="d-flex gap-2 justify-content-center justify-content-sm-end flex-wrap">
                            @if($userPage > 1)
                                <x-ui.button
                                    variant="ghost"
                                    class="btn-sm"
                                    href="{{ route($monthlyGridRoute, array_merge(['month' => $currentDate->format('Y-m')], $selectedProjectParamBody, ['user_page' => max(1, $userPage - 1)])) }}"
                                >
                                    <i class="bi bi-chevron-left"></i> Poprzedni
                                </x-ui.button>
                            @else
                                <x-ui.button variant="ghost" class="btn-sm" disabled>
                                    <i class="bi bi-chevron-left"></i> Poprzedni
                                </x-ui.button>
                            @endif

                            @if($userPage < $totalUserPages)
                                <x-ui.button
                                    variant="primary"
                                    class="btn-sm"
                                    href="{{ route($monthlyGridRoute, array_merge(['month' => $currentDate->format('Y-m')], $selectedProjectParamBody, ['user_page' => min($totalUserPages, $userPage + 1)])) }}"
                                >
                                    Następny <i class="bi bi-chevron-right"></i>
                                </x-ui.button>
                            @else
                                <x-ui.button variant="primary" class="btn-sm" disabled>
                                    Następny <i class="bi bi-chevron-right"></i>
                                </x-ui.button>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Przycisk zapisu - pod kartą tabelki, z prawej -->
                <div class="d-flex justify-content-stretch justify-content-md-end mb-4">
                    <x-ui.button variant="primary" type="submit" class="w-100 w-md-auto">
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
