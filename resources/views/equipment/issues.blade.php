@php
    $kind = $kind ?? 'all';
    $hasFilters = (request('kind') && request('kind') !== 'all')
        || request('status')
        || request('employee')
        || request('item');
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Magazyn">
            <x-slot name="right">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('equipment-consumptions.create', ['warehouse_id' => $warehouse->id]) }}"
                    routeName="equipment-consumptions.create"
                    action="create"
                >
                    Rozchód
                </x-ui.button>
                <x-ui.button
                    variant="primary"
                    href="{{ route('equipment-issues.create', ['warehouse_id' => $warehouse->id]) }}"
                    routeName="equipment-issues.create"
                    action="create"
                >
                    Wydaj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-ui.alert variant="success" title="Sukces" dismissible class="mb-3">
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <x-ui.card class="mb-4">
        @include('equipment._tabs', ['activeTab' => $activeTab ?? 'issues'])
        <form method="GET" action="{{ route('equipment.tab.issues') }}" id="filter-form" class="js-auto-submit">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small">
                        <i class="bi bi-funnel me-1"></i> Zdarzenie
                    </label>
                    <select name="kind" class="form-control" onchange="this.form.submit()">
                        <option value="all" @selected($kind === 'all')>Wszystkie</option>
                        <option value="issue" @selected($kind === 'issue')>Wydania do zwrotu</option>
                        <option value="given" @selected($kind === 'given')>Wydania bezzwrotne</option>
                        <option value="consumption" @selected($kind === 'consumption')>Rozchód</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">
                        <i class="bi bi-check-circle me-1"></i> Status
                    </label>
                    <select name="status" class="form-control" onchange="this.form.submit()" @disabled($kind === 'consumption')>
                        <option value="">Wszystkie statusy</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>
                                {{ \App\Models\EquipmentIssue::labelForStatus($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">
                        <i class="bi bi-person me-1"></i> Pracownik
                    </label>
                    <input
                        type="search"
                        name="employee"
                        value="{{ request('employee') }}"
                        class="form-control js-debounced"
                        placeholder="Szukaj pracownika…"
                        autocomplete="off"
                    >
                </div>
                <div class="col-md-3">
                    <label class="form-label small">
                        <i class="bi bi-box-seam me-1"></i> Typ
                    </label>
                    <input
                        type="search"
                        name="item"
                        value="{{ request('item') }}"
                        class="form-control js-debounced"
                        placeholder="Szukaj typu…"
                        autocomplete="off"
                    >
                </div>
            </div>
        </form>

        @if($hasFilters)
            <div class="mt-3 pt-3 border-top">
                <a href="{{ route('equipment.tab.issues') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                </a>
            </div>
        @endif
    </x-ui.card>

    <x-ui.card class="p-0">
        @if($entries->count() > 0)
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="padding-left:1rem;">Data</th>
                            <th>Zdarzenie</th>
                            <th>Magazyn</th>
                            <th>Pozycja</th>
                            <th>Pracownik</th>
                            <th class="text-end">Ilość</th>
                            <th>Status</th>
                            <th>Kto</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entries as $entry)
                            @if($entry['kind'] === 'issue')
                                @php $issue = $entry['issue']; @endphp
                                <tr>
                                    <td style="padding-left:1rem;white-space:nowrap;">
                                        {{ $issue->issue_date?->format('Y-m-d') }}
                                    </td>
                                    <td>{{ $issue->eventLabel() }}</td>
                                    <td>{{ $issue->warehouse?->display_name ?? '—' }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $issue->equipment?->name ?? '—' }}</div>
                                        <div class="small text-muted">{{ $issue->variant?->kind_label ?? '—' }}</div>
                                    </td>
                                    <td>
                                        <x-employee-cell :employee="$issue->employee" />
                                    </td>
                                    <td class="text-end" style="font-variant-numeric:tabular-nums;">{{ $issue->quantity_issued }}</td>
                                    <td>
                                        <x-ui.badge variant="{{ $issue->statusBadgeVariant() }}">{{ $issue->statusLabel() }}</x-ui.badge>
                                    </td>
                                    <td>{{ $issue->issuer?->name ?? '—' }}</td>
                                    <td>
                                        <div class="d-flex justify-content-end gap-1">
                                            <x-view-button href="{{ route('equipment-issues.show', $issue) }}" />
                                            @if($issue->isReturnableIssue() && $issue->equipment?->issuable && $issue->equipment?->returnable)
                                                <x-ui.button variant="success" href="{{ route('equipment-issues.return', $issue) }}" class="btn-sm" title="Zwróć/Zgłoś">
                                                    <i class="bi bi-arrow-return-left"></i>
                                                </x-ui.button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @else
                                @php $movement = $entry['movement']; @endphp
                                <tr>
                                    <td style="padding-left:1rem;white-space:nowrap;">
                                        {{ $movement->created_at?->format('Y-m-d H:i') }}
                                    </td>
                                    <td>Rozchód</td>
                                    <td>{{ $movement->warehouse?->display_name ?? '—' }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $movement->equipment?->name ?? '—' }}</div>
                                        <div class="small text-muted">{{ $movement->variant?->kind_label ?? '—' }}</div>
                                    </td>
                                    <td>
                                        @if($movement->employee)
                                            <x-employee-cell :employee="$movement->employee" />
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end" style="font-variant-numeric:tabular-nums;">−{{ $movement->quantity }}</td>
                                    <td>
                                        <x-ui.badge variant="accent">Zdjęto ze stanu</x-ui.badge>
                                    </td>
                                    <td>{{ $movement->creator?->name ?? '—' }}</td>
                                    <td>
                                        @if($movement->notes)
                                            <span class="small text-muted">{{ \Illuminate\Support\Str::limit($movement->notes, 40) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($entries->hasPages())
                <div class="px-3 py-2" style="border-top:1px solid var(--glass-border);">
                    <x-ui.pagination :paginator="$entries" />
                </div>
            @endif
        @else
            <div class="p-4">
                <x-ui.empty-state
                    icon="inbox"
                    :message="$hasFilters ? 'Brak zdarzeń spełniających kryteria.' : 'Brak wydań i rozchodów.'"
                >
                    @if(! $hasFilters)
                        <x-ui.button
                            variant="primary"
                            href="{{ route('equipment-issues.create', ['warehouse_id' => $warehouse->id]) }}"
                            action="create"
                        >
                            Wydaj pierwszą pozycję
                        </x-ui.button>
                    @else
                        <a href="{{ route('equipment.tab.issues') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                        </a>
                    @endif
                </x-ui.empty-state>
            </div>
        @endif
    </x-ui.card>

    @include('equipment._filter-debounce')
</x-app-layout>
