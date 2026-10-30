@php
    /** @var array{weeks: list<array>, rows: list<array>, totals: list<array>} $staffingDemandMatrix */
    $weeks = $staffingDemandMatrix['weeks'] ?? [];
    $rows = $staffingDemandMatrix['rows'] ?? [];
    $totals = $staffingDemandMatrix['totals'] ?? [];

    $chartLabels = collect($weeks)->map(function (array $week) {
        $label = 'T'.$week['number'];
        if ($week['is_current'] ?? false) {
            return $label.' · teraz';
        }
        if ($week['is_previous'] ?? false) {
            return $label.' · poprz.';
        }

        return $label;
    })->all();

    $chartNeeded = collect($totals)->map(fn (array $cell) => (int) ($cell['needed'] ?? 0))->all();
    $chartAssigned = collect($totals)->map(fn (array $cell) => (int) ($cell['assigned'] ?? 0))->all();
    $chartProjects = collect($rows)->map(fn (array $row) => [
        'label' => $row['project_name'],
        'data' => collect($row['cells'] ?? [])->map(fn (array $cell) => (int) ($cell['needed'] ?? 0))->all(),
    ])->values()->all();

    $chartPayload = [
        'labels' => $chartLabels,
        'needed' => $chartNeeded,
        'assigned' => $chartAssigned,
        'projects' => $chartProjects,
    ];
@endphp

@if(count($weeks) > 0 && count($rows) > 0)
    <x-ui.card class="mb-4 wo-demand-matrix-card">
        <div class="mb-3">
            <h3 class="h5 mb-1">Zapotrzebowanie na ludzi</h3>
            <p class="small text-muted mb-0">
                Osoby przypisane / osoby potrzebne, projekt po projekcie i tydzień po tygodniu
            </p>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 wo-demand-matrix">
                <thead>
                    <tr>
                        <th scope="col" class="wo-demand-matrix__project">Projekt</th>
                        @foreach($weeks as $week)
                            <th
                                scope="col"
                                @class([
                                    'text-center font-mono wo-demand-matrix__week',
                                    'wo-demand-matrix__week--current' => $week['is_current'] ?? false,
                                    'wo-demand-matrix__week--previous' => $week['is_previous'] ?? false,
                                ])
                            >
                                Tydzień {{ $week['number'] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td class="wo-demand-matrix__project">
                                <a href="{{ route('projects.show', $row['project_id']) }}" class="wo-demand-matrix__project-link">
                                    {{ $row['project_name'] }}
                                </a>
                            </td>
                            @foreach($row['cells'] as $i => $cell)
                                <td @class([
                                    'text-center font-mono wo-demand-cell',
                                    'wo-demand-cell--current-week' => $weeks[$i]['is_current'] ?? false,
                                    'wo-demand-cell--previous-week' => $weeks[$i]['is_previous'] ?? false,
                                ])>
                                    {{ $cell['assigned'] }}/{{ $cell['needed'] }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="wo-demand-matrix__total">
                        <td class="wo-demand-matrix__project">Razem</td>
                        @foreach($totals as $i => $cell)
                            <td @class([
                                'text-center font-mono wo-demand-cell',
                                'wo-demand-cell--current-week' => $weeks[$i]['is_current'] ?? false,
                                'wo-demand-cell--previous-week' => $weeks[$i]['is_previous'] ?? false,
                            ])>
                                {{ $cell['assigned'] }}/{{ $cell['needed'] }}
                            </td>
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="wo-demand-trend mt-4 pt-3">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <h3 class="wo-demand-trend__title mb-1">Popyt w czasie</h3>
                    <p class="small text-muted mb-0">
                        Zapotrzebowanie vs przypisani w tygodniu, z podziałem na projekty
                    </p>
                </div>
                <ul class="wo-demand-trend__legend list-unstyled mb-0 d-flex flex-wrap gap-3">
                    <li><span class="wo-demand-trend__swatch wo-demand-trend__swatch--needed"></span>Popyt razem</li>
                    <li><span class="wo-demand-trend__swatch wo-demand-trend__swatch--assigned"></span>Przypisani</li>
                    <li><span class="wo-demand-trend__swatch wo-demand-trend__swatch--gap"></span>Luka</li>
                    <li><span class="wo-demand-trend__swatch wo-demand-trend__swatch--project"></span>Popyt projektu</li>
                </ul>
            </div>
            <div class="wo-demand-trend__canvas-wrap">
                <canvas
                    id="wo-demand-trend-chart"
                    class="wo-demand-trend-chart"
                    aria-label="Wykres popytu i przypisań w kolejnych tygodniach"
                    data-chart='@json($chartPayload)'
                ></canvas>
            </div>
        </div>
    </x-ui.card>
@endif
