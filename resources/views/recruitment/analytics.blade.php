@php
    use App\Enums\RecruitmentContactOutcome;
    use App\Enums\RecruitmentStatus;
    use App\Services\RecruitmentAnalyticsService;

    $h = $data['headline'];
    $q = $data['dataQuality'];
    $wq = $data['workQueue'];
    $oq = $data['ownerQueue'];
    $cbd = $data['callsByDay'];
    $rt = $data['response'];

    /** Renders a percentage that may legitimately be unknown (empty denominator). */
    $pct = fn (?float $v, string $fallback = '—') => $v === null ? $fallback : rtrim(rtrim(number_format($v, 1, ',', ' '), '0'), ',').'%';
    $num = fn ($v) => number_format((float) $v, 0, ',', ' ');

    // Backfilled employees would otherwise show up as free wins and inflate this.
    $realConversion = $h['leads_real'] > 0
        ? round($h['hired_real'] * 100 / $h['leads_real'], 2)
        : null;

    /** Tooltip: rozbicie po wyniku połączenia. */
    $callOutcomeTooltip = function (array $outcomes) use ($num): string {
        $parts = [];
        foreach (RecruitmentContactOutcome::cases() as $case) {
            $n = $outcomes[$case->value] ?? 0;
            if ($n > 0) {
                $parts[] = $case->label().': '.$num($n);
            }
        }

        return implode(', ', $parts);
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Analityka rekrutacji">
            <x-slot name="right">
                <div class="btn-group btn-group-sm" role="group" aria-label="Zakres czasu">
                    @foreach($presets as $key => $p)
                        <a href="{{ route('recruitment-analytics.index', ['range' => $key]) }}"
                           class="btn {{ $preset === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                            {{ $p['label'] }}
                        </a>
                    @endforeach
                </div>
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

            .ra-funnel-row { display: flex; align-items: center; gap: .75rem; padding: .45rem 0; }
            .ra-funnel-bar { flex: 1; height: 26px; background: rgba(255, 255, 255, .05); border-radius: .35rem; overflow: hidden; }
            .ra-funnel-bar > span {
                display: block; height: 100%;
                background: linear-gradient(90deg, var(--primary), var(--accent));
                border-radius: .35rem;
            }
            .ra-funnel-name { width: 190px; flex-shrink: 0; font-size: .8rem; }
            .ra-funnel-meta { width: 190px; flex-shrink: 0; text-align: right; font-size: .75rem; color: var(--text-muted); }

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
            .ra-scroll-table { overflow-x: auto; }
            .ra-scroll-table table { min-width: max-content; }
            .ra-sticky-col {
                position: sticky;
                left: 0;
                z-index: 1;
                background: var(--bg-card, #1e2535);
                box-shadow: 2px 0 4px rgba(0, 0, 0, .15);
            }
            .ra-day-cell { min-width: 34px; text-align: center; font-size: .72rem; }
            .ra-day-cell--has { font-weight: 600; cursor: help; }
    </style>

    <div class="mb-3">
        <a href="{{ route('recruitment-processes.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Wróć do kandydatów
        </a>
    </div>

            <div class="ra-note mb-4">
                <i class="bi bi-calendar3 me-1"></i>
                Okres: <strong>{{ $from->translatedFormat('j M Y') }} – {{ $to->translatedFormat('j M Y') }}</strong>.
                Wskaźniki lejka i czasów reakcji liczone są dla leadów, które <em>wpadły</em> w tym okresie;
                liczba telefonów i zatrudnień — dla zdarzeń, które <em>zaszły</em> w tym okresie.
            </div>

            {{-- ── Telefony: kto ile dzwonił per dzień ───────────────────── --}}
            <x-ui.card label="Telefony — kto ile dzwonił" class="mb-4">
                <p class="ra-note mt-2 mb-3">
                    Liczba zarejestrowanych prób kontaktu per rekruter i dzień kalendarzowy w wybranym okresie.
                    Najedź na liczbę, żeby zobaczyć rozbicie: odebrane, brak odpowiedzi, numer nieaktywny, prośba o oddzwonienie.
                </p>

                @if($cbd['grand_total'] === 0)
                    <x-ui.empty-state icon="telephone-x" message="Brak zarejestrowanych telefonów w tym okresie" />
                @else
                    <div class="ra-scroll-table">
                        <table class="table table-sm table-hover ra-table mb-0">
                            <thead>
                                <tr>
                                    <th class="ra-sticky-col">Rekruter</th>
                                    <th class="text-end ra-sticky-col" style="left:140px;">Razem</th>
                                    @foreach($cbd['days'] as $day)
                                        <th class="ra-day-cell text-muted" title="{{ $day['key'] }}">{{ $day['label'] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cbd['rows'] as $row)
                                    <tr>
                                        <td class="ra-sticky-col fw-semibold">{{ $row['name'] }}</td>
                                        <td class="text-end fw-semibold ra-sticky-col {{ $row['total'] > 0 ? 'ra-day-cell--has' : '' }}"
                                            style="left:140px;"
                                            @if($row['total'] > 0) title="{{ $callOutcomeTooltip($row['outcomes']) }}" @endif>
                                            {{ $num($row['total']) }}
                                        </td>
                                        @foreach($cbd['days'] as $day)
                                            @php
                                                $cell = $row['by_day'][$day['key']] ?? ['total' => 0, 'outcomes' => []];
                                                $n = $cell['total'];
                                            @endphp
                                            <td class="ra-day-cell {{ $n > 0 ? 'ra-day-cell--has' : 'text-muted' }}"
                                                @if($n > 0) title="{{ $callOutcomeTooltip($cell['outcomes']) }}" @endif>
                                                {{ $n === 0 ? '·' : $num($n) }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr style="border-top:2px solid var(--glass-border);">
                                    <td class="ra-sticky-col fw-semibold">Razem</td>
                                    <td class="text-end fw-semibold ra-sticky-col {{ $cbd['grand_total'] > 0 ? 'ra-day-cell--has' : '' }}"
                                        style="left:140px;"
                                        @if($cbd['grand_total'] > 0) title="{{ $callOutcomeTooltip($cbd['grand_outcomes']) }}" @endif>
                                        {{ $num($cbd['grand_total']) }}
                                    </td>
                                    @foreach($cbd['days'] as $day)
                                        @php
                                            $dayOutcomes = array_fill_keys(
                                                array_map(fn ($o) => $o->value, RecruitmentContactOutcome::cases()),
                                                0
                                            );
                                            $dayTotal = 0;
                                            foreach ($cbd['rows'] as $r) {
                                                $cell = $r['by_day'][$day['key']] ?? ['total' => 0, 'outcomes' => []];
                                                $dayTotal += $cell['total'];
                                                foreach ($cell['outcomes'] as $outcome => $count) {
                                                    $dayOutcomes[$outcome] = ($dayOutcomes[$outcome] ?? 0) + $count;
                                                }
                                            }
                                        @endphp
                                        <td class="ra-day-cell {{ $dayTotal > 0 ? 'fw-semibold ra-day-cell--has' : 'text-muted' }}"
                                            @if($dayTotal > 0) title="{{ $callOutcomeTooltip($dayOutcomes) }}" @endif>
                                            {{ $dayTotal === 0 ? '·' : $num($dayTotal) }}
                                        </td>
                                    @endforeach
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </x-ui.card>

            {{-- ── Co z tego wynika ───────────────────────────────────────── --}}
            @if(count($data['insights']))
                <x-ui.card label="Co z tego wynika" class="mb-4">
                    <div class="d-flex flex-column gap-2 mt-2">
                        @foreach($data['insights'] as $insight)
                            <div class="ra-insight ra-insight--{{ $insight['severity'] }}">
                                <div class="ra-insight__title">{{ $insight['title'] }}</div>
                                <p class="ra-insight__body">{{ $insight['body'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            @endif

            {{-- ── KPI ────────────────────────────────────────────────────── --}}
            <div class="row g-3 mb-4">
                @php
                    $kpis = [
                        ['Nowe leady', $num($h['leads']), 'text-primary', $h['leads_synthetic'] > 0 ? $num($h['leads_real']).' realnych + '.$num($h['leads_synthetic']).' z backfillu' : 'Zgłoszenia w okresie'],
                        ['Obdzwonione', $pct($h['contact_rate']), 'text-info', $num($h['contacted']).' z '.$num($h['leads']).' leadów dostało telefon'],
                        ['Telefony', $num($h['calls_made']), 'text-info', 'Średnio '.number_format($h['calls_per_process'], 2, ',', ' ').' na kandydata'],
                        ['Skuteczność dodzwonienia', $pct($h['answer_rate']), 'text-success', $num($h['calls_answered']).' odebranych połączeń'],
                        ['Mediana czasu reakcji', $analytics->humanMinutes($rt['median_minutes']), 'text-warning', $pct($rt['within_24h'], 'brak danych').' w ciągu doby'],
                        ['Zatrudnienia', $num($h['hired_real']), 'text-success', $h['hired_synthetic'] > 0 ? '+ '.$num($h['hired_synthetic']).' backfillu (nie z lejka)' : 'Domknięte w okresie'],
                        ['Odrzucenia', $num($h['rejected']), 'text-danger', 'Decyzje odmowne w okresie'],
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

            <div class="row g-3 mb-4">
                {{-- ── Lejek ──────────────────────────────────────────────── --}}
                <div class="col-12 col-xl-7">
                    <x-ui.card label="Lejek pozyskania" class="h-100">
                        <p class="ra-note mt-2 mb-3">
                            Kohorta leadów z okresu, bez rekordów backfillowych (pracowników wstawionych od razu
                            jako zatrudnieni). Każdy etap to „dotarł tu przynajmniej raz”, więc odrzucenie
                            po weryfikacji nie kasuje faktu, że kandydat weryfikację przeszedł.
                        </p>
                        @php $top = max(1, $data['funnel']['stages'][0]['count']); @endphp
                        @foreach($data['funnel']['stages'] as $stage)
                            @php $width = $stage['count'] / $top * 100; @endphp
                            <div class="ra-funnel-row">
                                <div class="ra-funnel-name" title="{{ $stage['hint'] }}">{{ $stage['label'] }}</div>
                                <div class="ra-funnel-bar">
                                    <span style="width: {{ max($width, $stage['count'] > 0 ? 0.6 : 0) }}%;"></span>
                                </div>
                                <div class="ra-funnel-meta">
                                    <strong class="text-body">{{ $num($stage['count']) }}</strong>
                                    <span class="ms-1">({{ $pct($stage['of_leads']) }})</span>
                                    @if($stage['lost'] !== null && $stage['lost'] > 0)
                                        <span class="text-danger ms-1">−{{ $num($stage['lost']) }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        @if($data['funnel']['bottleneck'])
                            <div class="ra-insight ra-insight--danger mt-3">
                                <div class="ra-insight__title">
                                    Wąskie gardło: {{ $data['funnel']['bottleneck']['label'] }}
                                </div>
                                <p class="ra-insight__body">
                                    Z poprzedniego etapu przechodzi dalej tylko {{ $pct($data['funnel']['bottleneck']['step_rate']) }} —
                                    odpada {{ $num($data['funnel']['bottleneck']['lost']) }} osób.
                                </p>
                            </div>
                        @endif
                    </x-ui.card>
                </div>

                {{-- ── Czas reakcji ───────────────────────────────────────── --}}
                <div class="col-12 col-xl-5">
                    <x-ui.card label="Czas od leada do pierwszego telefonu" class="h-100">
                        @if($rt['sample'] === 0)
                            <x-ui.empty-state icon="clock-history" message="Brak realnych prób kontaktu w tym okresie" />
                        @else
                            <div class="row g-3 mt-1">
                                @foreach([
                                    ['Mediana', $analytics->humanMinutes($rt['median_minutes']), 'text-warning'],
                                    ['90. percentyl', $analytics->humanMinutes($rt['p90_minutes']), 'text-danger'],
                                    ['Do 1 godziny', $pct($rt['within_1h']), 'text-success'],
                                    ['Do 24 godzin', $pct($rt['within_24h']), 'text-info'],
                                ] as [$l, $v, $c])
                                    <div class="col-6">
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
                </div>
            </div>

            {{-- ── Kolejka pracy / starzenie się bazy ─────────────────────── --}}
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

            {{-- ── Właściciele procesów vs etap (stan na teraz) ───────────── --}}
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

            {{-- ── Trend ──────────────────────────────────────────────────── --}}
            <x-ui.card label="Wolumen miesiąc po miesiącu" class="mb-4">
                <p class="ra-note mt-2 mb-2">Ostatnie 12 miesięcy, niezależnie od wybranego zakresu.</p>
                <div class="ra-chart-wrap">
                    <canvas id="raTrendChart"
                            data-labels='@json(array_column($data["trend"], "label"))'
                            data-leads='@json(array_column($data["trend"], "leads"))'
                            data-calls='@json(array_column($data["trend"], "calls"))'
                            data-hires='@json(array_column($data["trend"], "hires"))'></canvas>
                </div>
            </x-ui.card>

            <div class="row g-3 mb-4">
                {{-- ── Kanały ─────────────────────────────────────────────── --}}
                <div class="col-12 col-xl-7">
                    <x-ui.card label="Skąd przychodzą kandydaci" class="h-100">
                        @if(collect($data['sources'])->contains('is_synthetic', true))
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
                                    @forelse($data['sources'] as $s)
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

                {{-- ── Wynik połączeń + pory dnia ─────────────────────────── --}}
                <div class="col-12 col-xl-5">
                    <x-ui.card label="Co się dzieje, gdy dzwonimy" class="h-100">
                        @if($data['outcomes']['total'] === 0)
                            <x-ui.empty-state icon="telephone" message="Brak zarejestrowanych połączeń" />
                        @else
                            <div class="mt-2 mb-3">
                                @foreach($data['outcomes']['rows'] as $o)
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge badge-{{ $o['variant'] === 'secondary' ? 'info' : $o['variant'] }}" style="min-width:130px;font-size:.65rem;">
                                            {{ $o['label'] }}
                                        </span>
                                        <div class="ra-minibar flex-grow-1">
                                            <span style="width: {{ $o['pct'] }}%; background: var(--{{ $o['variant'] === 'success' ? 'success' : ($o['variant'] === 'danger' ? 'danger' : 'warning') }});"></span>
                                        </div>
                                        <span style="font-size:.75rem;min-width:80px;text-align:right;">
                                            {{ $num($o['n']) }} <span class="text-muted">({{ $pct($o['pct']) }})</span>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if(count($data['callHeatmap']))
                            <div class="ra-kpi__label mt-3">Skuteczność wg godziny</div>
                            <p class="ra-note mb-2">Tylko realne rozmowy (bez rekordów importu).</p>
                            <div class="table-responsive">
                                <table class="table table-sm ra-table mb-0">
                                    <thead>
                                        <tr><th>Godzina</th><th class="text-end">Telefony</th><th class="text-end">Odebrane</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach($data['callHeatmap'] as $slot)
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

            <div class="row g-3 mb-4">
                {{-- ── Rekruterzy ─────────────────────────────────────────── --}}
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
                                    @forelse($data['recruiters'] as $r)
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
                        @if(count($data['recruiters']) === 1)
                            <p class="ra-note mt-2 mb-0">
                                Cała aktywność pochodzi od jednej osoby — porównania między rekruterami
                                i benchmark zespołu ruszą, gdy telefony zacznie rejestrować więcej kont.
                            </p>
                        @endif
                    </x-ui.card>
                </div>

                {{-- ── Powody odrzuceń ────────────────────────────────────── --}}
                <div class="col-12 col-xl-5">
                    <x-ui.card label="Dlaczego mówimy „nie”" class="h-100">
                        @if($data['rejections']['total'] === 0)
                            <x-ui.empty-state icon="hand-thumbs-down" message="Brak odrzuceń w tym okresie" />
                        @else
                            <div class="mt-2">
                                @foreach($data['rejections']['rows'] as $r)
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span style="font-size:.75rem;min-width:150px;">{{ $r['label'] }}</span>
                                        <div class="ra-minibar flex-grow-1">
                                            <span style="width: {{ $r['pct'] }}%; background: var(--danger);"></span>
                                        </div>
                                        <span style="font-size:.75rem;min-width:80px;text-align:right;">
                                            {{ $num($r['n']) }} <span class="text-muted">({{ $pct($r['pct']) }})</span>
                                        </span>
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

            {{-- ── Jakość danych ──────────────────────────────────────────── --}}
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

                @if(count($data['recommendations']))
                    <div class="ra-insight ra-insight--info mt-3">
                        <div class="ra-insight__title">Co odblokować w pierwszej kolejności</div>
                        <p class="ra-insight__body mb-2">
                            Dane, których dziś nie zbieracie, a które przesunęłyby ten raport z opisowego
                            w decyzyjny. Pozycje znikają z listy same, gdy luka zostanie zasypana.
                        </p>
                        <ul class="ra-reco">
                            @foreach($data['recommendations'] as $reco)
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
                const canvas = document.getElementById('raTrendChart');
                if (!canvas) return;

                function loadChartJs() {
                    return new Promise(resolve => {
                        if (window.Chart) { resolve(); return; }
                        const s = document.createElement('script');
                        s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
                        s.onload = resolve;
                        document.head.appendChild(s);
                    });
                }

                const read = key => JSON.parse(canvas.dataset[key]);
                const muted = 'rgba(255,255,255,.55)';
                const grid = 'rgba(255,255,255,.07)';

                loadChartJs().then(() => {
                    new Chart(canvas, {
                        data: {
                            labels: read('labels'),
                            datasets: [
                                { type: 'bar', label: 'Leady', data: read('leads'), backgroundColor: 'rgba(59,130,246,.55)', borderRadius: 4, order: 3 },
                                { type: 'bar', label: 'Telefony', data: read('calls'), backgroundColor: 'rgba(6,182,212,.45)', borderRadius: 4, order: 2 },
                                { type: 'line', label: 'Zatrudnienia', data: read('hires'), borderColor: '#22c55e', backgroundColor: '#22c55e', tension: .3, yAxisID: 'y1', order: 1 },
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
                });
            })();
        </script>
    @endpush
</x-app-layout>
