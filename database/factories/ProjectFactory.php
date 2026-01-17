<?php

namespace Database\Factories;

use App\Enums\ProjectType;
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
        $type = fake()->randomElement([ProjectType::HOURLY, ProjectType::CONTRACT]);
        
        $data = [
            'location_id' => Location::factory(),
            'name' => fake()->company(),
            'description' => fake()->sentence(),
            'status' => 'active',
            'type' => $type,
            'client_name' => fake()->company(),
            'budget' => fake()->randomFloat(2, 1000, 100000),
        ];

        if ($type === ProjectType::HOURLY) {
            $data['hourly_rate'] = fake()->randomFloat(2, 50, 500);
            $data['contract_amount'] = null;
            $data['currency'] = null;
        } else {
            $data['hourly_rate'] = null;
            $data['contract_amount'] = fake()->randomFloat(2, 10000, 1000000);
            $data['currency'] = fake()->randomElement(['PLN', 'EUR', 'USD', 'GBP']);
        }

        return $data;
    }
}
