<div>
    <x-ui.card>
        @if(session('success'))
            <x-ui.alert variant="success" dismissible>
                {{ session('success') }}
            </x-ui.alert>
        @endif

        <!-- Statystyki i Filtry -->
        <div class="mb-4 pb-3 border-top border-bottom">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div>
                    <h3 class="fs-5 fw-semibold mb-1">Payroll</h3>
                    <p class="small text-muted mb-0">
                        @if(!empty($search) || !empty($statusFilter) || !empty($currencyFilter))
                            Znaleziono: <span class="fw-semibold">{{ $payrolls->total() }}</span> payrolli
                        @else
                            Łącznie: <span class="fw-semibold">{{ $payrolls->total() }}</span> payrolli
                        @endif
                    </p>
                </div>
                <x-ui.button 
                    variant="ghost" 
                    wire:click="clearFilters" 
                    class="btn-sm"
                    :disabled="empty($search) && empty($statusFilter) && empty($currencyFilter)"
                >
                    <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                </x-ui.button>
            </div>

            <!-- Filtry -->
            <div class="row g-2 align-items-end">
                <!-- Wyszukiwanie po pracowniku -->
                <div class="col-md-4">
                    <label for="search" class="form-label small fw-semibold mb-1">
                        <i class="bi bi-search me-1"></i> Szukaj pracownika
                    </label>
                    <input type="text" 
                           id="search"
                           wire:model.live.debounce.300ms="search" 
                           class="form-control form-control-sm" 
                           placeholder="Imię lub nazwisko...">
                </div>

                <!-- Filtrowanie po statusie -->
                <div class="col-md-3">
                    <label for="statusFilter" class="form-label small fw-semibold mb-1">
                        Status
                    </label>
                    <select id="statusFilter" 
                            wire:model.live="statusFilter" 
                            class="form-select form-select-sm">
                        <option value="">Wszystkie</option>
                        <option value="draft">Szkic</option>
                        <option value="issued">Wystawiony</option>
                        <option value="approved">Zatwierdzony</option>
                        <option value="paid">Wypłacony</option>
                    </select>
                </div>

                <!-- Filtrowanie po walucie -->
                <div class="col-md-2">
                    <label for="currencyFilter" class="form-label small fw-semibold mb-1">
                        Waluta
                    </label>
                    <select id="currencyFilter" 
                            wire:model.live="currencyFilter" 
                            class="form-select form-select-sm">
                        <option value="">Wszystkie</option>
                        <option value="PLN">PLN</option>
                        <option value="EUR">EUR</option>
                        <option value="USD">USD</option>
                    </select>
                </div>
            </div>
        </div>

        @if($payrolls->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <x-livewire.sortable-header field="employee_id" :sortField="$sortField" :sortDirection="$sortDirection">
                                Payroll
                            </x-livewire.sortable-header>
                            <x-livewire.sortable-header field="hours_amount" :sortField="$sortField" :sortDirection="$sortDirection">
                                Kwota z godzin
                            </x-livewire.sortable-header>
                            <x-livewire.sortable-header field="adjustments_amount" :sortField="$sortField" :sortDirection="$sortDirection">
                                Korekty
                            </x-livewire.sortable-header>
                            <x-livewire.sortable-header field="total_amount" :sortField="$sortField" :sortDirection="$sortDirection">
                                Razem
                            </x-livewire.sortable-header>
                            <th class="text-start">Waluta</th>
                            <th class="text-start">Status</th>
                            <th class="text-start">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payrolls as $payroll)
                            <tr wire:key="payroll-{{ $payroll->id }}">
                                <td>
                                    <a href="{{ route('payrolls.show', $payroll) }}" 
                                       class="text-primary text-decoration-none fw-medium">
                                        {{ $payroll->display_name }}
                                    </a>
                                </td>
                                <td>
                                    <strong>{{ number_format($payroll->hours_amount, 2, ',', ' ') }}</strong>
                                </td>
                                <td>
                                    {{ number_format($payroll->adjustments_amount, 2, ',', ' ') }}
                                </td>
                                <td>
                                    <strong class="text-success">{{ number_format($payroll->total_amount, 2, ',', ' ') }}</strong>
                                </td>
                                <td>
                                    <x-ui.badge variant="secondary">{{ $payroll->currency }}</x-ui.badge>
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
                :message="!empty($search) || !empty($statusFilter) || !empty($currencyFilter) ? 'Nie znaleziono payrolli spełniających kryteria wyszukiwania.' : 'Brak payrolli w systemie.'"
                :has-filters="!empty($search) || !empty($statusFilter) || !empty($currencyFilter)"
                clear-filters-action="wire:clearFilters"
            >
                @if(empty($search) && empty($statusFilter) && empty($currencyFilter))
                    <x-ui.button variant="primary" href="{{ route('payrolls.create') }}">
                        <i class="bi bi-plus-circle"></i> Wygeneruj pierwszy payroll
                    </x-ui.button>
                @endif
            </x-ui.empty-state>
        @endif
    </x-ui.card>
</div>
