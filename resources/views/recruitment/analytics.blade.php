@php
    use App\Enums\RecruitmentContactOutcome;
    use App\Services\RecruitmentAnalyticsService;

    $eh = $engagement['headline'];
    $calls = $engagement['calls'];
    $funnel = $engagement['funnel'];

    $lh = $longTerm['headline'];
    $q = $longTerm['dataQuality'];
    $wq = $longTerm['workQueue'];
    $oq = $longTerm['ownerQueue'];
    $rt = $longTerm['response'];

    /** Renders a percentage that may legitimately be unknown (empty denominator). */
    $pct = fn (?float $v, string $fallback = '—') => $v === null ? $fallback : rtrim(rtrim(number_format($v, 1, ',', ' '), '0'), ',').'%';
    $num = fn ($v) => number_format((float) $v, 0, ',', ' ');

    // Backfilled employees would otherwise show up as free wins and inflate this.
    $realConversion = $lh['leads_real'] > 0
        ? round($lh['hired_real'] * 100 / $lh['leads_real'], 2)
        : null;

    // One palette drives both the doughnut slices and the legend beside it.
    $palette = ['#3b82f6', '#a855f7', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#ec4899', '#84cc16', '#f97316', '#6366f1'];

    $callSlices = [];
    foreach ($calls['rows'] as $i => $row) {
        $details = [
            'Udział: '.$pct($row['share']).' wszystkich telefonów',
            'Kandydaci: '.$num($row['processes']),
            'Prób na kandydata: '.number_format($row['calls_per_process'], 2, ',', ' '),
            'Skuteczność dodzwonienia: '.$pct($row['answer_rate']),
        ];
        foreach (RecruitmentContactOutcome::cases() as $case) {
            $n = $row['outcomes'][$case->value] ?? 0;
            if ($n > 0) {
                $details[] = $case->label().': '.$num($n);
            }
        }

        $callSlices[] = [
            'name' => $row['name'],
            'calls' => $row['calls'],
            'share' => $row['share'],
            'color' => $palette[$i % count($palette)],
            'details' => $details,
        ];
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Analityka rekrutacji">
            <x-slot name="right">
                <div class="btn-group btn-group-sm" role="group" aria-label="Perspektywa">
                    @foreach($periods as $key => $p)
                        <a href="{{ $nav['switchUrls'][$key] }}"
                           class="btn {{ $period === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                            {{ $p['label'] }}
                        </a>
                    @endforeach
                </div>
                <a href="{{ $nav['jumpUrl'] }}"
                   class="btn btn-sm btn-outline-primary {{ $nav['isCurrent'] ? 'disabled' : '' }}"
                   @if($nav['isCurrent']) aria-disabled="true" tabindex="-1" @endif>
                    <i class="bi bi-crosshair me-1"></i>{{ $nav['jumpLabel'] }}
                </a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    {{-- Layout stackuje tylko skrypty, więc styl sekcji ląduje inline (jak w kontrolingu). --}}
    <style>
            .ra-kpi {
                background: rgba(255, 255, 255, .03);
                border: 1px solid var(--glass-border);
                border-radius: .6rem;
                padding: .9rem 1rem;
                height: 100%;
            }
            .ra-kpi__label {
                font-size: .68rem;
                text-transform: uppercase;
                letter-spacing: .05em;
                color: var(--text-muted);
                margin-bottom: .35rem;
            }
            .ra-kpi__value { font-size: 1.6rem; font-weight: 700; line-height: 1.1; }
            .ra-kpi__hint { font-size: .7rem; color: var(--text-muted); margin-top: .3rem; }

            .ra-section {
                display: flex;
                align-items: flex-end;
                justify-content: space-between;
                gap: 1rem;
                flex-wrap: wrap;
                padding-bottom: .6rem;
                margin: 0 0 1.25rem;
                border-bottom: 1px solid var(--glass-border);
            }
            .ra-section__eyebrow {
                display: flex;
                align-items: center;
                gap: .5rem;
                font-size: .62rem;
                letter-spacing: .14em;
                text-transform: uppercase;
                color: var(--primary);
                margin-bottom: .3rem;
            }
            .ra-section__eyebrow::before {
                content: '';
                width: 18px;
                height: 1px;
                background: currentColor;
            }
            .ra-section__title { font-size: 1.15rem; font-weight: 700; margin: 0; }
            .ra-section__sub { font-size: .75rem; color: var(--text-muted); margin: .2rem 0 0; max-width: 62ch; }

            .ra-insight {
                border-left: 3px solid var(--text-muted);
                padding: .6rem .9rem;
                background: rgba(255, 255, 255, .02);
                border-radius: 0 .4rem .4rem 0;
            }
            .ra-insight--danger { border-left-color: var(--danger); }
            .ra-insight--warning { border-left-color: var(--warning); }
            .ra-insight--info { border-left-color: var(--primary); }
            .ra-insight__title { font-size: .85rem; font-weight: 600; margin-bottom: .15rem; }
            .ra-insight__body { font-size: .78rem; color: var(--text-muted); margin: 0; }

            .ra-table { font-size: .8rem; }
            .ra-table td, .ra-table th { vertical-align: middle; }
            .ra-minibar { height: 5px; background: rgba(255, 255, 255, .07); border-radius: 3px; overflow: hidden; min-width: 60px; }
            .ra-minibar > span { display: block; height: 100%; border-radius: 3px; }

            .ra-note { font-size: .72rem; color: var(--text-muted); }
            .ra-reco { margin: 0; padding-left: 1.1rem; font-size: .78rem; color: var(--text-muted); }
            .ra-reco li { margin-bottom: .3rem; }
            .ra-reco li:last-child { margin-bottom: 0; }
            .ra-reco strong { color: var(--text-main); }
            .ra-chart-wrap { position: relative; min-height: 240px; }
            .ra-donut-wrap { position: relative; height: 260px; }

            /* ── Hover z detalami (lejek + legenda wykresu) ───────────────── */
            .ra-has-tip { position: relative; }
            .ra-tip {
                position: absolute;
                z-index: 30;
                left: 50%;
                top: calc(100% + .4rem);
                transform: translateX(-50%);
                min-width: 240px;
                padding: .6rem .7rem;
                text-align: left;
                background: #111a2b;
                border: 1px solid var(--glass-border);
                border-radius: .5rem;
                box-shadow: 0 14px 36px rgba(0, 0, 0, .6);
                opacity: 0;
                visibility: hidden;
                transition: opacity .14s ease;
                pointer-events: none;
            }
            .ra-tip--up { top: auto; bottom: calc(100% + .4rem); }
            .ra-has-tip:hover > .ra-tip,
            .ra-has-tip:focus-within > .ra-tip { opacity: 1; visibility: visible; }
            .ra-tip__title { font-size: .76rem; font-weight: 600; margin-bottom: .35rem; }
            .ra-tip__row {
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                gap: 1.25rem;
                font-size: .7rem;
                color: var(--text-muted);
                line-height: 1.6;
            }
            .ra-tip__row strong { color: var(--text-main); font-variant-numeric: tabular-nums; }
            .ra-tip__line { font-size: .7rem; color: var(--text-muted); line-height: 1.6; }
            .ra-tip__note {
                font-size: .66rem;
                color: var(--text-muted);
                margin: .4rem 0 0;
                padding-top: .35rem;
                border-top: 1px solid var(--glass-border);
            }

            /* ── Legenda wykresu kołowego ─────────────────────────────────── */
            .ra-legend { list-style: none; margin: 0; padding: 0; }
            .ra-legend__item {
                display: flex;
                align-items: center;
                gap: .5rem;
                padding: .3rem .1rem;
                font-size: .76rem;
                border-bottom: 1px solid rgba(255, 255, 255, .04);
                cursor: help;
            }
            .ra-legend__item:last-child { border-bottom: 0; }
            .ra-legend__dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
            .ra-legend__name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .ra-legend__val { font-weight: 600; font-variant-numeric: tabular-nums; }
            .ra-legend__pct { color: var(--text-muted); font-variant-numeric: tabular-nums; min-width: 3.2rem; text-align: right; }

            /* ── Lejek leadów ─────────────────────────────────────────────── */
            .ra-funnel { max-width: 820px; margin: 0 auto; }
            .ra-funnel__row { display: flex; justify-content: center; }
            .ra-funnel__stage {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                padding: .7rem 1rem;
                background: linear-gradient(135deg, rgba(59, 130, 246, .18), rgba(168, 85, 247, .07));
                border: 1px solid var(--glass-border);
                border-radius: .85rem;
                box-shadow: 0 8px 26px -14px rgba(0, 0, 0, .7);
                transition: width .5s cubic-bezier(.2, .9, .25, 1), border-color .18s ease;
                cursor: help;
            }
            .ra-funnel__stage:hover { border-color: rgba(59, 130, 246, .55); }
            .ra-funnel__idx {
                font-size: .66rem;
                font-weight: 400;
                color: var(--text-muted);
                font-variant-numeric: tabular-nums;
                margin-right: .45rem;
            }
            .ra-funnel__name { font-size: .84rem; font-weight: 600; }
            .ra-funnel__count {
                font-size: 1.4rem;
                font-weight: 700;
                line-height: 1.15;
                font-variant-numeric: tabular-nums;
            }
            .ra-funnel__unit { font-size: .66rem; font-weight: 400; color: var(--text-muted); margin-left: .25rem; }
            .ra-funnel__right { text-align: right; flex-shrink: 0; }
            .ra-funnel__conv { font-size: 1rem; font-weight: 700; font-variant-numeric: tabular-nums; }
            .ra-funnel__conv-lbl {
                font-size: .6rem;
                text-transform: uppercase;
                letter-spacing: .05em;
                color: var(--text-muted);
            }
            .ra-funnel__conn {
                position: relative;
                height: 34px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .ra-funnel__line {
                width: 2px;
                height: 100%;
                background: linear-gradient(180deg, rgba(59, 130, 246, .75), rgba(59, 130, 246, .04));
            }
            .ra-funnel__drop {
                position: absolute;
                left: calc(50% + 1.1rem);
                white-space: nowrap;
                font-size: .68rem;
                font-variant-numeric: tabular-nums;
                color: var(--danger);
                background: rgba(239, 68, 68, .08);
                border: 1px solid rgba(239, 68, 68, .25);
                border-radius: .4rem;
                padding: .1rem .45rem;
            }
            .ra-funnel__drop--none {
                color: var(--text-muted);
                background: rgba(148, 163, 184, .08);
                border-color: rgba(148, 163, 184, .2);
            }

            @media (max-width: 575.98px) {
                .ra-funnel__stage { width: 100% !important; padding: .6rem .7rem; }
                .ra-funnel__count { font-size: 1.15rem; }
            }
    </style>

    <div class="mb-3">
        <a href="{{ route('recruitment-processes.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Wróć do kandydatów
        </a>
    </div>

    {{-- ══ SEKCJA 1: krótkoterminowe zaangażowanie ═════════════════════════ --}}
    <div class="ra-section">
        <div>
            <div class="ra-section__eyebrow">{{ $nav['perspective'] }}</div>
            <h3 class="ra-section__title">Krótkoterminowy przegląd zaangażowania</h3>
            <p class="ra-section__sub">
                Co zespół faktycznie zrobił w wybranym okresie: ile telefonów, z jakim skutkiem
                i jak daleko doszły leady, które w tym czasie wpadły.
            </p>
        </div>
    </div>

    <x-ui.period-nav>
        <x-slot name="prev">
            <x-ui.button variant="ghost" href="{{ $nav['prevUrl'] }}" class="btn-sm w-100">
                <i class="bi bi-chevron-left"></i>
                <span>{{ $nav['prevLabel'] }}</span>
            </x-ui.button>
        </x-slot>
        <div>
            <h3 class="fs-5 fw-bold mb-0">{{ $nav['title'] }}</h3>
            <p class="small text-muted mb-0">{{ $nav['subtitle'] }}</p>
        </div>
        <x-slot name="next">
            <x-ui.button variant="primary" href="{{ $nav['nextUrl'] }}" class="btn-sm w-100">
                <span>{{ $nav['nextLabel'] }}</span>
                <i class="bi bi-chevron-right"></i>
            </x-ui.button>
        </x-slot>
    </x-ui.period-nav>

    <div class="ra-note mb-4">
        <i class="bi bi-calendar3 me-1"></i>
        Okres: <strong>{{ $from->translatedFormat('j M Y') }} – {{ $to->translatedFormat('j M Y') }}</strong>.
        Telefony, zatrudnienia i odrzucenia to zdarzenia, które <em>zaszły</em> w tym okresie;
        lejek dotyczy leadów, które w nim <em>wpadły</em>.
    </div>

    {{-- ── KPI okresu ─────────────────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        @php
            $engagementKpis = [
                ['Telefony', $num($eh['calls_made']), 'text-info', $num($eh['processes_touched']).' unikalnych kandydatów'],
                ['Skuteczność dodzwonienia', $pct($eh['answer_rate']), 'text-success', $num($eh['calls_answered']).' odebranych połączeń'],
                ['Prób na kandydata', number_format($eh['calls_per_process'], 2, ',', ' '), 'text-accent', 'Średnia w okresie'],
                ['Nowe leady', $num($eh['leads']), 'text-primary', $eh['leads_synthetic'] > 0 ? $num($eh['leads_real']).' realnych + '.$num($eh['leads_synthetic']).' z backfillu' : 'Zgłoszenia w okresie'],
                ['Zatrudnienia', $num($eh['hired_real']), 'text-success', $eh['hired_synthetic'] > 0 ? '+ '.$num($eh['hired_synthetic']).' backfillu' : 'Domknięte w okresie'],
                ['Odrzucenia', $num($eh['rejected']), 'text-danger', 'Decyzje odmowne w okresie'],
            ];
        @endphp
        @foreach($engagementKpis as [$label, $value, $color, $hint])
            <div class="col-6 col-md-4 col-xl-2">
                <div class="ra-kpi">
                    <div class="ra-kpi__label">{{ $label }}</div>
                    <div class="ra-kpi__value {{ $color }}" style="font-size:1.4rem;">{{ $value }}</div>
                    <div class="ra-kpi__hint">{{ $hint }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        {{-- ── Kto ile dzwonił (wykres kołowy) ────────────────────────────── --}}
        <div class="col-12 col-xl-6">
            <x-ui.card label="Kto ile dzwonił" class="h-100">
                @if($calls['total'] === 0)
                    <x-ui.empty-state icon="telephone-x" message="Brak zarejestrowanych telefonów w tym okresie" />
                @else
                    <p class="ra-note mt-2 mb-2">
                        Udział rekruterów w telefonach wykonanych w okresie. Najedź na wycinek
                        lub wiersz legendy, żeby zobaczyć rozbicie po wyniku połączenia.
                    </p>
                    <div class="ra-donut-wrap">
                        @php $jsonFlags = JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP; @endphp
                        <canvas id="raCallsChart"
                                data-labels='@json(array_column($callSlices, "name"), $jsonFlags)'
                                data-values='@json(array_column($callSlices, "calls"), $jsonFlags)'
                                data-colors='@json(array_column($callSlices, "color"), $jsonFlags)'
                                data-details='@json(array_column($callSlices, "details"), $jsonFlags)'></canvas>
                    </div>
                    <ul class="ra-legend mt-3">
                        @foreach($callSlices as $slice)
                            <li class="ra-legend__item ra-has-tip" tabindex="0">
                                <span class="ra-legend__dot" style="background: {{ $slice['color'] }};"></span>
                                <span class="ra-legend__name">{{ $slice['name'] }}</span>
                                <span class="ra-legend__val">{{ $num($slice['calls']) }}</span>
                                <span class="ra-legend__pct">{{ $pct($slice['share']) }}</span>
                                <div class="ra-tip ra-tip--up">
                                    <div class="ra-tip__title">{{ $slice['name'] }}</div>
                                    @foreach($slice['details'] as $line)
                                        <div class="ra-tip__line">{{ $line }}</div>
                                    @endforeach
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>

        {{-- ── Wynik połączeń ─────────────────────────────────────────────── --}}
        <div class="col-12 col-xl-6">
            <x-ui.card label="Co się dzieje, gdy dzwonimy" class="h-100">
                @if($engagement['outcomes']['total'] === 0)
                    <x-ui.empty-state icon="telephone" message="Brak zarejestrowanych połączeń w tym okresie" />
                @else
                    <p class="ra-note mt-2 mb-3">
                        Rozkład wyników {{ $num($engagement['outcomes']['total']) }} prób kontaktu zarejestrowanych w okresie.
                        Najedź na wiersz, żeby zobaczyć, kto ile razy zarejestrował dany wynik.
                    </p>
                    @foreach($engagement['outcomes']['rows'] as $o)
                        <div class="d-flex align-items-center gap-2 mb-2 ra-has-tip" tabindex="0" style="cursor:help;">
                            <span class="badge badge-{{ $o['variant'] === 'secondary' ? 'info' : $o['variant'] }}" style="min-width:130px;font-size:.65rem;">
                                {{ $o['label'] }}
                            </span>
                            <div class="ra-minibar flex-grow-1">
                                <span style="width: {{ $o['pct'] }}%; background: var(--{{ $o['variant'] === 'success' ? 'success' : ($o['variant'] === 'danger' ? 'danger' : 'warning') }});"></span>
                            </div>
                            <span style="font-size:.75rem;min-width:80px;text-align:right;">
                                {{ $num($o['n']) }} <span class="text-muted">({{ $pct($o['pct']) }})</span>
                            </span>
                            <div class="ra-tip ra-tip--up">
                                <div class="ra-tip__title">{{ $o['label'] }} — {{ $num($o['n']) }}</div>
                                @forelse($o['by_recruiter'] as $rec)
                                    <div class="ra-tip__row">
                                        <span>{{ $rec['name'] }}</span>
                                        <strong>{{ $num($rec['n']) }} <span class="text-muted" style="font-weight:400;">({{ $pct($rec['pct']) }})</span></strong>
                                    </div>
                                @empty
                                    <div class="ra-tip__line">Brak rozbicia</div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                @endif
            </x-ui.card>
        </div>
    </div>

    {{-- ── Lejek leadów ───────────────────────────────────────────────────── --}}
    <x-ui.card label="Lejek leadów" class="mb-4">
        @php $topStage = max(1, $funnel['stages'][0]['count']); @endphp

        @if($funnel['stages'][0]['count'] === 0)
            <x-ui.empty-state icon="funnel" message="Brak leadów, które wpadły w tym okresie" />
        @else
            <p class="ra-note mt-2 mb-3">
                Kohorta leadów z okresu, bez rekordów backfillowych (pracowników wstawionych od razu
                jako zatrudnieni). Każdy etap to „dotarł tu przynajmniej raz”, więc odrzucenie po
                weryfikacji nie kasuje faktu, że kandydat weryfikację przeszedł.
                Najedź na etap, żeby zobaczyć detale przejścia.
            </p>

            <div class="ra-funnel">
                @php $stageCount = count($funnel['stages']); @endphp
                @foreach($funnel['stages'] as $i => $stage)
                    @php
                        // Najwęższy etap nie schodzi poniżej 44% — inaczej treść przestaje być czytelna.
                        $width = 44 + (100 - 44) * ($stage['count'] / $topStage);
                        $isLast = $i === $stageCount - 1;
                    @endphp
                    <div class="ra-funnel__row">
                        <div class="ra-funnel__stage ra-has-tip" style="width: {{ round($width, 2) }}%;" tabindex="0">
                            <div>
                                <div class="ra-funnel__name">
                                    <span class="ra-funnel__idx">{{ sprintf('%02d', $i + 1) }}</span>{{ $stage['label'] }}
                                </div>
                                <div class="ra-funnel__count">
                                    {{ $num($stage['count']) }}<span class="ra-funnel__unit">osób</span>
                                </div>
                            </div>
                            @if($stage['step_rate'] !== null)
                                <div class="ra-funnel__right">
                                    <div class="ra-funnel__conv">{{ $pct($stage['step_rate']) }}</div>
                                    <div class="ra-funnel__conv-lbl">z etapu wyżej</div>
                                </div>
                            @endif

                            <div class="ra-tip {{ $isLast ? 'ra-tip--up' : '' }}">
                                <div class="ra-tip__title">{{ $stage['label'] }}</div>
                                <div class="ra-tip__row"><span>Osób na etapie</span><strong>{{ $num($stage['count']) }}</strong></div>
                                <div class="ra-tip__row"><span>Z wszystkich leadów</span><strong>{{ $pct($stage['of_leads']) }}</strong></div>
                                @if($stage['step_rate'] !== null)
                                    <div class="ra-tip__row"><span>Przeszło z etapu wyżej</span><strong>{{ $pct($stage['step_rate']) }}</strong></div>
                                    <div class="ra-tip__row"><span>Odpadło na tym kroku</span><strong>{{ $num($stage['lost']) }}</strong></div>
                                @endif
                                <p class="ra-tip__note">{{ $stage['hint'] }}</p>
                            </div>
                        </div>
                    </div>

                    @if(! $isLast)
                        @php $lost = $funnel['stages'][$i + 1]['lost'] ?? 0; @endphp
                        <div class="ra-funnel__conn">
                            <div class="ra-funnel__line"></div>
                            @if($lost > 0)
                                <div class="ra-funnel__drop">
                                    −{{ $num($lost) }} ({{ $pct($stage['count'] > 0 ? round($lost * 100 / $stage['count'], 1) : null) }}) odpadło
                                </div>
                            @else
                                <div class="ra-funnel__drop ra-funnel__drop--none">bez odpadu</div>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>

            @if($funnel['bottleneck'])
                <div class="ra-insight ra-insight--danger mt-4">
                    <div class="ra-insight__title">Wąskie gardło: {{ $funnel['bottleneck']['label'] }}</div>
                    <p class="ra-insight__body">
                        Z poprzedniego etapu przechodzi dalej tylko {{ $pct($funnel['bottleneck']['step_rate']) }} —
                        odpada {{ $num($funnel['bottleneck']['lost']) }} osób. Poprawa o kilka punktów
                        procentowych na tym jednym kroku daje więcej niż zwiększanie budżetu na leady.
                    </p>
                </div>
            @endif
        @endif
    </x-ui.card>

    <div class="row g-3 mb-5">
        {{-- ── Rekruterzy ─────────────────────────────────────────────────── --}}
        <div class="col-12 col-xl-7">
            <x-ui.card label="Kto pracuje na wyniku" class="h-100">
                <p class="ra-note mt-2 mb-2">
                    Przypisanie po tym, kto zarejestrował telefon i kto zmienił status —
                    pole „przypisany rekruter” jest puste w {{ $pct($q['unassigned_pct']) }} procesów,
                    więc nie nadaje się na podstawę rozliczenia.
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-hover ra-table mb-0">
                        <thead>
                            <tr>
                                <th>Rekruter</th>
                                <th class="text-end">Telefony</th>
                                <th class="text-end">Kandydaci</th>
                                <th class="text-end">Dodzwoniono</th>
                                <th class="text-end">Zmiany statusu</th>
                                <th class="text-end">Weryfikacja</th>
                                <th class="text-end">Etat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($engagement['recruiters'] as $r)
                                <tr>
                                    <td>{{ $r['name'] }}</td>
                                    <td class="text-end fw-semibold">{{ $num($r['calls']) }}</td>
                                    <td class="text-end">{{ $num($r['processes']) }}</td>
                                    <td class="text-end">{{ $pct($r['answer_rate']) }}</td>
                                    <td class="text-end">{{ $num($r['transitions']) }}</td>
                                    <td class="text-end">{{ $r['verified'] === 0 ? '—' : $num($r['verified']) }}</td>
                                    <td class="text-end">{{ $r['hired'] === 0 ? '—' : $num($r['hired']) }}</td>
                                </tr>
                            @empty
                                <x-ui.empty-state icon="people" message="Brak aktywności w tym okresie" inTable colspan="7" />
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(count($engagement['recruiters']) === 1)
                    <p class="ra-note mt-2 mb-0">
                        Cała aktywność pochodzi od jednej osoby — porównania między rekruterami
                        i benchmark zespołu ruszą, gdy telefony zacznie rejestrować więcej kont.
                    </p>
                @endif
            </x-ui.card>
        </div>

        {{-- ── Powody odrzuceń ────────────────────────────────────────────── --}}
        <div class="col-12 col-xl-5">
            <x-ui.card label="Dlaczego mówimy „nie”" class="h-100">
                @if($engagement['rejections']['total'] === 0)
                    <x-ui.empty-state icon="hand-thumbs-down" message="Brak odrzuceń w tym okresie" />
                @else
                    <p class="ra-note mt-2 mb-2">
                        Najedź na powód, żeby zobaczyć, kto ile razy zarejestrował to odrzucenie.
                    </p>
                    <div class="mt-2">
                        @foreach($engagement['rejections']['rows'] as $r)
                            <div class="d-flex align-items-center gap-2 mb-2 ra-has-tip" tabindex="0" style="cursor:help;">
                                <span style="font-size:.75rem;min-width:150px;">{{ $r['label'] }}</span>
                                <div class="ra-minibar flex-grow-1">
                                    <span style="width: {{ $r['pct'] }}%; background: var(--danger);"></span>
                                </div>
                                <span style="font-size:.75rem;min-width:80px;text-align:right;">
                                    {{ $num($r['n']) }} <span class="text-muted">({{ $pct($r['pct']) }})</span>
                                </span>
                                <div class="ra-tip ra-tip--up">
                                    <div class="ra-tip__title">{{ $r['label'] }} — {{ $num($r['n']) }}</div>
                                    @forelse($r['by_recruiter'] as $rec)
                                        <div class="ra-tip__row">
                                            <span>{{ $rec['name'] }}</span>
                                            <strong>{{ $num($rec['n']) }} <span class="text-muted" style="font-weight:400;">({{ $pct($rec['pct']) }})</span></strong>
                                        </div>
                                    @empty
                                        <div class="ra-tip__line">Brak rozbicia</div>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($q['rejected_no_reason_pct'] !== null && $q['rejected_no_reason_pct'] >= 50)
                        <div class="ra-insight ra-insight--warning mt-3">
                            <div class="ra-insight__title">Ta sekcja jeszcze nic nie mówi</div>
                            <p class="ra-insight__body">
                                {{ $pct($q['rejected_no_reason_pct']) }} odrzuceń ma powód „Inne” albo żaden.
                                Dopóki rekruter nie wybiera konkretnej przyczyny, nie da się odróżnić
                                kandydata za drogiego od takiego, który po prostu nie odbierał telefonu —
                                a to dwa zupełnie różne problemy do rozwiązania.
                            </p>
                        </div>
                    @endif
                @endif
            </x-ui.card>
        </div>
    </div>

    {{-- ══ SEKCJA 2: analiza długoterminowa ════════════════════════════════ --}}
    <div class="ra-section">
        <div>
            <div class="ra-section__eyebrow">Perspektywa strukturalna</div>
            <h3 class="ra-section__title">Analiza długoterminowa</h3>
            <p class="ra-section__sub">
                Struktura lejka w dłuższym oknie: jakość kanałów, czasy reakcji, starzenie się bazy
                i to, na ile w ogóle można ufać tym liczbom. Tu pojedynczy słaby dzień się wygładza,
                a widać problemy systemowe.
            </p>
        </div>
        <div class="btn-group btn-group-sm" role="group" aria-label="Zakres analizy długoterminowej">
            @foreach($presets as $key => $p)
                <a href="{{ $nav['presetUrls'][$key] }}"
                   class="btn {{ $preset === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ $p['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="ra-note mb-4">
        <i class="bi bi-calendar3 me-1"></i>
        Okres: <strong>{{ $longFrom->translatedFormat('j M Y') }} – {{ $longTo->translatedFormat('j M Y') }}</strong>.
        Sekcje opisane jako „stan na teraz” są niezależne od tego zakresu.
    </div>

    {{-- ── Co z tego wynika ───────────────────────────────────────────────── --}}
    @if(count($longTerm['insights']))
        <x-ui.card label="Co z tego wynika" class="mb-4">
            <div class="d-flex flex-column gap-2 mt-2">
                @foreach($longTerm['insights'] as $insight)
                    <div class="ra-insight ra-insight--{{ $insight['severity'] }}">
                        <div class="ra-insight__title">{{ $insight['title'] }}</div>
                        <p class="ra-insight__body">{{ $insight['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @endif

    {{-- ── KPI zakresu ────────────────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        @php
            $kpis = [
                ['Nowe leady', $num($lh['leads']), 'text-primary', $lh['leads_synthetic'] > 0 ? $num($lh['leads_real']).' realnych + '.$num($lh['leads_synthetic']).' z backfillu' : 'Zgłoszenia w okresie'],
                ['Obdzwonione', $pct($lh['contact_rate']), 'text-info', $num($lh['contacted']).' z '.$num($lh['leads']).' leadów dostało telefon'],
                ['Telefony', $num($lh['calls_made']), 'text-info', 'Średnio '.number_format($lh['calls_per_process'], 2, ',', ' ').' na kandydata'],
                ['Skuteczność dodzwonienia', $pct($lh['answer_rate']), 'text-success', $num($lh['calls_answered']).' odebranych połączeń'],
                ['Mediana czasu reakcji', $analytics->humanMinutes($rt['median_minutes']), 'text-warning', $pct($rt['within_24h'], 'brak danych').' w ciągu doby'],
                ['Zatrudnienia', $num($lh['hired_real']), 'text-success', $lh['hired_synthetic'] > 0 ? '+ '.$num($lh['hired_synthetic']).' backfillu (nie z lejka)' : 'Domknięte w okresie'],
                ['Odrzucenia', $num($lh['rejected']), 'text-danger', 'Decyzje odmowne w okresie'],
                ['Konwersja lead → etat', $pct($realConversion), 'text-accent', 'Liczona bez backfillu'],
            ];
        @endphp
        @foreach($kpis as [$label, $value, $color, $hint])
            <div class="col-6 col-lg-3">
                <div class="ra-kpi">
                    <div class="ra-kpi__label">{{ $label }}</div>
                    <div class="ra-kpi__value {{ $color }}">{{ $value }}</div>
                    <div class="ra-kpi__hint">{{ $hint }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ── Czas reakcji ───────────────────────────────────────────────────── --}}
    <x-ui.card label="Czas od leada do pierwszego telefonu" class="mb-4">
        @if($rt['sample'] === 0)
            <x-ui.empty-state icon="clock-history" message="Brak realnych prób kontaktu w tym okresie" />
        @else
            <div class="row g-3 mt-1">
                @foreach([
                    ['Mediana', $analytics->humanMinutes($rt['median_minutes']), 'text-warning'],
                    ['90. percentyl', $analytics->humanMinutes($rt['p90_minutes']), 'text-danger'],
                    ['Do 1 godziny', $pct($rt['within_1h']), 'text-success'],
                    ['Do 24 godzin', $pct($rt['within_24h']), 'text-info'],
                    ['Do 72 godzin', $pct($rt['within_72h']), 'text-info'],
                ] as [$l, $v, $c])
                    <div class="col-6 col-lg-3 col-xl-2">
                        <div class="ra-kpi">
                            <div class="ra-kpi__label">{{ $l }}</div>
                            <div class="ra-kpi__value {{ $c }}" style="font-size:1.25rem;">{{ $v }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="ra-note mt-3 mb-0">
                Próba: {{ $num($rt['sample']) }} leadów.
                @if($rt['excluded_artifacts'] > 0)
                    Pominięto {{ $num($rt['excluded_artifacts']) }} rekordów, w których „telefon”
                    zapisał się w tej samej sekundzie co lead — to ślad importu, nie rozmowa.
                    Wliczenie ich zbiłoby medianę niemal do zera.
                @endif
            </p>
        @endif
    </x-ui.card>

    {{-- ── Kolejka pracy / starzenie się bazy ─────────────────────────────── --}}
    <x-ui.card label="Co leży w kolejce (stan na teraz)" class="mb-4">
        <p class="ra-note mt-2 mb-3">
            Niezależne od wybranego okresu — zaległość jest zaległością bez względu na filtr.
            Wiek liczony od ostatniej próby kontaktu, a gdy jej nie było — od daty zgłoszenia.
        </p>

        <div class="row g-3 mb-3">
            @php
                $queueKpis = [
                    ['Bez ruchu ponad '.RecruitmentAnalyticsService::STALE_DAYS.' dni', $num($wq['stale']), 'text-danger', $pct($wq['stale_share']).' aktywnych rozmów'],
                    ['Nigdy nie dzwoniono', $num($wq['never_called']), 'text-warning', 'Otwarte procesy bez jednej próby'],
                    ['„Nowy” po terminie', $num($wq['new_over_sla']), 'text-warning', 'Czeka dłużej niż '.RecruitmentAnalyticsService::NEW_LEAD_SLA_DAYS.' dni'],
                    ['Aktywne rozmowy', $num($wq['active_total']), 'text-info', 'W statusie „W trakcie kontaktu”'],
                ];
            @endphp
            @foreach($queueKpis as [$label, $value, $color, $hint])
                <div class="col-6 col-lg-3">
                    <div class="ra-kpi">
                        <div class="ra-kpi__label">{{ $label }}</div>
                        <div class="ra-kpi__value {{ $color }}">{{ $value }}</div>
                        <div class="ra-kpi__hint">{{ $hint }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover ra-table mb-0">
                <thead>
                    <tr>
                        <th>Etap</th>
                        <th class="text-end">Razem</th>
                        @foreach($wq['bucket_order'] as $bucket)
                            <th class="text-end">{{ $bucket }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($wq['matrix'] as $row)
                        <tr>
                            <td>
                                <span class="badge badge-{{ $row['status']->variant() === 'primary' ? 'info' : $row['status']->variant() }}">
                                    {{ $row['status']->label() }}
                                </span>
                            </td>
                            <td class="text-end fw-semibold">{{ $num($row['total']) }}</td>
                            @foreach($wq['bucket_order'] as $bucket)
                                @php
                                    $n = $row['buckets'][$bucket];
                                    $stale = in_array($bucket, ['15-30 dni', '30+ dni'], true);
                                @endphp
                                <td class="text-end {{ $n > 0 && $stale ? 'text-danger fw-semibold' : ($n === 0 ? 'text-muted' : '') }}">
                                    {{ $n === 0 ? '—' : $num($n) }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="ra-note mt-2 mb-0">
            Kolumny od 15 dni w górę to kandydaci, którzy w praktyce już wystygli —
            warto je czyścić hurtowo albo uruchomić kampanię odzyskową, zamiast trzymać
            w lejku i zawyżać liczbę „aktywnych” procesów.
        </p>
    </x-ui.card>

    {{-- ── Właściciele procesów vs etap (stan na teraz) ───────────────────── --}}
    <x-ui.card label="Właściciele procesów vs etap (stan na teraz)" class="mb-4">
        <p class="ra-note mt-2 mb-3">
            Niezależne od wybranego okresu — aktualny podział aktywnych procesów według
            przypisanego rekrutera i statusu w lejku. Wiersz „Nieprzypisany” to procesy
            bez właściciela.
        </p>

        @if($oq['grand_total'] === 0)
            <x-ui.empty-state icon="inbox" message="Brak aktywnych procesów w lejku" />
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover ra-table mb-0">
                    <thead>
                        <tr>
                            <th>Właściciel</th>
                            <th class="text-end">Razem</th>
                            @foreach($oq['statuses'] as $status)
                                <th class="text-end">{{ $status->label() }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($oq['rows'] as $row)
                            <tr class="{{ $row['user_id'] === null ? 'opacity-75' : '' }}">
                                <td class="fw-semibold">
                                    {{ $row['name'] }}
                                    @if($row['user_id'] === null)
                                        <span class="badge badge-secondary ms-1" style="font-size:.55rem;">brak właściciela</span>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold">{{ $num($row['total']) }}</td>
                                @foreach($oq['statuses'] as $status)
                                    @php $n = $row['by_status'][$status->value] ?? 0; @endphp
                                    <td class="text-end {{ $n === 0 ? 'text-muted' : '' }}">
                                        {{ $n === 0 ? '—' : $num($n) }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="border-top:2px solid var(--glass-border);">
                            <td class="fw-semibold">Razem</td>
                            <td class="text-end fw-semibold">{{ $num($oq['grand_total']) }}</td>
                            @foreach($oq['statuses'] as $status)
                                @php
                                    $statusTotal = array_sum(array_map(
                                        fn ($row) => $row['by_status'][$status->value] ?? 0,
                                        $oq['rows']
                                    ));
                                @endphp
                                <td class="text-end fw-semibold">{{ $statusTotal === 0 ? '—' : $num($statusTotal) }}</td>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </x-ui.card>

    {{-- ── Trend ──────────────────────────────────────────────────────────── --}}
    <x-ui.card label="Wolumen miesiąc po miesiącu" class="mb-4">
        <p class="ra-note mt-2 mb-2">Ostatnie 12 miesięcy, niezależnie od wybranego zakresu.</p>
        <div class="ra-chart-wrap">
            <canvas id="raTrendChart"
                    data-labels='@json(array_column($longTerm["trend"], "label"))'
                    data-leads='@json(array_column($longTerm["trend"], "leads"))'
                    data-calls='@json(array_column($longTerm["trend"], "calls"))'
                    data-hires='@json(array_column($longTerm["trend"], "hires"))'></canvas>
        </div>
    </x-ui.card>

    <div class="row g-3 mb-4">
        {{-- ── Kanały ─────────────────────────────────────────────────────── --}}
        <div class="col-12 col-xl-7">
            <x-ui.card label="Skąd przychodzą kandydaci" class="h-100">
                @if(collect($longTerm['sources'])->contains('is_synthetic', true))
                    <p class="ra-note mt-2 mb-2">
                        Wiersze oznaczone <span class="badge badge-secondary" style="font-size:.6rem;">backfill</span>
                        to nie pozyskanie — tak wchodzą do systemu osoby już zatrudnione lub przeniesione
                        z wcześniejszej bazy. Ich wyniki nie są zasługą marketingu i nie należy ich
                        zestawiać z kanałami płatnymi.
                    </p>
                @endif
                <div class="table-responsive">
                    <table class="table table-sm table-hover ra-table mb-0">
                        <thead>
                            <tr>
                                <th>Kanał</th>
                                <th class="text-end">Leady</th>
                                <th class="text-end">Obdzwonione</th>
                                <th class="text-end">Dodzwoniono</th>
                                <th class="text-end">Weryfikacja</th>
                                <th class="text-end">Etat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($longTerm['sources'] as $s)
                                <tr class="{{ $s['is_synthetic'] ? 'opacity-75' : '' }}">
                                    <td>
                                        {{ $s['label'] }}
                                        @if($s['is_synthetic'])
                                            <span class="badge badge-secondary ms-1" style="font-size:.55rem;">backfill</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold">{{ $num($s['leads']) }}</td>
                                    <td class="text-end">{{ $pct($s['contact_rate']) }}</td>
                                    <td class="text-end">{{ $pct($s['answer_rate']) }}</td>
                                    <td class="text-end">{{ $s['verified'] === 0 ? '—' : $num($s['verified']) }}</td>
                                    <td class="text-end">{{ $s['hired'] === 0 ? '—' : $num($s['hired']) }}</td>
                                </tr>
                            @empty
                                <x-ui.empty-state icon="inbox" message="Brak leadów w tym okresie" inTable colspan="6" />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>

        {{-- ── Pory dnia ──────────────────────────────────────────────────── --}}
        <div class="col-12 col-xl-5">
            <x-ui.card label="O której warto dzwonić" class="h-100">
                @if(! count($longTerm['callHeatmap']))
                    <x-ui.empty-state icon="clock" message="Brak realnych rozmów w tym okresie" />
                @else
                    <p class="ra-note mt-2 mb-2">Tylko realne rozmowy (bez rekordów importu).</p>
                    <div class="table-responsive">
                        <table class="table table-sm ra-table mb-0">
                            <thead>
                                <tr><th>Godzina</th><th class="text-end">Telefony</th><th class="text-end">Odebrane</th></tr>
                            </thead>
                            <tbody>
                                @foreach($longTerm['callHeatmap'] as $slot)
                                    <tr>
                                        <td>{{ sprintf('%02d:00', $slot['hour']) }}</td>
                                        <td class="text-end">{{ $num($slot['calls']) }}</td>
                                        <td class="text-end {{ ($slot['answer_rate'] ?? 0) >= 80 ? 'text-success fw-semibold' : '' }}">
                                            {{ $pct($slot['answer_rate']) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>

    {{-- ── Jakość danych ──────────────────────────────────────────────────── --}}
    <x-ui.card label="Na ile można ufać tym liczbom">
        <p class="ra-note mt-2 mb-3">
            Każdy raport jest tak dobry jak dane pod spodem. Poniżej luki, które dziś
            najbardziej ograniczają analitykę — i które są najtańsze do zasypania.
        </p>
        <div class="row g-3">
            @php
                $quality = [
                    ['Procesy bez właściciela', $pct($q['unassigned_pct']), $num($q['unassigned']).' z '.$num($q['processes']).' procesów', ($q['unassigned_pct'] ?? 0) >= 50],
                    ['Odrzucenia bez powodu', $pct($q['rejected_no_reason_pct']), $num($q['rejected_no_reason']).' rekordów', ($q['rejected_no_reason_pct'] ?? 0) >= 50],
                    ['Kontakty z importu', $pct($q['artifact_calls_pct']), $num($q['artifact_calls']).' wpisów bez realnej rozmowy', ($q['artifact_calls_pct'] ?? 0) >= 20],
                    ['Pokrycie zgodami RODO', $pct($q['consent_coverage_pct']), $num($q['active_consents']).' z '.$num($q['candidates']).' kandydatów', ($q['consent_coverage_pct'] ?? 0) < 50],
                    ['Kandydaci bez telefonu', $pct($q['no_phone_pct']), $num($q['no_phone']).' rekordów', ($q['no_phone_pct'] ?? 0) > 5],
                ];
            @endphp
            @foreach($quality as [$label, $value, $hint, $bad])
                <div class="col-6 col-lg-4 col-xl-3">
                    <div class="ra-kpi">
                        <div class="ra-kpi__label">{{ $label }}</div>
                        <div class="ra-kpi__value {{ $bad ? 'text-danger' : 'text-success' }}" style="font-size:1.3rem;">{{ $value }}</div>
                        <div class="ra-kpi__hint">{{ $hint }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        @if(count($longTerm['recommendations']))
            <div class="ra-insight ra-insight--info mt-3">
                <div class="ra-insight__title">Co odblokować w pierwszej kolejności</div>
                <p class="ra-insight__body mb-2">
                    Dane, których dziś nie zbieracie, a które przesunęłyby ten raport z opisowego
                    w decyzyjny. Pozycje znikają z listy same, gdy luka zostanie zasypana.
                </p>
                <ul class="ra-reco">
                    @foreach($longTerm['recommendations'] as $reco)
                        <li>
                            <strong>{{ $reco['title'] }}</strong> — {{ $reco['body'] }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </x-ui.card>

    @push('scripts')
        <script>
            (function () {
                const trendCanvas = document.getElementById('raTrendChart');
                const callsCanvas = document.getElementById('raCallsChart');
                if (!trendCanvas && !callsCanvas) return;

                function loadChartJs() {
                    return new Promise(resolve => {
                        if (window.Chart) { resolve(); return; }
                        const s = document.createElement('script');
                        s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
                        s.onload = resolve;
                        document.head.appendChild(s);
                    });
                }

                const nf = new Intl.NumberFormat('pl-PL');
                const muted = 'rgba(255,255,255,.55)';
                const grid = 'rgba(255,255,255,.07)';
                const read = (canvas, key) => JSON.parse(canvas.dataset[key]);

                function renderTrend() {
                    new Chart(trendCanvas, {
                        data: {
                            labels: read(trendCanvas, 'labels'),
                            datasets: [
                                { type: 'bar', label: 'Leady', data: read(trendCanvas, 'leads'), backgroundColor: 'rgba(59,130,246,.55)', borderRadius: 4, order: 3 },
                                { type: 'bar', label: 'Telefony', data: read(trendCanvas, 'calls'), backgroundColor: 'rgba(6,182,212,.45)', borderRadius: 4, order: 2 },
                                { type: 'line', label: 'Zatrudnienia', data: read(trendCanvas, 'hires'), borderColor: '#22c55e', backgroundColor: '#22c55e', tension: .3, yAxisID: 'y1', order: 1 },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            plugins: { legend: { labels: { color: muted, boxWidth: 12 } } },
                            scales: {
                                x: { ticks: { color: muted }, grid: { color: grid } },
                                y: { beginAtZero: true, ticks: { color: muted }, grid: { color: grid }, title: { display: true, text: 'Leady / telefony', color: muted } },
                                y1: { beginAtZero: true, position: 'right', ticks: { color: muted, precision: 0 }, grid: { drawOnChartArea: false }, title: { display: true, text: 'Zatrudnienia', color: muted } },
                            },
                        },
                    });
                }

                function renderCalls() {
                    const values = read(callsCanvas, 'values');
                    const details = read(callsCanvas, 'details');
                    const total = values.reduce((a, b) => a + b, 0);

                    // Suma w środku pierścienia — bez niej trzeba by liczyć wycinki wzrokiem.
                    const centerTotal = {
                        id: 'raCenterTotal',
                        afterDatasetsDraw(chart) {
                            const { ctx, chartArea } = chart;
                            const cx = (chartArea.left + chartArea.right) / 2;
                            const cy = (chartArea.top + chartArea.bottom) / 2;
                            ctx.save();
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';
                            ctx.fillStyle = '#f1f5f9';
                            ctx.font = '700 26px "Albert Sans", system-ui, sans-serif';
                            ctx.fillText(nf.format(total), cx, cy - 9);
                            ctx.fillStyle = muted;
                            ctx.font = '600 10px "Albert Sans", system-ui, sans-serif';
                            ctx.fillText('TELEFONÓW', cx, cy + 13);
                            ctx.restore();
                        },
                    };

                    new Chart(callsCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: read(callsCanvas, 'labels'),
                            datasets: [{
                                data: values,
                                backgroundColor: read(callsCanvas, 'colors'),
                                borderColor: 'rgba(10,15,29,.9)',
                                borderWidth: 2,
                                hoverOffset: 10,
                                hoverBorderColor: 'rgba(255,255,255,.35)',
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '60%',
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: 'rgba(17,26,43,.97)',
                                    borderColor: 'rgba(255,255,255,.12)',
                                    borderWidth: 1,
                                    padding: 12,
                                    boxPadding: 4,
                                    titleFont: { size: 13, weight: '600' },
                                    bodyFont: { size: 12 },
                                    displayColors: true,
                                    callbacks: {
                                        label: ctx => {
                                            const share = total > 0 ? (ctx.parsed / total * 100) : 0;
                                            return ` ${nf.format(ctx.parsed)} telefonów (${share.toFixed(1).replace('.', ',')}%)`;
                                        },
                                        afterBody: items => details[items[0].dataIndex] || [],
                                    },
                                },
                            },
                        },
                        plugins: [centerTotal],
                    });
                }

                loadChartJs().then(() => {
                    if (trendCanvas) renderTrend();
                    if (callsCanvas) renderCalls();
                });
            })();
        </script>
    @endpush
</x-app-layout>
