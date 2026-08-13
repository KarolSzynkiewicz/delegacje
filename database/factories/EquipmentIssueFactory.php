<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Equipment;
use App\Models\EquipmentIssue;
use App\Models\EquipmentVariant;
use App\Models\User;
use App\Services\WarehouseService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentIssue>
 */
class EquipmentIssueFactory extends Factory
{
    protected $model = EquipmentIssue::class;

    public function definition(): array
    {
        return [
            'equipment_id' => Equipment::factory(),
            'equipment_variant_id' => function (array $attributes) {
                return EquipmentVariant::factory()->create([
                    'equipment_id' => $attributes['equipment_id'],
                ]);
            },
            'warehouse_id' => fn () => app(WarehouseService::class)->default()->id,
            'employee_id' => Employee::factory(),
            'project_assignment_id' => null,
            'quantity_issued' => 1,
            'issue_date' => now()->toDateString(),
            'expected_return_date' => null,
            'actual_return_date' => null,
            'status' => 'issued',
            'notes' => null,
            'batch_id' => null,
            'issued_by' => User::factory(),
            'returned_by' => null,
        ];
    }
}
