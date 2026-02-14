<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectDemand>
 */
class ProjectDemandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 year', 'now');
        $endDate = fake()->dateTimeBetween($startDate, '+1 year');

        return [
            'project_id' => Project::factory(),
            'role_id' => Role::factory(),
            'required_count' => fake()->numberBetween(1, 10),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
