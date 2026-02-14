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
use App\Models\Vehicle;
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
        $baseLocation = Location::factory()->create(['is_base' => true]);
        
        $employee = Employee::factory()->create();
        $project = Project::factory()->create(['location_id' => $baseLocation->id]);
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

        // Create active project assignment (not cancelled, dates overlap)
        $projectAssignment = ProjectAssignment::create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => $date->copy()->subDays(5),
            'end_date' => $date->copy()->addDays(5),
            'is_cancelled' => false,
        ]);

        // Create active accommodation assignment
        $accommodationAssignment = AccommodationAssignment::create([
            'employee_id' => $employee->id,
            'accommodation_id' => $accommodation->id,
            'start_date' => $date->copy()->subDays(3),
            'end_date' => $date->copy()->addDays(3),
        ]);

        $assignments = $this->service->getActiveAssignmentsForEmployees([$employee->id], $date);

        $this->assertCount(2, $assignments);
        $this->assertTrue($assignments->contains('id', $projectAssignment->id));
        $this->assertTrue($assignments->contains('id', $accommodationAssignment->id));
    }

    /** @test */
    public function it_gets_active_vehicle_assignment()
    {
        $employee = Employee::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $date = now();

        // Create active vehicle assignment
        $vehicleAssignment = VehicleAssignment::create([
            'employee_id' => $employee->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => $date->copy()->subDays(5),
            'end_date' => $date->copy()->addDays(5),
            'is_return_trip' => false,
        ]);

        $assignment = $this->service->getActiveVehicleAssignment($employee->id, $date);

        $this->assertNotNull($assignment);
        $this->assertEquals($vehicleAssignment->id, $assignment->id);
    }

    /** @test */
    public function it_returns_null_when_no_active_vehicle_assignment()
    {
        $employee = Employee::factory()->create();
        $date = now();

        $assignment = $this->service->getActiveVehicleAssignment($employee->id, $date);

        $this->assertNull($assignment);
    }
}
