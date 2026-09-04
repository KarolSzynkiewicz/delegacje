<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmployeeBankAccount>
 */
class EmployeeBankAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'account_number' => fake()->numerify('##########################'),
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'notes' => null,
        ];
    }
}
