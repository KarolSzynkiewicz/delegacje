<?php

namespace App\Livewire;

use App\Models\Location;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class EquipmentWarehouseManager extends Component
{
    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingWarehouseId = null;

    public ?int $deletingWarehouseId = null;

    public string $name = '';

    public bool $isDefault = false;

    public ?int $createLocationId = null;

    public ?string $createName = null;

    public ?int $targetWarehouseId = null;

    #[On('open-warehouse-create')]
    public function openCreateModalFromHeader(): void
    {
        $this->openCreateModal();
    }

    public function openCreateModal(): void
    {
        $this->resetErrorBag();
        $this->createLocationId = $this->locationsWithoutWarehouse()->first()?->id;
        $this->createName = null;
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
    }

    public function createWarehouse(WarehouseService $warehouseService): void
    {
        $this->validate([
            'createLocationId' => 'required|exists:locations,id|unique:warehouses,location_id',
            'createName' => 'nullable|string|max:255',
        ], [
            'createLocationId.required' => 'Wybierz lokalizację.',
            'createLocationId.unique' => 'Ta lokalizacja ma już magazyn.',
        ]);

        try {
            $created = $warehouseService->createForLocation(
                Location::query()->findOrFail($this->createLocationId),
                filled($this->createName) ? $this->createName : null,
            );
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());

            return;
        }

        $this->showCreateModal = false;
        session()->flash('success', "Dodano magazyn „{$created->name}”.");
        $this->redirect(route('equipment.tab.warehouses'), navigate: false);
    }

    public function openEditModal(int $warehouseId): void
    {
        $warehouse = Warehouse::query()->findOrFail($warehouseId);
        $this->resetErrorBag();
        $this->editingWarehouseId = $warehouse->id;
        $this->name = $warehouse->name;
        $this->isDefault = $warehouse->is_default;
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingWarehouseId = null;
    }

    public function updateWarehouse(WarehouseService $warehouseService): void
    {
        $warehouse = Warehouse::query()->findOrFail($this->editingWarehouseId);

        $this->validate([
            'name' => 'required|string|max:255',
        ], [
            'name.required' => 'Nazwa magazynu jest wymagana.',
        ]);

        try {
            $warehouseService->update($warehouse, $this->name, $this->isDefault);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());

            return;
        }

        $this->showEditModal = false;
        session()->flash('success', 'Zapisano zmiany magazynu.');
        $this->redirect(route('equipment.tab.warehouses'), navigate: false);
    }

    public function openDeleteModal(int $warehouseId): void
    {
        $warehouse = Warehouse::query()->findOrFail($warehouseId);
        $this->resetErrorBag();
        $this->deletingWarehouseId = $warehouse->id;
        $this->targetWarehouseId = $this->otherWarehouses($warehouse)->first()?->id;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingWarehouseId = null;
    }

    public function deleteWarehouse(WarehouseService $warehouseService): void
    {
        $warehouse = Warehouse::query()->findOrFail($this->deletingWarehouseId);

        $this->validate([
            'targetWarehouseId' => 'required|exists:warehouses,id|different:deletingWarehouseId',
        ], [
            'targetWarehouseId.required' => 'Wybierz magazyn, do którego przenieść asortyment i wydania.',
            'targetWarehouseId.different' => 'Wybierz inny magazyn docelowy.',
        ]);

        $target = Warehouse::query()->findOrFail($this->targetWarehouseId);
        $deletedName = $warehouse->name;

        try {
            $warehouseService->mergeAndDelete($warehouse, $target);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());

            return;
        }

        $this->showDeleteModal = false;
        session()->flash('success', "Usunięto magazyn „{$deletedName}”. Asortyment i wydania przeniesiono do „{$target->name}”.");
        $this->redirect(route('equipment.tab.warehouses'), navigate: false);
    }

    public function render(WarehouseService $warehouseService)
    {
        $warehouses = $warehouseService->all();
        $counts = $warehouseService->assortmentCounts($warehouses);
        $deletingWarehouse = $this->deletingWarehouseId
            ? $warehouses->firstWhere('id', $this->deletingWarehouseId)
            : null;

        $total = $warehouses->count();
        $paginator = new LengthAwarePaginator(
            $warehouses,
            $total,
            max($total, 1),
            1,
            ['path' => route('equipment.tab.warehouses')],
        );

        return view('livewire.equipment-warehouse-manager', [
            'warehouses' => $warehouses,
            'counts' => $counts,
            'paginator' => $paginator,
            'locationsWithoutWarehouse' => $this->locationsWithoutWarehouse(),
            'deletingWarehouse' => $deletingWarehouse,
            'deleteTargets' => $deletingWarehouse
                ? $this->otherWarehouses($deletingWarehouse)
                : collect(),
        ]);
    }

    /**
     * @return Collection<int, Location>
     */
    private function locationsWithoutWarehouse(): Collection
    {
        return app(WarehouseService::class)->locationsWithoutWarehouse();
    }

    /**
     * @return Collection<int, Warehouse>
     */
    private function otherWarehouses(Warehouse $warehouse): Collection
    {
        return Warehouse::query()
            ->whereKeyNot($warehouse->id)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }
}
