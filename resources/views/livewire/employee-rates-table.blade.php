<div>
    <x-ui.card>
        @if(session('success'))
            <x-ui.alert variant="success" dismissible>
                {{ session('success') }}
            </x-ui.alert>
        @endif

        <!-- Filtry -->
        <div class="mb-4 pb-3 border-top border-bottom">
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

                <!-- Filtrowanie po walucie -->
                <div class="col-md-3">
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

                <!-- Przycisk wyczyść -->
                <div class="col-md-2">
                    <x-ui.button variant="ghost" wire:click="clearFilters" class="w-100 btn-sm">
                        <i class="bi bi-x-circle"></i> Wyczyść
                    </x-ui.button>
                </div>

                <!-- Informacja o liczbie wyników -->
                <div class="col-md-1 text-end">
                    @if($rates->total() > 0)
                        <small class="text-muted">
                            <strong>{{ $rates->total() }}</strong>
                        </small>
                    @endif
                </div>
            </div>
        </div>

        @if($rates->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <x-livewire.sortable-header field="employee_id" :sortField="$sortField" :sortDirection="$sortDirection">
                                Pracownik
                            </x-livewire.sortable-header>
                            <x-livewire.sortable-header field="start_date" :sortField="$sortField" :sortDirection="$sortDirection">
                                Od
                            </x-livewire.sortable-header>
                            <x-livewire.sortable-header field="end_date" :sortField="$sortField" :sortDirection="$sortDirection">
                                Do
                            </x-livewire.sortable-header>
                            <x-livewire.sortable-header field="amount" :sortField="$sortField" :sortDirection="$sortDirection">
                                Kwota
                            </x-livewire.sortable-header>
                            <x-livewire.sortable-header field="currency" :sortField="$sortField" :sortDirection="$sortDirection">
                                Waluta
                            </x-livewire.sortable-header>
                            <th class="text-start">Status</th>
                            <th class="text-start">Notatki</th>
                            <th class="text-start">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rates as $rate)
                            <tr wire:key="rate-{{ $rate->id }}">
                                <td>
                                    <a href="{{ route('employees.show', $rate->employee) }}" 
                                       class="text-primary text-decoration-none fw-medium">
                                        {{ $rate->employee->full_name }}
                                    </a>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $rate->start_date->format('Y-m-d') }}</small>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $rate->end_date ? $rate->end_date->format('Y-m-d') : '-' }}</small>
                                </td>
                                <td>
                                    <strong>{{ number_format($rate->amount, 2, ',', ' ') }}</strong>
                                </td>
                                <td>
                                    <x-ui.badge variant="secondary">{{ $rate->currency }}</x-ui.badge>
                                </td>
                                <td>
                                    @php
                                        if ($rate->isCurrentlyActive()) {
                                            $statusLabel = 'Aktywna';
                                            $badgeVariant = 'success';
                                        } elseif ($rate->isPast()) {
                                            $statusLabel = 'Zakończona';
                                            $badgeVariant = 'accent';
                                        } elseif ($rate->isScheduled()) {
                                            $statusLabel = 'Zaplanowana';
                                            $badgeVariant = 'info';
                                        } else {
                                            $statusLabel = 'Nieznany';
                                            $badgeVariant = 'accent';
                                        }
                                    @endphp
                                    <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $rate->notes ? Str::limit($rate->notes, 50) : '-' }}</small>
                                </td>
                                <td>
                                    <x-action-buttons
                                        viewRoute="{{ route('employee-rates.show', $rate) }}"
                                        editRoute="{{ route('employee-rates.edit', $rate) }}"
                                        deleteRoute="{{ route('employee-rates.destroy', $rate) }}"
                                        deleteMessage="Czy na pewno chcesz usunąć tę stawkę?"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($rates->hasPages())
                <div class="mt-3">
                    {{ $rates->links() }}
                </div>
            @endif
        @else
            <x-ui.empty-state 
                icon="inbox"
                :message="!empty($search) || !empty($currencyFilter) ? 'Nie znaleziono stawek spełniających kryteria wyszukiwania.' : 'Brak stawek w systemie.'"
                :has-filters="!empty($search) || !empty($currencyFilter)"
                clear-filters-action="wire:clearFilters"
            >
                @if(empty($search) && empty($currencyFilter))
                    <x-ui.button variant="primary" href="{{ route('employee-rates.create') }}">
                        <i class="bi bi-plus-circle"></i> Dodaj pierwszą stawkę
                    </x-ui.button>
                @endif
            </x-ui.empty-state>
        @endif
    </x-ui.card>
</div>
