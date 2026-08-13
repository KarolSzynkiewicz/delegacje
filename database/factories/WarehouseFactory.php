<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'name' => fake()->unique()->city().' magazyn',
            'is_default' => false,
        ];
    }

    public function defaultWarehouse(): static
    {
        return $this->state(fn () => [
            'is_default' => true,
            'name' => 'Siedziba',
        ]);
    }
}
