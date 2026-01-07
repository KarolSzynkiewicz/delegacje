<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Accommodation>
 */
class AccommodationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'capacity' => fake()->numberBetween(1, 10),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
