<x-app-layout>
    @php
        $picking = $warehouseDispatch->isReserved();
        $warehouse = $warehouseDispatch->warehouse;
        $reservedIssues = $warehouseDispatch->issues->where('status', \App\Models\EquipmentIssue::STATUS_RESERVED)->values();
        $selectedInit = $reservedIssues->mapWithKeys(fn ($issue) => [(string) $issue->id => false])->all();
        $grouped = $reservedIssues->groupBy('employee_id');
        $backRoute = $picking
            ? route('equipment.tab.orders', $warehouse ? ['warehouse_id' => $warehouse->id] : [])
            : route('equipment.tab.issues');
    @endphp
    <x-slot name="header">
        <x-ui.page-header :title="($picking ? 'Kompletacja ' : 'Wydanie ').$warehouseDispatch->number">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ $backRoute }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
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

    @if($picking)
        <style>
            .form-check.form-check-table {
                padding: 0;
                margin: 0;
                background: transparent;
                border: none;
                gap: 0;
            }
        </style>
        <form
            method="POST"
            action="{{ route('warehouse-dispatches.fulfill', $warehouseDispatch) }}"
            x-data="{
                selected: {{ \Illuminate\Support\Js::from($selectedInit) }},
                get checked() { return Object.values(this.selected).filter(Boolean).length },
                get total() { return Object.keys(this.selected).length },
                get percent() { return this.total ? Math.round(this.checked / this.total * 100) : 0 },
                get all() { return this.total > 0 && this.checked === this.total },
                toggleAll() {
                    const next = ! this.all;
                    Object.keys(this.selected).forEach((id) => { this.selected[id] = next });
                },
                confirmPartial(event) {
                    if (this.checked === 0) {
                        event.preventDefault();
                        return;
                    }
                    if (this.checked < this.total && ! confirm('Wydasz tylko odhaczone pozycje. Pozostałe wrócą do dostępnych jako niewydane.')) {
                        event.preventDefault();
                    }
                }
            }"
            @submit="confirmPartial($event)"
        >
            @csrf
            <x-ui.card label="Kompletacja zlecenia" class="mb-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <h6 class="text-muted small mb-1">Numer</h6>
                        <p class="fw-semibold mb-0">{{ $warehouseDispatch->number }}</p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted small mb-1">Magazyn</h6>
                        <p class="fw-semibold mb-0">{{ $warehouseDispatch->warehouse?->display_name ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted small mb-1">Zlecił</h6>
                        <p class="fw-semibold mb-0">{{ $warehouseDispatch->creator?->name ?? '—' }}</p>
                    </div>
                    @if($warehouseDispatch->tasks->isNotEmpty())
                        <div class="col-md-4">
                            <h6 class="text-muted small mb-1">Zadanie</h6>
                            <p class="fw-semibold mb-0">
                                @foreach($warehouseDispatch->tasks as $task)
                                    <a href="{{ route('tasks.show', $task) }}" class="text-decoration-none">
                                        {{ $task->name }}
                                    </a>
                                    @if($task->assignedTo)
                                        <span class="text-muted fw-normal"> · {{ $task->assignedTo->name }}</span>
                                    @endif
                                    @if(! $loop->last)<br>@endif
                                @endforeach
                            </p>
                        </div>
                    @endif
                </div>

                <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                    <div>
                        <div class="fw-semibold">Postęp kompletacji</div>
                        <div class="small text-muted" x-text="`${checked} / ${total} pozycji`"></div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="toggleAll()">
                        <span x-text="all ? 'Odznacz wszystkie' : 'Zaznacz wszystkie'"></span>
                    </button>
                </div>
                <div class="progress-ui mb-1">
                    <div class="progress-bar-ui" :style="`width: ${percent}%;`"></div>
                </div>
                <div class="small text-muted mb-4" x-text="`${percent}%`"></div>

                <p class="text-muted small mb-3">
                    Odhacz to, co realnie zbierasz z półki. Brakujące pozycje zostaw puste — po wydaniu wrócą do dostępnych.
                </p>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:2.75rem;"></th>
                                <th>Pracownik</th>
                                <th>Pozycja</th>
                                <th>Rodzaj</th>
                                <th class="text-end">Ilość</th>
                                <th class="text-end">Na półce</th>
                                <th>Typ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($grouped as $employeeIssues)
                                @php
                                    $first = $employeeIssues->first();
                                @endphp
                                @foreach($employeeIssues as $index => $issue)
                                    @php
                                        $onHand = $issue->variant && $warehouse
                                            ? $issue->variant->quantityIn($warehouse)
                                            : 0;
                                        $short = $onHand < (int) $issue->quantity_issued;
                                    @endphp
                                    <tr @class(['table-warning' => $short])>
                                        <td>
                                            <x-ui.input
                                                type="checkbox"
                                                class="form-check-table"
                                                name="issue_ids[]"
                                                :id="'pick-issue-'.$issue->id"
                                                :value="$issue->id"
                                                x-model="selected['{{ $issue->id }}']"
                                            />
                                        </td>
                                        @if($index === 0)
                                            <td rowspan="{{ $employeeIssues->count() }}">
                                                @if($first?->employee)
                                                    <x-employee-cell :employee="$first->employee" :show-phone="false" avatar-size="32px" />
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        @endif
                                        <td>
                                            <x-ui.person
                                                :user="(object) [
                                                    'name' => $issue->equipment?->name ?? '—',
                                                    'image_path' => $issue->equipment?->image_path,
                                                    'image_url' => $issue->equipment?->image_url,
                                                ]"
                                                :show-email="false"
                                                avatar-size="36px"
                                                avatar-shape="rounded"
                                            />
                                        </td>
                                        <td>{{ $issue->variant?->kind_label ?? '—' }}</td>
                                        <td class="text-end" style="font-variant-numeric:tabular-nums;">{{ $issue->quantity_issued }}</td>
                                        <td class="text-end" style="font-variant-numeric:tabular-nums;">
                                            {{ $onHand }}
                                            @if($short)
                                                <x-ui.badge variant="warning">Brak</x-ui.badge>
                                            @endif
                                        </td>
                                        <td>
                                            <x-ui.badge variant="{{ $issue->equipment?->returnable ? 'info' : 'accent' }}">
                                                {{ $issue->equipment?->returnable ? 'Do zwrotu' : 'Bezzwrotne' }}
                                            </x-ui.badge>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="7" class="text-muted">Brak pozycji do kompletacji.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <x-ui.button variant="primary" type="submit" action="save" x-bind:disabled="checked === 0">
                        Wydaj odhaczone
                    </x-ui.button>
                </div>
            </x-ui.card>
        </form>
    @else
        <x-ui.card :label="$warehouseDispatch->isPartial() ? 'Częściowo wydane' : 'Wydanie z magazynu'">
            @include('equipment-issues._dispatch-summary', ['summary' => $warehouseDispatch->summary()])
        </x-ui.card>
    @endif
</x-app-layout>
