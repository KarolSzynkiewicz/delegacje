<div>
    <div class="d-flex align-items-center gap-1 mb-3">
        <h3 class="fs-6 fw-semibold text-muted mb-0">
            Koszty Księgowe
        </h3>
        <x-tooltip title="Koszty księgowe to faktyczne wpisy kosztów ogólnofirmowych w systemie. Mogą być generowane automatycznie z szablonów (przy użyciu funkcji 'Generuj Koszty Ogólnofirmowe') lub dodawane ręcznie jako koszty niestandardowe. Każdy wpis zawiera kwotę, okres obowiązywania, datę księgowania i opcjonalnie powiązanie z szablonem. Te wpisy są używane do obliczania rzeczywistych kosztów w raportach zysków i strat." direction="bottom">
            <i class="bi bi-info-circle text-primary fs-6"></i>
        </x-tooltip>
    </div>

    <x-data-table-filters
        :count="$entries->total()"
        :has-filters="$hasFilters"
        item-label="kosztów księgowych"
    >
        <div class="dt-filter-field dt-filter-field--wide">
            <label for="fixed-cost-entries-search" class="form-label small">
                <i class="bi bi-search me-1"></i> Szukaj
            </label>
            <input
                type="text"
                id="fixed-cost-entries-search"
                wire:model.live.debounce.300ms="search"
                class="form-control"
                placeholder="Nazwa, kategoria, szablon, notatki…"
                autocomplete="off"
            >
        </div>
        <div class="dt-filter-field">
            <label for="fixed-cost-entries-category" class="form-label small">
                <i class="bi bi-tags me-1"></i> Kategoria
            </label>
            <select
                id="fixed-cost-entries-category"
                wire:model.live="categoryFilter"
                class="form-select"
            >
                <option value="">Wszystkie kategorie</option>
                @foreach($categoryOptions as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </x-data-table-filters>

    @if($entries->count() > 0)
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <x-livewire.sortable-header field="name" :sortField="$sortField" :sortDirection="$sortDirection">
                            Nazwa
                        </x-livewire.sortable-header>
                        <x-livewire.sortable-header field="category" :sortField="$sortField" :sortDirection="$sortDirection">
                            Kategoria
                        </x-livewire.sortable-header>
                        <x-livewire.sortable-header field="amount" :sortField="$sortField" :sortDirection="$sortDirection">
                            Kwota
                        </x-livewire.sortable-header>
                        <x-livewire.sortable-header field="period_start" :sortField="$sortField" :sortDirection="$sortDirection">
                            Okres
                        </x-livewire.sortable-header>
                        <x-livewire.sortable-header field="accounting_date" :sortField="$sortField" :sortDirection="$sortDirection">
                            Data księgowania
                        </x-livewire.sortable-header>
                        <th>Szablon</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entries as $entry)
                        <tr wire:key="fixed-cost-entry-{{ $entry->id }}">
                            <td>{{ $entry->name }}</td>
                            <td>
                                @if($entry->category)
                                    <x-ui.badge variant="secondary">
                                        {{ $categoryOptions[$entry->category] ?? $entry->category }}
                                    </x-ui.badge>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="fw-semibold">{{ number_format($entry->amount, 2) }} {{ $entry->currency }}</td>
                            <td>
                                {{ $entry->period_start->format('Y-m-d') }} - {{ $entry->period_end->format('Y-m-d') }}
                            </td>
                            <td>{{ $entry->accounting_date->format('Y-m-d') }}</td>
                            <td>
                                @if($entry->template)
                                    <a href="{{ route('fixed-costs.show', $entry->template) }}" class="text-decoration-none">
                                        {{ $entry->template->name }}
                                    </a>
                                @else
                                    <span class="text-muted">Brak szablonu</span>
                                @endif
                            </td>
                            <td>
                                <x-action-buttons
                                    viewRoute="{{ route('fixed-cost-entries.show', $entry) }}"
                                    deleteRoute="{{ route('fixed-cost-entries.destroy', $entry) }}"
                                    deleteMessage="Czy na pewno chcesz usunąć ten koszt księgowy?"
                                />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($entries->hasPages())
            <div class="mt-3">
                {{ $entries->links() }}
            </div>
        @endif
    @else
        <x-ui.empty-state
            icon="folder-x"
            :message="$hasFilters ? 'Brak kosztów księgowych spełniających kryteria wyszukiwania' : 'Brak wygenerowanych kosztów księgowych'"
            :has-filters="$hasFilters"
            clear-filters-action="wire:clearFilters"
        >
            @if(! $hasFilters)
                <x-ui.button
                    variant="primary"
                    href="{{ route('fixed-costs.generate') }}"
                    routeName="fixed-costs.generate"
                >
                    Generuj Koszty Ogólnofirmowe
                </x-ui.button>
            @endif
        </x-ui.empty-state>
    @endif
</div>
