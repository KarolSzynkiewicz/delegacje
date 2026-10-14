<?php

namespace Tests\Unit;

use App\Enums\TaskStatus;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\ProjectTask;
use App\Models\Role;
use App\Models\Rotation;
use App\Models\User;
use App\Services\ProjectAssignmentService;
use App\Services\SprintActivityFeed;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SlowRequestOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_assignment_form_loads_availability_without_a_query_per_employee(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14'));
        $start = Carbon::parse('2026-10-12');
        $end = Carbon::parse('2026-10-18');

        $open = Employee::factory()->create();
        Rotation::factory()->create([
            'employee_id' => $open->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'status' => 'active',
        ]);

        $busy = Employee::factory()->create();
        Rotation::factory()->create([
            'employee_id' => $busy->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'status' => 'active',
        ]);
        $role = Role::factory()->create();
        $project = Project::factory()->create();
        ProjectAssignment::factory()->create([
            'employee_id' => $busy->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]);

        $withoutRotation = Employee::factory()->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $employees = app(ProjectAssignmentService::class)
            ->getEmployeesWithAvailabilityStatus($start, $end)
            ->keyBy('id');

        $queries = DB::getQueryLog();
        $rotationQueries = collect($queries)->filter(
            fn (array $query) => str_contains($query['query'], 'from `rotations`')
        )->count();
        $assignmentQueries = collect($queries)->filter(
            fn (array $query) => str_contains($query['query'], 'from `project_assignments`')
        )->count();

        $this->assertTrue($employees[$open->id]->availability_status['available']);
        $this->assertFalse($employees[$busy->id]->availability_status['available']);
        $this->assertFalse($employees[$withoutRotation->id]->availability_status['available']);
        $this->assertSame(1, $rotationQueries);
        $this->assertSame(1, $assignmentQueries);
        $this->assertLessThan(25, count($queries));
    }

    public function test_task_history_reads_a_bounded_audit_slice(): void
    {
        $user = User::factory()->create();
        $task = ProjectTask::query()->create([
            'name' => 'Historia',
            'status' => TaskStatus::PENDING,
            'created_by' => $user->id,
        ]);

        $sql = [];
        DB::listen(function ($query) use (&$sql) {
            if (str_contains($query->sql, 'audit_logs')) {
                $sql[] = $query->sql;
            }
        });

        app(SprintActivityFeed::class)->forTask($task);

        $this->assertNotEmpty($sql);
        foreach ($sql as $statement) {
            $this->assertStringContainsString('limit', strtolower($statement));
        }
    }
}
