<div>
    @if(session('success'))
        <x-ui.alert variant="success" dismissible class="mb-3">
            {{ session('success') }}
        </x-ui.alert>
    @endif

    <x-data-table-filters
        :count="$payrolls->total()"
        :has-filters="(bool) (!empty($search) || !empty($statusFilter) || !empty($companyFilter) || !empty($dateFrom) || !empty($dateTo))"
        item-label="payrolli"
    >
        <x-slot:actions>
            <x-ui.button
                variant="{{ $bulkMode ? 'warning' : 'ghost' }}"
                wire:click="toggleBulkMode"
                class="btn-sm"
            >
                <i class="bi bi-check2-square me-1"></i> {{ $bulkMode ? 'Tryb wyboru: WŁ.' : 'Rozliczenie zbiorowe' }}
            </x-ui.button>
        </x-slot:actions>

        <div class="dt-filter-field">
            <label for="search" class="form-label small">
                <i class="bi bi-search me-1"></i> Szukaj pracownika
            </label>
            <input type="text" id="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Imię lub nazwisko...">
        </div>

        <div class="dt-filter-field">
            <label for="companyFilter" class="form-label small">
                <i class="bi bi-building me-1"></i> Spółka
            </label>
            <select id="companyFilter" wire:model.live="companyFilter" class="form-select">
                <option value="">Wszystkie spółki</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="dt-filter-field">
            <label for="statusFilter" class="form-label small">Status</label>
            <select id="statusFilter" wire:model.live="statusFilter" class="form-select">
                <option value="">Wszystkie</option>
                <option value="draft">Szkic</option>
                <option value="issued">Wystawiony</option>
                <option value="approved">Zatwierdzony</option>
                <option value="paid">Wypłacony</option>
            </select>
        </div>

        <div class="dt-filter-field">
            <label for="dateFrom" class="form-label small">
                <i class="bi bi-calendar-event me-1"></i> Data od
            </label>
            <input type="date" id="dateFrom" wire:model.live="dateFrom" class="form-control" max="{{ $dateTo ? $dateTo : '' }}">
        </div>

        <div class="dt-filter-field">
            <label for="dateTo" class="form-label small">
                <i class="bi bi-calendar-check me-1"></i> Data do
            </label>
            <input type="date" id="dateTo" wire:model.live="dateTo" class="form-control" min="{{ $dateFrom ? $dateFrom : '' }}">
        </div>
    </x-data-table-filters>

    <x-ui.card>
        @if($bulkMode)
            <div class="mb-3 d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-between gap-2">
                <div class="small text-muted text-center text-sm-start">
                    Zaznaczono: <span class="fw-semibold">{{ count($selectedPayrollIds) }}</span>
                    @error('selectedPayrollIds')
                        <span class="text-danger ms-2">{{ $message }}</span>
                    @enderror
                </div>
                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center justify-content-sm-end">
                    <x-ui.button variant="primary" class="btn-sm w-100 w-sm-auto" wire:click="openBulkWizard" :disabled="count($selectedPayrollIds) < 1">
                        <i class="bi bi-plus-circle me-1"></i> Utwórz wyrównanie
                    </x-ui.button>
                    <x-ui.button variant="ghost" class="btn-sm w-100 w-sm-auto" wire:click="toggleBulkMode">
                        Zakończ
                    </x-ui.button>
                </div>
            </div>
        @endif

        @if($payrolls->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            @if($bulkMode)
                                <th class="text-start" style="width: 38px;">
                                    <x-ui.input
                                        type="checkbox"
                                        name="selectAllOnPage"
                                        id="payrolls-bulk-select-all"
                                        wire:model.live="selectAllOnPage"
                                        class="m-0"
                                    />
                                </th>
                            @endif
                            <x-livewire.sortable-header field="employee_id" :sortField="$sortField" :sortDirection="$sortDirection">
                                Pracownik
                            </x-livewire.sortable-header>
                            <x-livewire.sortable-header field="period_start" :sortField="$sortField" :sortDirection="$sortDirection">
                                Daty
                            </x-livewire.sortable-header>
                            <th class="text-start">Stawka</th>
                            <x-livewire.sortable-header field="hours_amount" :sortField="$sortField" :sortDirection="$sortDirection">
                                Kwota z godzin
                            </x-livewire.sortable-header>
                            <x-livewire.sortable-header field="adjustments_amount" :sortField="$sortField" :sortDirection="$sortDirection">
                                Korekty
                            </x-livewire.sortable-header>
                            <x-livewire.sortable-header field="total_amount" :sortField="$sortField" :sortDirection="$sortDirection">
                                Razem
                            </x-livewire.sortable-header>
                            <th class="text-start">Status</th>
                            <th class="text-start">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payrolls as $payroll)
                            <tr wire:key="payroll-{{ $payroll->id }}">
                                @if($bulkMode)
                                    <td>
                                        <x-ui.input
                                            type="checkbox"
                                            name="selectedPayrollIds"
                                            :value="$payroll->id"
                                            wire:model.live="selectedPayrollIds"
                                            class="m-0"
                                        />
                                    </td>
                                @endif
                                <td>
                                    <x-employee-cell :employee="$payroll->employee" />
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1 lh-sm">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-calendar-event text-muted"></i>
                                            <span class="small">{{ $payroll->period_start->format('d-m-Y') }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-calendar-check text-muted"></i>
                                            <span class="small">{{ $payroll->period_end->format('d-m-Y') }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $rateSummary = $payroll->rate_summary ?? null;
                                    @endphp
                                    <div class="d-flex flex-column lh-sm">
                                        @if(($rateSummary['type'] ?? null) === 'single')
                                            <span class="fw-semibold">{{ number_format((float) $rateSummary['amount'], 2, ',', ' ') }}</span>
                                        @elseif(($rateSummary['type'] ?? null) === 'multiple')
                                            <span class="fw-semibold">Różne</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                        @if(!empty($payroll->currency) && ($rateSummary['type'] ?? null) !== 'none')
                                            <span class="text-muted" style="font-size:.75rem;">{{ $payroll->currency }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column lh-sm">
                                        <span class="fw-semibold">{{ number_format($payroll->hours_amount, 2, ',', ' ') }}</span>
                                        @if((float) $payroll->hours_amount !== 0.0)
                                            <span class="text-muted" style="font-size:.75rem;">{{ $payroll->currency }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $corrTotals = $payroll->correction_totals_by_currency ?? $payroll->correctionTotalsByCurrency();
                                    @endphp
                                    @if(count($corrTotals) > 0)
                                        <div class="d-flex flex-column gap-1 lh-sm">
                                            @foreach($corrTotals as $cCur => $cAmt)
                                                <div class="d-flex flex-column lh-sm">
                                                    <span @class(['fw-semibold', 'text-danger' => $cAmt < 0, 'text-success' => $cAmt > 0])>
                                                        {{ $cAmt >= 0 ? '+' : '' }}{{ number_format($cAmt, 2, ',', ' ') }}
                                                    </span>
                                                    <span class="text-muted" style="font-size:.75rem;">{{ $cCur }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="d-flex flex-column lh-sm">
                                            <span class="fw-semibold">{{ number_format($payroll->adjustments_amount, 2, ',', ' ') }}</span>
                                            @if((float) $payroll->adjustments_amount !== 0.0)
                                                <span class="text-muted" style="font-size:.75rem;">{{ $payroll->currency }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $payoutTotals = $payroll->payout_totals_by_currency ?? $payroll->payoutTotalsByCurrency();
                                    @endphp
                                    <div class="d-flex flex-column gap-1 lh-sm">
                                        @foreach($payoutTotals as $pCur => $pAmt)
                                            <div class="d-flex flex-column lh-sm">
                                                <span @class([
                                                    'fw-semibold',
                                                    'text-muted' => abs((float) $pAmt) < 0.00001,
                                                    'text-danger' => (float) $pAmt < 0,
                                                    'text-success' => (float) $pAmt > 0,
                                                ])>
                                                    @if(abs((float) $pAmt) >= 0.00001)
                                                        {{ (float) $pAmt >= 0 ? '+' : '' }}
                                                    @endif
                                                    {{ number_format((float) $pAmt, 2, ',', ' ') }}
                                                </span>
                                                <span class="text-muted" style="font-size:.75rem;">{{ $pCur }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $badgeVariant = match($payroll->status->value) {
                                            'draft' => 'accent',
                                            'issued' => 'warning',
                                            'approved' => 'info',
                                            'paid' => 'success',
                                            default => 'accent'
                                        };
                                    @endphp
                                    <x-ui.badge variant="{{ $badgeVariant }}">{{ $payroll->status->label() }}</x-ui.badge>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        @if(in_array($payroll->status->value, ['draft', 'issued']))
                                        <form action="{{ route('payrolls.recalculate', $payroll) }}" method="POST" class="d-inline">
                                            @csrf
                                            <x-ui.button variant="warning" type="submit" class="btn-sm" title="Przelicz na podstawie aktualnych stawek">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </x-ui.button>
                                        </form>
                                        @endif
                                        <x-action-buttons
                                            viewRoute="{{ route('payrolls.show', $payroll) }}"
                                            editRoute="{{ route('payrolls.edit', $payroll) }}"
                                            deleteRoute="{{ route('payrolls.destroy', $payroll) }}"
                                            deleteMessage="Czy na pewno chcesz usunąć ten payroll?"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($payrolls->hasPages())
                <div class="mt-3 pt-3 border-top">
                    {{ $payrolls->links() }}
                </div>
            @endif
        @else
            <x-ui.empty-state 
                icon="inbox"
                :message="!empty($search) || !empty($statusFilter) || !empty($dateFrom) || !empty($dateTo) ? 'Nie znaleziono payrolli spełniających kryteria wyszukiwania.' : 'Brak payrolli w systemie.'"
                :has-filters="!empty($search) || !empty($statusFilter) || !empty($dateFrom) || !empty($dateTo)"
                clear-filters-action="wire:clearFilters"
            >
                @if(empty($search) && empty($statusFilter) && empty($dateFrom) && empty($dateTo))
                    <x-ui.button variant="primary" href="{{ route('payrolls.create') }}">
                        <i class="bi bi-plus-circle"></i> Wygeneruj pierwszy payroll
                    </x-ui.button>
                @endif
            </x-ui.empty-state>
        @endif
    </x-ui.card>

    @if($showBulkWizard)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.55);" wire:click.self="$set('showBulkWizard', false)">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="background: var(--bg-card); border: 1px solid var(--glass-border); border-radius: 20px;">
                    <div class="modal-header" style="border-color: var(--glass-border) !important;">
                        <h5 class="modal-title">Rozliczenie zbiorowe</h5>
                        <button type="button" class="btn-close" aria-label="Close" wire:click="$set('showBulkWizard', false)"></button>
                    </div>
                    <div class="modal-body">
                        <div class="small text-muted mb-3">
                            Wybrane payrolle: <span class="fw-semibold">{{ count($selectedPayrollIds) }}</span>
                        </div>

                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small fw-semibold mb-1">Kwota</label>
                                <input type="number" step="0.01" class="form-control" wire:model.defer="bulkAmount">
                                @error('bulkAmount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Data</label>
                                <input type="date" class="form-control" wire:model.defer="bulkDate">
                                @error('bulkDate') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Waluta</label>
                                <select class="form-select" wire:model.defer="bulkCurrency">
                                    <option value="PLN">PLN</option>
                                    <option value="EUR">EUR</option>
                                    <option value="USD">USD</option>
                                </select>
                                @error('bulkCurrency') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold mb-1">Opis</label>
                                <textarea rows="3" class="form-control" wire:model.defer="bulkDescription"></textarea>
                                @error('bulkDescription') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-color: var(--glass-border) !important;">
                        <x-ui.button variant="ghost" class="btn-sm" wire:click="$set('showBulkWizard', false)">Anuluj</x-ui.button>
                        <x-ui.button variant="primary" class="btn-sm" wire:click="createBulkAdjustments">
                            Utwórz koszt
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
