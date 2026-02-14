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
        $projectAssignment = ProjectAssignment::create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => $returnDate->copy()->subDays(5),
            'end_date' => $returnDate->copy()->addDays(5),
        ]);

        // Create active accommodation assignment
        $accommodationAssignment = AccommodationAssignment::create([
            'employee_id' => $employee->id,
            'accommodation_id' => $accommodation->id,
            'start_date' => $returnDate->copy()->subDays(3),
            'end_date' => $returnDate->copy()->addDays(3),
        ]);

        $event = $this->service->createReturn(
            [$employee->id],
            $returnDate,
            $vehicle,
            'Test return trip'
        );

        $this->assertInstanceOf(LogisticsEvent::class, $event);
        $this->assertEquals(LogisticsEventType::RETURN, $event->type);
        $this->assertEquals($this->baseLocation->id, $event->to_location_id);

        // Check assignments end dates are updated
        $projectAssignment->refresh();
        $this->assertEquals($returnDate->format('Y-m-d'), $projectAssignment->end_date->format('Y-m-d'));

        // Check vehicle assignment is created
        $vehicleAssignment = VehicleAssignment::where('employee_id', $employee->id)
            ->where('vehicle_id', $vehicle->id)
            ->where('is_return_trip', true)
            ->first();
        $this->assertNotNull($vehicleAssignment);
    }
}
