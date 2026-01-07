<?php

namespace Database\Factories;

use App\Models\TimeLog;
use App\Models\ProjectAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeLog>
 */
class TimeLogFactory extends Factory
{
    protected $model = TimeLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $workDate = $this->faker->dateTimeBetween('-30 days', 'now');
        $startTime = Carbon::parse($workDate)->setTime(8, 0);
        $hoursWorked = $this->faker->randomFloat(1, 4, 12);
        $endTime = $startTime->copy()->addHours($hoursWorked);

        return [
            'project_assignment_id' => ProjectAssignment::factory(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'hours_worked' => $hoursWorked,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
