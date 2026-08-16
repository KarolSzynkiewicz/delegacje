<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WarehouseDispatch;
use App\Services\WarehouseService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WarehouseDispatch>
 */
class WarehouseDispatchFactory extends Factory
{
    protected $model = WarehouseDispatch::class;

    public function definition(): array
    {
        $year = (int) now()->year;
        $sequence = fake()->unique()->numberBetween(1, 99999);

        return [
            'number' => sprintf('WZ-%d-%04d', $year, $sequence),
            'year' => $year,
            'sequence' => $sequence,
            'warehouse_id' => fn () => app(WarehouseService::class)->default()->id,
            'issue_date' => now()->toDateString(),
            'notes' => null,
            'status' => WarehouseDispatch::STATUS_ISSUED,
            'issued_at' => now(),
            'created_by' => User::factory(),
            'issued_by' => fn (array $attributes) => $attributes['created_by'] ?? User::factory(),
        ];
    }
}
