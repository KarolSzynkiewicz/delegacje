<?php

namespace Tests\Feature;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Enums\VehiclePosition;
use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use App\Models\AccommodationLease;
use App\Models\Employee;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\ProjectDemand;
use App\Models\Role;
use App\Models\Rotation;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Services\AssignmentTimelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AssignmentTimelinePickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_house_picker_grays_a_full_house_and_keeps_a_free_one(): void
    {
        $location = Location::factory()->create();
        $otherLocation = Location::factory()->create();
        $employee = Employee::factory()->create();
        $project = Project::factory()->create(['location_id' => $location->id, 'status' => 'active']);
        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $employee->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-20',
        ]);

        $full = Accommodation::factory()->create([
            'location_id' => $location->id,
            'name' => 'Dom Pełny',
            'capacity' => 1,
        ]);
        $free = Accommodation::factory()->create([
            'location_id' => $otherLocation->id,
            'name' => 'Dom Wolny',
            'capacity' => 4,
        ]);
        foreach ([$full, $free] as $house) {
            AccommodationLease::query()->create([
                'accommodation_id' => $house->id,
                'type' => 'wynajmowany',
                'start_date' => '2026-07-01',
                'end_date' => '2026-08-31',
            ]);
        }
        AccommodationAssignment::factory()->create([
            'accommodation_id' => $full->id,
            'start_date' => '2026-07-05',
            'end_date' => '2026-07-08',
        ]);

        $options = app(AssignmentTimelineService::class)->houseOptions($employee, '2026-07-01', '2026-07-20');
        $byKey = collect($options)->keyBy('key');

        $this->assertFalse($byKey[(string) $full->id]['enabled']);
        $this->assertStringContainsString('od 05.07', $byKey[(string) $full->id]['meta']);
        $this->assertTrue($byKey[(string) $free->id]['enabled']);
        $this->assertSame(0, $byKey[(string) $free->id]['group']);
        $this->assertGreaterThan($byKey[(string) $free->id]['group'], $byKey[(string) $full->id]['group']);
    }

    public function test_house_picker_allows_owned_houses_without_a_lease(): void
    {
        $employee = Employee::factory()->create();
        $owned = Accommodation::factory()->create([
            'name' => 'Dom Własny',
            'capacity' => 3,
        ]);
        $rentedOutside = Accommodation::factory()->create([
            'name' => 'Dom Poza Umową',
            'capacity' => 3,
        ]);
        AccommodationLease::query()->create([
            'accommodation_id' => $rentedOutside->id,
            'type' => 'wynajmowany',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-10',
        ]);

        $options = collect(app(AssignmentTimelineService::class)->houseOptions($employee, '2026-07-01', '2026-07-20'))
            ->keyBy('key');

        $this->assertTrue($options[(string) $owned->id]['enabled']);
        $this->assertSame('0/3', $options[(string) $owned->id]['meta']);
        $this->assertFalse($options[(string) $rentedOutside->id]['enabled']);
        $this->assertSame('poza umową', $options[(string) $rentedOutside->id]['meta']);
    }

    public function test_role_picker_uses_the_smallest_gap_and_grays_a_missing_role(): void
    {
        $employee = Employee::factory()->create();
        $held = Role::factory()->create(['name' => 'Spawacz']);
        $missing = Role::factory()->create(['name' => 'Kierowca']);
        $employee->roles()->attach($held->id);

        $project = Project::factory()->create(['status' => 'active', 'name' => 'Most']);
        ProjectDemand::factory()->create([
            'project_id' => $project->id,
            'role_id' => $held->id,
            'required_count' => 2,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-03',
        ]);
        ProjectDemand::factory()->create([
            'project_id' => $project->id,
            'role_id' => $missing->id,
            'required_count' => 2,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-03',
        ]);
        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'role_id' => $held->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-03',
        ]);

        $options = collect(app(AssignmentTimelineService::class)->projectOptions($employee, '2026-07-01', '2026-07-03'));
        $heldOption = $options->first(fn (array $option): bool => str_contains($option['key'], ':'.$held->id));
        $missingOption = $options->first(fn (array $option): bool => str_contains($option['key'], ':'.$missing->id));

        $this->assertTrue($heldOption['enabled']);
        $this->assertSame('min. 1', $heldOption['meta']);
        $this->assertFalse($missingOption['enabled']);
        $this->assertSame('brak roli', $missingOption['meta']);
    }

    public function test_same_project_car_sorts_before_another_free_car(): void
    {
        $employee = Employee::factory()->create();
        $coworker = Employee::factory()->create();
        $project = Project::factory()->create(['status' => 'active']);
        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $employee->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-10',
        ]);
        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $coworker->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-10',
        ]);

        $shared = Vehicle::factory()->create([
            'type' => 'company_vehicle',
            'capacity' => 5,
            'registration_number' => 'WX 10001',
        ]);
        $other = Vehicle::factory()->create([
            'type' => 'company_vehicle',
            'capacity' => 5,
            'registration_number' => 'WX 20002',
        ]);
        $inBase = Vehicle::factory()->create([
            'type' => 'company_vehicle',
            'capacity' => 5,
            'registration_number' => 'WX 30003',
        ]);
        VehicleAssignment::factory()->create([
            'vehicle_id' => $shared->id,
            'employee_id' => $coworker->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-10',
        ]);
        VehicleAssignment::factory()->create([
            'vehicle_id' => $other->id,
            'employee_id' => Employee::factory()->create()->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-10',
        ]);
        $this->putEmployeeInField($employee, '2026-06-20', '2026-06-28');

        $options = collect(app(AssignmentTimelineService::class)->vehicleOptions($employee, '2026-07-01', '2026-07-10'))
            ->keyBy('key');

        $this->assertSame(0, $options[(string) $shared->id]['group']);
        $this->assertSame(1, $options[(string) $other->id]['group']);
        $this->assertStringContainsString('ten sam projekt', $options[(string) $shared->id]['meta']);
        $this->assertTrue($options[(string) $shared->id]['enabled']);
        $this->assertTrue($options[(string) $other->id]['enabled']);
        $this->assertFalse($options[(string) $inBase->id]['enabled']);
        $this->assertSame('w bazie', $options[(string) $inBase->id]['meta']);
    }

    public function test_vehicle_picker_blocks_base_employee_from_field_car_without_trip(): void
    {
        $employee = Employee::factory()->create();
        $fieldCar = Vehicle::factory()->create([
            'type' => 'company_vehicle',
            'capacity' => 4,
            'registration_number' => 'WX FIELD',
        ]);
        $base = Location::factory()->create();
        $field = Location::factory()->create();
        $actor = User::factory()->create();

        LogisticsEvent::query()->create([
            'type' => LogisticsEventType::DEPARTURE,
            'event_date' => '2026-06-01',
            'end_date' => '2026-06-10',
            'from_location_id' => $base->id,
            'to_location_id' => $field->id,
            'vehicle_id' => $fieldCar->id,
            'status' => LogisticsEventStatus::COMPLETED,
            'created_by' => $actor->id,
        ]);

        $options = collect(app(AssignmentTimelineService::class)->vehicleOptions($employee, '2026-07-01', '2026-07-10'))
            ->keyBy('key');

        $this->assertFalse($options[(string) $fieldCar->id]['enabled']);
        $this->assertSame('brak wyjazdu', $options[(string) $fieldCar->id]['meta']);

        $this->expectException(ValidationException::class);
        app(AssignmentTimelineService::class)->commitEmployee($employee, [
            'lane' => 'vehicle',
            'id' => null,
            'start' => '2026-07-01',
            'end' => '2026-07-10',
            'keep_open' => false,
        ], (string) $fieldCar->id, VehiclePosition::PASSENGER->value);
    }

    public function test_moving_the_start_of_an_open_stay_keeps_the_end_empty(): void
    {
        $employee = Employee::factory()->create();
        Rotation::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-08-31',
        ]);
        $house = Accommodation::factory()->create(['capacity' => 2]);
        AccommodationLease::query()->create([
            'accommodation_id' => $house->id,
            'type' => 'wynajmowany',
            'start_date' => '2026-07-01',
            'end_date' => '2026-08-31',
        ]);
        $assignment = AccommodationAssignment::factory()->create([
            'employee_id' => $employee->id,
            'accommodation_id' => $house->id,
            'start_date' => '2026-07-01',
            'end_date' => null,
        ]);

        app(AssignmentTimelineService::class)->commitEmployee($employee, [
            'lane' => 'accommodation',
            'id' => $assignment->id,
            'start' => '2026-07-10',
            'end' => null,
            'keep_open' => true,
        ], null, 'passenger');

        $assignment->refresh();
        $this->assertSame('2026-07-10', $assignment->start_date->toDateString());
        $this->assertNull($assignment->end_date);
    }

    private function putEmployeeInField(Employee $employee, string $start, string $end): void
    {
        $base = Location::factory()->create();
        $field = Location::factory()->create();
        $actor = User::factory()->create();
        $departure = LogisticsEvent::query()->create([
            'type' => LogisticsEventType::DEPARTURE,
            'event_date' => $start,
            'end_date' => $end,
            'from_location_id' => $base->id,
            'to_location_id' => $field->id,
            'status' => LogisticsEventStatus::COMPLETED,
            'created_by' => $actor->id,
        ]);
        $departure->participants()->create(['employee_id' => $employee->id]);
    }
}
