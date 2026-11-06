<?php

namespace Database\Factories;

use App\Enums\ClientContactChannel;
use App\Enums\ClientContactOutcome;
use App\Enums\ClientContactPurpose;
use App\Models\Client;
use App\Models\ClientContactAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientContactAttempt>
 */
class ClientContactAttemptFactory extends Factory
{
    protected $model = ClientContactAttempt::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'project_id' => null,
            'client_contact_id' => null,
            'user_id' => User::factory(),
            'channel' => fake()->randomElement(ClientContactChannel::cases()),
            'purpose' => fake()->randomElement(ClientContactPurpose::cases()),
            'outcome' => fake()->randomElement(ClientContactOutcome::cases()),
            'contacted_at' => now()->subDays(fake()->numberBetween(0, 14)),
            'next_action_at' => null,
            'summary' => fake()->optional()->sentence(),
        ];
    }
}
