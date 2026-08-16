<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Equipment;
use App\Models\EquipmentStockMovement;
use App\Models\EquipmentVariant;
use App\Services\WarehouseService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentStockMovement>
 */
class EquipmentStockMovementFactory extends Factory
{
    protected $model = EquipmentStockMovement::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => fn () => app(WarehouseService::class)->default()->id,
            'equipment_id' => Equipment::factory(),
            'equipment_variant_id' => function (array $attributes) {
                return EquipmentVariant::factory()->create([
                    'equipment_id' => $attributes['equipment_id'],
                ]);
            },
            'type' => StockMovementType::CONSUMPTION,
            'reason' => null,
            'quantity' => 1,
            'employee_id' => null,
            'notes' => null,
            'batch_id' => null,
            'created_by' => null,
        ];
    }
}
