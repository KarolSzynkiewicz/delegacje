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
        $dateFrom = fake()->dateTimeBetween('-1 year', 'now');
        $dateTo = fake()->dateTimeBetween($dateFrom, '+1 year');

        return [
            'project_id' => Project::factory(),
            'role_id' => Role::factory(),
            'required_count' => fake()->numberBetween(1, 10),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
