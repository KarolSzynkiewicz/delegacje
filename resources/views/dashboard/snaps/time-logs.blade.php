@php
    $grid = $snaps['timeLogs'];
    $days = $grid['days'];
    $currentDate = $grid['currentDate'];
    $monthStart = $grid['monthStart'];
    $monthEnd = $grid['monthEnd'];
@endphp

<x-dashboard.snap
    kicker="Ewidencja godzin"
    title="Widok miesięczny"
    caption="Siatka Projekt → osoba → dzień. Białe pole = pracownik był przypisany tego dnia (system wie to z ProjectAssignment). Szare = poza przypisaniem, nie da się wpisać godzin."
    :href="Route::has('time-logs.monthly-grid') ? route('time-logs.monthly-grid') : null"
    tall
>
    <x-slot:note>
        Nie prowadzisz osobnego kalendarza „kto kiedy pracował” — siatka buduje się z przypisań do projektów.
    </x-slot:note>

    @include('time-logs.partials.monthly-grid-styles')

    <div class="time-logs-monthly-grid-root time-logs-monthly-grid-root--contained">
        <div class="text-center mb-3">
            <div class="fw-bold">{{ $currentDate->locale('pl')->translatedFormat('F Y') }}</div>
            <div class="small text-muted">{{ $monthStart->format('d.m.Y') }} – {{ $monthEnd->format('d.m.Y') }}</div>
        </div>
        <div class="card monthly-grid-table-card mb-0">
            <p class="d-md-none small text-muted mb-0 px-3 pt-3 pb-2 border-bottom border-secondary border-opacity-10">
                <i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i>
                Przewiń w poziomie, aby zobaczyć dni miesiąca.
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
                            <th class="text-center fw-bold">Projekt / Osoba</th>
                            @foreach($days as $day)
                                <th class="text-center fw-bold {{ $day['isWeekend'] ? 'weekend-header' : '' }}">
                                    <div class="monthly-grid-day-num">{{ $day['number'] }}</div>
                                    <div class="monthly-grid-day-dow text-muted">{{ $day['date']->format('D') }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($grid['projectsData'] as $projectData)
                            @php $project = $projectData['project']; @endphp
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
                                    <td class="{{ $day['isWeekend'] ? 'weekend-cell' : '' }}"></td>
                                @endforeach
                            </tr>
                            @foreach($projectData['assignments'] as $assignmentData)
                                <tr>
                                    <td class="ps-4 border-end-2">
                                        <i class="bi bi-person me-2"></i>{{ $assignmentData['employee']->last_name }}, {{ $assignmentData['employee']->first_name }}
                                    </td>
                                    @foreach($days as $day)
                                        @php
                                            $n = $day['number'];
                                            $in = isset($assignmentData['daysInAssignment'][$n]);
                                            $hours = $assignmentData['timeLogs'][$n]['hours'] ?? '';
                                            $hoursText = '';
                                            if ($hours !== '' && $hours !== null) {
                                                $hoursFloat = (float) $hours;
                                                $totalMinutes = (int) round($hoursFloat * 60);
                                                $roundedMinutes = (int) (round($totalMinutes / 15) * 15);
                                                $hPart = intdiv($roundedMinutes, 60);
                                                $mPart = $roundedMinutes % 60;
                                                $hoursText = $roundedMinutes === 0 ? '' : ($hPart.':'.str_pad((string) $mPart, 2, '0', STR_PAD_LEFT));
                                            }
                                        @endphp
                                        <td class="text-center {{ $day['isWeekend'] ? 'weekend-cell' : '' }} {{ $in ? '' : 'disabled-cell' }}">
                                            <input
                                                type="text"
                                                class="form-control form-control-sm text-center {{ $in ? 'time-input' : 'disabled-input' }}"
                                                value="{{ $hoursText }}"
                                                readonly
                                                tabindex="-1"
                                                {{ $in ? '' : 'disabled' }}
                                            >
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-dashboard.snap>
