<div>
    @if(! $equipment->issuable)
        <p class="text-muted mb-0">Ta pozycja nie jest wydawana pracownikom.</p>
    @else
        <div class="mb-4 pb-3 border-bottom">
            <div class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label for="issue-history-search" class="form-label small fw-semibold mb-1">
                        <i class="bi bi-search me-1"></i> Szukaj
                    </label>
                    <input
                        id="issue-history-search"
                        type="search"
                        class="form-control form-control-sm"
                        placeholder="Pracownik, SKU, magazyn, ZW…"
                        autocomplete="off"
                        wire:model.live.debounce.300ms="search"
                    >
                </div>
                <div class="col-md-3">
                    @if(filled($search) || $sortField !== 'date' || $sortDirection !== 'desc')
                        <x-ui.button variant="ghost" wire:click="clearFilters" class="btn-sm">
                            <i class="bi bi-x-circle"></i> Wyczyść
                        </x-ui.button>
                    @endif
                </div>
                @if($issues && $issues->total() > 0)
                    <div class="col-md-3 text-end">
                        <small class="text-muted"><strong>{{ $issues->total() }}</strong></small>
                    </div>
                @endif
            </div>
        </div>

        @if($issues && $issues->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <x-livewire.sortable-header field="employee" :sortField="$sortField" :sortDirection="$sortDirection">
                                Pracownik
                            </x-livewire.sortable-header>
                            <x-livewire.sortable-header field="sku" :sortField="$sortField" :sortDirection="$sortDirection">
                                SKU
                            </x-livewire.sortable-header>
                            <x-livewire.sortable-header field="warehouse" :sortField="$sortField" :sortDirection="$sortDirection">
                                Magazyn
                            </x-livewire.sortable-header>
                            <x-livewire.sortable-header field="quantity" :sortField="$sortField" :sortDirection="$sortDirection" class="text-end">
                                Ilość
                            </x-livewire.sortable-header>
                            <x-livewire.sortable-header field="date" :sortField="$sortField" :sortDirection="$sortDirection">
                                Data
                            </x-livewire.sortable-header>
                            <x-livewire.sortable-header field="status" :sortField="$sortField" :sortDirection="$sortDirection">
                                Status
                            </x-livewire.sortable-header>
                            <x-livewire.sortable-header field="dispatch" :sortField="$sortField" :sortDirection="$sortDirection">
                                ZW
                            </x-livewire.sortable-header>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($issues as $issue)
                            <tr wire:key="issue-{{ $issue->id }}">
                                <td>
                                    <x-employee-cell :employee="$issue->employee" />
                                </td>
                                <td>
                                    <x-ui.person
                                        :user="(object) [
                                            'name' => $issue->variant?->sku ?? $issue->variant?->kind_label ?? '—',
                                            'email' => $equipment->hasVariants()
                                                ? ($equipment->variant_label ?: 'Wariant').': '.($issue->variant?->kind_label ?? '—')
                                                : null,
                                            'image_path' => $equipment->image_path,
                                            'image_url' => $equipment->image_url,
                                        ]"
                                        avatar-size="28px"
                                        avatar-shape="rounded"
                                    />
                                </td>
                                <td>{{ $issue->warehouse?->display_name ?? '—' }}</td>
                                <td class="text-end" style="font-variant-numeric:tabular-nums;">{{ $issue->quantity_issued }}</td>
                                <td>{{ $issue->issue_date?->format('Y-m-d') }}</td>
                                <td>
                                    <x-ui.badge variant="{{ $issue->statusBadgeVariant() }}">{{ $issue->statusLabel() }}</x-ui.badge>
                                </td>
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
                                    <x-view-button href="{{ route('equipment-issues.show', $issue) }}" />
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
                :message="filled($search) ? 'Nie znaleziono wydań spełniających kryteria.' : 'Brak wydań.'"
                :has-filters="filled($search)"
                clear-filters-action="wire:clearFilters"
            />
        @endif
    @endif
</div>
