<?php

namespace Database\Factories;

use App\Enums\ClientType;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'type' => ClientType::Client,
            'short_name' => fake()->optional()->companySuffix(),
            'nip' => null,
            'tax_id' => null,
            'address' => fake()->optional()->streetAddress(),
            'city' => fake()->optional()->city(),
            'postal_code' => fake()->optional()->postcode(),
            'country' => 'PL',
            'contact_person' => fake()->optional()->name(),
            'email' => fake()->optional()->companyEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'notes' => null,
            'is_active' => true,
        ];
    }

    public function serviceProvider(): static
    {
        return $this->state(fn () => [
            'type' => ClientType::ServiceProvider,
        ]);
    }
}
