<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Sprinty">
            <x-slot name="right">
                <a href="{{ route('tasks.grid') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-grid-3x3-gap me-1"></i>Siatka zadań
                </a>
                <x-ui.button
                    variant="primary"
                    href="{{ route('sprints.create') }}"
                    routeName="sprints.create"
                    action="create"
                >
                    Nowy sprint
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-ui.alert variant="success" title="Sukces" dismissible class="mb-3">
            {{ session('success') }}
        </x-ui.alert>
    @endif

    @if($sprints->isEmpty())
        <x-ui.card>
            <div class="text-center text-muted py-5">
                <i class="bi bi-calendar3 display-5 d-block mb-2 opacity-30"></i>
                <div>Brak sprintów.</div>
                <a href="{{ route('sprints.create') }}" class="btn btn-sm btn-link mt-1">Dodaj pierwszy sprint</a>
            </div>
        </x-ui.card>
    @else
        <div class="row g-3">
            @foreach($sprints as $sprint)
                @php
                    $done = (int) ($sprint->completed_tasks_count ?? 0);
                    $total = (int) ($sprint->tasks_count ?? 0);
                    $pct = $total > 0 ? (int) round($done / $total * 100) : 0;
                    $statusVariant = $sprint->isCurrentlyActive() ? 'success' : ($sprint->isScheduled() ? 'info' : 'secondary');
                    $barVariant = $sprint->isCurrentlyActive() ? 'success' : 'default';
                @endphp
                <div class="col-md-6 col-xl-4">
                    <a href="{{ route('sprints.show', $sprint) }}" class="text-decoration-none text-reset">
                        <x-ui.card variant="hover" class="h-100">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <div class="fw-semibold">{{ $sprint->name }}</div>
                                    <div class="small text-muted">
                                        {{ $sprint->start_date->format('d.m') }} – {{ $sprint->end_date->format('d.m.Y') }}
                                    </div>
                                </div>
                                <x-ui.badge :variant="$statusVariant">{{ $sprint->statusLabel() }}</x-ui.badge>
                            </div>
                            <p class="small text-muted mb-3" style="min-height:2.4em">{{ Str::limit($sprint->goal, 90) ?: 'Bez celu' }}</p>
                            <x-ui.progress :value="$pct" :max="100" :variant="$barVariant" />
                            <div class="d-flex justify-content-between small text-muted mt-2">
                                <span>{{ $done }}/{{ $total }} zadań</span>
                                <span>{{ $pct }}%</span>
                            </div>
                        </x-ui.card>
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</x-app-layout>
