<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmployeeDocument>
 */
class EmployeeDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $kind = fake()->randomElement(['okresowy', 'bezokresowy']);
        $validFrom = fake()->dateTimeBetween('-1 year', 'now');
        $validTo = $kind === 'okresowy' ? fake()->dateTimeBetween($validFrom, '+1 year') : null;

        return [
            'employee_id' => Employee::factory(),
            'document_id' => Document::factory(),
            'valid_from' => $validFrom,
            'valid_to' => $validTo,
            'kind' => $kind,
            'notes' => fake()->optional()->sentence(),
            'file_path' => null,
        ];
    }
}
