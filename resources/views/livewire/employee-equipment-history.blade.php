<div>
    <div class="card">
        <div class="card-body">
            <x-ui.table-header title="Asortyment i wydania z magazynu" subtitle="Co pracownik dostał i kiedy oddał — ze wszystkich magazynów.">
                <x-slot name="actions">
                    <x-ui.button
                        variant="primary"
                        href="{{ route('equipment-issues.create') }}"
                        routeName="equipment-issues.create"
                        class="btn-sm"
                    >
                        Zleć wydanie
                    </x-ui.button>
                </x-slot>
            </x-ui.table-header>

            @if($held->isNotEmpty() || $reserved->isNotEmpty())
                <div class="row g-3 mb-4">
                    @if($held->isNotEmpty())
                        <div class="col-lg-8">
                            <div class="p-3 rounded h-100" style="background:rgba(56,189,248,.08);border:1px solid rgba(56,189,248,.25);">
                                <div class="d-flex justify-content-between align-items-baseline gap-2 mb-2">
                                    <h6 class="mb-0">
                                        <i class="bi bi-person-check me-1"></i>
                                        Aktualnie u pracownika
                                    </h6>
                                    <small class="text-muted">
                                        {{ $held->sum('quantity_issued') }} szt.
                                    </small>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Pozycja</th>
                                                <th class="text-end">Ilość</th>
                                                <th>Otrzymał</th>
                                                <th>Magazyn</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($held as $issue)
                                                <tr wire:key="held-{{ $issue->id }}">
                                                    <td>
                                                        <div class="fw-semibold">{{ $issue->variant?->sku ?? $issue->item_label }}</div>
                                                        @if($issue->dispatch)
                                                            <div class="small text-muted">{{ $issue->dispatch->number }}</div>
                                                        @endif
                                                    </td>
                                                    <td class="text-end" style="font-variant-numeric:tabular-nums;">{{ $issue->quantity_issued }}</td>
                                                    <td>{{ $issue->issue_date?->format('Y-m-d') ?? '—' }}</td>
                                                    <td>{{ $issue->warehouse?->display_name ?? '—' }}</td>
                                                    <td class="text-end">
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
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                    @if($reserved->isNotEmpty())
                        <div class="{{ $held->isNotEmpty() ? 'col-lg-4' : 'col-12' }}">
                            <div class="p-3 rounded h-100" style="background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.25);">
                                <h6 class="mb-2">
                                    <i class="bi bi-hourglass-split me-1"></i>
                                    Oczekuje na wydanie
                                </h6>
                                <ul class="list-unstyled mb-0 small">
                                    @foreach($reserved as $issue)
                                        <li class="d-flex justify-content-between gap-2 mb-1" wire:key="reserved-{{ $issue->id }}">
                                            <span>
                                                {{ $issue->variant?->sku ?? $issue->item_label }}
                                                @if($issue->dispatch)
                                                    <span class="text-muted">· {{ $issue->dispatch->number }}</span>
                                                @endif
                                            </span>
                                            <span style="font-variant-numeric:tabular-nums;">{{ $issue->quantity_issued }} szt.</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <div class="mb-4 pb-3 border-bottom">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label for="employee-equipment-search" class="form-label small fw-semibold mb-1">
                            <i class="bi bi-search me-1"></i> Szukaj
                        </label>
                        <input
                            id="employee-equipment-search"
                            type="search"
                            class="form-control form-control-sm"
                            placeholder="SKU, magazyn, ZW…"
                            autocomplete="off"
                            wire:model.live.debounce.300ms="search"
                        >
                    </div>
                    <div class="col-md-4">
                        <label for="employee-equipment-status" class="form-label small fw-semibold mb-1">Status</label>
                        <select id="employee-equipment-status" class="form-select form-select-sm" wire:model.live="statusFilter">
                            <option value="">Wszystkie</option>
                            <option value="issued">U pracownika</option>
                            <option value="given">Bezzwrotne</option>
                            <option value="returned">Oddane</option>
                            <option value="reserved">Zarezerwowane</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        @if(filled($search) || $statusFilter !== '' || $sortField !== 'date' || $sortDirection !== 'desc')
                            <x-ui.button variant="ghost" wire:click="clearFilters" class="btn-sm">
                                <i class="bi bi-x-circle"></i> Wyczyść
                            </x-ui.button>
                        @endif
                    </div>
                </div>
            </div>

            @if($issues && $issues->count() > 0)
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <x-livewire.sortable-header field="sku" :sortField="$sortField" :sortDirection="$sortDirection">
                                    Pozycja
                                </x-livewire.sortable-header>
                                <x-livewire.sortable-header field="quantity" :sortField="$sortField" :sortDirection="$sortDirection" class="text-end">
                                    Ilość
                                </x-livewire.sortable-header>
                                <x-livewire.sortable-header field="date" :sortField="$sortField" :sortDirection="$sortDirection">
                                    Otrzymał
                                </x-livewire.sortable-header>
                                <x-livewire.sortable-header field="returned" :sortField="$sortField" :sortDirection="$sortDirection">
                                    Oddał
                                </x-livewire.sortable-header>
                                <x-livewire.sortable-header field="status" :sortField="$sortField" :sortDirection="$sortDirection">
                                    Status
                                </x-livewire.sortable-header>
                                <x-livewire.sortable-header field="warehouse" :sortField="$sortField" :sortDirection="$sortDirection">
                                    Magazyn
                                </x-livewire.sortable-header>
                                <x-livewire.sortable-header field="dispatch" :sortField="$sortField" :sortDirection="$sortDirection">
                                    ZW
                                </x-livewire.sortable-header>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($issues as $issue)
                                <tr wire:key="employee-issue-{{ $issue->id }}">
                                    <td>
                                        <x-ui.person
                                            :user="(object) [
                                                'name' => $issue->variant?->sku ?? $issue->item_label,
                                                'email' => $issue->equipment?->hasVariants()
                                                    ? ($issue->equipment->variant_label ?: 'Wariant').': '.($issue->variant?->kind_label ?? '—')
                                                    : null,
                                                'image_path' => $issue->equipment?->image_path,
                                                'image_url' => $issue->equipment?->image_url,
                                            ]"
                                            avatar-size="28px"
                                            avatar-shape="rounded"
                                        />
                                    </td>
                                    <td class="text-end" style="font-variant-numeric:tabular-nums;">{{ $issue->quantity_issued }}</td>
                                    <td>{{ $issue->issue_date?->format('Y-m-d') ?? '—' }}</td>
                                    <td>
                                        @if($issue->actual_return_date)
                                            {{ $issue->actual_return_date->format('Y-m-d') }}
                                        @elseif($issue->isPermanentIssue())
                                            <span class="text-muted">nie podlega zwrotowi</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <x-ui.badge variant="{{ $issue->statusBadgeVariant() }}">{{ $issue->statusLabel() }}</x-ui.badge>
                                    </td>
                                    <td>{{ $issue->warehouse?->display_name ?? '—' }}</td>
                                    <td>
                                        @if($issue->dispatch)
                                            <a href="{{ route('warehouse-dispatches.show', $issue->dispatch) }}" class="text-decoration-none">
                                                {{ $issue->dispatch->number }}
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
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
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($issues->hasPages())
                    <div class="mt-3">
                        {{ $issues->links(data: ['scrollTo' => false]) }}
                    </div>
                @endif
            @else
                <x-ui.empty-state
                    icon="inbox"
                    :message="filled($search) || $statusFilter !== '' ? 'Nie znaleziono wydań spełniających kryteria.' : 'Ten pracownik nie ma jeszcze wydań z magazynu.'"
                    :has-filters="filled($search) || $statusFilter !== ''"
                    clear-filters-action="wire:clearFilters"
                />
            @endif
        </div>
    </div>
</div>
