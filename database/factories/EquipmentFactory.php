<?php

namespace Database\Factories;

use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipment>
 */
class EquipmentFactory extends Factory
{
    protected $model = Equipment::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => null,
            'category' => 'Ochrona',
            'variant_label' => 'Rozmiar',
            'unit_cost' => null,
            'currency' => 'PLN',
            'issuable' => true,
            'returnable' => true,
            'is_archived' => false,
            'removed_at' => null,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'is_archived' => true,
            'removed_at' => now(),
        ]);
    }

    public function withoutKinds(): static
    {
        return $this->state(fn () => [
            'variant_label' => null,
        ]);
    }

    public function notIssuable(): static
    {
        return $this->state(fn () => [
            'issuable' => false,
            'returnable' => false,
        ]);
    }

    public function notReturnable(): static
    {
        return $this->state(fn () => [
            'issuable' => true,
            'returnable' => false,
        ]);
    }
}
