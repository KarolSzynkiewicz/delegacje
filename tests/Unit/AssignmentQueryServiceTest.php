<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AssignmentQueryService;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\AccommodationAssignment;
use App\Models\VehicleAssignment;
use App\Models\Accommodation;
use App\Models\Location;
use App\Models\Role;
use App\Models\Rotation;
use App\Enums\AssignmentStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AssignmentQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AssignmentQueryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AssignmentQueryService::class);
    }

    /** @test */
    public function it_gets_active_assignments_for_employees_at_date()
    {
        $employee = Employee::factory()->create();
        $project = Project::factory()->create();
        $role = Role::factory()->create();
        $location = Location::factory()->create();
        $accommodation = Accommodation::factory()->create(['location_id' => $location->id]);

        // Create rotation
        Rotation::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(10),
        ]);

        $date = now();

        // Create active project assignment
        $projectAssignment = ProjectAssignment::factory()->create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => $date->copy()->subDays(5),
            'end_date' => $date->copy()->addDays(5),
            'status' => AssignmentStatus::ACTIVE,
        ]);

        // Create active accommodation assignment
        $accommodationAssignment = AccommodationAssignment::factory()->create([
            'employee_id' => $employee->id,
            'accommodation_id' => $accommodation->id,
            'start_date' => $date->copy()->subDays(3),
            'end_date' => $date->copy()->addDays(3),
            'status' => AssignmentStatus::ACTIVE,
        ]);

        $assignments = $this->service->getActiveAssignmentsForEmployees([$employee->id], $date);

        $this->assertCount(2, $assignments);
        $this->assertTrue($assignments->contains('id', $projectAssignment->id));
        $this->assertTrue($assignments->contains('id', $accommodationAssignment->id));
    }

    /** @test */
    public function it_does_not_get_inactive_assignments()
    {
        $employee = Employee::factory()->create();
        $project = Project::factory()->create();
        $role = Role::factory()->create();

        Rotation::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(10),
        ]);

        $date = now();

        // Create completed assignment
        ProjectAssignment::factory()->create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => $date->copy()->subDays(5),
            'end_date' => $date->copy()->addDays(5),
            'status' => AssignmentStatus::COMPLETED,
        ]);

        $assignments = $this->service->getActiveAssignmentsForEmployees([$employee->id], $date);

        $this->assertCount(0, $assignments);
    }

    /** @test */
    public function it_checks_if_employee_has_active_assignment()
    {
        $employee = Employee::factory()->create();
        $project = Project::factory()->create();
        $role = Role::factory()->create();

        Rotation::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(10),
        ]);

        $date = now();

        ProjectAssignment::factory()->create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => $date->copy()->subDays(5),
            'end_date' => $date->copy()->addDays(5),
            'status' => AssignmentStatus::ACTIVE,
        ]);

        $this->assertTrue($this->service->hasActiveAssignment($employee->id, $date));
    }

    /** @test */
    public function it_returns_false_when_employee_has_no_active_assignment()
    {
        $employee = Employee::factory()->create();
        $date = now();

        $this->assertFalse($this->service->hasActiveAssignment($employee->id, $date));
    }

    /** @test */
    public function it_gets_employees_with_active_assignments()
    {
        $employee1 = Employee::factory()->create();
        $employee2 = Employee::factory()->create();
        $project = Project::factory()->create();
        $role = Role::factory()->create();

        Rotation::factory()->create([
            'employee_id' => $employee1->id,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(10),
        ]);

        $date = now();

        ProjectAssignment::factory()->create([
            'employee_id' => $employee1->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => $date->copy()->subDays(5),
            'end_date' => $date->copy()->addDays(5),
            'status' => AssignmentStatus::ACTIVE,
        ]);

        $employees = $this->service->getEmployeesWithActiveAssignments($date);

        $this->assertTrue($employees->contains($employee1));
        $this->assertFalse($employees->contains($employee2));
    }

    /** @test */
    public function it_gets_active_vehicle_assignment()
    {
        $employee = Employee::factory()->create();
        $vehicle = \App\Models\Vehicle::factory()->create();
        $date = now();

        $vehicleAssignment = VehicleAssignment::factory()->create([
            'employee_id' => $employee->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => $date->copy()->subDays(2),
            'end_date' => $date->copy()->addDays(2),
            'status' => AssignmentStatus::ACTIVE,
        ]);

        $result = $this->service->getActiveVehicleAssignment($employee->id, $date);

        $this->assertNotNull($result);
        $this->assertEquals($vehicleAssignment->id, $result->id);
    }

    /** @test */
    public function it_returns_null_when_no_active_vehicle_assignment()
    {
        $employee = Employee::factory()->create();
        $date = now();

        $result = $this->service->getActiveVehicleAssignment($employee->id, $date);

        $this->assertNull($result);
    }
}
