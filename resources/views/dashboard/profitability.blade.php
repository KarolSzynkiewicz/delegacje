@php
    use App\Enums\ProjectStatus;
    use App\Enums\ProjectType;
    use App\Services\StatusColorService;

    // ── Helpery formatowania ──────────────────────────────────────────
    function formatNumber($number, $decimals = 2) {
        $formatted = number_format((float) $number, $decimals, ',', ' ');
        if ($decimals > 0) {
            // Usuń zbędne zera po przecinku (np. 120,00 -> 120)
            $formatted = preg_replace('/,0+$/', '', $formatted);
        }
        return $formatted;
    }

    function formatCurrency($currency) {
        $symbol = match(strtoupper((string) $currency)) {
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'PLN' => 'zł',
            default => $currency,
        };

        $color = match(strtoupper((string) $currency)) {
            'EUR' => 'text-primary',
            'PLN' => 'text-success',
            default => '',
        };

        if ($color) {
            return '<span class="'.$color.' fw-bold">'.$symbol.'</span>';
        }

        return $symbol;
    }

    /**
     * Mały "chip" ze zmianą % względem poprzedniego miesiąca (kontroling m/m).
     * $goodWhenUp = true, gdy wzrost jest korzystny (przychód, marża); false dla kosztów.
     */
    // Mapuje kolor statusu projektu na dostępne warianty x-ui.badge (success/danger/warning/info)
    function pdbStatusBadgeVariant($status) {
        return match(StatusColorService::getProjectStatusColor($status)) {
            'success' => 'success',
            'danger' => 'danger',
            'warning' => 'warning',
            default => 'info',
        };
    }

    function pdbDeltaChip($current, $previous, $goodWhenUp = true) {
        $current = (float) $current;
        $previous = (float) $previous;

        if (abs($previous) < 0.005) {
            return '';
        }

        $pct = (($current - $previous) / abs($previous)) * 100;
        $up = $pct >= 0;
        $good = $goodWhenUp ? $up : ! $up;
        $isFlat = abs($pct) < 0.5;

        $color = $isFlat ? 'text-muted' : ($good ? 'text-success' : 'text-danger');
        $icon = $isFlat ? 'bi-dash' : ($up ? 'bi-arrow-up-short' : 'bi-arrow-down-short');

        return '<span class="small '.$color.' d-inline-flex align-items-center" title="Zmiana względem poprzedniego miesiąca">'
            .'<i class="bi '.$icon.'"></i>'.formatNumber(abs($pct), 1).'% m/m</span>';
    }

    // ── Dane pomocnicze na potrzeby linków sortowania w tabeli ─────────
    $sortLinkUrl = function (string $column) use ($filters, $navigation) {
        $nextDir = ($filters['sortBy'] === $column && $filters['sortDir'] === 'desc') ? 'asc' : 'desc';
        $query = array_filter([
            'year' => $navigation['current']['year'],
            'month' => $navigation['current']['month'],
            'statuses' => $filters['statuses'],
            'type' => $filters['type'],
            'search' => $filters['search'],
            'sort_by' => $column,
            'sort_dir' => $nextDir,
        ], fn ($v) => $v !== null && $v !== []);

        return route('profitability.index').'?'.http_build_query($query);
    };
    $sortIconHtml = function (string $column) use ($filters) {
        if ($filters['sortBy'] !== $column) {
            return '<i class="bi bi-arrow-down-up opacity-25 ms-1" style="font-size:.65rem"></i>';
        }

        return $filters['sortDir'] === 'desc'
            ? '<i class="bi bi-sort-down-alt ms-1 text-primary" style="font-size:.75rem"></i>'
            : '<i class="bi bi-sort-up ms-1 text-primary" style="font-size:.75rem"></i>';
    };

    $resetUrl = route('profitability.index', [
        'year' => $navigation['current']['year'],
        'month' => $navigation['current']['month'],
    ]);

    // Wszystkie kategorie kosztów wliczane do kosztów łącznych / marży FIRMOWEJ (nie projektowej) —
    // koszty pracy i projektowe rozliczane są też per-projekt, ogólnofirmowe/transport/najem tylko firmowo.
    $costCategoryKeys = [
        'labor_costs_by_currency', 'variable_costs_by_currency', 'fixed_costs_by_currency',
        'transport_costs_by_currency', 'accommodation_costs_by_currency',
    ];

    // ── Dominująca waluta kosztów w miesiącu (do wykresu struktury kosztów) ─
    $costCurrencyTotals = [];
    foreach ($costCategoryKeys as $key) {
        foreach ($summary[$key] ?? [] as $cur => $amt) {
            $costCurrencyTotals[$cur] = ($costCurrencyTotals[$cur] ?? 0) + $amt;
        }
    }
    arsort($costCurrencyTotals);
    $dominantCostCurrency = array_key_first($costCurrencyTotals);

    $totalCostsByCurrency = [];
    foreach ($costCategoryKeys as $key) {
        foreach ($summary[$key] ?? [] as $cur => $amt) {
            $totalCostsByCurrency[$cur] = ($totalCostsByCurrency[$cur] ?? 0) + $amt;
        }
    }

    $marginByCurrency = [];
    $allCurrencies = array_unique(array_merge(array_keys($summary['revenue_by_currency'] ?? []), array_keys($totalCostsByCurrency)));
    foreach ($allCurrencies as $currency) {
        $revenue = $summary['revenue_by_currency'][$currency] ?? 0;
        $costs = $totalCostsByCurrency[$currency] ?? 0;
        $marginByCurrency[$currency] = [
            'amount' => $revenue - $costs,
            'percentage' => $revenue > 0 ? (($revenue - $costs) / $revenue) * 100 : ($costs > 0 ? -100 : 0),
        ];
    }

    // Poprzedni miesiąc - łączne koszty per waluta (do delty m/m)
    $prevTotalCostsByCurrency = [];
    foreach ($costCategoryKeys as $key) {
        foreach ($previousSummary[$key] ?? [] as $cur => $amt) {
            $prevTotalCostsByCurrency[$cur] = ($prevTotalCostsByCurrency[$cur] ?? 0) + $amt;
        }
    }

    // ── Przeliczenie orientacyjne na jedną walutę referencyjną (gdy znany kurs) ──
    // Nie zastępuje kwot źródłowych per-walutowo (wyżej) — daje DODATKOWY, zbiorczy obraz,
    // gdy w grę wchodzi więcej niż jedna waluta. Patrz ProfitabilityService::convertedTotal().
    $referenceCurrency = $dominantCostCurrency ?? array_key_first($summary['revenue_by_currency'] ?? []) ?? 'EUR';
    $profitabilityService = app(\App\Services\ProfitabilityService::class);
    $revenueConverted = $profitabilityService->convertedTotal($summary['revenue_by_currency'] ?? [], $referenceCurrency, $navigation['current']['end']);
    $costsConverted = $profitabilityService->convertedTotal($totalCostsByCurrency, $referenceCurrency, $navigation['current']['end']);
    $showCurrencyConversion = count($allCurrencies) > 1;

    // ── Dane do wykresu margin ranking (top projekty wg marży %) ──────
    $marginRankingRows = collect($projectsProfitability)
        ->filter(fn ($row) => $row['margin_percentage'] !== null)
        ->sortByDesc(fn ($row) => abs($row['margin_percentage']))
        ->take(12)
        ->sortByDesc('margin_percentage')
        ->values();

    $activeFilterCount = ($filters['statuses'] ? 1 : 0) + ($filters['type'] ? 1 : 0) + ($filters['search'] ? 1 : 0);

    // ── Dane dla wykresów JS (osobne zmienne — @json nie radzi sobie z literałami tablic z przecinkami) ─
    $costBreakdownJs = [
        'labels' => ['Praca', 'Projektowe', 'Ogólnofirmowe', 'Transport', 'Najem'],
        'data' => $dominantCostCurrency ? [
            $summary['labor_costs_by_currency'][$dominantCostCurrency] ?? 0,
            $summary['variable_costs_by_currency'][$dominantCostCurrency] ?? 0,
            $summary['fixed_costs_by_currency'][$dominantCostCurrency] ?? 0,
            $summary['transport_costs_by_currency'][$dominantCostCurrency] ?? 0,
            $summary['accommodation_costs_by_currency'][$dominantCostCurrency] ?? 0,
        ] : [],
    ];

    $marginRankingJs = [
        'labels' => $marginRankingRows->map(fn ($r) => $r['project']->name)->values(),
        'data' => $marginRankingRows->map(fn ($r) => $r['margin_percentage'])->values(),
        'revenue' => $marginRankingRows->map(fn ($r) => $r['revenue'])->values(),
        'costs' => $marginRankingRows->map(fn ($r) => $r['total_costs_by_currency'][$r['revenue_currency']] ?? 0)->values(),
        'margin' => $marginRankingRows->map(fn ($r) => $r['margin'])->values(),
        'currency' => $marginRankingRows->map(fn ($r) => $r['revenue_currency'])->values(),
    ];
