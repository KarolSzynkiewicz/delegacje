<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Accommodation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AccommodationAssignment>
 */
class AccommodationAssignmentFactory extends Factory
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
            'employee_id' => Employee::factory(),
            'accommodation_id' => Accommodation::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
