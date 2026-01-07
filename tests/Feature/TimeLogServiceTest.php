<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\TimeLogService;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\TimeLog;
use App\Models\Role;
use App\Models\Rotation;
use App\Enums\AssignmentStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

class TimeLogServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TimeLogService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TimeLogService::class);
    }

    /** @test */
    public function it_creates_time_log_for_assignment()
    {
        $employee = Employee::factory()->create();
        $project = Project::factory()->create();
        $role = Role::factory()->create();

        Rotation::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(10),
        ]);

        $assignment = ProjectAssignment::factory()->create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(5),
            'status' => AssignmentStatus::ACTIVE,
        ]);

        $workDate = now();
        $data = [
            'work_date' => $workDate->format('Y-m-d'),
            'hours_worked' => 8.0,
            'notes' => 'Test work',
        ];

        $timeLog = $this->service->createTimeLog($assignment, $data);

        $this->assertInstanceOf(TimeLog::class, $timeLog);
        $this->assertEquals($assignment->id, $timeLog->project_assignment_id);
        $this->assertEquals(8.0, $timeLog->hours_worked);
    }

    /** @test */
    public function it_throws_exception_when_work_date_is_before_assignment_start()
    {
        $employee = Employee::factory()->create();
        $project = Project::factory()->create();
        $role = Role::factory()->create();

        Rotation::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(10),
        ]);

        $assignment = ProjectAssignment::factory()->create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => now(),
            'end_date' => now()->addDays(5),
            'status' => AssignmentStatus::ACTIVE,
        ]);

        $data = [
            'work_date' => now()->subDays(1)->format('Y-m-d'),
            'hours_worked' => 8.0,
        ];

        $this->expectException(ValidationException::class);
        $this->service->createTimeLog($assignment, $data);
    }

    /** @test */
    public function it_throws_exception_when_work_date_is_after_assignment_end()
    {
        $employee = Employee::factory()->create();
        $project = Project::factory()->create();
        $role = Role::factory()->create();

        Rotation::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(10),
        ]);

        $assignment = ProjectAssignment::factory()->create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => now()->subDays(5),
            'end_date' => now(),
            'status' => AssignmentStatus::ACTIVE,
        ]);

        $data = [
            'work_date' => now()->addDays(1)->format('Y-m-d'),
            'hours_worked' => 8.0,
        ];

        $this->expectException(ValidationException::class);
        $this->service->createTimeLog($assignment, $data);
    }

    /** @test */
    public function it_throws_exception_when_hours_worked_exceeds_24()
    {
        $employee = Employee::factory()->create();
        $project = Project::factory()->create();
        $role = Role::factory()->create();

        Rotation::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(10),
        ]);

        $assignment = ProjectAssignment::factory()->create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(5),
            'status' => AssignmentStatus::ACTIVE,
        ]);

        $data = [
            'work_date' => now()->format('Y-m-d'),
            'hours_worked' => 25.0,
        ];

        $this->expectException(ValidationException::class);
        $this->service->createTimeLog($assignment, $data);
    }

    /** @test */
    public function it_throws_exception_when_time_log_already_exists_for_date()
    {
        $employee = Employee::factory()->create();
        $project = Project::factory()->create();
        $role = Role::factory()->create();

        Rotation::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(10),
        ]);

        $assignment = ProjectAssignment::factory()->create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(5),
            'status' => AssignmentStatus::ACTIVE,
        ]);

        $workDate = now();
        TimeLog::factory()->create([
            'project_assignment_id' => $assignment->id,
            'start_time' => $workDate->copy()->setTime(8, 0),
            'end_time' => $workDate->copy()->setTime(16, 0),
            'hours_worked' => 8.0,
        ]);

        $data = [
            'work_date' => $workDate->format('Y-m-d'),
            'hours_worked' => 8.0,
        ];

        $this->expectException(ValidationException::class);
        $this->service->createTimeLog($assignment, $data);
    }

    /** @test */
    public function it_calculates_total_hours_for_assignment()
    {
        $employee = Employee::factory()->create();
        $project = Project::factory()->create();
        $role = Role::factory()->create();

        Rotation::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(10),
        ]);

        $assignment = ProjectAssignment::factory()->create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(5),
            'status' => AssignmentStatus::ACTIVE,
        ]);

        $workDate1 = now()->subDays(2);
        $workDate2 = now()->subDays(1);
        
        TimeLog::factory()->create([
            'project_assignment_id' => $assignment->id,
            'start_time' => $workDate1->copy()->setTime(8, 0),
            'end_time' => $workDate1->copy()->setTime(16, 0),
            'hours_worked' => 8.0,
        ]);

        TimeLog::factory()->create([
            'project_assignment_id' => $assignment->id,
            'start_time' => $workDate2->copy()->setTime(8, 0),
            'end_time' => $workDate2->copy()->setTime(15, 30),
            'hours_worked' => 7.5,
        ]);

        $total = $this->service->getTotalHoursForAssignment($assignment);

        $this->assertEquals(15.5, $total);
    }
}
