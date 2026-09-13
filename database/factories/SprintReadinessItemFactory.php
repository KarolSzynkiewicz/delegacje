<?php

namespace Database\Factories;

use App\Models\Sprint;
use App\Models\SprintReadinessItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SprintReadinessItem>
 */
class SprintReadinessItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sprint_id' => Sprint::factory(),
            'name' => fake()->sentence(3),
            'completed_at' => null,
            'position' => 0,
            'created_by' => null,
        ];
    }
}
