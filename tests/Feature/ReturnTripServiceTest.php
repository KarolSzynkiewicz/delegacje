<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\ReturnTripService;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\AccommodationAssignment;
use App\Models\VehicleAssignment;
use App\Models\Vehicle;
use App\Models\Location;
use App\Models\Accommodation;
use App\Models\Role;
use App\Models\Rotation;
use App\Models\LogisticsEvent;
use App\Enums\AssignmentStatus;
use App\Enums\LogisticsEventType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

class ReturnTripServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReturnTripService $service;
    protected Location $baseLocation;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create authenticated user
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);
        
        $this->service = app(ReturnTripService::class);
        
        // Create base location
        $this->baseLocation = Location::factory()->create(['is_base' => true]);
    }

    /** @test */
    public function it_creates_return_trip_for_employees_with_active_assignments()
    {
        $employee = Employee::factory()->create();
        $project = Project::factory()->create();
        $role = Role::factory()->create();
        $vehicle = Vehicle::factory()->create(['type' => 'company_vehicle']);
        $location = Location::factory()->create();
        $accommodation = Accommodation::factory()->create(['location_id' => $location->id]);

        // Create rotation
        Rotation::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(10),
        ]);

        $returnDate = now();

        // Create active project assignment
        $projectAssignment = ProjectAssignment::factory()->create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => $returnDate->copy()->subDays(5),
            'end_date' => $returnDate->copy()->addDays(5),
            'status' => AssignmentStatus::ACTIVE,
        ]);

        // Create active accommodation assignment
        $accommodationAssignment = AccommodationAssignment::factory()->create([
            'employee_id' => $employee->id,
            'accommodation_id' => $accommodation->id,
            'start_date' => $returnDate->copy()->subDays(3),
            'end_date' => $returnDate->copy()->addDays(3),
            'status' => AssignmentStatus::ACTIVE,
        ]);

        $data = [
            'vehicle_id' => $vehicle->id,
            'employee_ids' => [$employee->id],
            'return_date' => $returnDate->format('Y-m-d'),
            'notes' => 'Test return trip',
        ];

        $event = $this->service->createReturn($data);

        $this->assertInstanceOf(LogisticsEvent::class, $event);
        $this->assertEquals(LogisticsEventType::RETURN, $event->type);
        $this->assertEquals($this->baseLocation->id, $event->to_location_id);

        // Check assignments are completed
        $projectAssignment->refresh();
        $accommodationAssignment->refresh();
        $this->assertEquals(AssignmentStatus::COMPLETED, $projectAssignment->status);
        $this->assertEquals(AssignmentStatus::COMPLETED, $accommodationAssignment->status);

        // Check vehicle assignment is created
        $vehicleAssignment = VehicleAssignment::where('employee_id', $employee->id)
            ->where('vehicle_id', $vehicle->id)
            ->where('status', AssignmentStatus::IN_TRANSIT)
            ->first();
        $this->assertNotNull($vehicleAssignment);
    }

    /** @test */
    public function it_throws_exception_when_employee_has_no_active_assignments()
    {
        $employee = Employee::factory()->create();
        $vehicle = Vehicle::factory()->create(['type' => 'company_vehicle']);
        $returnDate = now();

        $data = [
            'vehicle_id' => $vehicle->id,
            'employee_ids' => [$employee->id],
            'return_date' => $returnDate->format('Y-m-d'),
        ];

        $this->expectException(ValidationException::class);
        $this->service->createReturn($data);
    }

    /** @test */
    public function it_throws_exception_when_vehicle_is_already_assigned()
    {
        $employee1 = Employee::factory()->create();
        $employee2 = Employee::factory()->create();
        $project = Project::factory()->create();
        $role = Role::factory()->create();
        $vehicle = Vehicle::factory()->create(['type' => 'company_vehicle']);

        Rotation::factory()->create([
            'employee_id' => $employee1->id,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(10),
        ]);

        Rotation::factory()->create([
            'employee_id' => $employee2->id,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(10),
        ]);

        $returnDate = now();

        // Create active assignment for employee1
        ProjectAssignment::factory()->create([
            'employee_id' => $employee1->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => $returnDate->copy()->subDays(5),
            'end_date' => $returnDate->copy()->addDays(5),
            'status' => AssignmentStatus::ACTIVE,
        ]);

        // Create active vehicle assignment for employee2
        VehicleAssignment::factory()->create([
            'employee_id' => $employee2->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => $returnDate->copy()->subDays(2),
            'end_date' => $returnDate->copy()->addDays(2),
            'status' => AssignmentStatus::ACTIVE,
        ]);

        $data = [
            'vehicle_id' => $vehicle->id,
            'employee_ids' => [$employee1->id],
            'return_date' => $returnDate->format('Y-m-d'),
        ];

        $this->expectException(ValidationException::class);
        $this->service->createReturn($data);
    }
}
