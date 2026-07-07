<x-app-layout :edge-to-edge="true">
    <style>
        /* ── Layout root ─────────────────────────────────────── */
        .tla-root {
            width: 100%;
            max-width: 100%;
            margin-left: 0;
            margin-right: 0;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
            box-sizing: border-box;
        }

        /* ── Table card & scroll ─────────────────────────────── */
        .tla-root .tla-table-card {
            width: 100%;
            max-width: 100%;
        }
        .tla-root .tla-table-scroll {
            width: 100%;
            overflow-x: auto;
            overflow-y: visible;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-x: contain;
            scrollbar-gutter: stable;
        }

        /* ── Grid table ──────────────────────────────────────── */
        .tla-root #tlaGrid {
            border-collapse: collapse;
            table-layout: auto;
            width: max-content;
            min-width: 100%;
        }

        /* Sticky first column */
        .tla-root #tlaGrid thead th:first-child,
        .tla-root #tlaGrid tbody td:first-child {
            width: min(42vw, 160px);
            min-width: min(42vw, 160px);
            max-width: min(42vw, 160px);
            font-size: 0.78rem;
            overflow: hidden;
            box-sizing: border-box;
        }
        .tla-root #tlaGrid thead th:first-child {
            position: sticky;
            left: 0;
            z-index: 14;
            background: var(--bg-card) !important;
            box-shadow: 6px 0 12px -6px rgba(0,0,0,.45);
        }
        .tla-root #tlaGrid tbody td:first-child {
            position: sticky;
            left: 0;
            z-index: 6;
            background: var(--bg-card) !important;
            box-shadow: 6px 0 12px -6px rgba(0,0,0,.3);
        }

        /* Day columns */
        .tla-root #tlaGrid thead th.day-th,
        .tla-root #tlaGrid tbody td.day-td {
            min-width: 38px;
            width: 38px;
            max-width: 38px;
            padding: 2px 2px !important;
            vertical-align: middle;
            text-align: center;
            font-size: 0.65rem;
        }

        /* Week subtotal columns */
        .tla-root #tlaGrid thead th.week-sub-th,
        .tla-root #tlaGrid tbody td.week-sub-td {
            min-width: 44px;
            width: 44px;
            max-width: 44px;
            padding: 2px 4px !important;
            vertical-align: middle;
            text-align: center;
            font-size: 0.7rem;
            background: rgba(59,130,246,.08) !important;
        }

        /* Month total column */
        .tla-root #tlaGrid thead th.month-total-th,
        .tla-root #tlaGrid tbody td.month-total-td {
            min-width: 52px;
            width: 52px;
            padding: 2px 4px !important;
            vertical-align: middle;
            text-align: center;
            font-size: 0.72rem;
            background: rgba(16,185,129,.08) !important;
        }

        /* Week group header row (row 1 of thead) */
        .tla-root #tlaGrid .week-group-th {
            font-size: 0.6rem;
            text-align: center;
            letter-spacing: .02em;
            padding: 4px 2px !important;
            border-bottom: 1px solid rgba(255,255,255,.1);
        }

        /* Day header number + dow */
        .tla-root .day-num { font-size: 0.65rem; line-height: 1.2; }
        .tla-root .day-dow { font-size: 0.52rem; opacity: .7; }

        /* Weekend cells */
        .weekend-header { background: rgba(239,68,68,.08) !important; }
        .weekend-cell   { background: rgba(239,68,68,.05) !important; }

        /* Today highlight */
        .today-th { background: rgba(59,130,246,.14) !important; }
        .today-td { background: rgba(59,130,246,.08) !important; }

        /* Project header row */
        .project-header-row td:first-child {
            background: rgba(59,130,246,.1) !important;
            z-index: 7;
        }
        .project-header-row td {
            background: rgba(59,130,246,.06) !important;
        }

        /* Project subtotal row */
        .project-subtotal-row td {
            background: rgba(59,130,246,.04) !important;
            font-size: 0.72rem;
            border-top: 1px solid rgba(59,130,246,.2);
        }
        .project-subtotal-row td:first-child {
            background: rgba(59,130,246,.1) !important;
        }

        /* Grand total row */
        .grand-total-row td {
            background: rgba(16,185,129,.06) !important;
            border-top: 2px solid rgba(16,185,129,.3);
            font-weight: 700;
        }
        .grand-total-row td:first-child {
            background: rgba(16,185,129,.12) !important;
        }

        /* Hours badge in cell */
        .hours-badge {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--bs-body-color);
        }

        /* Hour heat-map: applied via inline style on <td> */

        /* Summary cards */
        .tla-stat-card {
            border-radius: 0.5rem;
            padding: 1rem 1.25rem;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
        }
        .tla-stat-value { font-size: 1.75rem; font-weight: 700; }
        .tla-stat-label { font-size: 0.75rem; opacity: .65; }

        /* Chart containers */
        .tla-chart-wrap { position: relative; min-height: 200px; }

        /* Project filter dropdown */
        .tla-filter-dropdown { min-width: 300px; max-height: 300px; overflow-y: auto; }

        @media (min-width: 768px) {
            .tla-root {
                width: 100vw;
                max-width: 100vw;
                margin-left: calc(50% - 50vw);
                margin-right: calc(50% - 50vw);
                padding-left: 1rem;
                padding-right: 1rem;
            }
            .tla-root #tlaGrid {
                table-layout: fixed;
                width: 100%;
                min-width: 0;
            }
            .tla-root #tlaGrid thead th:first-child,
            .tla-root #tlaGrid tbody td:first-child {
                width: 220px; min-width: 220px; max-width: 220px;
                font-size: inherit;
            }
            .tla-root #tlaGrid thead th.day-th,
            .tla-root #tlaGrid tbody td.day-td {
                min-width: 0; width: auto; max-width: none;
                font-size: 0.68rem;
                padding: 3px 3px !important;
            }
        }
    </style>

    <x-slot name="header">
        @php
            $analyticsRoute = 'time-logs.analytics';
            $projectQs = collect($selectedProjectIds)->map(fn($id) => 'project_ids[]=' . $id)->implode('&');
            $prevUrl = route($analyticsRoute, ['month' => $prevMonth]) . ($projectQs ? '&' . $projectQs : '');
            $nextUrl = route($analyticsRoute, ['month' => $nextMonth]) . ($projectQs ? '&' . $projectQs : '');
        @endphp

        <x-ui.period-nav class="mb-0">
            <x-slot name="prev">
                <x-ui.button variant="ghost" href="{{ $prevUrl }}" class="btn-sm w-100">
                    <i class="bi bi-chevron-left"></i>
                    <span>Poprzedni miesiąc</span>
                </x-ui.button>
            </x-slot>
            <h2 class="fw-semibold fs-4 mb-0">
                Analityka Godzin – Widok Miesięczny
            </h2>
            <x-slot name="next">
                <x-ui.button variant="primary" href="{{ $nextUrl }}" class="btn-sm w-100">
                    <span>Następny miesiąc</span>
                    <i class="bi bi-chevron-right"></i>
                </x-ui.button>
            </x-slot>
        </x-ui.period-nav>
    </x-slot>

    <div class="tla-root">
    <div class="py-2">

        @php
            /* ── Helpers ───────────────────────────────────── */
            $fmtH = function (float $h): string {
                if ($h <= 0) return '';
                return $h == floor($h) ? (string)(int)$h : number_format($h, 2, ',', '');
            };

            /* Max daily hours across all projects (for heat map) */
            $maxDailyH = 0;
            foreach ($byProject as $pd) {
                foreach ($pd['dailyTotals'] as $h) {
                    if ($h > $maxDailyH) $maxDailyH = $h;
                }
            }
            if ($maxDailyH === 0) $maxDailyH = 8;

            /* Total employees with entries */
            $totalEmployees = 0;
            $empSet = [];
            foreach ($byProject as $pd) {
                foreach ($pd['employees'] as $eid => $_) {
                    $empSet[$eid] = true;
                }
            }
            $totalEmployees = count($empSet);

            /* Total columns count (for empty state colspan) */
            $colCount = 1 + count($days) + count($weeks) + 1;

            /* CSV export URL with current filters */
            $csvParams = ['month' => $month, 'format' => 'csv'];
            foreach ($selectedProjectIds as $pid) {
                $csvParams['project_ids'][] = $pid;
            }
            $csvUrl = route('time-logs.analytics') . '?' . http_build_query($csvParams);
        @endphp

        {{-- ── Filter bar ───────────────────────────────────── --}}
        <div class="d-flex flex-column flex-md-row gap-3 align-items-stretch align-items-md-end justify-content-between mb-4">
            {{-- Month label --}}
            <div class="text-center text-md-start">
                <div class="fw-bold fs-5">{{ $currentDate->locale('pl')->translatedFormat('F Y') }}</div>
                <div class="small text-muted">{{ $monthStart->format('d.m.Y') }} – {{ $monthEnd->format('d.m.Y') }}</div>
            </div>

            {{-- Right side: project filter + CSV export --}}
            <div class="d-flex gap-2 flex-wrap align-items-center justify-content-end">
                @if ($availableProjects->isNotEmpty())
                <form id="tlaFilterForm" method="GET" action="{{ route('time-logs.analytics') }}" class="d-flex gap-2">
                    <input type="hidden" name="month" value="{{ $month }}">

                    <div class="dropdown">
                        <button
                            class="btn btn-outline-secondary btn-sm dropdown-toggle"
                            type="button"
                            data-bs-toggle="dropdown"
                            data-bs-auto-close="outside"
                        >
                            <i class="bi bi-funnel me-1"></i>
                            Projekty
                            @if (count($selectedProjectIds) < $availableProjects->count())
                                <span class="badge bg-primary ms-1">{{ count($selectedProjectIds) }}</span>
                            @endif
                        </button>
                        <div class="dropdown-menu p-3 tla-filter-dropdown">
                            <div class="d-flex gap-2 mb-3">
                                <button type="button" class="btn btn-sm btn-outline-secondary flex-1" onclick="tlaSelectAll(true)">Wszystkie</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary flex-1" onclick="tlaSelectAll(false)">Odznacz</button>
                            </div>
                            @foreach ($availableProjects as $proj)
                                <div class="form-check mb-1">
                                    <input
                                        class="form-check-input tla-proj-cb"
                                        type="checkbox"
                                        name="project_ids[]"
                                        value="{{ $proj->id }}"
                                        id="tla_proj_{{ $proj->id }}"
                                        {{ in_array($proj->id, $selectedProjectIds) ? 'checked' : '' }}
                                    >
                                    <label class="form-check-label small" for="tla_proj_{{ $proj->id }}">
                                        {{ $proj->name }}
                                    </label>
                                </div>
                            @endforeach
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary btn-sm w-100">Zastosuj</button>
                            </div>
                        </div>
                    </div>
                </form>
                @endif

                <a href="{{ $csvUrl }}" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-download me-1"></i>Eksport CSV
                </a>
            </div>
        </div>

        {{-- ── Summary cards ──────────────────────────────────── --}}
        <div class="row g-3 mb-4">
            <div class="col-4">
                <div class="tla-stat-card text-center">
                    <div class="tla-stat-value text-info">{{ number_format($grandMonthTotal, 1, ',', '') }}</div>
                    <div class="tla-stat-label">Godzin łącznie</div>
                </div>
            </div>
            <div class="col-4">
                <div class="tla-stat-card text-center">
                    <div class="tla-stat-value text-primary">{{ count($byProject) }}</div>
                    <div class="tla-stat-label">Projekty</div>
                </div>
            </div>
            <div class="col-4">
                <div class="tla-stat-card text-center">
                    <div class="tla-stat-value text-success">{{ $totalEmployees }}</div>
                    <div class="tla-stat-label">Pracownicy z wpisami</div>
                </div>
            </div>
        </div>

        @if (!empty($byProject))
        {{-- ── Charts ─────────────────────────────────────────── --}}
        <div class="row g-3 mb-4">
            {{-- Donut: hours by project --}}
            <div class="col-md-4">
                <x-ui.card class="h-100">
                    <div class="fw-semibold small mb-3 text-muted text-uppercase" style="letter-spacing:.06em">Godziny wg projektu</div>
                    <div class="tla-chart-wrap" style="min-height:220px">
                        <canvas id="tlaChartDonut"></canvas>
                    </div>
                </x-ui.card>
            </div>

            {{-- Stacked bar: hours by week per project --}}
            <div class="col-md-8">
                <x-ui.card class="h-100">
                    <div class="fw-semibold small mb-3 text-muted text-uppercase" style="letter-spacing:.06em">Godziny tygodniowo wg projektu</div>
                    <div class="tla-chart-wrap" style="min-height:220px">
                        <canvas id="tlaChartWeek"></canvas>
                    </div>
                </x-ui.card>
            </div>
        </div>

        {{-- Daily stacked bar --}}
        <div class="row g-3 mb-4">
            <div class="col-12">
                <x-ui.card>
                    <div class="fw-semibold small mb-3 text-muted text-uppercase" style="letter-spacing:.06em">Godziny dziennie wg projektu</div>
                    <div class="tla-chart-wrap" style="min-height:160px">
                        <canvas id="tlaChartDaily"></canvas>
                    </div>
                </x-ui.card>
            </div>
        </div>
        @endif

        {{-- ── Main grid table ─────────────────────────────────── --}}
        <div class="card mb-4 tla-table-card">
            <p class="d-md-none small text-muted mb-0 px-3 pt-3 pb-2 border-bottom border-secondary border-opacity-10">
                <i class="bi bi-arrow-left-right me-1"></i>
                Przewiń tabelę w poziomie, aby zobaczyć wszystkie dni miesiąca.
            </p>
            <div class="tla-table-scroll">
                <table class="table mb-0 align-middle" id="tlaGrid">
                    <colgroup>
                        <col>
                        @foreach ($weeks as $week)
                            @foreach ($week['days'] as $_)
                                <col>
                            @endforeach
                            <col>{{-- week subtotal --}}
                        @endforeach
                        <col>{{-- month total --}}
                    </colgroup>

                    <thead class="sticky-top">
                        {{-- Row 1: week group headers --}}
                        <tr>
                            <th rowspan="2" class="text-center fw-bold align-middle border-end">
                                Osoba
                            </th>
                            @foreach ($weeks as $week)
                                <th
                                    colspan="{{ count($week['days']) + 1 }}"
                                    class="week-group-th fw-semibold border-end"
                                    style="background: rgba(59,130,246,.06)!important"
                                >
                                    {{ $week['label'] }} &middot; {{ $week['dateRange'] }}
                                </th>
                            @endforeach
                            <th
                                rowspan="2"
                                class="month-total-th text-center fw-bold align-middle"
                                title="Suma miesięczna"
                            >
                                Σ<br><span style="font-size:.55rem;opacity:.7">Mies.</span>
                            </th>
                        </tr>

                        {{-- Row 2: individual day headers + week subtotal headers --}}
                        <tr>
                            @foreach ($weeks as $week)
                                @foreach ($week['days'] as $dayNum)
                                    @php
                                        $dayData = $days[$dayNum - 1];
                                        $isToday = $dayData['date']->isToday();
                                    @endphp
                                    <th
                                        class="day-th {{ $dayData['isWeekend'] ? 'weekend-header' : '' }} {{ $isToday ? 'today-th' : '' }}"
                                        title="{{ $dayData['date']->locale('pl')->translatedFormat('l, j F Y') }}"
                                    >
                                        <div class="day-num">{{ $dayNum }}</div>
                                        <div class="day-dow">{{ $dayData['date']->format('D') }}</div>
                                    </th>
                                @endforeach
                                <th class="week-sub-th fw-bold border-start" title="{{ $week['label'] }} – suma">
                                    Σ{{ $week['index'] }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($byProject as $projData)
                            {{-- Project header row --}}
                            <tr class="project-header-row">
                                <td class="fw-bold border-end">
                                    <div class="d-flex align-items-center">
                                        <span class="project-dot me-2"></span>
                                        <span style="font-size:.8rem">{{ $projData['name'] }}</span>
                                    </div>
                                </td>
                                @foreach ($weeks as $week)
                                    @foreach ($week['days'] as $dayNum)
                                        @php $dayData = $days[$dayNum - 1]; @endphp
                                        <td class="day-td {{ $dayData['isWeekend'] ? 'weekend-cell' : '' }}"></td>
                                    @endforeach
                                    @php $wTotal = $projData['weeklyTotals'][$week['key']] ?? 0; @endphp
                                    <td class="week-sub-td border-start fw-bold text-info" title="{{ $week['label'] }} suma projektu">
                                        {{ $wTotal > 0 ? $fmtH($wTotal) : '' }}
                                    </td>
                                @endforeach
                                <td class="month-total-td fw-bold text-info">
                                    {{ $projData['monthTotal'] > 0 ? $fmtH($projData['monthTotal']) : '' }}
                                </td>
                            </tr>

                            {{-- Employee rows --}}
                            @foreach ($projData['employees'] as $empData)
                                <tr>
                                    <td class="ps-3 border-end" style="font-size:.8rem">
                                        <i class="bi bi-person me-1 opacity-50"></i>
                                        {{ $empData['last_name'] }}, {{ $empData['first_name'] }}
                                    </td>
                                    @foreach ($weeks as $week)
                                        @foreach ($week['days'] as $dayNum)
                                            @php
                                                $dayData = $days[$dayNum - 1];
                                                $h = $empData['dailyHours'][$dayNum] ?? 0.0;
                                                $isToday = $dayData['date']->isToday();
                                                // Heat map: opacity proportional to hours (max 8h = full)
                                                $heatOpacity = $h > 0 ? min(0.4, ($h / 8) * 0.4) : 0;
                                                $heatStyle = $h > 0 ? 'background: rgba(59,130,246,' . number_format($heatOpacity, 2, '.', '') . ')!important;' : '';
                                            @endphp
                                            <td
                                                class="day-td {{ $dayData['isWeekend'] ? 'weekend-cell' : '' }} {{ $isToday ? 'today-td' : '' }}"
                                                style="{{ $heatStyle }}"
                                                title="{{ $dayData['date']->format('d.m.Y') }}{{ $h > 0 ? ' · ' . $fmtH($h) . 'h' : '' }}"
                                            >
                                                @if ($h > 0)
                                                    <span class="hours-badge">{{ $fmtH($h) }}</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        @php $empWeekTotal = $empData['weeklyTotals'][$week['key']] ?? 0.0; @endphp
                                        <td class="week-sub-td border-start">
                                            {{ $empWeekTotal > 0 ? $fmtH($empWeekTotal) : '' }}
                                        </td>
                                    @endforeach
                                    <td class="month-total-td fw-semibold">
                                        {{ $fmtH($empData['monthTotal']) }}
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Project subtotal row --}}
                            <tr class="project-subtotal-row">
                                <td class="fw-semibold ps-2 border-end" style="font-size:.72rem">
                                    <i class="bi bi-sigma me-1 text-info"></i>{{ $projData['name'] }}
                                </td>
                                @foreach ($weeks as $week)
                                    @foreach ($week['days'] as $dayNum)
                                        @php
                                            $dayData = $days[$dayNum - 1];
                                            $h = $projData['dailyTotals'][$dayNum] ?? 0.0;
                                        @endphp
                                        <td
                                            class="day-td {{ $dayData['isWeekend'] ? 'weekend-cell' : '' }}"
                                            title="{{ $dayData['date']->format('d.m.Y') }} – suma projektu"
                                        >
                                            @if ($h > 0)
                                                <span class="hours-badge" style="color:rgba(59,130,246,.9)">{{ $fmtH($h) }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    @php $wt = $projData['weeklyTotals'][$week['key']] ?? 0.0; @endphp
                                    <td class="week-sub-td border-start fw-bold text-info">
                                        {{ $wt > 0 ? $fmtH($wt) : '' }}
                                    </td>
                                @endforeach
                                <td class="month-total-td fw-bold text-info">
                                    {{ $projData['monthTotal'] > 0 ? $fmtH($projData['monthTotal']) : '' }}
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="{{ $colCount }}" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2 opacity-50"></i>
                                    Brak wpisów godzin w tym miesiącu
                                    @if (count($selectedProjectIds) < $availableProjects->count())
                                        (dla wybranych projektów)
                                    @endif
                                </td>
                            </tr>
                        @endforelse

                        {{-- Grand total row --}}
                        @if (!empty($byProject))
                            <tr class="grand-total-row">
                                <td class="border-end" style="font-size:.8rem;letter-spacing:.02em">
                                    <i class="bi bi-sigma me-1"></i> ŁĄCZNIE
                                </td>
                                @foreach ($weeks as $week)
                                    @foreach ($week['days'] as $dayNum)
                                        @php
                                            $dayData = $days[$dayNum - 1];
                                            $h = $grandDailyTotals[$dayNum] ?? 0.0;
                                        @endphp
                                        <td
                                            class="day-td {{ $dayData['isWeekend'] ? 'weekend-cell' : '' }}"
                                            title="{{ $dayData['date']->format('d.m.Y') }} – łącznie"
                                        >
                                            @if ($h > 0)
                                                <span class="hours-badge text-success">{{ $fmtH($h) }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    @php $gwt = $grandWeeklyTotals[$week['key']] ?? 0.0; @endphp
                                    <td class="week-sub-td border-start fw-bold text-success">
                                        {{ $gwt > 0 ? $fmtH($gwt) : '' }}
                                    </td>
                                @endforeach
                                <td class="month-total-td fw-bold text-success" style="font-size:.85rem">
                                    {{ $fmtH($grandMonthTotal) }}
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── Legend ──────────────────────────────────────────── --}}
        <x-ui.card class="mb-4">
            <div class="d-flex flex-wrap gap-4 align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:28px;height:16px;border-radius:3px;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.2)"></div>
                    <span class="small">Weekend</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div style="width:28px;height:16px;border-radius:3px;background:rgba(59,130,246,.14);border:1px solid rgba(59,130,246,.3)"></div>
                    <span class="small">Dzisiaj</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div style="width:28px;height:16px;border-radius:3px;background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.15)"></div>
                    <span class="small">Σ tygodnia</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div style="width:28px;height:16px;border-radius:3px;background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.2)"></div>
                    <span class="small">Σ miesiąca</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div style="width:28px;height:16px;border-radius:3px;background:linear-gradient(to right, rgba(59,130,246,.05), rgba(59,130,246,.4))"></div>
                    <span class="small">Intensywność godzin (heat map)</span>
                </div>
            </div>
        </x-ui.card>

    </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        const PALETTE = [
            '#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6',
            '#06b6d4','#f97316','#84cc16','#ec4899','#14b8a6',
            '#a855f7','#64748b','#0ea5e9','#d97706','#16a34a',
        ];

        function mkColor(i) { return PALETTE[i % PALETTE.length]; }
        function mkAlpha(hex, a) {
            const r = parseInt(hex.slice(1,3),16);
            const g = parseInt(hex.slice(3,5),16);
            const b = parseInt(hex.slice(5,7),16);
            return `rgba(${r},${g},${b},${a})`;
        }

        const chartData = @json($chartData);

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

        function mkChart(id, config) {
            const el = document.getElementById(id);
            if (!el || !window.Chart) return null;
            return new Chart(el.getContext('2d'), config);
        }

        function initCharts() {
            if (!window.Chart) return;

            Chart.defaults.color = 'rgba(255,255,255,0.45)';
            Chart.defaults.borderColor = 'rgba(255,255,255,0.08)';
            Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
            Chart.defaults.font.size = 11;

            const labels = chartData.projectLabels;
            const totals = chartData.projectTotals;
            const colors = labels.map((_, i) => mkColor(i));

            // ── Donut: hours per project ──────────────────────
            if (document.getElementById('tlaChartDonut')) {
                mkChart('tlaChartDonut', {
                    type: 'doughnut',
                    data: {
                        labels,
                        datasets: [{
                            data: totals,
                            backgroundColor: colors.map(c => mkAlpha(c, 0.25)),
                            borderColor: colors,
                            borderWidth: 2,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '58%',
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: {
                                    boxWidth: 10,
                                    padding: 8,
                                    font: { size: 10 },
                                    generateLabels(chart) {
                                        const ds = chart.data.datasets[0];
                                        const total = ds.data.reduce((s, v) => s + v, 0);
                                        return chart.data.labels.map((lbl, i) => ({
                                            text: `${lbl} (${ds.data[i]}h)`,
                                            fillStyle: ds.backgroundColor[i],
                                            strokeStyle: ds.borderColor[i],
                                            lineWidth: 1,
                                            index: i,
                                        }));
                                    },
                                },
                            },
                            tooltip: {
                                callbacks: {
                                    label: ctx => ` ${ctx.label}: ${ctx.raw}h`,
                                },
                            },
                        },
                    },
                });
            }

            // ── Stacked bar: hours by week per project ────────
            if (document.getElementById('tlaChartWeek')) {
                mkChart('tlaChartWeek', {
                    type: 'bar',
                    data: {
                        labels: chartData.weekLabels,
                        datasets: chartData.weekDatasets.map((ds, i) => ({
                            label: ds.label,
                            data: ds.data,
                            backgroundColor: mkAlpha(mkColor(i), 0.7),
                            borderColor: mkColor(i),
                            borderWidth: 1,
                        })),
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 10, padding: 8, font: { size: 10 } } },
                            tooltip: { callbacks: { label: ctx => ` ${ctx.dataset.label}: ${ctx.raw}h` } },
                        },
                        scales: {
                            x: { stacked: true, grid: { display: false } },
                            y: { stacked: true, title: { display: true, text: 'Godziny', font: { size: 10 } } },
                        },
                    },
                });
            }

            // ── Stacked bar: hours per day ────────────────────
            if (document.getElementById('tlaChartDaily')) {
                mkChart('tlaChartDaily', {
                    type: 'bar',
                    data: {
                        labels: chartData.dayLabels,
                        datasets: chartData.dayDatasets.map((ds, i) => ({
                            label: ds.label,
                            data: ds.data,
                            backgroundColor: mkAlpha(mkColor(i), 0.72),
                            borderColor: mkColor(i),
                            borderWidth: 1,
                        })),
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: ctx => ` ${ctx.dataset.label}: ${ctx.raw}h` } },
                        },
                        scales: {
                            x: { stacked: true, grid: { display: false }, ticks: { font: { size: 9 } } },
                            y: { stacked: true, title: { display: true, text: 'h', font: { size: 10 } } },
                        },
                    },
                });
            }
        }

        loadChartJs().then(initCharts);

        // ── Project filter helpers ──────────────────────────────
        window.tlaSelectAll = function (checked) {
            document.querySelectorAll('.tla-proj-cb').forEach(cb => cb.checked = checked);
        };
    })();
    </script>
    @endpush

</x-app-layout>
