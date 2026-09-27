<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectDemand;
use App\Models\Role;
use App\Models\Rotation;
use App\Services\WeeklyDashboardKpiService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeeklyDashboardKpiServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_total_demand_sums_slots_across_projects_for_the_week(): void
    {
        $weekStart = Carbon::parse('2026-09-21')->startOfDay();
        $weekEnd = $weekStart->copy()->endOfWeek();
        $role = Role::factory()->create();

        $first = Project::factory()->create();
        $second = Project::factory()->create();
        $outside = Project::factory()->create();

        ProjectDemand::factory()->create([
            'project_id' => $first->id,
            'role_id' => $role->id,
            'required_count' => 40,
            'start_date' => $weekStart,
            'end_date' => $weekEnd,
        ]);
        ProjectDemand::factory()->create([
            'project_id' => $second->id,
            'role_id' => $role->id,
            'required_count' => 25,
            'start_date' => $weekStart->copy()->subWeek(),
            'end_date' => $weekEnd->copy()->addDay(),
        ]);
        ProjectDemand::factory()->create([
            'project_id' => $outside->id,
            'role_id' => $role->id,
            'required_count' => 9,
            'start_date' => $weekEnd->copy()->addWeek(),
            'end_date' => $weekEnd->copy()->addWeeks(2),
        ]);

        $service = app(WeeklyDashboardKpiService::class);

        $this->assertSame(65, $service->totalDemandForWeek($weekStart, $weekEnd));

        $byProject = $service->employeesInFieldByProjectForWeek($weekStart, $weekEnd);
        $this->assertCount(2, $byProject);
        $this->assertSame(0, (int) $byProject->firstWhere('project_id', $first->id)->employee_count);
        $this->assertSame(40, (int) $byProject->firstWhere('project_id', $first->id)->needed_count);
        $this->assertSame(25, (int) $byProject->firstWhere('project_id', $second->id)->needed_count);
    }

    public function test_rotations_ending_include_the_wednesday_after_the_week(): void
    {
        $weekStart = Carbon::parse('2026-09-21')->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();
        $service = app(WeeklyDashboardKpiService::class);
        $cutoff = $service->rotationCutoffAfterWeek($weekEnd);

        $this->assertTrue($cutoff->isWednesday());
        $this->assertTrue($cutoff->greaterThan($weekEnd->copy()->startOfDay()));

        $includedThisWeek = Employee::factory()->create();
        $includedOnWednesday = Employee::factory()->create();
        $tooLate = Employee::factory()->create();
        $alreadyFinished = Employee::factory()->create();
        $samePerson = Employee::factory()->create();

        Rotation::factory()->create([
            'employee_id' => $includedThisWeek->id,
            'start_date' => $weekStart->copy()->subWeeks(3),
            'end_date' => $weekStart->copy()->addDays(2),
        ]);
        Rotation::factory()->create([
            'employee_id' => $includedOnWednesday->id,
            'start_date' => $weekStart->copy()->subWeeks(2),
            'end_date' => $cutoff->copy(),
        ]);
        Rotation::factory()->create([
            'employee_id' => $tooLate->id,
            'start_date' => $weekStart->copy()->subWeek(),
            'end_date' => $cutoff->copy()->addDay(),
        ]);
        Rotation::factory()->create([
            'employee_id' => $alreadyFinished->id,
            'start_date' => $weekStart->copy()->subWeeks(6),
            'end_date' => $weekStart->copy()->subDay(),
        ]);
        Rotation::factory()->create([
            'employee_id' => $samePerson->id,
            'start_date' => $weekStart->copy()->subWeeks(4),
            'end_date' => $weekStart->copy()->addDay(),
        ]);
        Rotation::factory()->create([
            'employee_id' => $samePerson->id,
            'start_date' => $weekStart->copy()->addDays(3),
            'end_date' => $cutoff->copy(),
        ]);

        $ending = $service->rotationsEndingThrough($weekStart, $cutoff);

        $this->assertEqualsCanonicalizing(
            [$includedThisWeek->id, $includedOnWednesday->id, $samePerson->id],
            $ending->pluck('employee_id')->all()
        );
    }
}
