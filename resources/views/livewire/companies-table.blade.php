<div>
    @if(session('success'))
        <x-ui.alert variant="success" dismissible class="mb-3">{{ session('success') }}</x-ui.alert>
    @endif

    <x-data-table-filters
        :count="$companies->total()"
        :has-filters="(bool) !empty($search)"
        item-label="spółek"
    >
        <div class="dt-filter-field dt-filter-field--wide">
            <label for="search" class="form-label small">
                <i class="bi bi-search me-1"></i> Szukaj
            </label>
            <input type="text" id="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Nazwa, NIP, miasto, prezes...">
        </div>
    </x-data-table-filters>

    <x-ui.card>
        @if($companies->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <x-livewire.sortable-header field="name" :sortField="$sortField" :sortDirection="$sortDirection">Nazwa</x-livewire.sortable-header>
                            <x-livewire.sortable-header field="nip" :sortField="$sortField" :sortDirection="$sortDirection">NIP</x-livewire.sortable-header>
                            <th class="text-start">Miasto</th>
                            <th class="text-start">Prezes</th>
                            <x-livewire.sortable-header field="founded_at" :sortField="$sortField" :sortDirection="$sortDirection">Założona</x-livewire.sortable-header>
                            <th class="text-start">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($companies as $company)
                            <tr wire:key="company-{{ $company->id }}">
                                <td>
                                    <a href="{{ route('companies.show', $company) }}" class="text-primary fw-semibold text-decoration-none">
                                        {{ $company->name }}
                                    </a>
                                </td>
                                <td><small class="text-muted">{{ $company->nip }}</small></td>
                                <td>{{ $company->city ?? '-' }}</td>
                                <td>{{ $company->president_name ?? '-' }}</td>
                                <td><small class="text-muted">{{ $company->founded_at ? $company->founded_at->format('Y-m-d') : '-' }}</small></td>
                                <td>
                                    <x-action-buttons
                                        viewRoute="{{ route('companies.show', $company) }}"
                                        editRoute="{{ route('companies.edit', $company) }}"
                                        deleteRoute="{{ route('companies.destroy', $company) }}"
                                        deleteMessage="Czy na pewno chcesz usunąć tę spółkę?"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($companies->hasPages())
                <div class="mt-3">{{ $companies->links() }}</div>
            @endif
        @else
            <x-ui.empty-state
                icon="building"
                :message="!empty($search) ? 'Nie znaleziono spółek spełniających kryteria.' : 'Brak spółek w systemie.'"
                :has-filters="!empty($search)"
                clear-filters-action="wire:clearFilters"
            >
                @if(empty($search))
                    <x-ui.button variant="primary" href="{{ route('companies.create') }}">
                        <i class="bi bi-plus-circle"></i> Dodaj pierwszą spółkę
                    </x-ui.button>
                @endif
            </x-ui.empty-state>
        @endif
    </x-ui.card>
</div>
