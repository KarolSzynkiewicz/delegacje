<?php

namespace App\Services;

use App\Enums\LocationPurposeType;
use App\Models\EquipmentIssue;
use App\Models\EquipmentStock;
use App\Models\EquipmentStockMovement;
use App\Models\Location;
use App\Models\Warehouse;
use App\Models\WarehouseDispatch;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WarehouseService
{
    public const SESSION_KEY = 'equipment.warehouse_id';

    /**
     * @return Collection<int, Warehouse>
     */
    public function all(): Collection
    {
        return Warehouse::query()
            ->with('location')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    /**
     * Ile typów asortymentu ma stan > 0 w danym magazynie.
     *
     * @param  Collection<int, Warehouse>  $warehouses
     * @return Collection<int, int>
     */
    public function assortmentCounts(Collection $warehouses): Collection
    {
        if ($warehouses->isEmpty()) {
            return collect();
        }

        $counts = EquipmentStock::query()
            ->join('equipment_variants', 'equipment_variants.id', '=', 'equipment_stocks.equipment_variant_id')
            ->join('equipment', 'equipment.id', '=', 'equipment_variants.equipment_id')
            ->whereIn('equipment_stocks.warehouse_id', $warehouses->pluck('id'))
            ->where('equipment_stocks.quantity_in_stock', '>', 0)
            ->where('equipment.is_archived', false)
            ->whereNull('equipment.removed_at')
            ->groupBy('equipment_stocks.warehouse_id')
            ->selectRaw('equipment_stocks.warehouse_id, COUNT(DISTINCT equipment.id) as aggregate')
            ->pluck('aggregate', 'warehouse_id');

        return $warehouses->mapWithKeys(
            fn (Warehouse $warehouse) => [$warehouse->id => (int) ($counts[$warehouse->id] ?? 0)]
        );
    }

    public function default(): Warehouse
    {
        $warehouse = Warehouse::query()->where('is_default', true)->orderBy('id')->first()
            ?? Warehouse::query()->orderBy('id')->first();

        if ($warehouse) {
            return $warehouse;
        }

        return DB::transaction(function () {
            $location = Location::query()->where('is_base', true)->first()
                ?? Location::query()->orderBy('id')->first();

            $created = Warehouse::query()->create([
                'location_id' => $location?->id,
                'name' => $location?->name ?? 'Siedziba',
                'is_default' => true,
            ]);

            $this->attachWarehousePurpose($location);

            return $created;
        });
    }

    public function current(?Request $request = null): Warehouse
    {
        $request ??= request();
        $requestedId = $request->query('warehouse_id');

        if ($request->hasSession()) {
            $requestedId ??= $request->session()->get(self::SESSION_KEY);
        }

        if ($requestedId) {
            $warehouse = Warehouse::query()->with('location')->find($requestedId);
            if ($warehouse) {
                $this->remember($warehouse, $request);

                return $warehouse;
            }
        }

        $warehouse = $this->default()->loadMissing('location');
        $this->remember($warehouse, $request);

        return $warehouse;
    }

    public function remember(Warehouse $warehouse, ?Request $request = null): void
    {
        $request ??= request();

        if (! $request->hasSession()) {
            return;
        }

        $request->session()->put(self::SESSION_KEY, $warehouse->id);
    }

    public function createForLocation(Location $location, ?string $name = null): Warehouse
    {
        if (Warehouse::query()->where('location_id', $location->id)->exists()) {
            throw ValidationException::withMessages([
                'location_id' => 'Ta lokalizacja ma już magazyn.',
            ]);
        }

        return DB::transaction(function () use ($location, $name) {
            $isFirst = ! Warehouse::query()->exists();

            $warehouse = Warehouse::query()->create([
                'location_id' => $location->id,
                'name' => filled($name) ? trim($name) : $location->name,
                'is_default' => $isFirst,
            ]);

            $this->attachWarehousePurpose($location);

            return $warehouse;
        });
    }

    public function update(Warehouse $warehouse, string $name, bool $isDefault = false): Warehouse
    {
        $name = trim($name);
        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => 'Nazwa magazynu jest wymagana.',
            ]);
        }

        return DB::transaction(function () use ($warehouse, $name, $isDefault) {
            if ($isDefault && ! $warehouse->is_default) {
                Warehouse::query()->where('is_default', true)->update(['is_default' => false]);
                $warehouse->is_default = true;
            }

            if (! $isDefault && $warehouse->is_default) {
                throw ValidationException::withMessages([
                    'is_default' => 'Ustaw najpierw inny magazyn jako siedzibę, albo zostaw ten jako domyślny.',
                ]);
            }

            $warehouse->name = $name;
            $warehouse->save();
            $this->attachWarehousePurpose($warehouse->location);

            return $warehouse->fresh('location');
        });
    }

    public function delete(Warehouse $warehouse): void
    {
        if ($warehouse->is_default) {
            throw ValidationException::withMessages([
                'warehouse' => 'Nie można usunąć magazynu siedziby. Ustaw najpierw inny magazyn jako domyślny.',
            ]);
        }

        if (Warehouse::query()->count() <= 1) {
            throw ValidationException::withMessages([
                'warehouse' => 'Nie można usunąć jedynego magazynu.',
            ]);
        }

        if ($warehouse->issues()->exists()) {
            throw ValidationException::withMessages([
                'warehouse' => 'Nie można usunąć magazynu, który ma wydania.',
            ]);
        }

        if ($warehouse->movements()->exists()) {
            throw ValidationException::withMessages([
                'warehouse' => 'Nie można usunąć magazynu, który ma rozchody lub inne ruchy stanu.',
            ]);
        }

        $hasStock = $warehouse->stocks()->where('quantity_in_stock', '>', 0)->exists();
        if ($hasStock) {
            throw ValidationException::withMessages([
                'warehouse' => 'Nie można usunąć magazynu, w którym leży sprzęt. Najpierw zeruj stany albo przenieś je.',
            ]);
        }

        DB::transaction(function () use ($warehouse) {
            $location = $warehouse->location;
            $warehouse->stocks()->delete();
            $warehouse->delete();

            if ($location) {
                $location->purposes()
                    ->where('purpose', LocationPurposeType::WAREHOUSE->value)
                    ->delete();
            }
        });
    }

    public function mergeAndDelete(Warehouse $from, Warehouse $to): void
    {
        if ($from->id === $to->id) {
            throw ValidationException::withMessages([
                'target_warehouse_id' => 'Wybierz inny magazyn docelowy.',
            ]);
        }

        if ($from->is_default) {
            throw ValidationException::withMessages([
                'warehouse' => 'Nie można usunąć magazynu siedziby. Ustaw najpierw inny magazyn jako domyślny.',
            ]);
        }

        if (Warehouse::query()->count() <= 1) {
            throw ValidationException::withMessages([
                'warehouse' => 'Nie można usunąć jedynego magazynu.',
            ]);
        }

        DB::transaction(function () use ($from, $to) {
            $from = Warehouse::query()->whereKey($from->id)->lockForUpdate()->firstOrFail();
            $to = Warehouse::query()->whereKey($to->id)->lockForUpdate()->firstOrFail();

            foreach ($from->stocks()->lockForUpdate()->get() as $stock) {
                if ((int) $stock->quantity_in_stock < 1) {
                    continue;
                }

                $target = EquipmentStock::query()
                    ->where('warehouse_id', $to->id)
                    ->where('equipment_variant_id', $stock->equipment_variant_id)
                    ->lockForUpdate()
                    ->first();

                if ($target) {
                    $target->increment('quantity_in_stock', (int) $stock->quantity_in_stock);
                    $target->update([
                        'min_quantity' => max((int) $target->min_quantity, (int) $stock->min_quantity),
                    ]);
                } else {
                    EquipmentStock::query()->create([
                        'warehouse_id' => $to->id,
                        'equipment_variant_id' => $stock->equipment_variant_id,
                        'quantity_in_stock' => (int) $stock->quantity_in_stock,
                        'min_quantity' => (int) $stock->min_quantity,
                    ]);
                }
            }

            EquipmentIssue::query()
                ->where('warehouse_id', $from->id)
                ->update(['warehouse_id' => $to->id]);

            WarehouseDispatch::query()
                ->where('warehouse_id', $from->id)
                ->update(['warehouse_id' => $to->id]);

            EquipmentStockMovement::query()
                ->where('warehouse_id', $from->id)
                ->update(['warehouse_id' => $to->id]);

            $location = $from->location;
            $from->stocks()->delete();
            $from->delete();

            if ($location) {
                $location->purposes()
                    ->where('purpose', LocationPurposeType::WAREHOUSE->value)
                    ->delete();
            }
        });
    }

    private function attachWarehousePurpose(?Location $location): void
    {
        $location?->addPurposes([LocationPurposeType::WAREHOUSE]);
    }

    /**
     * @return Collection<int, Location>
     */
    public function locationsWithoutWarehouse(): Collection
    {
        return Location::query()
            ->whereDoesntHave('warehouse')
            ->orderBy('name')
            ->get();
    }
}
