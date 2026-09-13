<?php

namespace Database\Factories;

use App\Models\Sprint;
use App\Models\SprintDodItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SprintDodItem>
 */
class SprintDodItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sprint_id' => Sprint::factory(),
            'name' => fake()->sentence(4),
            'completed_at' => null,
            'position' => 0,
            'created_by' => null,
        ];
    }
}
