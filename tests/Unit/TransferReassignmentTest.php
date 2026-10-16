<?php

namespace Tests\Unit;

use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Role;
use App\Models\User;
use App\Models\VehicleAssignment;
use App\Services\TransferService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferReassignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_replaces_overlapping_manual_assignments_and_can_be_reversed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $employee = Employee::factory()->create();
        $role = Role::factory()->create();
        $from = Location::factory()->create();
        $to = Location::factory()->create();
        $sourceProject = Project::factory()->create(['location_id' => $from->id]);
        $targetProject = Project::factory()->create(['location_id' => $to->id]);
        $laterProject = Project::factory()->create(['location_id' => $to->id]);
        $oldHouse = Accommodation::factory()->create();
        $manualHouse = Accommodation::factory()->create();
        $newHouse = Accommodation::factory()->create();

        $coveringProject = ProjectAssignment::factory()->create([
            'employee_id' => $employee->id,
            'project_id' => $sourceProject->id,
            'role_id' => $role->id,
            'start_date' => '2026-09-01',
            'end_date' => null,
            'notes' => 'przed transferem',
        ]);
        $manualProject = ProjectAssignment::factory()->create([
            'employee_id' => $employee->id,
            'project_id' => $laterProject->id,
            'role_id' => $role->id,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-20',
            'notes' => 'ręczny projekt',
        ]);
        $endedHouse = AccommodationAssignment::factory()->create([
            'employee_id' => $employee->id,
            'accommodation_id' => $oldHouse->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'notes' => 'kwatera sprzed transferu',
        ]);
        $manualHouseAssignment = AccommodationAssignment::factory()->create([
            'employee_id' => $employee->id,
            'accommodation_id' => $manualHouse->id,
            'start_date' => '2026-10-02',
            'end_date' => '2026-10-15',
            'notes' => 'ręczna kwatera',
        ]);

        $event = app(TransferService::class)->commitTransfer([
            'employee_ids' => [$employee->id],
            'from_location_id' => $from->id,
            'to_location_id' => $to->id,
            'transfer_date' => Carbon::parse('2026-10-01 15:00:00'),
            'vehicle_id' => null,
            'notes' => null,
            'route_distance' => null,
            'route_duration' => null,
            'route_waypoints' => null,
            'has_reassignment' => true,
            'reassignments' => [
                $employee->id => [
                    'project_id' => $targetProject->id,
                    'role_id' => $role->id,
                    'source_project_assignment_id' => $coveringProject->id,
                    'start_date' => '2026-10-05',
                    'end_date' => '2026-10-31',
                    'accommodation_id' => $newHouse->id,
                    'accommodation_start_date' => '2026-10-08',
                    'accommodation_end_date' => '2026-10-20',
                    'skip_old_vehicle_shorten' => true,
                ],
            ],
            'driver_employee_id' => null,
            'driver_payment_amount' => null,
            'driver_payment_currency' => null,
            'driver_payroll_id' => null,
        ]);

        $coveringProject->refresh();
        $this->assertSame('2026-09-30', $coveringProject->end_date->toDateString());

        $projects = ProjectAssignment::query()->where('employee_id', $employee->id)->orderBy('start_date')->get();
        $this->assertCount(2, $projects);
        $this->assertNull(ProjectAssignment::query()->where('notes', 'ręczny projekt')->first());
        $createdProject = $projects->firstWhere('project_id', $targetProject->id);
        $this->assertNotNull($createdProject);
        $this->assertSame('2026-10-05', $createdProject->start_date->toDateString());
        $this->assertSame('2026-10-31', $createdProject->end_date->toDateString());
        $this->assertSame($event->id, (int) $createdProject->logistics_event_id);

        $endedHouse->refresh();
        $this->assertSame('2026-09-30', $endedHouse->end_date->toDateString());
        $manualHouseAssignment->refresh();
        $this->assertSame('2026-10-07', $manualHouseAssignment->end_date->toDateString());

        $houses = AccommodationAssignment::query()->where('employee_id', $employee->id)->orderBy('start_date')->get();
        $this->assertCount(3, $houses);
        $createdHouse = $houses->firstWhere('accommodation_id', $newHouse->id);
        $this->assertNotNull($createdHouse);
        $this->assertSame('2026-10-08', $createdHouse->start_date->toDateString());
        $this->assertSame('2026-10-20', $createdHouse->end_date->toDateString());

        app(TransferService::class)->reverseTransfer($event->fresh());

        $coveringProject->refresh();
        $this->assertNull($coveringProject->end_date);
        $restoredProject = ProjectAssignment::query()->where('notes', 'ręczny projekt')->first();
        $this->assertNotNull($restoredProject);
        $this->assertSame('2026-10-05', $restoredProject->start_date->toDateString());
        $this->assertSame('2026-10-20', $restoredProject->end_date->toDateString());
        $this->assertNull(ProjectAssignment::query()->find($createdProject->id));

        $manualHouseAssignment->refresh();
        $this->assertSame('2026-10-02', $manualHouseAssignment->start_date->toDateString());
        $this->assertSame('2026-10-15', $manualHouseAssignment->end_date->toDateString());
        $this->assertNull(AccommodationAssignment::query()->find($createdHouse->id));
        $this->assertSame(0, VehicleAssignment::query()->where('employee_id', $employee->id)->count());
    }
}
