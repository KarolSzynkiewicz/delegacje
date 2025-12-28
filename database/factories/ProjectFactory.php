<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'name' => fake()->company(),
            'description' => fake()->sentence(),
            'status' => 'active',
            'client_name' => fake()->company(),
            'budget' => fake()->randomFloat(2, 1000, 100000),
        ];
    }
}
