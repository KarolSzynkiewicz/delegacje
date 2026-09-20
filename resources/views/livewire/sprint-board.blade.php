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
    @endphp

    @include('livewire.partials.sprint-board-styles')

    @if($flash)
        <div class="alert alert-success py-2 px-3 mb-3">{{ $flash }}</div>
    @endif

    <x-ui.card class="mb-3">
        <div class="sb-hero sb-hero--show">
            <div class="sb-hero__goal">
                <div class="sb-goal-kicker">Cel sprintu</div>
                @if($sprint->goal)
                    <p class="sb-goal-text">{{ $sprint->goal }}</p>
                @else
                    <p class="sb-goal-text is-empty">Brak celu — dopisz, po co ten sprint istnieje.</p>
                @endif
                <div class="sb-hero__chips">
                    <x-sprint.status-badge :sprint="$sprint" />
                    <x-ui.badge :variant="$healthMeta['variant']">
                        <i class="bi bi-{{ $healthMeta['icon'] }} me-1"></i>{{ $healthMeta['label'] }}
                    </x-ui.badge>
                </div>
                <div class="sb-hero__dates">
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    <span>
                        {{ $sprint->start_date->translatedFormat('j F Y') }} – {{ $sprint->end_date->translatedFormat('j F Y') }}
                        <span class="sb-hero__dates-sep">·</span>
                        {{ $insights['days_total'] }} dni
                    </span>
                </div>
                <p class="sb-hero__coach mb-0">
                    <i class="bi bi-lightbulb" aria-hidden="true"></i>
                    {{ $insights['coach'] }}
                </p>
            </div>

            <div class="sb-hero__stats">
                <x-sprint.progress :sprint="$sprint" size="lg" layout="side" />
                <div class="sb-hero__countdown">
                    @if($sprint->isClosed())
                        Zakończony {{ $sprint->closed_at->format('d.m.Y') }}
                    @elseif($sprint->isParked())
                        Odstawiony na później
                    @elseif($sprint->isScheduled())
                        <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>
                        {{ (int) $insights['starts_in'] }} {{ (int) $insights['starts_in'] === 1 ? 'dzień do startu' : 'dni do startu' }}
                    @elseif($sprint->isCurrentlyActive())
                        {{ $insights['days_left'] }} dni do końca
                        <span class="sb-hero__dates-sep">·</span>
                        dzień {{ $insights['days_elapsed'] }}/{{ $insights['days_total'] }}
                    @else
                        Termin minął — sprint wciąż otwarty
                    @endif
                    @if($sprint->createdBy)
                        <span class="sb-hero__dates-sep">·</span>{{ $sprint->createdBy->name }}
                    @endif
                </div>
            </div>
        </div>
    </x-ui.card>

    <div class="row g-3 mb-3 align-items-stretch">
        <div class="col-lg-4 d-flex">
            <x-ui.card class="h-100 w-100 sb-list-card">
                @include('livewire.partials.sprint-list-head', [
                    'icon' => 'play-circle',
                    'title' => 'Co potrzeba, by zacząć pracę?',
                    'hint' => 'Co musi być, byśmy mogli w ogóle zacząć nad tym pracować.',
                    'items' => $sprint->readinessItems,
                ])
                @include('livewire.partials.sprint-checkbox-list', [
                    'items' => $sprint->readinessItems,
                    'canMutate' => $canMutate,
                    'empty' => 'Brak warunków startu — dopisz, bez czego nie ruszamy.',
                    'placeholder' => 'np. Design zatwierdzony',
                    'addMethod' => 'addReadinessItem',
                    'toggleMethod' => 'toggleReadinessItem',
                    'deleteMethod' => 'deleteReadinessItem',
                    'newName' => 'newReadinessName',
                    'keyPrefix' => 'dor',
                ])
            </x-ui.card>
        </div>
        <div class="col-lg-4 d-flex">
            <x-ui.card class="h-100 w-100 sb-list-card">
                @include('livewire.partials.sprint-list-head', [
                    'icon' => 'flag',
                    'title' => 'Kiedy uznamy, że zrobione?',
                    'hint' => 'Warunki, bez których zadanie nie schodzi z tablicy.',
                    'items' => $sprint->doneItems,
                ])
                @include('livewire.partials.sprint-checkbox-list', [
                    'items' => $sprint->doneItems,
                    'canMutate' => $canMutate,
                    'empty' => 'Brak warunków ukończenia — dopisz, kiedy „done” znaczy done.',
                    'placeholder' => 'np. Na produkcji',
                    'addMethod' => 'addDoneItem',
                    'toggleMethod' => 'toggleDoneItem',
                    'deleteMethod' => 'deleteDoneItem',
                    'newName' => 'newDoneName',
                    'keyPrefix' => 'dod',
                ])
            </x-ui.card>
        </div>
        <div class="col-lg-4 d-flex">
            <x-ui.card class="h-100 w-100 sb-list-card">
                @include('livewire.partials.sprint-list-head', [
                    'icon' => 'diamond',
                    'title' => 'Przełomowe osiągnięcia',
                    'hint' => 'Kamienie milowe — demo, freeze, wdrożenie.',
                    'items' => $sprint->milestones,
                ])
                <div class="sb-stack">
                    <div class="sb-stack-body">
                        @if($sprint->milestones->isEmpty())
                            <div class="text-muted small">Brak kamieni — dodaj np. „Demo”, „Freeze kodu”, „Wdrożenie”.</div>
                        @else
                            <div class="sb-runway">
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
                                    <div class="sb-ms" style="left: {{ $pct }}%" title="{{ $ms->name }} · {{ $ms->due_date->format('d.m') }}">
                                        <div class="sb-ms-dot" style="background:{{ $dot }}"></div>
                                    </div>
                                @endforeach
                            </div>
                            @foreach($sprint->milestones as $ms)
                                <div class="sb-ms-row" wire:key="ms-{{ $ms->id }}-{{ $ms->isCompleted() ? '1' : '0' }}">
                                    <label class="sb-check {{ $ms->isCompleted() ? 'is-done' : '' }}">
                                        @if($canMutate)
                                            <input type="checkbox" @checked($ms->isCompleted()) wire:click.prevent="toggleMilestone({{ $ms->id }})">
                                        @else
                                            <input type="checkbox" @checked($ms->isCompleted()) disabled>
                                        @endif
                                        <span>{{ $ms->name }}</span>
                                    </label>
                                    <div class="sb-ms-meta">
                                        <span class="small text-muted font-mono">{{ $ms->due_date->format('d.m') }}</span>
                                        @if($ms->isOverdue())
                                            <x-ui.badge variant="danger">po terminie</x-ui.badge>
                                        @endif
                                        @if($canMutate)
                                            <button type="button" class="btn btn-sm btn-link sb-ghost p-0" wire:click="deleteMilestone({{ $ms->id }})" wire:confirm="Usunąć kamień milowy?">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    @if($canMutate)
                        <div class="sb-add-wrap">
                            <div class="sb-add sb-add--ms">
                                <input type="text" class="form-control form-control-sm" placeholder="np. Demo, freeze…" wire:model="newMilestoneName" wire:keydown.enter="addMilestone">
                                <input type="date" class="form-control form-control-sm" wire:model="newMilestoneDue">
                                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addMilestone">Dodaj</button>
                            </div>
                            @error('newMilestoneName') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    @endif
                </div>
            </x-ui.card>
        </div>
    </div>

    <div class="mb-4">
        <div class="fw-semibold mb-2">Backlog sprintu</div>
        <livewire:tasks-grid :locked-sprint-id="$sprint->id" :key="'sprint-grid-'.$sprint->id" />
    </div>

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

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <x-ui.card label="Obciążenie" class="h-100">
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
