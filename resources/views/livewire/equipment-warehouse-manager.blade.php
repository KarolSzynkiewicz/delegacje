<div>
    <x-data-table :paginator="$paginator">
        <x-slot:filters>
            <x-data-table-filters :count="$warehouses->count()">
                <x-slot:note>
                    Przy usuwaniu asortyment i wydania przechodzą do wybranego magazynu.
                </x-slot:note>
                <x-slot:actions>
                    <x-ui.button variant="primary" type="button" wire:click="openCreateModal" class="btn-sm" action="create">
                        Dodaj magazyn
                    </x-ui.button>
                </x-slot:actions>
            </x-data-table-filters>
        </x-slot:filters>

        <x-slot:head>
            <tr>
                <th>Magazyn</th>
                <th class="d-none d-md-table-cell">Lokalizacja</th>
                <th class="text-end">Pozycje</th>
                <th class="text-end">Akcje</th>
            </tr>
        </x-slot:head>

        <x-slot:body>
            @foreach($warehouses as $warehouse)
                @include('livewire.partials.equipment-warehouse-row', [
                    'warehouse' => $warehouse,
                    'count' => (int) $counts->get($warehouse->id, 0),
                    'canDelete' => ! $warehouse->is_default && $warehouses->count() > 1,
                ])
            @endforeach
        </x-slot:body>

        <x-slot:cards>
            @foreach($warehouses as $warehouse)
                @include('livewire.partials.equipment-warehouse-row-card', [
                    'warehouse' => $warehouse,
                    'count' => (int) $counts->get($warehouse->id, 0),
                    'canDelete' => ! $warehouse->is_default && $warehouses->count() > 1,
                ])
            @endforeach
        </x-slot:cards>

        <x-slot:empty>
            <x-ui.empty-state
                icon="buildings"
                message="Brak magazynów"
            />
        </x-slot:empty>
    </x-data-table>

    @if($showCreateModal)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.65);" wire:click.self="closeCreateModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Dodaj magazyn</h5>
                        <button type="button" class="btn-close" wire:click="closeCreateModal"></button>
                    </div>
                    <div class="modal-body">
                        <x-ui.errors />
                        @if($locationsWithoutWarehouse->isEmpty())
                            <p class="text-muted mb-0">Wszystkie lokalizacje mają już magazyn. Dodaj najpierw nową lokalizację.</p>
                        @else
                            <div class="mb-3">
                                <label class="form-label" for="create-location">Lokalizacja</label>
                                <select id="create-location" class="form-select" wire:model="createLocationId">
                                    @foreach($locationsWithoutWarehouse as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                                @error('createLocationId') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-0">
                                <label class="form-label" for="create-name">Nazwa magazynu</label>
                                <input id="create-name" type="text" class="form-control" wire:model="createName" placeholder="Opcjonalnie — domyślnie nazwa lokalizacji">
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <x-ui.button variant="ghost" type="button" wire:click="closeCreateModal">Anuluj</x-ui.button>
                        @if($locationsWithoutWarehouse->isNotEmpty())
                            <x-ui.button variant="primary" type="button" wire:click="createWarehouse" action="save">Dodaj</x-ui.button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($showEditModal)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.65);" wire:click.self="closeEditModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edytuj magazyn</h5>
                        <button type="button" class="btn-close" wire:click="closeEditModal"></button>
                    </div>
                    <div class="modal-body">
                        <x-ui.errors />
                        <div class="mb-3">
                            <label class="form-label" for="edit-name">Nazwa magazynu</label>
                            <input id="edit-name" type="text" class="form-control" wire:model="name" required>
                            @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-check">
                            <input id="edit-default" type="checkbox" class="form-check-input" wire:model="isDefault">
                            <label class="form-check-label" for="edit-default">Magazyn siedziby (domyślny)</label>
                        </div>
                        @error('is_default') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="modal-footer">
                        <x-ui.button variant="ghost" type="button" wire:click="closeEditModal">Anuluj</x-ui.button>
                        <x-ui.button variant="primary" type="button" wire:click="updateWarehouse" action="save">Zapisz</x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($showDeleteModal && $deletingWarehouse)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.65);" wire:click.self="closeDeleteModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Usuń magazyn „{{ $deletingWarehouse->name }}”</h5>
                        <button type="button" class="btn-close" wire:click="closeDeleteModal"></button>
                    </div>
                    <div class="modal-body">
                        <x-ui.errors />
                        <p class="small text-muted">
                            Asortyment na stanie, wydania i obowiązki zwrotu powiązane z tym magazynem trafią do magazynu docelowego.
                        </p>
                        <div class="mb-0">
                            <label class="form-label" for="target-warehouse">Przenieś do magazynu</label>
                            <select id="target-warehouse" class="form-select" wire:model="targetWarehouseId">
                                @foreach($deleteTargets as $target)
                                    <option value="{{ $target->id }}">{{ $target->display_name }}</option>
                                @endforeach
                            </select>
                            @error('targetWarehouseId') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <x-ui.button variant="ghost" type="button" wire:click="closeDeleteModal">Anuluj</x-ui.button>
                        <x-ui.button variant="danger" type="button" wire:click="deleteWarehouse" action="delete">Usuń i przenieś</x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
