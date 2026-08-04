<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Role;
use App\Services\WeeklyOverviewService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TerminatedEmployeesWithAssignmentsAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_finds_terminated_employee_with_overlapping_project_assignment(): void
    {
        $weekStart = Carbon::parse('2026-08-03')->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $terminated = Employee::factory()->create([
            'terminated_at' => now()->subDay(),
            'termination_reason' => 'other',
        ]);
        $active = Employee::factory()->create(['terminated_at' => null]);

        $project = Project::factory()->create();
        $role = Role::factory()->create();

        ProjectAssignment::factory()->create([
            'employee_id' => $terminated->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => $weekStart->toDateString(),
            'end_date' => $weekEnd->toDateString(),
        ]);

        ProjectAssignment::factory()->create([
            'employee_id' => $active->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => $weekStart->toDateString(),
            'end_date' => $weekEnd->toDateString(),
        ]);

        $result = app(WeeklyOverviewService::class)
            ->getTerminatedEmployeesWithAssignments($weekStart, $weekEnd);

        $this->assertCount(1, $result);
        $this->assertSame($terminated->id, $result->first()['employee']->id);
        $this->assertTrue($result->first()['project_assignments']->isNotEmpty());
    }

    public function test_ignores_terminated_employee_without_overlapping_assignments(): void
    {
        $weekStart = Carbon::parse('2026-08-03')->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        Employee::factory()->create([
            'terminated_at' => now()->subDay(),
            'termination_reason' => 'other',
        ]);

        $result = app(WeeklyOverviewService::class)
            ->getTerminatedEmployeesWithAssignments($weekStart, $weekEnd);

        $this->assertCount(0, $result);
    }
}
