<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_create_vehicle_assignment()
    {
        $employee = Employee::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $response = $this->actingAs($this->user)->post(route('vehicle-assignments.store'), [
            'employee_id' => $employee->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('vehicle-assignments.index'));
        $this->assertDatabaseHas('vehicle_assignments', [
            'employee_id' => $employee->id,
            'vehicle_id' => $vehicle->id,
        ]);
    }

    public function test_vehicle_availability_validation()
    {
        $employee1 = Employee::factory()->create();
        $employee2 = Employee::factory()->create();
        $vehicle = Vehicle::factory()->create();

        // Create first assignment
        VehicleAssignment::create([
            'employee_id' => $employee1->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-31',
        ]);

        // Try to assign same vehicle to another employee in overlapping period
        $response = $this->actingAs($this->user)->post(route('vehicle-assignments.store'), [
            'employee_id' => $employee2->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => '2025-01-15',
            'end_date' => '2025-02-15',
        ]);

        $response->assertSessionHasErrors('vehicle_id');
    }
}
