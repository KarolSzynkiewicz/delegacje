@php
    $sprint = $snaps['sprint'];
    $tasks = $snaps['tasks'];
    $statusMap = [
        'pending'     => ['variant' => 'warning', 'label' => 'Oczekujące'],
        'in_progress' => ['variant' => 'info', 'label' => 'W trakcie'],
        'completed'   => ['variant' => 'success', 'label' => 'Ukończone'],
        'cancelled'   => ['variant' => 'danger', 'label' => 'Anulowane'],
    ];
@endphp

<x-dashboard.snap
    kicker="Praca zespołu"
    title="Zadania i sprint"
    caption="Backlog to lista roboty (kategoria, priorytet, owner). Sprint pokazuje zakres, zdrowie i burndown tego samego zestawu — nie duplikujesz tasków w Excelu."
    :href="Route::has('tasks.grid') ? route('tasks.grid') : (Route::has('tasks.index') ? route('tasks.index') : null)"
    tall
>
    <div class="row g-3">
        <div class="col-lg-5">
            <x-ui.card label="Przykładowa lista zadań">
                @foreach($tasks as $task)
                    @php $sc = $statusMap[$task['status']->value]; @endphp
                    <div class="d-flex align-items-center gap-2 py-2 border-bottom" style="border-color: var(--glass-border) !important;">
                        <x-ui.badge :variant="$sc['variant']">{{ $sc['label'] }}</x-ui.badge>
                        <div class="flex-grow-1 min-width-0">
                            <div class="small fw-semibold text-truncate">#{{ $task['id'] }} {{ $task['name'] }}</div>
                            <div class="small text-muted">{{ $task['category'] }} · {{ $task['assignee'] }}</div>
                        </div>
                    </div>
                @endforeach
            </x-ui.card>
        </div>
        <div class="col-lg-7">
            @include('livewire.partials.sprint-board-styles')
            <div class="sb">
                <x-ui.card class="mb-3">
                    <div class="sb-hero">
                        <div class="sb-ring">
                            <svg width="110" height="110" viewBox="0 0 110 110">
                                <circle cx="55" cy="55" r="42" fill="none" stroke="rgba(255,255,255,.08)" stroke-width="8"/>
                                <circle cx="55" cy="55" r="42" fill="none" stroke="url(#dashSbGrad)" stroke-width="8"
                                        stroke-linecap="round"
                                        stroke-dasharray="{{ $sprint['ring'] }}"
                                        stroke-dashoffset="{{ $sprint['dash'] }}"/>
                                <defs>
                                    <linearGradient id="dashSbGrad" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0%" stop-color="#3b82f6"/>
                                        <stop offset="100%" stop-color="#a855f7"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                            <div class="sb-ring-label">
                                <strong style="font-size:1.35rem; letter-spacing:-.04em">{{ $sprint['progress'] }}%</strong>
                                <span class="small text-muted">done</span>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <x-ui.badge variant="success">Aktywny</x-ui.badge>
                                <x-ui.badge variant="success"><i class="bi bi-lightning-charge me-1"></i>{{ $sprint['health'] }}</x-ui.badge>
                                <span class="text-muted small">
                                    {{ $sprint['start']->format('d.m.Y') }} – {{ $sprint['end']->format('d.m.Y') }}
                                    · {{ $sprint['days_total'] }} dni
                                </span>
                            </div>
                            <p class="mb-1 fs-5" style="letter-spacing:-.02em">{{ $sprint['name'] }}</p>
                            <p class="mb-2 text-muted">{{ $sprint['goal'] }}</p>
                            <p class="mb-0 text-muted small">{{ $sprint['coach'] }}</p>
                        </div>
                        <div class="text-end">
                            <div style="font-size:1.8rem; font-weight:700; letter-spacing:-.04em">{{ $sprint['days_left'] }}</div>
                            <div class="small text-muted">dni do końca</div>
                            <div class="small text-muted mt-1">dzień {{ $sprint['days_elapsed'] }}/{{ $sprint['days_total'] }}</div>
                        </div>
                    </div>
                </x-ui.card>
                <div class="sb-kpis mb-3">
                    <div class="sb-kpi">
                        <div class="v">{{ $sprint['done'] }}<span class="text-muted" style="font-size:.9rem">/{{ $sprint['scope'] }}</span></div>
                        <div class="l">W zakresie</div>
                    </div>
                    <div class="sb-kpi">
                        <div class="v text-info">{{ $sprint['in_progress'] }}</div>
                        <div class="l">W trakcie</div>
                    </div>
                    <div class="sb-kpi">
                        <div class="v {{ $sprint['overdue'] ? 'text-danger' : '' }}">{{ $sprint['overdue'] }}</div>
                        <div class="l">Po terminie</div>
                    </div>
                    <div class="sb-kpi">
                        <div class="v">{{ $sprint['milestones_done'] }}<span class="text-muted" style="font-size:.9rem">/{{ $sprint['milestones_total'] }}</span></div>
                        <div class="l">Kamienie milowe</div>
                    </div>
                </div>
                @foreach($sprint['tasks'] as $row)
                    <div class="sb-task">
                        <span class="sb-grip"><i class="bi bi-grip-vertical"></i></span>
                        <span class="sb-pos">{{ $row['pos'] }}</span>
                        <span class="text-truncate">{{ $row['name'] }}</span>
                        <span class="small text-muted text-truncate">{{ $row['who'] }}</span>
                        @if($row['done'])
                            <x-ui.badge variant="success">done</x-ui.badge>
                        @else
                            <x-ui.badge variant="info">open</x-ui.badge>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-dashboard.snap>
