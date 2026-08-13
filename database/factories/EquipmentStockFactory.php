<?php

namespace Database\Factories;

use App\Models\EquipmentStock;
use App\Models\EquipmentVariant;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentStock>
 */
class EquipmentStockFactory extends Factory
{
    protected $model = EquipmentStock::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'equipment_variant_id' => EquipmentVariant::factory(),
            'quantity_in_stock' => 10,
            'min_quantity' => 2,
        ];
    }
}
