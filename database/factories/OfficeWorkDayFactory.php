<?php

namespace Database\Factories;

use App\Enums\OfficeWorkLocation;
use App\Models\OfficeProfile;
use App\Models\OfficeWorkDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficeWorkDay>
 */
class OfficeWorkDayFactory extends Factory
{
    protected $model = OfficeWorkDay::class;

    public function definition(): array
    {
        return [
            'office_profile_id' => OfficeProfile::factory(),
            'work_date' => now()->toDateString(),
            'starts_at' => '09:00:00',
            'ends_at' => '17:00:00',
            'hours' => 8,
            'location' => OfficeWorkLocation::Office->value,
            'notes' => null,
        ];
    }
}
