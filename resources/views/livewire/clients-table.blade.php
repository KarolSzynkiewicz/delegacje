<div>
    @if(session('success'))
        <x-ui.alert variant="success" dismissible class="mb-3">{{ session('success') }}</x-ui.alert>
    @endif

    <x-data-table-filters
        :count="$clients->total()"
        :has-filters="(bool) (!empty($search) || $statusFilter !== 'active' || $typeFilter !== 'all' || $attentionFilter !== 'all')"
        item-label="kontrahentów"
    >
        <div class="dt-filter-field dt-filter-field--wide">
            <label for="search" class="form-label small">
                <i class="bi bi-search me-1"></i> Szukaj
            </label>
            <input type="text" id="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Nazwa, NIP, miasto, kontakt...">
        </div>
        <div class="dt-filter-field">
            <label for="typeFilter" class="form-label small">Typ</label>
            <select id="typeFilter" wire:model.live="typeFilter" class="form-select">
                <option value="all">Wszystkie</option>
                @foreach($typeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="dt-filter-field">
            <label for="statusFilter" class="form-label small">Status</label>
            <select id="statusFilter" wire:model.live="statusFilter" class="form-select">
                <option value="active">Aktywni</option>
                <option value="inactive">Nieaktywni</option>
                <option value="all">Wszyscy</option>
            </select>
        </div>
        <div class="dt-filter-field">
            <label for="attentionFilter" class="form-label small">Uwaga</label>
            <select id="attentionFilter" wire:model.live="attentionFilter" class="form-select">
                <option value="all">Wszyscy</option>
                <option value="followups">Do oddzwonienia</option>
            </select>
        </div>
    </x-data-table-filters>

    <x-ui.card>
        @if($clients->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <x-livewire.sortable-header field="name" :sortField="$sortField" :sortDirection="$sortDirection">Nazwa</x-livewire.sortable-header>
                            <th class="text-start">Typ</th>
                            <th class="text-start">Kontakt główny</th>
                            <th class="text-start">Ostatni kontakt</th>
                            <x-livewire.sortable-header field="overdue_followups_count" :sortField="$sortField" :sortDirection="$sortDirection" title="Zaległe i nadchodzące terminy oddzwonienia">Follow-upy</x-livewire.sortable-header>
                            <x-livewire.sortable-header field="projects_count" :sortField="$sortField" :sortDirection="$sortDirection" title="Liczba projektów przypisanych do kontrahenta">Projekty</x-livewire.sortable-header>
                            <th class="text-start">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clients as $client)
                            <tr wire:key="client-{{ $client->id }}">
                                <td>
                                    <a href="{{ route('clients.show', $client) }}" class="text-primary fw-semibold text-decoration-none">
                                        {{ $client->name }}
                                    </a>
                                    @if($client->short_name)
                                        <div class="small text-muted">{{ $client->short_name }}</div>
                                    @endif
                                </td>
                                <td>
                                    <x-ui.badge variant="{{ $client->type === \App\Enums\ClientType::ServiceProvider ? 'accent' : 'info' }}">
                                        {{ $client->type?->shortLabel() ?? 'Klient' }}
                                    </x-ui.badge>
                                </td>
                                <td>
                                    @if($client->primaryContact)
                                        {{ $client->primaryContact->name }}
                                        @if($client->primaryContact->phone)
                                            <div class="small text-muted font-mono">{{ $client->primaryContact->phone }}</div>
                                        @elseif($client->primaryContact->email)
                                            <div class="small text-muted">{{ $client->primaryContact->email }}</div>
                                        @endif
                                    @else
                                        {{ $client->contact_person ?? '—' }}
                                    @endif
                                </td>
                                <td class="font-mono small">
                                    @if($client->last_contact_at)
                                        {{ \Carbon\Carbon::parse($client->last_contact_at)->format('d.m.Y') }}
                                    @else
                                        <span class="text-muted">brak</span>
                                    @endif
                                </td>
                                <td>
                                    @if(($client->overdue_followups_count ?? 0) > 0)
                                        <span class="text-danger fw-semibold">
                                            {{ $client->overdue_followups_count }} zaległe
                                        </span>
                                        @if(($client->due_followups_count ?? 0) > ($client->overdue_followups_count ?? 0))
                                            <div class="small text-warning">
                                                {{ $client->due_followups_count }} w 3 dniach
                                            </div>
                                        @endif
                                    @elseif(($client->due_followups_count ?? 0) > 0)
                                        <span class="text-warning fw-semibold">
                                            {{ $client->due_followups_count }} w 3 dniach
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="font-mono text-muted">
                                    @if($client->canOwnProjects())
                                        {{ $client->projects_count }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <x-action-buttons
                                        viewRoute="{{ route('clients.show', $client) }}"
                                        editRoute="{{ route('clients.edit', $client) }}"
                                        deleteRoute="{{ route('clients.destroy', $client) }}"
                                        deleteMessage="Czy na pewno chcesz usunąć tego kontrahenta?"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($clients->hasPages())
                <div class="mt-3">{{ $clients->links() }}</div>
            @endif
        @else
            <x-ui.empty-state
                icon="building"
                :message="(!empty($search) || $statusFilter !== 'active' || $typeFilter !== 'all' || $attentionFilter !== 'all') ? 'Nie znaleziono kontrahentów spełniających kryteria.' : 'Brak kontrahentów w CRM.'"
                :has-filters="!empty($search) || $statusFilter !== 'active' || $typeFilter !== 'all' || $attentionFilter !== 'all'"
                clear-filters-action="wire:clearFilters"
            >
                @if(empty($search) && $statusFilter === 'active' && $typeFilter === 'all' && $attentionFilter === 'all')
                    <x-ui.button variant="primary" href="{{ route('clients.create') }}">
                        <i class="bi bi-plus-circle"></i> Dodaj pierwszego kontrahenta
                    </x-ui.button>
                @endif
            </x-ui.empty-state>
        @endif
    </x-ui.card>
</div>
