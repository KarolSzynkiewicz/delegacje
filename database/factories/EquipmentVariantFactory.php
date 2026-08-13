<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\EquipmentStock;
use App\Models\EquipmentVariant;
use App\Services\WarehouseService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentVariant>
 */
class EquipmentVariantFactory extends Factory
{
    protected $model = EquipmentVariant::class;

    public function definition(): array
    {
        return [
            'equipment_id' => Equipment::factory(),
            'value' => fake()->randomElement(['S', 'M', 'L', 'XL']),
            'sort_order' => 0,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (EquipmentVariant $variant) {
            if ($variant->stocks()->exists()) {
                return;
            }

            EquipmentStock::query()->create([
                'warehouse_id' => app(WarehouseService::class)->default()->id,
                'equipment_variant_id' => $variant->id,
                'quantity_in_stock' => 10,
                'min_quantity' => 2,
            ]);
        });
    }

    public function inStock(int $quantity, int $min = 2, $warehouse = null): static
    {
        return $this->afterCreating(function (EquipmentVariant $variant) use ($quantity, $min, $warehouse) {
            EquipmentStock::query()->updateOrCreate(
                [
                    'warehouse_id' => ($warehouse ?? app(WarehouseService::class)->default())->id,
                    'equipment_variant_id' => $variant->id,
                ],
                [
                    'quantity_in_stock' => $quantity,
                    'min_quantity' => $min,
                ]
            );
        });
    }

    public function unnamed(): static
    {
        return $this->state(fn () => [
            'value' => null,
        ]);
    }
}
