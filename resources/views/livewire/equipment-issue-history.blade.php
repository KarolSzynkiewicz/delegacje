<div>
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
                    placeholder="Pracownik, przeznaczenie, SKU, magazyn, ZW…"
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
            @if($entries->total() > 0)
                <div class="col-md-3 text-end">
                    <small class="text-muted"><strong>{{ $entries->total() }}</strong></small>
                </div>
            @endif
        </div>
    </div>

    @if($entries->count() > 0)
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <x-livewire.sortable-header field="employee" :sortField="$sortField" :sortDirection="$sortDirection">
                            Komu / gdzie
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
                    @foreach($entries as $entry)
                        @if($entry['kind'] === 'issue')
                            @php $issue = $entry['issue']; @endphp
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
                                    <div class="d-flex justify-content-end gap-1">
                                        <x-view-button href="{{ route('equipment-issues.show', $issue) }}" />
                                        @if($issue->isReturnableIssue() && $issue->equipment?->issuable && $issue->equipment?->returnable)
                                            <x-ui.button
                                                variant="success"
                                                href="{{ route('equipment-issues.return', $issue) }}"
                                                class="btn-sm"
                                                title="Zwróć/Zgłoś"
                                            >
                                                <i class="bi bi-arrow-return-left"></i>
                                            </x-ui.button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @else
                            @php $movement = $entry['movement']; @endphp
                            <tr wire:key="consumption-{{ $movement->id }}">
                                <td>
                                    @if($movement->destinationHref())
                                        <a href="{{ $movement->destinationHref() }}" class="text-decoration-none fw-semibold">
                                            {{ $movement->destinationMeta() ?? $movement->destinationLabel() ?? '—' }}
                                        </a>
                                    @else
                                        <span class="fw-semibold">{{ $movement->destinationMeta() ?? $movement->destinationLabel() ?? '—' }}</span>
                                    @endif
                                    @if($movement->notes)
                                        <div class="small text-muted">{{ $movement->notes }}</div>
                                    @endif
                                </td>
                                <td>
                                    <x-ui.person
                                        :user="(object) [
                                            'name' => $movement->variant?->sku ?? $movement->variant?->kind_label ?? '—',
                                            'email' => $equipment->hasVariants()
                                                ? ($equipment->variant_label ?: 'Wariant').': '.($movement->variant?->kind_label ?? '—')
                                                : null,
                                            'image_path' => $equipment->image_path,
                                            'image_url' => $equipment->image_url,
                                        ]"
                                        avatar-size="28px"
                                        avatar-shape="rounded"
                                    />
                                </td>
                                <td>{{ $movement->warehouse?->display_name ?? '—' }}</td>
                                <td class="text-end" style="font-variant-numeric:tabular-nums;">{{ $movement->quantity }}</td>
                                <td>{{ $movement->created_at?->format('Y-m-d') }}</td>
                                <td>
                                    <x-ui.badge variant="accent">Rozchód</x-ui.badge>
                                </td>
                                <td><span class="text-muted">—</span></td>
                                <td class="text-end">
                                    @if($movement->destinationHref())
                                        <x-view-button href="{{ $movement->destinationHref() }}" title="Zobacz przeznaczenie" />
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($entries->hasPages())
            <div class="mt-3">
                {{ $entries->links(data: ['scrollTo' => false]) }}
            </div>
        @endif
    @else
        <x-ui.empty-state
            icon="inbox"
            :message="filled($search) ? 'Nie znaleziono wydań ani rozchodów spełniających kryteria.' : 'Brak wydań i rozchodów.'"
            :has-filters="filled($search)"
            clear-filters-action="wire:clearFilters"
        />
    @endif
</div>
