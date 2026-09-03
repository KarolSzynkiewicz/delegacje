<div>
    <x-data-table :paginator="$users" :has-filters="(bool) ($search || $roleFilter)">
        <x-slot:filters>
            <x-data-table-filters :count="$users->total()">
                <x-data-table-search
                    wire:model.live.debounce.500ms="search"
                    placeholder="Nazwa, e-mail lub rola..."
                />
                <select wire:model.live="roleFilter" class="form-select form-select-sm">
                    <option value="">Rola: wszystkie</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </x-data-table-filters>
        </x-slot:filters>

        <x-slot:activeFilters>
            @if($search)
                <x-data-table-filter-chip label="Szukaj: {{ $search }}" wire:click="$set('search', '')" />
            @endif
            @if($roleFilter)
                <x-data-table-filter-chip label="Rola: {{ $roleFilter }}" wire:click="$set('roleFilter', '')" />
            @endif
        </x-slot:activeFilters>

        <x-slot:head>
            <tr>
                <x-livewire.sortable-header field="name" :sortField="$sortField" :sortDirection="$sortDirection">Nazwa</x-livewire.sortable-header>
                <x-livewire.sortable-header field="email" :sortField="$sortField" :sortDirection="$sortDirection">Email</x-livewire.sortable-header>
                <th class="text-start">Role</th>
                <th class="text-start">Kierownik</th>
                <th class="text-end">Akcje</th>
            </tr>
        </x-slot:head>
        <x-slot:body>
            @foreach ($users as $user)
                @include('livewire.partials.users-row', ['user' => $user])
            @endforeach
        </x-slot:body>
        <x-slot:cards>
            @foreach ($users as $user)
                @include('livewire.partials.users-row-card', ['user' => $user])
            @endforeach
        </x-slot:cards>
        <x-slot:empty>
            <x-ui.empty-state
                icon="inbox"
                :message="$search || $roleFilter ? 'Brak użytkowników spełniających kryteria wyszukiwania' : 'Brak użytkowników w systemie.'"
                :has-filters="(bool) ($search || $roleFilter)"
                clear-filters-action="wire:clearFilters"
            >
                @if(! $search && ! $roleFilter)
                    <x-ui.button
                        variant="primary"
                        href="{{ route('users.create') }}"
                        routeName="users.create"
                        action="create"
                    >
                        Dodaj pierwszego użytkownika
                    </x-ui.button>
                @endif
            </x-ui.empty-state>
        </x-slot:empty>
    </x-data-table>
</div>
