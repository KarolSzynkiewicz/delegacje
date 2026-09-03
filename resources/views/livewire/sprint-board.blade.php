<div class="sb">
    @php
        $health = $insights['health'];
        $healthMeta = match ($health) {
            'on_track' => ['label' => 'Na kursie', 'variant' => 'success', 'icon' => 'lightning-charge'],
            'at_risk' => ['label' => 'Ryzyko', 'variant' => 'warning', 'icon' => 'exclamation-triangle'],
            'off_track' => ['label' => 'Poza kursem', 'variant' => 'danger', 'icon' => 'sign-stop'],
            'upcoming' => ['label' => 'Przed startem', 'variant' => 'info', 'icon' => 'hourglass-split'],
            'done' => ['label' => 'Domknięty', 'variant' => 'success', 'icon' => 'trophy'],
            default => ['label' => 'Niedomknięty', 'variant' => 'danger', 'icon' => 'flag'],
        };
        $ring = 2 * pi() * 42;
        $dash = $ring * (1 - ($insights['progress'] / 100));
        $statusVariant = $sprint->isCurrentlyActive() ? 'success' : ($sprint->isScheduled() ? 'info' : 'secondary');
    @endphp

    @include('livewire.partials.sprint-board-styles')

    @if($flash)
        <div class="alert alert-success py-2 px-3 mb-3">{{ $flash }}</div>
    @endif

    <x-ui.card class="mb-3">
        <div class="sb-hero">
            <div class="sb-ring">
                <svg width="110" height="110" viewBox="0 0 110 110">
                    <circle cx="55" cy="55" r="42" fill="none" stroke="rgba(255,255,255,.08)" stroke-width="8"/>
                    <circle cx="55" cy="55" r="42" fill="none" stroke="url(#sbGrad)" stroke-width="8"
                            stroke-linecap="round"
                            stroke-dasharray="{{ $ring }}"
                            stroke-dashoffset="{{ $dash }}"/>
                    <defs>
                        <linearGradient id="sbGrad" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#3b82f6"/>
                            <stop offset="100%" stop-color="#a855f7"/>
                        </linearGradient>
                    </defs>
                </svg>
                <div class="sb-ring-label">
                    <strong style="font-size:1.35rem; letter-spacing:-.04em">{{ $insights['progress'] }}%</strong>
                    <span class="small text-muted">done</span>
                </div>
            </div>

            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <x-ui.badge :variant="$statusVariant">{{ $sprint->statusLabel() }}</x-ui.badge>
                    <x-ui.badge :variant="$healthMeta['variant']">
                        <i class="bi bi-{{ $healthMeta['icon'] }} me-1"></i>{{ $healthMeta['label'] }}
                    </x-ui.badge>
                    <span class="text-muted small">
                        {{ $sprint->start_date->format('d.m.Y') }} – {{ $sprint->end_date->format('d.m.Y') }}
                        · {{ $insights['days_total'] }} dni
                    </span>
                </div>
                @if($sprint->goal)
                    <p class="mb-2 fs-5" style="letter-spacing:-.02em">{{ $sprint->goal }}</p>
                @else
                    <p class="text-muted mb-2">Brak celu — dopisz, po co ten sprint istnieje.</p>
                @endif
                <p class="mb-0 text-muted small">{{ $insights['coach'] }}</p>
            </div>

            <div class="text-end">
                @if($sprint->isScheduled())
                    <div class="v" style="font-size:1.8rem; font-weight:700; letter-spacing:-.04em">{{ $insights['starts_in'] }}</div>
                    <div class="small text-muted">dni do startu</div>
                @elseif($sprint->isCurrentlyActive())
                    <div style="font-size:1.8rem; font-weight:700; letter-spacing:-.04em">{{ $insights['days_left'] }}</div>
                    <div class="small text-muted">dni do końca</div>
                    <div class="small text-muted mt-1">dzień {{ $insights['days_elapsed'] }}/{{ $insights['days_total'] }}</div>
                @else
                    <div class="small text-muted">Sprint zamknięty kalendarzowo</div>
                    @if($insights['forecast_finish'])
                        <div class="small">Prognoza: {{ \Carbon\Carbon::parse($insights['forecast_finish'])->format('d.m') }}</div>
                    @endif
                @endif
                @if($sprint->createdBy)
                    <div class="small text-muted mt-2">{{ $sprint->createdBy->name }}</div>
                @endif
            </div>
        </div>
    </x-ui.card>

    <div class="sb-kpis mb-3">
        <div class="sb-kpi">
            <div class="v">{{ $insights['done'] }}<span class="text-muted" style="font-size:.9rem">/{{ $insights['scope'] }}</span></div>
            <div class="l">W zakresie</div>
        </div>
        <div class="sb-kpi">
            <div class="v text-info">{{ $insights['in_progress'] }}</div>
            <div class="l">W trakcie</div>
        </div>
        <div class="sb-kpi">
            <div class="v {{ $insights['overdue'] ? 'text-danger' : '' }}">{{ $insights['overdue'] }}</div>
            <div class="l">Po terminie</div>
        </div>
        <div class="sb-kpi">
            <div class="v">{{ $insights['milestones_done'] }}<span class="text-muted" style="font-size:.9rem">/{{ $insights['milestones_total'] }}</span></div>
            <div class="l">Kamienie milowe</div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <x-ui.card label="Burndown">
                <div class="small text-muted mb-2">Pozostały zakres vs linia idealna · {{ $insights['velocity'] }} zad./dzień</div>
                <div class="sb-chart" wire:ignore>
                    <canvas id="sb-burndown-{{ $sprint->id }}"></canvas>
                </div>
            </x-ui.card>
        </div>
        <div class="col-lg-5">
            <x-ui.card label="Status">
                <div class="sb-chart" wire:ignore>
                    <canvas id="sb-donut-{{ $sprint->id }}"></canvas>
                </div>
            </x-ui.card>
        </div>
    </div>

    <x-ui.card label="Kamienie milowe" class="mb-3">
        @if($sprint->milestones->isEmpty())
            <div class="text-muted small mb-3">Brak kamieni — dodaj np. „Demo”, „Freeze kodu”, „Wdrożenie”.</div>
        @else
            <div class="sb-runway mb-3">
                @php
                    $span = max(1, $sprint->start_date->diffInDays($sprint->end_date));
                    $todayPct = min(100, max(0, $sprint->start_date->diffInDays(now()) / $span * 100));
                    if (now()->lt($sprint->start_date)) $todayPct = 0;
                    if (now()->gt($sprint->end_date)) $todayPct = 100;
                @endphp
                <div class="sb-runway-track">
                    <div class="sb-runway-fill" style="width: {{ $todayPct }}%"></div>
                </div>
                @foreach($sprint->milestones as $ms)
                    @php
                        $pct = min(96, max(4, $sprint->start_date->diffInDays($ms->due_date) / $span * 100));
                        $dot = $ms->isCompleted() ? 'var(--success)' : ($ms->isOverdue() ? 'var(--danger)' : 'var(--primary)');
                    @endphp
                    <div class="sb-ms" style="left: {{ $pct }}%">
                        <div class="sb-ms-dot" style="background:{{ $dot }}"></div>
                        <div class="small" style="font-size:.68rem; line-height:1.2">{{ $ms->name }}</div>
                    </div>
                @endforeach
            </div>
            <div class="d-flex flex-column gap-2">
                @foreach($sprint->milestones as $ms)
                    <div class="d-flex align-items-center gap-2" wire:key="ms-{{ $ms->id }}">
                        @if($canMutate)
                            <button type="button" class="btn btn-sm btn-link p-0" wire:click="toggleMilestone({{ $ms->id }})" title="Oznacz">
                                <i class="bi {{ $ms->isCompleted() ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' }}"></i>
                            </button>
                        @endif
                        <div class="flex-grow-1 {{ $ms->isCompleted() ? 'text-decoration-line-through text-muted' : '' }}">
                            {{ $ms->name }}
                            <span class="small text-muted ms-1">{{ $ms->due_date->format('d.m') }}</span>
                            @if($ms->isOverdue())
                                <x-ui.badge variant="danger" class="ms-1">po terminie</x-ui.badge>
                            @endif
                        </div>
                        @if($canMutate)
                            <button type="button" class="btn btn-sm btn-link text-danger p-0" wire:click="deleteMilestone({{ $ms->id }})" wire:confirm="Usunąć kamień milowy?">
                                <i class="bi bi-trash"></i>
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if($canMutate)
            <div class="row g-2 mt-3">
                <div class="col-md-6">
                    <input type="text" class="form-control form-control-sm" placeholder="Nazwa kamienia…" wire:model="newMilestoneName" wire:keydown.enter="addMilestone">
                    @error('newMilestoneName') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <input type="date" class="form-control form-control-sm" wire:model="newMilestoneDue">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-primary w-100" wire:click="addMilestone">Dodaj</button>
                </div>
            </div>
        @endif
    </x-ui.card>

    <div class="row g-3">
        <div class="col-lg-4">
            <x-ui.card label="Obciążenie">
                @forelse($insights['workload'] as $row)
                    <div class="sb-work">
                        <div class="small" style="width:110px" title="{{ $row['name'] }}">{{ \Illuminate\Support\Str::limit($row['name'], 14) }}</div>
                        <div class="sb-work-bar">
                            <span style="width: {{ $row['total'] ? round($row['done'] / $row['total'] * 100) : 0 }}%"></span>
                        </div>
                        <div class="small text-muted" style="width:42px; text-align:right">{{ $row['done'] }}/{{ $row['total'] }}</div>
                    </div>
                @empty
                    <div class="text-muted small">Brak zadań.</div>
                @endforelse
                @if($insights['unassigned'] || $insights['scope_added'] || $insights['completed_today'])
                    <div class="small text-muted mt-3">
                        @if($insights['completed_today']) Dziś domknięto {{ $insights['completed_today'] }}. @endif
                        @if($insights['unassigned']) {{ $insights['unassigned'] }} bez osoby. @endif
                        @if($insights['scope_added']) +{{ $insights['scope_added'] }} doszło po starcie. @endif
                    </div>
                @endif
            </x-ui.card>
        </div>
        <div class="col-lg-4">
            <x-ui.card label="Definition of Done">
                @if($sprint->definition_of_done)
                    <div style="white-space:pre-wrap" class="small">{{ $sprint->definition_of_done }}</div>
                @else
                    <div class="text-muted small">Nie ustawiono — dopisz przy edycji sprintu, żeby zespół wiedział kiedy „done” znaczy done.</div>
                @endif
            </x-ui.card>
        </div>
        <div class="col-lg-4">
            <x-ui.card label="Załączniki">
                <x-attachment-list :attachments="$sprint->attachments" class="mb-3" />
                @if($sprint->attachments->isEmpty())
                    <div class="text-muted small mb-3">Specyfikacja, nagranie demo, checklisty…</div>
                @endif
                @if($canMutate)
                    <input type="file" class="form-control form-control-sm" wire:model="uploads" multiple
                           accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.txt,.zip">
                    @error('uploads.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" wire:click="saveUploads" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveUploads">Wgraj</span>
                        <span wire:loading wire:target="saveUploads">Wgrywanie…</span>
                    </button>
                    <div class="small text-muted mt-1">Do 15 plików, max 15 MB.</div>
                @endif
            </x-ui.card>
            <form method="POST" action="{{ route('sprints.destroy', $sprint) }}" class="mt-3"
                  onsubmit="return confirm('Usunąć sprint? Zadania zostaną odpięte (nie usunięte).')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash me-1"></i>Usuń sprint
                </button>
            </form>
        </div>
    </div>
</div>

@assets
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endassets

@script
<script>
    const sprintId = {{ $sprint->id }};
    const lineEl = () => document.getElementById('sb-burndown-' + sprintId);
    const donutEl = () => document.getElementById('sb-donut-' + sprintId);

    const defaults = () => {
        if (!window.Chart) return;
        Chart.defaults.color = 'rgba(255,255,255,0.45)';
        Chart.defaults.borderColor = 'rgba(255,255,255,0.08)';
        Chart.defaults.font.family = "'Albert Sans', system-ui, sans-serif";
        Chart.defaults.font.size = 11;
    };

    const drawLine = (data) => {
        const canvas = lineEl();
        if (!canvas || !window.Chart || !data) return;
        defaults();
        const existing = Chart.getChart(canvas);
        if (existing) {
            existing.data.labels = data.labels;
            existing.data.datasets[0].data = data.ideal;
            existing.data.datasets[1].data = data.actual;
            existing.update('none');
            return;
        }
        new Chart(canvas, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    { label: 'Idealny', data: data.ideal, borderColor: 'rgba(148,163,184,.7)', borderDash: [5,5], pointRadius: 0, tension: .2 },
                    { label: 'Pozostało', data: data.actual, borderColor: '#a855f7', backgroundColor: 'rgba(168,85,247,.12)', fill: true, tension: .3, spanGaps: false },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: { legend: { display: true } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            }
        });
    };

    const drawDonut = (data) => {
        const canvas = donutEl();
        if (!canvas || !window.Chart || !data) return;
        const existing = Chart.getChart(canvas);
        if (existing) {
            existing.data.labels = data.labels;
            existing.data.datasets[0].data = data.values;
            existing.update('none');
            return;
        }
        new Chart(canvas, {
            type: 'doughnut',
            data: { labels: data.labels, datasets: [{ data: data.values, backgroundColor: data.colors, borderWidth: 0 }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                cutout: '62%',
                plugins: { legend: { position: 'bottom' } },
            }
        });
    };

    const paint = (burndown, statusChart) => {
        drawLine(burndown);
        drawDonut(statusChart);
    };

    paint(@json($insights['burndown']), @json($insights['status_chart']));

    $wire.on('sb-charts', (payload) => {
        const p = Array.isArray(payload) ? payload[0] : payload;
        if (!p) return;
        paint(p.burndown, p.statusChart);
    });
</script>
@endscript