@endphp

<x-app-layout>
    <style>
        .pdb-stat-card {
            border-radius: .6rem;
            padding: .9rem 1rem;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            height: 100%;
        }
        .pdb-stat-value { font-size: 1.5rem; font-weight: 700; line-height: 1.1; }
        .pdb-stat-label { font-size: .72rem; opacity: .65; text-transform: uppercase; letter-spacing: .04em; }
        .pdb-kpi-card {
            text-align: center;
            padding: 1rem .75rem;
            background: rgba(255,255,255,.03);
            border-radius: .6rem;
            height: 100%;
        }
        .pdb-chart-wrap { position: relative; min-height: 240px; }
        .pdb-filter-dropdown { min-width: 260px; max-height: 320px; overflow-y: auto; }
        .pdb-rank-row { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .4rem 0; border-bottom: 1px solid rgba(255,255,255,.06); }
        .pdb-rank-row:last-child { border-bottom: none; }
        .pdb-table thead th { white-space: nowrap; font-size: .72rem; text-transform: uppercase; letter-spacing: .03em; color: var(--text-main); }
        .pdb-table thead th a { color: var(--text-main) !important; }
        .pdb-table tbody td { font-size: .82rem; vertical-align: middle; }
        .pdb-currency-toggle .btn { font-size: .72rem; padding: .2rem .55rem; }
    </style>

    <div class="py-4">
        <div class="container-xxl">

            <x-ui.table-header
                title="Kontroling — Rentowność projektów"
                subtitle="Analiza finansowa, statystyki i wskaźniki controllingowe dla wybranego miesiąca"
            >
                <x-slot name="actions">
                    <a href="{{ $navigation['exportUrl'] }}" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-download me-1"></i>Eksport CSV
                    </a>
                </x-slot>
            </x-ui.table-header>

            {{-- Nawigacja między miesiącami --}}
            <x-ui.period-nav>
                <x-slot name="prev">
                    <x-ui.button variant="ghost" href="{{ $navigation['prevUrl'] }}" class="w-100">
                        <i class="bi bi-chevron-left"></i>
                        <span>Poprzedni miesiąc</span>
                    </x-ui.button>
                </x-slot>
                <div>
                    <h3 class="fs-5 fw-bold mb-0">{{ $navigation['current']['label'] }}</h3>
                    <p class="small text-muted mb-0">
                        {{ $navigation['current']['start']->format('d.m.Y') }} – {{ $navigation['current']['end']->format('d.m.Y') }}
                    </p>
                </div>
                <x-slot name="next">
                    <x-ui.button variant="primary" href="{{ $navigation['nextUrl'] }}" class="w-100">
                        <span>Następny miesiąc</span>
                        <i class="bi bi-chevron-right"></i>
                    </x-ui.button>
                </x-slot>
            </x-ui.period-nav>

            {{-- ── Filtry ─────────────────────────────────────────────── --}}
            <x-ui.card class="mb-4">
                <form method="GET" action="{{ route('profitability.index') }}" class="d-flex flex-wrap gap-2 align-items-center">
                    <input type="hidden" name="year" value="{{ $navigation['current']['year'] }}">
                    <input type="hidden" name="month" value="{{ $navigation['current']['month'] }}">
                    <input type="hidden" name="sort_by" value="{{ $filters['sortBy'] }}">
                    <input type="hidden" name="sort_dir" value="{{ $filters['sortDir'] }}">

                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                            <i class="bi bi-funnel me-1"></i>Status projektu
                            @if($filters['statuses'])
                                <span class="badge bg-primary ms-1">{{ count($filters['statuses']) }}</span>
                            @endif
                        </button>
                        <div class="dropdown-menu p-3 pdb-filter-dropdown">
                            <p class="small text-muted mb-2">
                                Domyślnie liczą się wszystkie statusy — zakończony projekt też się „odbył”.
                            </p>
                            @foreach(ProjectStatus::cases() as $status)
                                <div class="form-check mb-1">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="statuses[]"
                                        value="{{ $status->value }}"
                                        id="pdb_status_{{ $status->value }}"
                                        {{ (! $filters['statuses'] || in_array($status->value, $filters['statuses'], true)) ? 'checked' : '' }}
                                    >
                                    <label class="form-check-label small" for="pdb_status_{{ $status->value }}">
                                        <x-ui.badge variant="{{ pdbStatusBadgeVariant($status) }}">{{ $status->label() }}</x-ui.badge>
                                    </label>
                                </div>
                            @endforeach
                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100">Zastosuj</button>
                            </div>
                        </div>
                    </div>

                    <select name="type" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                        <option value="">Wszystkie typy</option>
                        @foreach(ProjectType::cases() as $type)
                            <option value="{{ $type->value }}" {{ $filters['type'] === $type->value ? 'selected' : '' }}>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </select>

                    <input
                        type="text"
                        name="search"
                        value="{{ $filters['search'] }}"
                        class="form-control form-control-sm"
                        style="width:220px"
                        placeholder="Szukaj projektu lub klienta…"
                    >

                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-search me-1"></i>Filtruj
                    </button>

                    @if($activeFilterCount > 0)
                        <a href="{{ $resetUrl }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle me-1"></i>Wyczyść filtry
                        </a>
                    @endif

                    <span class="small text-muted ms-auto">
                        {{ count($projectsProfitability) }} {{ count($projectsProfitability) === 1 ? 'projekt' : 'projektów' }} w wybranym miesiącu
                    </span>
                </form>
            </x-ui.card>

            {{-- ── KPI kontrolingowe ──────────────────────────────────── --}}
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="pdb-kpi-card">
                        <div class="pdb-stat-value text-primary">{{ count($projectsProfitability) }}</div>
                        <div class="pdb-stat-label">Projekty w miesiącu</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="pdb-kpi-card">
                        <div class="pdb-stat-value {{ ($breakdown['avg_margin_percentage'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $breakdown['avg_margin_percentage'] !== null ? formatNumber($breakdown['avg_margin_percentage'], 1).'%' : '—' }}
                        </div>
                        <div class="pdb-stat-label d-flex align-items-center justify-content-center gap-1">
                            Śr. marża projektu
                            <x-tooltip title="Średnia marża procentowa (przychód minus koszty pracy i projektowe) liczona po projektach, które miały przychód w tym miesiącu.">
                                <i class="bi bi-info-circle fs-6"></i>
                            </x-tooltip>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="pdb-kpi-card">
                        <div class="pdb-stat-value {{ $breakdown['avg_plan_execution'] >= 100 ? 'text-success' : ($breakdown['avg_plan_execution'] >= 80 ? 'text-warning' : 'text-danger') }}">
                            {{ formatNumber($breakdown['avg_plan_execution'], 1) }}%
                        </div>
                        <div class="pdb-stat-label">Śr. wykonanie planu godzin</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="pdb-kpi-card">
                        <div class="pdb-stat-value">
                            @forelse($marginByCurrency as $currency => $marginData)
                                <div class="{{ $marginData['amount'] >= 0 ? 'text-success' : 'text-danger' }}" style="font-size:1.1rem">
                                    {{ formatNumber($marginData['amount']) }} {!! formatCurrency($currency) !!}
                                </div>
                            @empty
                                <span class="text-muted" style="font-size:1.1rem">—</span>
                            @endforelse
                        </div>
                        <div class="pdb-stat-label">Marża firmowa (miesiąc)</div>
                    </div>
                </div>
            </div>

            {{-- ── Podsumowanie finansowe ─────────────────────────────── --}}
            <div class="row mb-4">
                <div class="col-12">
                    <x-ui.card label="Podsumowanie finansowe miesiąca">
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <div class="text-center p-3 bg-light bg-opacity-10 rounded">
                                    <div class="text-muted small mb-1 d-flex align-items-center justify-content-center gap-1">
                                        Przychody
                                        <x-tooltip title="Suma przychodów ze wszystkich projektów uwzględnionych w wybranym miesiącu (niezależnie od bieżącego statusu projektu — liczy się aktywność w danym miesiącu). Dla projektów zakontraktowanych: proporcja dni miesiąca × kwota kontraktu. Dla godzinowych: stawka godzinowa × zarejestrowane godziny.">
                                            <i class="bi bi-cash-coin text-success fs-6"></i>
                                        </x-tooltip>
                                    </div>
                                    @forelse(($summary['revenue_by_currency'] ?? []) as $currency => $amount)
                                        <div class="h5 mb-0 text-success">{{ formatNumber($amount) }} {!! formatCurrency($currency) !!}</div>
                                        {!! pdbDeltaChip($amount, $previousSummary['revenue_by_currency'][$currency] ?? 0, true) !!}
                                    @empty
                                        <div class="h5 mb-0 text-muted">—</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-center p-3 bg-light bg-opacity-10 rounded">
                                    <div class="text-muted small mb-1 d-flex align-items-center justify-content-center gap-1">
                                        Koszty pracy
                                        <x-tooltip title="Suma kosztów pracy dla wszystkich pracowników przypisanych do projektów w wybranym miesiącu.">
                                            <i class="bi bi-people text-danger fs-6"></i>
                                        </x-tooltip>
                                    </div>
                                    @forelse(($summary['labor_costs_by_currency'] ?? []) as $currency => $amount)
                                        <div class="h5 mb-0 text-danger">{{ formatNumber($amount) }} {!! formatCurrency($currency) !!}</div>
                                        {!! pdbDeltaChip($amount, $previousSummary['labor_costs_by_currency'][$currency] ?? 0, false) !!}
                                    @empty
                                        <div class="h5 mb-0 text-muted">—</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-center p-3 bg-light bg-opacity-10 rounded">
                                    <div class="text-muted small mb-1 d-flex align-items-center justify-content-center gap-1">
                                        Koszty projektowe
                                        <x-tooltip title="Suma kosztów zmiennych przypisanych do projektów w wybranym miesiącu (materiały, sprzęt, podwykonawcy itp.).">
                                            <i class="bi bi-arrow-repeat text-warning fs-6"></i>
                                        </x-tooltip>
                                    </div>
                                    @forelse(($summary['variable_costs_by_currency'] ?? []) as $currency => $amount)
                                        <div class="h5 mb-0 text-warning">{{ formatNumber($amount) }} {!! formatCurrency($currency) !!}</div>
                                        {!! pdbDeltaChip($amount, $previousSummary['variable_costs_by_currency'][$currency] ?? 0, false) !!}
                                    @empty
                                        <div class="h5 mb-0 text-muted">—</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-center p-3 bg-light bg-opacity-10 rounded">
                                    <div class="text-muted small mb-1 d-flex align-items-center justify-content-center gap-1">
                                        Koszty ogólnofirmowe
                                        <x-tooltip title="Suma kosztów stałych (ogólnofirmowych) w wybranym miesiącu — księgowość, leasingi, opłaty administracyjne itp. Nie są przypisane do konkretnych projektów.">
                                            <i class="bi bi-lock text-info fs-6"></i>
                                        </x-tooltip>
                                    </div>
                                    @forelse(($summary['fixed_costs_by_currency'] ?? []) as $currency => $amount)
                                        <div class="h5 mb-0 text-info">{{ formatNumber($amount) }} {!! formatCurrency($currency) !!}</div>
                                        {!! pdbDeltaChip($amount, $previousSummary['fixed_costs_by_currency'][$currency] ?? 0, false) !!}
                                    @empty
                                        <div class="h5 mb-0 text-muted">—</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-center p-3 bg-light bg-opacity-10 rounded">
                                    <div class="text-muted small mb-1 d-flex align-items-center justify-content-center gap-1">
                                        Koszty transportu
                                        <x-tooltip title="Suma kosztów transportu (paliwo, bilety, parkingi, opłaty drogowe) w wybranym miesiącu, wg daty poniesienia. Liczone ogólnofirmowo — nie per projekt, bo jeden wyjazd/transfer często obsługuje pracowników z różnych projektów naraz.">
                                            <i class="bi bi-truck text-warning fs-6"></i>
                                        </x-tooltip>
                                    </div>
                                    @forelse(($summary['transport_costs_by_currency'] ?? []) as $currency => $amount)
                                        <div class="h5 mb-0 text-warning">{{ formatNumber($amount) }} {!! formatCurrency($currency) !!}</div>
                                        {!! pdbDeltaChip($amount, $previousSummary['transport_costs_by_currency'][$currency] ?? 0, false) !!}
                                    @empty
                                        <div class="h5 mb-0 text-muted">—</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-center p-3 bg-light bg-opacity-10 rounded">
                                    <div class="text-muted small mb-1 d-flex align-items-center justify-content-center gap-1">
                                        Koszty najmu
                                        <x-tooltip title="Czynsze za mieszkania (accommodation_leases) przypadające na wybrany miesiąc. Umowy na czas określony: kwota za cały okres podzielona proporcjonalnie na miesiące. Umowy bezterminowe: stawka miesięczna. Liczone ogólnofirmowo — nie per projekt.">
                                            <i class="bi bi-building text-purple fs-6" style="color:#8b5cf6"></i>
                                        </x-tooltip>
                                    </div>
                                    @forelse(($summary['accommodation_costs_by_currency'] ?? []) as $currency => $amount)
                                        <div class="h5 mb-0" style="color:#8b5cf6">{{ formatNumber($amount) }} {!! formatCurrency($currency) !!}</div>
                                        {!! pdbDeltaChip($amount, $previousSummary['accommodation_costs_by_currency'][$currency] ?? 0, false) !!}
                                    @empty
                                        <div class="h5 mb-0 text-muted">—</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-center p-3 bg-light bg-opacity-10 rounded">
                                    <div class="text-muted small mb-1 d-flex align-items-center justify-content-center gap-1">
                                        Łączne koszty
                                        <x-tooltip title="Koszty pracy + koszty projektowe + koszty ogólnofirmowe + koszty transportu + koszty najmu w wybranym miesiącu.">
                                            <i class="bi bi-calculator text-danger fs-6"></i>
                                        </x-tooltip>
                                    </div>
                                    @forelse($totalCostsByCurrency as $currency => $amount)
                                        <div class="h5 mb-0 text-danger">{{ formatNumber($amount) }} {!! formatCurrency($currency) !!}</div>
                                        {!! pdbDeltaChip($amount, $prevTotalCostsByCurrency[$currency] ?? 0, false) !!}
                                    @empty
                                        <div class="h5 mb-0 text-muted">—</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-center p-3 bg-light bg-opacity-10 rounded">
                                    <div class="text-muted small mb-1 d-flex align-items-center justify-content-center gap-1">
                                        Marża
                                        <x-tooltip title="Przychody minus łączne koszty (praca + projektowe + ogólnofirmowe + transport + najem). Wartość dodatnia = zysk, ujemna = strata.">
                                            <i class="bi bi-graph-up-arrow text-success fs-6"></i>
                                        </x-tooltip>
                                    </div>
                                    @forelse($marginByCurrency as $currency => $marginData)
                                        <div class="h5 mb-0 {{ $marginData['amount'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ formatNumber($marginData['amount']) }} {!! formatCurrency($currency) !!}
                                        </div>
                                        <div class="small text-muted">({{ formatNumber($marginData['percentage'], 1) }}%)</div>
                                    @empty
                                        <div class="h5 mb-0 text-muted">—</div>
                                    @endforelse
                                </div>
                            </div>
                            @if($showCurrencyConversion)
                            <div class="col-6 col-md-3">
                                <div class="text-center p-3 bg-light bg-opacity-10 rounded border" style="border-color:rgba(99,102,241,.35) !important">
                                    <div class="text-muted small mb-1 d-flex align-items-center justify-content-center gap-1">
                                        Marża po przeliczeniu
                                        <x-tooltip title="Orientacyjna marża po przeliczeniu WSZYSTKICH walut na {{ $referenceCurrency }} wg ostatniego znanego kursu ({{ $navigation['current']['end']->format('d.m.Y') }} lub wcześniej). Kwoty źródłowe per-walutowo (wyżej) się NIE zmieniają — to tylko dodatkowy, zbiorczy pogląd. Zarządzaj kursami w sekcji „Kursy walut”.">
                                            <i class="bi bi-currency-exchange text-primary fs-6"></i>
                                        </x-tooltip>
                                    </div>
                                    @php $marginConverted = $revenueConverted['total'] - $costsConverted['total']; @endphp
                                    <div class="h5 mb-0 {{ $marginConverted >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ formatNumber($marginConverted) }} {!! formatCurrency($referenceCurrency) !!}
                                    </div>
                                    @if($revenueConverted['unconverted'] || $costsConverted['unconverted'])
                                        <div class="small text-muted">
                                            <i class="bi bi-exclamation-triangle text-warning"></i> brak kursu dla części kwot
                                        </div>
                                    @else
                                        <div class="small text-muted">wszystkie waluty przeliczone</div>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                    </x-ui.card>
                </div>
            </div>

            {{-- ── Wykresy kontrolingowe ──────────────────────────────── --}}
            <div class="row g-3 mb-4">
                <div class="col-lg-4">
                    <x-ui.card class="h-100" label="Struktura kosztów (miesiąc)">
                        @if($dominantCostCurrency)
                            <div class="pdb-chart-wrap" style="min-height:220px">
                                <canvas id="pdbCostDonut"></canvas>
                            </div>
                            <p class="small text-muted text-center mb-0 mt-2">Waluta: {{ $dominantCostCurrency }}</p>
                        @else
                            <x-ui.empty-state icon="pie-chart" message="Brak kosztów w tym miesiącu" />
                        @endif
                    </x-ui.card>
                </div>
                <div class="col-lg-8">
                    <x-ui.card class="h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <span class="card-label mb-0">Trend 12 miesięcy — przychody, koszty, marża</span>
                            @if(count($trend['currencies']) > 1)
                                <div class="btn-group pdb-currency-toggle" role="group">
                                    @foreach(array_keys($trend['currencies']) as $i => $currency)
                                        <button type="button" class="btn btn-outline-secondary pdb-currency-btn {{ $i === 0 ? 'active' : '' }}" data-currency="{{ $currency }}">
                                            {{ $currency }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @if(count($trend['currencies']) > 0)
                            <div class="pdb-chart-wrap" style="min-height:220px">
                                <canvas id="pdbTrendChart"></canvas>
                            </div>
                        @else
                            <x-ui.empty-state icon="graph-up" message="Brak danych historycznych do wyświetlenia trendu" />
                        @endif
                    </x-ui.card>
                </div>
            </div>

            @if($marginRankingRows->isNotEmpty())
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <x-ui.card label="Marża % wg projektu (miesiąc)">
                        <div class="d-flex align-items-center gap-3 flex-wrap mb-2 small text-muted">
                            <span class="d-inline-flex align-items-center gap-1"><span style="width:10px;height:10px;border-radius:2px;background:#10b981;display:inline-block"></span> Zysk (marża ≥ 0%)</span>
                            <span class="d-inline-flex align-items-center gap-1"><span style="width:10px;height:10px;border-radius:2px;background:#ef4444;display:inline-block"></span> Strata (marża &lt; 0%)</span>
                            <x-tooltip title="Marża % = (Przychód − Koszty pracy − Koszty projektowe) / Przychód × 100. Nie uwzględnia kosztów ogólnofirmowych, transportu i najmu — te rozliczane są firmowo, nie per projekt. Najedź na słupek, aby zobaczyć pełne kwoty (przychód / koszty / zysk).">
                                <span class="d-inline-flex align-items-center gap-1 text-decoration-underline" style="text-decoration-style:dotted;cursor:help"><i class="bi bi-info-circle"></i> Jak liczona jest marża %?</span>
                            </x-tooltip>
                        </div>
                        <div class="pdb-chart-wrap" style="min-height:{{ max(200, $marginRankingRows->count() * 34) }}px">
                            <canvas id="pdbMarginRankChart"></canvas>
                        </div>
                    </x-ui.card>
                </div>
            </div>
            @endif

            {{-- ── Rankingi rentowności ────────────────────────────────── --}}
            <div class="row g-3 mb-4">
                <div class="col-lg-6">
                    <x-ui.card label="Najbardziej rentowne projekty" class="h-100">
                        @forelse($rankings['best'] as $row)
                            <div class="pdb-rank-row">
                                <a href="{{ route('projects.show', $row['project']) }}" class="text-decoration-none text-truncate me-2" style="max-width:65%">
                                    {{ $row['project']->name }}
                                </a>
                                <span class="fw-bold text-success">+{{ formatNumber($row['margin_percentage'], 1) }}%</span>
                            </div>
                        @empty
                            <x-ui.empty-state icon="graph-up-arrow" message="Brak danych o marży" />
                        @endforelse
                    </x-ui.card>
                </div>
                <div class="col-lg-6">
                    <x-ui.card label="Najmniej rentowne projekty" class="h-100">
                        @forelse($rankings['worst'] as $row)
                            <div class="pdb-rank-row">
                                <a href="{{ route('projects.show', $row['project']) }}" class="text-decoration-none text-truncate me-2" style="max-width:65%">
                                    {{ $row['project']->name }}
                                </a>
                                <span class="fw-bold {{ $row['margin_percentage'] < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $row['margin_percentage'] >= 0 ? '+' : '' }}{{ formatNumber($row['margin_percentage'], 1) }}%
                                </span>
                            </div>
                        @empty
                            <x-ui.empty-state icon="graph-down-arrow" message="Brak danych o marży" />
                        @endforelse
                    </x-ui.card>
                </div>
            </div>

            {{-- ── Koszty najmu — Top mieszkania (koszt / osobonoc) ──────── --}}
            @if(!empty($topAccommodations))
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <x-ui.card>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="card-label mb-0">Koszty najmu — Top mieszkania</span>
                            <x-tooltip title="Czynsz przypadający na wybrany miesiąc (pro-rated wg okresu obowiązywania umowy — patrz Uwagi metodologiczne) podzielony przez liczbę osobonocy (suma dni × liczba przypisanych osób) w tym samym okresie. Niższy koszt/osobonoc = efektywniejsze wykorzystanie mieszkania.">
                                <i class="bi bi-info-circle text-muted" style="font-size:.75rem"></i>
                            </x-tooltip>
                        </div>
                        <p class="small text-muted mb-3">Sortowane po koszcie w okresie · w nawiasie koszt na osobę/noc</p>

                        @php $maxAccAmount = max(1, collect($topAccommodations)->max('amount')); @endphp
                        <div class="d-flex flex-column">
                            @foreach($topAccommodations as $i => $item)
                                @php
                                    $accLease = $item['lease'];
                                    $accommodation = $accLease->accommodation;
                                    $accName = $accommodation->name ?? ('Najem #'.$accLease->id);
                                    $accCity = $accommodation->city ?? null;
                                    $accPct = max(2, round($item['amount'] / $maxAccAmount * 100));
                                @endphp
                                <div class="d-flex align-items-center gap-2 py-1 border-bottom" style="border-color:var(--glass-border) !important">
                                    <span class="text-muted" style="font-size:.7rem;min-width:20px">#{{ $i + 1 }}</span>
                                    <span class="flex-grow-1 small text-truncate" title="{{ $accName }}{{ $accCity ? ', '.$accCity : '' }}">
                                        @if($accommodation)
                                            <a href="{{ route('accommodations.show', $accommodation) }}" class="text-decoration-none text-body">{{ $accName }}{{ $accCity ? ', '.$accCity : '' }}</a>
                                        @else
                                            {{ $accName }}
                                        @endif
                                    </span>
                                    <span class="text-muted text-nowrap" style="font-size:.7rem">{{ $item['person_nights'] }} os-nocy</span>
                                    <span class="text-nowrap" style="font-size:.7rem;color:#8b5cf6;min-width:120px;text-align:right">
                                        {{ $item['cost_per_person_night'] !== null ? formatNumber($item['cost_per_person_night']).' '.$item['currency'].' / os-noc' : '—' }}
                                    </span>
                                    <div style="width:80px;height:4px;background:rgba(255,255,255,.07);border-radius:2px;overflow:hidden;flex-shrink:0">
                                        <div style="width:{{ $accPct }}%;height:100%;background:#8b5cf6;border-radius:2px"></div>
                                    </div>
                                    <span class="fw-semibold text-nowrap" style="font-size:.8rem;min-width:100px;text-align:right;color:#8b5cf6">
                                        {{ formatNumber($item['amount']) }} {{ $item['currency'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </x-ui.card>
                </div>
            </div>
            @endif

            {{-- ── Tabela projektów ───────────────────────────────────── --}}
            <div class="row mb-4">
                <div class="col-12">
                    <x-ui.card label="Projekty — szczegóły rentowności">
                        @if(count($projectsProfitability) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-hover pdb-table mb-0">
                                    <thead>
                                        <tr>
                                            <th><a href="{{ $sortLinkUrl('name') }}" class="text-decoration-none text-body">Projekt {!! $sortIconHtml('name') !!}</a></th>
                                            <th><a href="{{ $sortLinkUrl('status') }}" class="text-decoration-none text-body">Status {!! $sortIconHtml('status') !!}</a></th>
                                            <th><a href="{{ $sortLinkUrl('type') }}" class="text-decoration-none text-body">Typ {!! $sortIconHtml('type') !!}</a></th>
                                            <th class="text-end"><a href="{{ $sortLinkUrl('revenue') }}" class="text-decoration-none text-body">Przychód {!! $sortIconHtml('revenue') !!}</a></th>
                                            <th class="text-end">Koszty pracy</th>
                                            <th class="text-end">Koszty projekt.</th>
                                            <th class="text-end"><a href="{{ $sortLinkUrl('margin') }}" class="text-decoration-none text-body">Marża {!! $sortIconHtml('margin') !!}</a></th>
                                            <th class="text-end"><a href="{{ $sortLinkUrl('margin_percentage') }}" class="text-decoration-none text-body">Marża % {!! $sortIconHtml('margin_percentage') !!}</a></th>
                                            <th class="text-end"><a href="{{ $sortLinkUrl('actual_hours') }}" class="text-decoration-none text-body">Godziny {!! $sortIconHtml('actual_hours') !!}</a></th>
                                            <th class="text-end"><a href="{{ $sortLinkUrl('plan_execution') }}" class="text-decoration-none text-body">Plan {!! $sortIconHtml('plan_execution') !!}</a></th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($projectsProfitability as $row)
                                            @php $project = $row['project']; $cur = $row['revenue_currency']; @endphp
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold">{{ $project->name }}</div>
                                                    <div class="small text-muted">{{ $project->client_name ?? 'Brak klienta' }}</div>
                                                </td>
                                                <td>
                                                    <x-ui.badge variant="{{ pdbStatusBadgeVariant($project->status) }}">
                                                        {{ $project->status?->label() ?? '—' }}
                                                    </x-ui.badge>
                                                </td>
                                                <td><span class="small">{{ $project->type?->label() ?? '—' }}</span></td>
                                                <td class="text-end text-success fw-semibold">
                                                    {{ formatNumber($row['revenue']) }} {!! formatCurrency($cur) !!}
                                                </td>
                                                <td class="text-end text-danger">
                                                    {{ formatNumber($row['labor_costs_by_currency'][$cur] ?? 0) }} {!! formatCurrency($cur) !!}
                                                </td>
                                                <td class="text-end text-warning">
                                                    {{ formatNumber($row['variable_costs_by_currency'][$cur] ?? 0) }} {!! formatCurrency($cur) !!}
                                                </td>
                                                <td class="text-end fw-semibold {{ $row['margin'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ formatNumber($row['margin']) }} {!! formatCurrency($cur) !!}
                                                    @if($row['has_costs_in_other_currencies'])
                                                        @php
                                                            $otherCurrencyParts = [];
                                                            foreach (collect($row['total_costs_by_currency'])->except($cur)->filter(fn ($v) => abs((float) $v) > 0.00001) as $ocCur => $ocAmt) {
                                                                $ocConverted = \App\Models\ExchangeRate::convert((float) $ocAmt, $ocCur, $cur, $navigation['current']['end']);
                                                                $otherCurrencyParts[] = formatNumber($ocAmt).' '.$ocCur.($ocConverted !== null ? ' (~'.formatNumber($ocConverted).' '.$cur.')' : ' (brak kursu do '.$cur.')');
                                                            }
                                                        @endphp
                                                        <x-tooltip title="Ten projekt ma też koszty w innej walucie niż przychód, nieujęte w marży: {{ implode(', ', $otherCurrencyParts) }}. Kwoty w nawiasach to orientacyjne przeliczenie wg ostatniego znanego kursu — zarządzaj kursami w sekcji „Kursy walut”.">
                                                            <i class="bi bi-info-circle text-muted" style="font-size:.7rem"></i>
                                                        </x-tooltip>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if($row['margin_percentage'] !== null)
                                                        <span class="badge {{ $row['margin_percentage'] >= 0 ? 'text-bg-success' : 'text-bg-danger' }}">
                                                            {{ $row['margin_percentage'] >= 0 ? '+' : '' }}{{ formatNumber($row['margin_percentage'], 1) }}%
                                                        </span>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-end small">
                                                    {{ formatNumber($row['actual_hours'], 1) }}h
                                                    <span class="text-muted">/ {{ formatNumber($row['estimated_hours'], 1) }}h</span>
                                                </td>
                                                <td class="text-end">
                                                    <x-ui.badge variant="{{ $row['plan_execution'] >= 100 ? 'success' : ($row['plan_execution'] >= 80 ? 'warning' : 'danger') }}">
                                                        {{ formatNumber($row['plan_execution'], 0) }}%
                                                    </x-ui.badge>
                                                </td>
                                                <td class="text-end">
                                                    <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <x-ui.empty-state
                                icon="folder-x"
                                message="Brak projektów spełniających kryteria w wybranym miesiącu"
                                :hasFilters="$activeFilterCount > 0"
                                :clearFiltersAction="$resetUrl"
                            />
                        @endif
                    </x-ui.card>
                </div>
            </div>

            {{-- ── Statystyki pracownicze ─────────────────────────────── --}}
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <x-ui.card label="Najlepsi pracownicy (przychody, miesiąc)">
                        @if(count($topEmployees) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Pracownik</th>
                                            <th class="text-end">Godziny</th>
                                            <th class="text-end">Przychody</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($topEmployees as $employeeData)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('employees.show', $employeeData['employee']) }}" class="text-decoration-none">
                                                        {{ $employeeData['employee']->full_name }}
                                                    </a>
                                                </td>
                                                <td class="text-end">{{ formatNumber($employeeData['total_hours']) }}h</td>
                                                <td class="text-end">
                                                    @forelse(($employeeData['total_revenue_by_currency'] ?? []) as $currency => $revenue)
                                                        <strong>{{ formatNumber($revenue) }} {!! formatCurrency($currency) !!}</strong>
                                                        @if(!$loop->last)<br>@endif
                                                    @empty
                                                        <strong>0 {!! formatCurrency('EUR') !!}</strong>
                                                    @endforelse
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <x-ui.empty-state icon="people" message="Brak danych o pracownikach" />
                        @endif
                    </x-ui.card>
                </div>

                <div class="col-lg-6 mb-4">
                    <x-ui.card label="Najdłuższe rotacje (całościowo)">
                        @if(count($longestRotations) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Pracownik</th>
                                            <th class="text-end">Dni</th>
                                            <th class="text-end">Rotacji</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($longestRotations as $rotationData)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('employees.show', $rotationData['employee']) }}" class="text-decoration-none">
                                                        {{ $rotationData['employee']->full_name }}
                                                    </a>
                                                </td>
                                                <td class="text-end"><strong>{{ formatNumber($rotationData['total_days'], 0) }}</strong></td>
                                                <td class="text-end">{{ $rotationData['rotation_count'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p class="small text-muted mb-0 mt-2">
                                <i class="bi bi-info-circle me-1"></i>Ranking uwzględnia wszystkie rotacje pracownika (nie tylko wybrany miesiąc).
                            </p>
                        @else
                            <x-ui.empty-state icon="arrow-repeat" message="Brak danych o rotacjach" />
                        @endif
                    </x-ui.card>
                </div>
            </div>

            {{-- ── Uwagi metodologiczne ───────────────────────────────── --}}
            <x-ui.card class="mb-4">
                <div class="small text-muted">
                    <div class="fw-semibold text-body mb-2"><i class="bi bi-info-circle me-1"></i>Uwagi metodologiczne</div>
                    <ul class="mb-0 ps-3">
                        <li>Projekt liczy się do danego miesiąca na podstawie aktywności w tym okresie (przypisania, koszty, zakres dat) — niezależnie od jego bieżącego statusu. Zakończony projekt nadal poprawnie wlicza się do historii miesięcy, w których się odbywał.</li>
                        <li>Marża <strong>projektu</strong> uwzględnia tylko koszty pracy i koszty projektowe (zmienne). Koszty <strong>ogólnofirmowe, transportu i najmu</strong> nie są przypisywane do konkretnego projektu — rozliczane są firmowo i wchodzą tylko do marży firmowej w podsumowaniu miesiąca.</li>
                        <li><strong>Transport</strong> (koszty logistyczne) traktowany jest jako koszt ogólnofirmowy, ponieważ jeden wyjazd/transfer często obsługuje pracowników z różnych projektów naraz — alokacja per-projekt byłaby myląca. Liczony wg daty poniesienia kosztu (<code>cost_date</code>).</li>
                        <li><strong>Najem mieszkań</strong> również jest kosztem ogólnofirmowym (te same mieszkania mieszczą pracowników z różnych projektów). Kwota za miesiąc liczona jest hybrydowo: dla umów na czas określony <code>monthly_rent</code> to kwota za CAŁY okres umowy, dzielona proporcjonalnie do liczby dni w każdym miesiącu (np. umowa 2 miesiące za 200€ → 100€/miesiąc); dla umów bezterminowych <code>monthly_rent</code> to nawracająca stawka miesięczna. Sekcja „Top mieszkania” dodatkowo dzieli ten koszt przez liczbę osobonocy (dni × liczba przypisanych osób), by ocenić efektywność wykorzystania mieszkania.</li>
                        <li>Koszty w walucie innej niż waluta przychodu projektu nie wchodzą do marży <strong>projektu</strong> (marża per-projekt liczona jest tylko w walucie przychodu) — oznaczone ikoną <i class="bi bi-info-circle"></i> w tabeli, z podglądem kwoty i orientacyjnego przeliczenia po najechaniu. Na poziomie <strong>firmy</strong> podsumowanie dodatkowo pokazuje kartę „Marża po przeliczeniu” — orientacyjną sumę wszystkich walut przeliczoną na jedną walutę referencyjną wg kursów zdefiniowanych w sekcji <a href="{{ route('exchange-rates.index') }}" class="text-decoration-none">Kursy walut</a>. To przeliczenie jest wyłącznie poglądowe i nigdy nie zmienia kwot źródłowych.</li>
                        <li>Ranking „Najdłuższe rotacje” obejmuje całą historię pracownika, niezależnie od wybranego miesiąca.</li>
                    </ul>
                </div>
            </x-ui.card>

            {{-- ── Metodologia kontrolingu — pełny opis ───────────────── --}}
            <x-ui.card class="mb-4">
                <div class="small text-muted">
                    <div class="fw-semibold text-body mb-2"><i class="bi bi-journal-text me-1"></i>Metodologia liczenia kosztów i propozycje lepszego zbierania danych</div>
                    <p class="mb-2">
                        Kontroling rozdziela koszty na dwa poziomy: <strong>per-projekt</strong> (praca + koszty zmienne — wpływają na marżę konkretnego projektu)
                        i <strong>ogólnofirmowy</strong> (koszty stałe, transport, najem — wspólne dla wielu projektów naraz, wpływają tylko na marżę firmy).
                        Taki podział odzwierciedla rzeczywistość: kierowca wioząc ludzi na trasie czy mieszkanie z pracownikami z różnych ekip nie da się jednoznacznie i sprawiedliwie przypisać do jednego projektu bez dodatkowej pracy ewidencyjnej.
                    </p>
                    <p class="mb-2">
                        <strong>Co można zbierać lepiej, by w przyszłości podnieść precyzję (opcjonalnie, per projekt):</strong>
                    </p>
                    <ul class="mb-2 ps-3">
                        <li><code>accounting_date</code> dla <code>TransportCost</code> i <code>AccommodationLease</code> — obecnie <code>FixedCostEntry</code> ma osobne pole na datę poniesienia i datę księgowania, a transport/najem nie. Dodanie tego pola pozwoliłoby rozróżnić „kiedy koszt faktycznie powstał” od „w którym miesiącu został zaksięgowany”, co ułatwi zamykanie miesięcy zgodnie z zasadą memoriału.</li>
                        <li>Opcjonalne powiązanie <code>TransportCost</code> z <code>ProjectAssignment</code> / <code>LogisticsEvent</code> (relacja już istnieje) pozwoliłoby w przyszłości wprowadzić <em>podział proporcjonalny</em> kosztu transportu między projekty obsłużone w danym zdarzeniu logistycznym — bez wymuszania jednoznacznej alokacji tam, gdzie jest to niemożliwe.</li>
                        <li>Podobnie <code>AccommodationAssignment</code> już wiąże pracownika z projektem (przez przypisanie) — można by wyliczać udział kosztu najmu proporcjonalnie do liczby osobonocy zajmowanych przez pracowników danego projektu w danym mieszkaniu, jeśli w przyszłości potrzebna będzie dokładniejsza rentowność projektowa uwzględniająca zakwaterowanie.</li>
                        <li>Ujednolicenie walut kosztów projektowych z walutą kontraktu (albo systematyczne uzupełnianie kursów w sekcji „Kursy walut”) zmniejszyłoby liczbę przypadków „kosztów w innej walucie” i podniosło wiarygodność marży per-projekt.</li>
                    </ul>
                    <p class="mb-0">
                        Powyższe usprawnienia są przygotowane pod kątem ewolucji, ale nie są wymagane do działania obecnego modelu — dashboard już teraz daje pełny, uczciwy obraz kosztów firmy (nic nie „gubi się” w podziale projekt/firma), tylko z mniejszą granularnością przypisania tam, gdzie rzeczywistość biznesowa i tak nie pozwala na jednoznaczny podział.
                    </p>
                </div>
            </x-ui.card>

        </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        const PALETTE = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'];

        function mkAlpha(hex, a) {
            const r = parseInt(hex.slice(1,3),16);
            const g = parseInt(hex.slice(3,5),16);
            const b = parseInt(hex.slice(5,7),16);
            return `rgba(${r},${g},${b},${a})`;
        }

        const costBreakdown = @json($costBreakdownJs);

        const trendData = @json($trend);

        const marginRanking = @json($marginRankingJs);

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

        let trendChart = null;

        function renderTrendChart(currency) {
            const el = document.getElementById('pdbTrendChart');
            if (!el || !window.Chart) return;
            const d = trendData.currencies[currency];
            if (!d) return;

            if (trendChart) { trendChart.destroy(); }

            trendChart = new Chart(el.getContext('2d'), {
                type: 'line',
                data: {
                    labels: trendData.labels,
                    datasets: [
                        {
                            label: 'Przychód (' + currency + ')',
                            data: d.revenue,
                            borderColor: '#10b981',
                            backgroundColor: mkAlpha('#10b981', .12),
                            fill: true,
                            tension: .3,
                        },
                        {
                            label: 'Koszty (' + currency + ')',
                            data: d.costs,
                            borderColor: '#ef4444',
                            backgroundColor: mkAlpha('#ef4444', .08),
                            fill: true,
                            tension: .3,
                        },
                        {
                            label: 'Marża (' + currency + ')',
                            data: d.margin,
                            borderColor: '#3b82f6',
                            backgroundColor: mkAlpha('#3b82f6', .06),
                            borderDash: [5, 4],
                            fill: false,
                            tension: .3,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 10, padding: 8, font: { size: 10 } } },
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: { title: { display: true, text: currency, font: { size: 10 } } },
                    },
                },
            });
        }

        function initCharts() {
            if (!window.Chart) return;

            Chart.defaults.color = 'rgba(255,255,255,0.45)';
            Chart.defaults.borderColor = 'rgba(255,255,255,0.08)';
            Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
            Chart.defaults.font.size = 11;

            if (document.getElementById('pdbCostDonut') && costBreakdown.data.length) {
                const donutColors = ['#ef4444', '#f59e0b', '#06b6d4', '#3b82f6', '#8b5cf6'];
                mkChart('pdbCostDonut', {
                    type: 'doughnut',
                    data: {
                        labels: costBreakdown.labels,
                        datasets: [{
                            data: costBreakdown.data,
                            backgroundColor: donutColors.map(c => mkAlpha(c, .25)),
                            borderColor: donutColors,
                            borderWidth: 2,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '58%',
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 10, padding: 8, font: { size: 10 } } },
                        },
                    },
                });
            }

            const currencies = Object.keys(trendData.currencies || {});
            if (currencies.length) {
                renderTrendChart(currencies[0]);
                document.querySelectorAll('.pdb-currency-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        document.querySelectorAll('.pdb-currency-btn').forEach(b => b.classList.remove('active'));
                        this.classList.add('active');
                        renderTrendChart(this.dataset.currency);
                    });
                });
            }

            if (document.getElementById('pdbMarginRankChart') && marginRanking.labels.length) {
                const fmtMoney = (v, cur) => Number(v).toLocaleString('pl-PL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + cur;
                mkChart('pdbMarginRankChart', {
                    type: 'bar',
                    data: {
                        labels: marginRanking.labels,
                        datasets: [{
                            label: 'Marża %',
                            data: marginRanking.data,
                            backgroundColor: marginRanking.data.map(v => v >= 0 ? mkAlpha('#10b981', .6) : mkAlpha('#ef4444', .6)),
                            borderColor: marginRanking.data.map(v => v >= 0 ? '#10b981' : '#ef4444'),
                            borderWidth: 1,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    title: ctx => marginRanking.labels[ctx[0].dataIndex],
                                    label: ctx => {
                                        const i = ctx.dataIndex;
                                        const cur = marginRanking.currency[i] || '';
                                        return [
                                            `Marża: ${ctx.raw}%`,
                                            `Przychód: ${fmtMoney(marginRanking.revenue[i], cur)}`,
                                            `Koszty: ${fmtMoney(marginRanking.costs[i], cur)}`,
                                            `Zysk/strata: ${fmtMoney(marginRanking.margin[i], cur)}`,
                                        ];
                                    },
                                },
                            },
                        },
                        scales: {
                            x: { title: { display: true, text: 'Marża %', font: { size: 10 } } },
                            y: { grid: { display: false } },
                        },
                    },
                });
            }
        }

        loadChartJs().then(initCharts);
    })();
    </script>
    @endpush
</x-app-layout>
