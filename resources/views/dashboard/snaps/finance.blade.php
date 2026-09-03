@php $f = $snaps['finance']; @endphp

<x-dashboard.snap
    kicker="Kontroling"
    title="Dashboard finansowy"
    caption="Rentowność miesiąca: przychód z projektów, koszty pracy, zmienne, marża. Te same kafelki KPI co na prawdziwym kontrolingu."
    :href="Route::has('profitability.index') ? route('profitability.index') : null"
>
    <x-ui.period-nav>
        <x-slot name="prev">
            <x-ui.button variant="ghost" type="button" class="w-100" disabled>
                <i class="bi bi-chevron-left"></i>
                <span>Poprzedni miesiąc</span>
            </x-ui.button>
        </x-slot>
        <div>
            <h3 class="fs-5 fw-bold mb-0">{{ $f['label'] }}</h3>
            <p class="small text-muted mb-0">demo · bez kursu walut</p>
        </div>
        <x-slot name="next">
            <x-ui.button variant="primary" type="button" class="w-100" disabled>
                <span>Następny miesiąc</span>
                <i class="bi bi-chevron-right"></i>
            </x-ui.button>
        </x-slot>
    </x-ui.period-nav>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="pdb-kpi-card">
                <div class="pdb-stat-value text-primary">{{ $f['kpis']['projects'] }}</div>
                <div class="pdb-stat-label">Projekty w miesiącu</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="pdb-kpi-card">
                <div class="pdb-stat-value text-success">{{ number_format($f['kpis']['avg_margin'], 1, ',', ' ') }}%</div>
                <div class="pdb-stat-label">Śr. marża projektu</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="pdb-kpi-card">
                <div class="pdb-stat-value text-warning">{{ number_format($f['kpis']['plan'], 1, ',', ' ') }}%</div>
                <div class="pdb-stat-label">Śr. wykonanie planu godzin</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="pdb-kpi-card">
                <div class="pdb-stat-value text-success" style="font-size:1.1rem">{{ number_format($f['summary']['margin'], 0, ',', ' ') }} €</div>
                <div class="pdb-stat-label">Marża firmowa</div>
            </div>
        </div>
    </div>

    <x-ui.card label="Podsumowanie finansowe miesiąca" class="mb-4">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="text-center p-3 rounded" style="background:rgba(255,255,255,.04)">
                    <div class="text-muted small mb-1">Przychody</div>
                    <div class="h5 mb-0 text-success">{{ number_format($f['summary']['revenue'], 0, ',', ' ') }} €</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-center p-3 rounded" style="background:rgba(255,255,255,.04)">
                    <div class="text-muted small mb-1">Koszty pracy</div>
                    <div class="h5 mb-0 text-danger">{{ number_format($f['summary']['labor'], 0, ',', ' ') }} €</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-center p-3 rounded" style="background:rgba(255,255,255,.04)">
                    <div class="text-muted small mb-1">Koszty projektowe</div>
                    <div class="h5 mb-0">{{ number_format($f['summary']['variable'], 0, ',', ' ') }} €</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-center p-3 rounded" style="background:rgba(255,255,255,.04)">
                    <div class="text-muted small mb-1">Marża</div>
                    <div class="h5 mb-0 text-success">{{ number_format($f['summary']['margin'], 0, ',', ' ') }} €</div>
                </div>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card>
        <div class="table-responsive">
            <table class="table pdb-table mb-0">
                <thead>
                    <tr>
                        <th>Projekt</th>
                        <th class="text-end">Przychód</th>
                        <th class="text-end">Marża %</th>
                        <th class="text-end">Godziny</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($f['projects'] as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td class="text-end font-mono">{{ number_format($row['revenue'], 0, ',', ' ') }} €</td>
                            <td class="text-end">
                                <x-ui.badge :variant="$row['margin'] >= 18 ? 'success' : ($row['margin'] >= 12 ? 'warning' : 'danger')">
                                    {{ number_format($row['margin'], 1, ',', ' ') }}%
                                </x-ui.badge>
                            </td>
                            <td class="text-end font-mono">{{ number_format($row['hours'], 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>
</x-dashboard.snap>
