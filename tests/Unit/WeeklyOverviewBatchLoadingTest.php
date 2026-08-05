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
}
