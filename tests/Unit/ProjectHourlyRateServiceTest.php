<?php

namespace Tests\Unit;

use App\Enums\ProjectType;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\TimeLog;
use App\Services\ProfitabilityService;
use App\Services\ProjectHourlyRateService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProjectHourlyRateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_rate_starts_on_the_chosen_day_and_leaves_earlier_hours_on_the_old_amount(): void
    {
        $project = Project::factory()->create([
            'type' => ProjectType::HOURLY,
            'hourly_rate' => 30,
            'currency' => 'EUR',
            'start_date' => '2026-01-01',
            'end_date' => null,
        ]);

        $rates = app(ProjectHourlyRateService::class);
        $rates->seedOpeningRate($project);
        $rates->addRateFrom($project, Carbon::parse('2026-10-01'), 45);

        $project->refresh();
        $periods = $project->hourlyRates()->reorder()->orderBy('start_date')->get();

        $this->assertCount(2, $periods);
        $this->assertSame('30.00', (string) $periods[0]->amount);
        $this->assertSame('2026-09-30', $periods[0]->end_date->toDateString());
        $this->assertSame('45.00', (string) $periods[1]->amount);
        $this->assertSame('2026-10-01', $periods[1]->start_date->toDateString());
        $this->assertNull($periods[1]->end_date);
        $this->assertSame('45.00', (string) $project->hourly_rate);

        $assignment = ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-31',
        ]);
        TimeLog::factory()->create([
            'project_assignment_id' => $assignment->id,
            'start_time' => '2026-09-15 08:00:00',
            'end_time' => '2026-09-15 18:00:00',
            'hours_worked' => 10,
        ]);
        TimeLog::factory()->create([
            'project_assignment_id' => $assignment->id,
            'start_time' => '2026-10-02 08:00:00',
            'end_time' => '2026-10-02 18:00:00',
            'hours_worked' => 10,
        ]);

        $profit = app(ProfitabilityService::class);
        $september = $profit->getProjectProfitabilityForMonth(
            $project->fresh(),
            Carbon::parse('2026-09-01'),
            Carbon::parse('2026-09-30')
        );
        $october = $profit->getProjectProfitabilityForMonth(
            $project->fresh(),
            Carbon::parse('2026-10-01'),
            Carbon::parse('2026-10-31')
        );

        $this->assertSame(300.0, $september['revenue']);
        $this->assertSame(450.0, $october['revenue']);
    }

    public function test_a_new_rate_cannot_start_on_or_before_the_open_period(): void
    {
        $project = Project::factory()->create([
            'type' => ProjectType::HOURLY,
            'hourly_rate' => 30,
            'currency' => 'EUR',
            'start_date' => '2026-01-01',
        ]);

        $rates = app(ProjectHourlyRateService::class);
        $rates->seedOpeningRate($project);

        $this->expectException(ValidationException::class);
        $rates->addRateFrom($project, Carbon::parse('2026-01-01'), 50);
    }
}
