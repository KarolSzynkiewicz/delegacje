<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            // Role są teraz w tabeli pivot employee_role, nie w employees
        ];
    }

    /**
     * Configure the model factory to attach roles.
     */
    public function configure(): static
    {
        return $this->afterCreating(function ($employee) {
            // Attach a random role if roles exist
            if (Role::count() > 0) {
                $employee->roles()->attach(Role::inRandomOrder()->first());
            }
        });
    }
}
