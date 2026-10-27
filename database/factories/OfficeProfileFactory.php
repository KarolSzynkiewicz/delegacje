<?php

namespace Database\Factories;

use App\Enums\OfficeContractType;
use App\Enums\OfficePayMode;
use App\Models\OfficeProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficeProfile>
 */
class OfficeProfileFactory extends Factory
{
    protected $model = OfficeProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'contract_type' => fake()->randomElement(OfficeContractType::cases())->value,
            'pay_mode' => OfficePayMode::Hourly->value,
            'fte' => 1,
            'hourly_rate' => fake()->randomFloat(2, 40, 120),
            'currency' => 'PLN',
            'started_on' => now()->subMonths(3)->toDateString(),
            'ended_on' => null,
            'is_active' => true,
            'notes' => null,
        ];
    }
}
