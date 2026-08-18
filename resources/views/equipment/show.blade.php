<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="{{ $equipment->name }}">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('equipment.tab.stock', array_filter([
                        'warehouse_id' => $warehouse->id,
                        'withdrawn' => $equipment->isArchived() ? 1 : null,
                    ])) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                @if($equipment->isArchived())
                    <form
                        action="{{ route('equipment.restore', ['equipment' => $equipment, 'warehouse_id' => $warehouse->id]) }}"
                        method="POST"
                        class="d-inline"
                        onsubmit="return confirm('Przywrócić tę pozycję do asortymentu?')"
                    >
                        @csrf
                        <x-ui.button variant="primary" type="submit">
                            Przywróć
                        </x-ui.button>
                    </form>
                @else
                    <x-ui.button
                        variant="ghost"
                        href="{{ route('equipment.edit', ['equipment' => $equipment, 'warehouse_id' => $warehouse->id]) }}"
                        routeName="equipment.edit"
                        action="edit"
                    >
                        Edytuj
                    </x-ui.button>
                    <form
                        action="{{ route('equipment.destroy', ['equipment' => $equipment, 'warehouse_id' => $warehouse->id]) }}"
                        method="POST"
                        class="d-inline"
                        onsubmit="return confirm('Wycofać tę pozycję z ewidencji? Historia wydań zostanie zachowana — nie będziemy już śledzić jej stanu.')"
                    >
                        @csrf
                        @method('DELETE')
                        <x-ui.button variant="danger" type="submit">
                            Wycofaj
                        </x-ui.button>
                    </form>
                @endif
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-ui.alert variant="success" title="Sukces" dismissible class="mb-3">
            {{ session('success') }}
        </x-ui.alert>
    @endif

    @if(session('error'))
        <x-ui.alert variant="danger" title="Błąd" dismissible class="mb-3">
            {{ session('error') }}
        </x-ui.alert>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <x-ui.card label="Zdjęcie" class="h-100">
                @if($equipment->image_url)
                    <img
                        src="{{ $equipment->image_url }}"
                        alt="{{ $equipment->name }}"
                        class="img-fluid rounded"
                        style="width:100%;max-height:22rem;object-fit:contain;background:rgba(255,255,255,.03);"
                    >
                @else
                    <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
                        <i class="bi bi-image" style="font-size:2.5rem;opacity:.45;"></i>
                        <p class="small mb-0 mt-2">Brak zdjęcia</p>
                        @unless($equipment->isArchived())
                            <a href="{{ route('equipment.edit', ['equipment' => $equipment, 'warehouse_id' => $warehouse->id]) }}" class="small mt-1">
                                Dodaj zdjęcie
                            </a>
                        @endunless
                    </div>
                @endif
            </x-ui.card>
        </div>
        <div class="col-lg-8">
            <x-ui.card label="Katalog" class="h-100">
                <div class="d-flex flex-wrap align-items-center gap-3 mb-3" style="color:var(--text-muted);">
                    @if($equipment->isArchived())
                        <span title="Wycofane z ewidencji — nie śledzimy już stanu tej pozycji.">
                            <i class="bi bi-archive me-1"></i>Wycofane
                        </span>
                    @endif
                    @if($equipment->issuable)
                        <span title="Wydawalny pracownikom">
                            <i class="bi bi-box-arrow-up me-1"></i>Wydawalny
                        </span>
                        @if($equipment->returnable)
                            <span title="Zwracalny">
                                <i class="bi bi-arrow-return-left me-1"></i>Zwracalny
                            </span>
                        @else
                            <span title="Bezzwrotny">
                                <i class="bi bi-box-arrow-right me-1"></i>Bezzwrotny
                            </span>
                        @endif
                    @else
                        <span title="Nie wydawany pracownikom">
                            <i class="bi bi-slash-circle me-1"></i>Niewydawalny
                        </span>
                    @endif
                    @if($equipment->category)
                        <span title="Kategoria">
                            <i class="bi bi-tag me-1"></i>{{ $equipment->category }}
                        </span>
                    @endif
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="text-muted small mb-1">Nazwa</h6>
                        <p class="fw-semibold mb-0">{{ $equipment->name }}</p>
                    </div>
                    @if($equipment->hasVariants())
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Wariant</h6>
                            <div class="d-flex flex-wrap align-items-center gap-1">
                                <span class="fw-semibold me-1">{{ $equipment->variant_label }}</span>
                                @foreach($equipment->variants as $variant)
                                    <x-ui.badge variant="secondary">{{ $variant->kind_label }}</x-ui.badge>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <div class="col-12">
                        <h6 class="text-muted small mb-1">SKU</h6>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($equipment->variants as $variant)
                                <x-ui.badge variant="info">{{ $variant->sku }}</x-ui.badge>
                            @endforeach
                        </div>
                    </div>
                    @if($equipment->unit_cost)
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Koszt jednostkowy</h6>
                            <p class="fw-semibold mb-0">{{ number_format($equipment->unit_cost, 2) }} {{ $equipment->currency?->value ?? 'PLN' }}</p>
                        </div>
                    @endif
                    @if($equipment->isArchived() && $equipment->removed_at)
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">W historii od</h6>
                            <p class="fw-semibold mb-0">{{ $equipment->removed_at->format('Y-m-d') }}</p>
                        </div>
                    @endif
                    @if($equipment->description)
                        <div class="col-12">
                            <h6 class="text-muted small mb-1">Opis</h6>
                            <p class="mb-0">{{ $equipment->description }}</p>
                        </div>
                    @endif
                </div>
                <p class="small text-muted mb-0 mt-3">
                    Katalog jest wspólny. Stan na półce zmieniasz przyjęciem albo korektą — każdy ruch zostawia ślad.
                </p>
            </x-ui.card>
        </div>
    </div>

    @unless($equipment->isArchived())
        <x-ui.card label="Stan" class="mb-3" id="stock-movement">
            <livewire:equipment-stock-movement-form
                :equipment="$equipment"
                :warehouse="$warehouse"
                :key="'eq-move-'.$equipment->id"
            />
        </x-ui.card>
    @endunless

    <x-ui.card label="Rozkład sztuk" class="mb-3">
        @if($distributions->isNotEmpty())
            <p class="small text-muted mb-4">
                @if($equipment->hasVariants())
                    Wg {{ mb_strtolower($equipment->variant_label ?: 'wariantu') }}, z podziałem na magazyny.
                @else
                    Z podziałem na magazyny.
                @endif
            </p>
            <div class="eq-stock-breakdown">
                <div class="eq-stock-breakdown__donut">
                    <div class="eq-stock-breakdown__donut-wrap">
                        <canvas
                            class="eq-distribution-chart"
                            style="width:100%;height:100%;"
                            data-labels='@json(collect($variantOverview['slices'])->pluck('label'))'
                            data-values='@json(collect($variantOverview['slices'])->pluck('value'))'
                            data-colors='@json(collect($variantOverview['slices'])->pluck('color'))'
                        ></canvas>
                        <div class="eq-stock-breakdown__donut-center">
                            <div class="eq-stock-breakdown__donut-total">{{ $variantOverview['total'] }}</div>
                            <div class="eq-stock-breakdown__donut-caption">szt. łącznie</div>
                        </div>
                    </div>
                    <ul class="eq-stock-breakdown__variant-legend list-unstyled mb-0">
                        @forelse($variantOverview['slices'] as $slice)
                            <li>
                                <span style="background:{{ $slice['color'] }};"></span>
                                {{ $slice['label'] }}
                                <em>{{ $slice['value'] }}</em>
                            </li>
                        @empty
                            <li class="text-muted">Brak sztuk w magazynach i u ludzi.</li>
                        @endforelse
                    </ul>
                </div>
                <div class="eq-stock-breakdown__bars">
                    @foreach($distributions as $distribution)
                        @php
                            $barWidth = $barScale > 0 ? ($distribution['total'] / $barScale) * 100 : 0;
                            $minLeft = $distribution['min'] > 0 ? ($distribution['min'] / $barScale) * 100 : null;
                        @endphp
                        <div class="eq-stock-row">
                            <div class="eq-stock-row__meta">
                                <span class="eq-stock-row__dot" style="background:{{ $distribution['color'] }};"></span>
                                <div>
                                    <div class="eq-stock-row__name">{{ $distribution['label'] }}</div>
                                    <div class="eq-stock-row__qty">{{ $distribution['total'] }} szt.</div>
                                </div>
                            </div>
                            <div class="eq-stock-row__track">
                                <div class="eq-stock-row__fill" style="width:{{ min(100, $barWidth) }}%;">
                                    @foreach($distribution['slices'] as $slice)
                                        <div
                                            class="eq-stock-row__seg"
                                            title="{{ $slice['label'] }}: {{ $slice['value'] }} szt."
                                            style="width:{{ $distribution['total'] > 0 ? ($slice['value'] / $distribution['total']) * 100 : 0 }}%;background:{{ $slice['color'] }};"
                                        ></div>
                                    @endforeach
                                </div>
                                @if($minLeft !== null)
                                    <div class="eq-stock-row__min" style="left:{{ min(100, $minLeft) }}%;">
                                        <span>min. {{ $distribution['min'] }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="eq-stock-row__status">
                                @if($distribution['low'])
                                    <span class="eq-stock-status eq-stock-status--low">
                                        <i></i> Poniżej minimum
                                    </span>
                                @elseif($distribution['min'] > 0)
                                    <span class="eq-stock-status eq-stock-status--ok">
                                        <i></i> Powyżej minimum
                                    </span>
                                @else
                                    <span class="eq-stock-status eq-stock-status--none">
                                        <i></i> Brak minimum
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    @if(count($locationLegend) > 0)
                        <ul class="eq-stock-breakdown__location-legend list-unstyled mb-0">
                            @foreach($locationLegend as $item)
                                <li>
                                    <span style="background:{{ $item['color'] }};"></span>
                                    {{ $item['label'] }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @else
            <x-ui.empty-state icon="inbox" message="Brak pozycji magazynowej." />
        @endif
    </x-ui.card>

    <x-ui.card label="Ruch magazynowy" class="mb-3">
        <livewire:equipment-stock-timeline :equipment="$equipment" :key="'eq-timeline-'.$equipment->id" />
    </x-ui.card>

    @if($equipment->requirements->count() > 0)
        <x-ui.card label="Wymagania dla ról" class="mb-3">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Rola</th>
                            <th class="text-end">Wymagana ilość</th>
                            <th>Obowiązkowe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($equipment->requirements as $requirement)
                            <tr>
                                <td>{{ $requirement->role->name }}</td>
                                <td class="text-end" style="font-variant-numeric:tabular-nums;">{{ $requirement->required_quantity }}</td>
                                <td>
                                    @if($requirement->is_mandatory)
                                        <x-ui.badge variant="danger">Tak</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="accent">Nie</x-ui.badge>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif

    <x-ui.card label="Historia wydań i rozchodów">
        <livewire:equipment-issue-history :equipment="$equipment" :key="'eq-issues-'.$equipment->id" />
    </x-ui.card>
    @push('scripts')
        <style>
            .eq-stock-breakdown { display: flex; gap: 2rem; align-items: flex-start; }
            .eq-stock-breakdown__donut { flex: 0 0 220px; display: flex; flex-direction: column; align-items: center; gap: 1rem; }
            .eq-stock-breakdown__donut-wrap { position: relative; width: 200px; height: 200px; flex-shrink: 0; }
            .eq-stock-breakdown__donut-center { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none; z-index: 2; line-height: 1.15; }
            .eq-stock-breakdown__donut-total { font-weight: 700; font-size: 1.7rem; color: var(--text-main); }
            .eq-stock-breakdown__donut-caption { font-size: 0.68rem; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-muted); }
            .eq-stock-breakdown__variant-legend li,
            .eq-stock-breakdown__location-legend li { display: flex; align-items: center; gap: 0.45rem; font-size: 0.8rem; padding: 0.15rem 0; color: var(--text-main); }
            .eq-stock-breakdown__variant-legend li span,
            .eq-stock-breakdown__location-legend li span { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; display: inline-block; }
            .eq-stock-breakdown__variant-legend li em { font-style: normal; color: var(--text-muted); margin-left: auto; padding-left: 0.75rem; font-variant-numeric: tabular-nums; }
            .eq-stock-breakdown__bars { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1.15rem; padding-top: 0.35rem; }
            .eq-stock-row { display: grid; grid-template-columns: 4.5rem minmax(0, 1fr) auto; gap: 0.85rem; align-items: center; }
            .eq-stock-row__meta { display: flex; align-items: flex-start; gap: 0.45rem; min-width: 0; }
            .eq-stock-row__dot { width: 8px; height: 8px; border-radius: 50%; margin-top: 0.4rem; flex-shrink: 0; }
            .eq-stock-row__name { font-weight: 650; line-height: 1.2; }
            .eq-stock-row__qty { font-size: 0.75rem; color: var(--text-muted); font-variant-numeric: tabular-nums; }
            .eq-stock-row__track { position: relative; height: 18px; background: rgba(255, 255, 255, 0.06); border-radius: 6px; margin-top: 1.1rem; }
            .eq-stock-row__fill { display: flex; height: 100%; border-radius: 6px; overflow: hidden; min-width: 0; }
            .eq-stock-row__seg { height: 100%; min-width: 0; }
            .eq-stock-row__min { position: absolute; top: -1.15rem; bottom: -4px; width: 0; border-left: 1px dashed rgba(255, 255, 255, 0.45); z-index: 2; pointer-events: none; }
            .eq-stock-row__min span { position: absolute; top: 0; left: 50%; transform: translate(-50%, 0); font-size: 0.65rem; font-weight: 600; color: var(--text-muted); background: rgba(15, 23, 42, 0.92); border: 1px solid var(--glass-border); border-radius: 4px; padding: 0 0.35rem; white-space: nowrap; line-height: 1.4; }
            .eq-stock-status { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.72rem; font-weight: 600; border-radius: 999px; padding: 0.2rem 0.55rem; white-space: nowrap; border: 1px solid transparent; }
            .eq-stock-status i { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
            .eq-stock-status--low { color: #fca5a5; border-color: rgba(239, 68, 68, 0.45); background: rgba(239, 68, 68, 0.12); }
            .eq-stock-status--low i { background: #ef4444; }
            .eq-stock-status--ok { color: #6ee7b7; border-color: rgba(16, 185, 129, 0.4); background: rgba(16, 185, 129, 0.1); }
            .eq-stock-status--ok i { background: #10b981; }
            .eq-stock-status--none { color: var(--text-muted); border-color: var(--glass-border); background: rgba(255, 255, 255, 0.03); }
            .eq-stock-status--none i { background: #64748b; }
            .eq-stock-breakdown__location-legend { display: flex; flex-wrap: wrap; gap: 0.75rem 1.1rem; margin-top: 0.25rem; padding-top: 0.35rem; }
            .eq-movement__legend {
                display: flex;
                flex-wrap: wrap;
                gap: 0.85rem 1.25rem;
                font-size: 0.8rem;
                color: var(--text-muted);
            }
            .eq-movement__legend span { display: inline-flex; align-items: center; gap: 0.4rem; }
            .eq-movement__legend i { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
            .eq-movement__legend strong { color: var(--text-main); font-variant-numeric: tabular-nums; }
            .eq-movement__chart { height: 220px; position: relative; }
            .eq-movement-split {
                display: grid;
                grid-template-columns: minmax(0, 5fr) minmax(0, 7fr);
                gap: 1.75rem;
                align-items: start;
            }
            .eq-movement-split__history {
                max-height: 22rem;
                overflow-y: auto;
            }
            @media (max-width: 991.98px) {
                .eq-stock-breakdown { flex-direction: column; align-items: stretch; }
                .eq-stock-breakdown__donut { flex-basis: auto; }
                .eq-stock-row { grid-template-columns: 4rem minmax(0, 1fr); }
                .eq-stock-row__status { grid-column: 1 / -1; justify-self: start; }
                .eq-movement-split { grid-template-columns: minmax(0, 1fr); }
                .eq-movement-split__history { max-height: none; }
            }
        </style>
        <script>
            (function () {
                function mkAlpha(hex, a) {
                    const r = parseInt(hex.slice(1, 3), 16);
                    const g = parseInt(hex.slice(3, 5), 16);
                    const b = parseInt(hex.slice(5, 7), 16);
                    return `rgba(${r},${g},${b},${a})`;
                }

                function loadChartJs() {
                    return new Promise((resolve) => {
                        if (window.Chart) {
                            resolve();
                            return;
                        }
                        const s = document.createElement('script');
                        s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
                        s.onload = resolve;
                        s.onerror = resolve;
                        document.head.appendChild(s);
                    });
                }

                function initDistributionCharts() {
                    if (! window.Chart) return;
                    Chart.defaults.color = 'rgba(255,255,255,0.45)';
                    Chart.defaults.borderColor = 'rgba(255,255,255,0.08)';
                    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
                    Chart.defaults.font.size = 11;

                    document.querySelectorAll('.eq-distribution-chart').forEach((canvas) => {
                        const labels = JSON.parse(canvas.dataset.labels || '[]');
                        const values = JSON.parse(canvas.dataset.values || '[]');
                        const colors = JSON.parse(canvas.dataset.colors || '[]');
                        if (! labels.length) return;
                        new Chart(canvas.getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels,
                                datasets: [{
                                    data: values,
                                    backgroundColor: colors.map((c) => mkAlpha(c, 0.55)),
                                    borderColor: colors,
                                    borderWidth: 2,
                                    hoverOffset: 4,
                                }],
                            },
                            options: {
                                responsive: false,
                                maintainAspectRatio: false,
                                cutout: '70%',
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            label: (ctx) => ` ${ctx.label}: ${ctx.raw} szt.`,
                                        },
                                    },
                                },
                            },
                        });
                    });

                    document.querySelectorAll('.eq-movement-chart').forEach((canvas) => {
                        if (canvas.dataset.chartReady === '1') return;
                        const labels = JSON.parse(canvas.dataset.labels || '[]');
                        const inbound = JSON.parse(canvas.dataset.inbound || '[]');
                        const outbound = JSON.parse(canvas.dataset.outbound || '[]');
                        if (! labels.length) return;
                        canvas.dataset.chartReady = '1';
                        new Chart(canvas.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels,
                                datasets: [
                                    {
                                        label: 'Przyjęcia',
                                        data: inbound,
                                        borderColor: '#14b8a6',
                                        backgroundColor: mkAlpha('#14b8a6', 0.12),
                                        fill: true,
                                        tension: 0.35,
                                        pointRadius: 2,
                                        pointHoverRadius: 4,
                                        borderWidth: 2,
                                    },
                                    {
                                        label: 'Rozchody',
                                        data: outbound,
                                        borderColor: '#f43f5e',
                                        backgroundColor: mkAlpha('#f43f5e', 0.1),
                                        fill: true,
                                        tension: 0.35,
                                        pointRadius: 2,
                                        pointHoverRadius: 4,
                                        borderWidth: 2,
                                    },
                                ],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: { mode: 'index', intersect: false },
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            label: (ctx) => ` ${ctx.dataset.label}: ${ctx.raw} szt.`,
                                        },
                                    },
                                },
                                scales: {
                                    x: {
                                        grid: { display: false },
                                        ticks: { maxTicksLimit: 8, maxRotation: 0 },
                                    },
                                    y: {
                                        beginAtZero: true,
                                        ticks: { precision: 0 },
                                        grid: { color: 'rgba(255,255,255,0.06)' },
                                    },
                                },
                            },
                        });
                    });
                }

                loadChartJs().then(initDistributionCharts);
            })();
        </script>
    @endpush
</x-app-layout>
