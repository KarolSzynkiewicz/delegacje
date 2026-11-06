<?php

namespace Database\Factories;

use App\Models\OfficeLeaveAdjustment;
use App\Models\OfficeProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficeLeaveAdjustment>
 */
class OfficeLeaveAdjustmentFactory extends Factory
{
    protected $model = OfficeLeaveAdjustment::class;

    public function definition(): array
    {
        return [
            'office_profile_id' => OfficeProfile::factory(),
            'year' => (int) now()->year,
            'days' => fake()->randomElement([1, 2, 3, -1, -2]),
            'reason' => fake()->sentence(4),
            'effective_on' => now()->toDateString(),
            'created_by' => User::factory(),
        ];
    }
}
