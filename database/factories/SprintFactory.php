<?php

namespace Database\Factories;

use App\Models\Sprint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sprint>
 */
class SprintFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->startOfWeek();

        return [
            'name' => 'Sprint '.fake()->unique()->numberBetween(1, 9999),
            'goal' => fake()->sentence(),
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays(13)->toDateString(),
            'closed_at' => null,
            'parked_at' => null,
            'created_by' => null,
        ];
    }
}
