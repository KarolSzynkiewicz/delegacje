<div>
    <x-data-table :paginator="$userRoles" :has-filters="(bool) $search">
        <x-slot:filters>
            <x-data-table-filters :count="$userRoles->total()">
                <x-data-table-search
                    wire:model.live.debounce.500ms="search"
                    placeholder="Nazwa roli lub uprawnienie..."
                />
            </x-data-table-filters>
        </x-slot:filters>

        <x-slot:activeFilters>
            @if($search)
                <x-data-table-filter-chip label="Szukaj: {{ $search }}" wire:click="$set('search', '')" />
            @endif
        </x-slot:activeFilters>

        <x-slot:head>
            <tr>
                <x-livewire.sortable-header field="name" :sortField="$sortField" :sortDirection="$sortDirection">Nazwa</x-livewire.sortable-header>
                <th class="text-start">Uprawnienia</th>
                <th class="text-start">Użytkownicy</th>
                <th class="text-end">Akcje</th>
            </tr>
        </x-slot:head>
        <x-slot:body>
            @foreach ($userRoles as $userRole)
                @include('livewire.partials.user-roles-row', ['userRole' => $userRole, 'permissionCount' => $permissionCount])
            @endforeach
        </x-slot:body>
        <x-slot:cards>
            @foreach ($userRoles as $userRole)
                @include('livewire.partials.user-roles-row-card', ['userRole' => $userRole, 'permissionCount' => $permissionCount])
            @endforeach
        </x-slot:cards>
        <x-slot:empty>
            <x-ui.empty-state
                icon="inbox"
                :message="$search ? 'Brak ról spełniających kryteria wyszukiwania' : 'Brak ról w systemie.'"
                :has-filters="(bool) $search"
                clear-filters-action="wire:clearFilters"
            >
                @if(! $search)
                    <x-ui.button
                        variant="primary"
                        href="{{ route('user-roles.create') }}"
                        routeName="user-roles.create"
                        action="create"
                    >
                        Dodaj pierwszą rolę
                    </x-ui.button>
                @endif
            </x-ui.empty-state>
        </x-slot:empty>
    </x-data-table>
</div>
