<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectAssignment>
 */
class ProjectAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 year', 'now');
        $endDate = fake()->optional()->dateTimeBetween($startDate, '+1 year');

        return [
            'project_id' => Project::factory(),
            'employee_id' => Employee::factory(),
            'role_id' => Role::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => fake()->randomElement(['pending', 'active', 'completed', 'cancelled']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
