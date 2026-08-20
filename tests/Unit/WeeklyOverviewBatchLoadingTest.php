<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\ProjectDemand;
use App\Models\Role;
use App\Services\WeeklyOverviewService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WeeklyOverviewBatchLoadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_projects_with_weekly_data_query_count_does_not_scale_with_projects(): void
    {
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();
        $role = Role::factory()->create();

        foreach (range(1, 5) as $i) {
            $project = Project::factory()->create([
                'start_date' => $weekStart->copy()->subWeek(),
                'end_date' => $weekEnd->copy()->addWeek(),
            ]);

            ProjectDemand::factory()->create([
                'project_id' => $project->id,
                'role_id' => $role->id,
                'required_count' => 1,
                'start_date' => $weekStart,
                'end_date' => $weekEnd,
            ]);

            ProjectAssignment::factory()->create([
                'project_id' => $project->id,
                'role_id' => $role->id,
                'start_date' => $weekStart,
                'end_date' => $weekEnd,
            ]);
        }

        $service = app(WeeklyOverviewService::class);
        $weeks = $service->getWeeks($weekStart);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $result = $service->getProjectsWithWeeklyData($weeks);

        $queryCount = count(DB::getQueryLog());

        $this->assertCount(5, $result);
        // Batched path: fixed set of queries (~30), not ~27 × N projects.
        $this->assertLessThan(
            50,
            $queryCount,
            "Expected batched week load under 50 queries, got {$queryCount}"
        );
    }

    public function test_accommodation_card_data_does_not_query_per_home_or_assignment(): void
    {
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();
        $role = Role::factory()->create();
        $project = Project::factory()->create([
            'start_date' => $weekStart->copy()->subWeek(),
            'end_date' => $weekEnd->copy()->addWeek(),
        ]);

        foreach (range(1, 5) as $i) {
            $employee = \App\Models\Employee::factory()->create();
            $accommodation = \App\Models\Accommodation::factory()->create(['capacity' => 4]);
            \App\Models\AccommodationLease::query()->create([
                'accommodation_id' => $accommodation->id,
                'type' => 'wynajmowany',
                'start_date' => $weekStart->copy()->subMonth()->toDateString(),
                'end_date' => $weekEnd->copy()->addMonth()->toDateString(),
                'monthly_rent' => 400,
                'currency' => 'EUR',
            ]);
            \App\Models\AccommodationAssignment::factory()->create([
                'employee_id' => $employee->id,
                'accommodation_id' => $accommodation->id,
                'start_date' => $weekStart,
                'end_date' => $weekEnd,
            ]);
            ProjectAssignment::factory()->create([
                'project_id' => $project->id,
                'employee_id' => $employee->id,
                'role_id' => $role->id,
                'start_date' => $weekStart,
                'end_date' => $weekEnd,
            ]);
        }

        $service = app(WeeklyOverviewService::class);
        $weeks = $service->getWeeks($weekStart);
        $result = $service->getProjectsWithWeeklyData($weeks);

        DB::flushQueryLog();
        DB::enableQueryLog();

        foreach ($result as $projectData) {
            foreach ($projectData['weeks_data'][0]['accommodations'] as $accommodationData) {
                $accommodation = $accommodationData['accommodation'];
                $this->assertNotNull($accommodation->type);
                $this->assertTrue(
                    $accommodation->lease_end_date === null || $accommodation->lease_end_date instanceof Carbon
                );
                foreach ($accommodationData['assignments'] as $assignment) {
                    $this->assertNotEmpty($assignment->employee->full_name);
                }
            }
        }

        $this->assertSame(
            0,
            count(DB::getQueryLog()),
            'Accommodation type, lease dates and assignment employees should already be eager-loaded'
        );
    }
}
