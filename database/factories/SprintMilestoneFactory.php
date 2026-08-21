<?php

namespace Database\Factories;

use App\Models\Sprint;
use App\Models\SprintMilestone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SprintMilestone>
 */
class SprintMilestoneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sprint_id' => Sprint::factory(),
            'name' => fake()->sentence(3),
            'notes' => null,
            'due_date' => now()->addDays(fake()->numberBetween(1, 10))->toDateString(),
            'completed_at' => null,
            'position' => 0,
            'created_by' => null,
        ];
    }
}
