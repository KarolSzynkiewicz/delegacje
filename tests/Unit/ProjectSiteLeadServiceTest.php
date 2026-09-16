<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectSiteLead;
use App\Services\ProjectSiteLeadService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProjectSiteLeadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_site_lead_relation_picks_today(): void
    {
        Carbon::setTestNow('2026-04-15');

        $project = Project::factory()->create();
        $past = Employee::factory()->create();
        $current = Employee::factory()->create();
        $future = Employee::factory()->create();

        ProjectSiteLead::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $past->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
        ]);
        ProjectSiteLead::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $current->id,
            'start_date' => '2026-04-01',
            'end_date' => '2026-06-30',
        ]);
        ProjectSiteLead::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $future->id,
            'start_date' => '2026-07-01',
            'end_date' => null,
        ]);

        $this->assertTrue($project->currentSiteLead->employee->is($current));

        Carbon::setTestNow();
    }

    public function test_weekly_overview_includes_site_lead_for_the_week(): void
    {
        $weekStart = Carbon::parse('2026-04-13')->startOfWeek();
        $project = Project::factory()->create([
            'start_date' => $weekStart->copy()->subWeek(),
            'end_date' => $weekStart->copy()->addWeeks(2),
        ]);
        $employee = Employee::factory()->create();

        ProjectSiteLead::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $employee->id,
            'start_date' => $weekStart,
            'end_date' => $weekStart->copy()->endOfWeek(),
        ]);

        $service = app(\App\Services\WeeklyOverviewService::class);
        $weeks = $service->getWeeks($weekStart);
        $result = $service->getProjectsWithWeeklyData($weeks);

        $card = collect($result)->firstWhere(fn ($row) => $row['project']->id === $project->id);
        $this->assertNotNull($card);
        $this->assertCount(1, $card['weeks_data'][0]['site_leads']);
        $this->assertTrue($card['weeks_data'][0]['site_leads']->first()->employee->is($employee));
    }

    public function test_assign_rejects_when_new_period_starts_before_existing_lead(): void
    {
        $project = Project::factory()->create();
        $employee = Employee::factory()->create();

        app(ProjectSiteLeadService::class)->assign(
            $project,
            $employee,
            Carbon::parse('2026-06-01')
        );

        $this->expectException(ValidationException::class);

        app(ProjectSiteLeadService::class)->assign(
            $project,
            Employee::factory()->create(),
            Carbon::parse('2026-03-01')
        );
    }
}
