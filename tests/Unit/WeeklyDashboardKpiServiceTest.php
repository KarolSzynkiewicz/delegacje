<?php

namespace Tests\Unit;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Enums\ServiceActionType;
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
use App\Models\VehicleRepair;
use App\Services\WeeklyDashboardKpiService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeeklyDashboardKpiServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_total_demand_sums_slots_across_projects_for_the_week(): void
    {
        $weekStart = Carbon::parse('2026-09-21')->startOfDay();
        $weekEnd = $weekStart->copy()->endOfWeek();
        $role = Role::factory()->create();

        $first = Project::factory()->create();
        $second = Project::factory()->create();
        $outside = Project::factory()->create();

        ProjectDemand::factory()->create([
            'project_id' => $first->id,
            'role_id' => $role->id,
            'required_count' => 40,
            'start_date' => $weekStart,
            'end_date' => $weekEnd,
        ]);
        ProjectDemand::factory()->create([
            'project_id' => $second->id,
            'role_id' => $role->id,
            'required_count' => 25,
            'start_date' => $weekStart->copy()->subWeek(),
            'end_date' => $weekEnd->copy()->addDay(),
        ]);
        ProjectDemand::factory()->create([
            'project_id' => $outside->id,
            'role_id' => $role->id,
            'required_count' => 9,
            'start_date' => $weekEnd->copy()->addWeek(),
            'end_date' => $weekEnd->copy()->addWeeks(2),
        ]);

        $service = app(WeeklyDashboardKpiService::class);

        $this->assertSame(65, $service->totalDemandForWeek($weekStart, $weekEnd));

        $byProject = $service->employeesInFieldByProjectForWeek($weekStart, $weekEnd);
        $this->assertCount(2, $byProject);
        $this->assertSame(0, (int) $byProject->firstWhere('project_id', $first->id)->employee_count);
        $this->assertSame(40, (int) $byProject->firstWhere('project_id', $first->id)->needed_count);
        $this->assertSame(25, (int) $byProject->firstWhere('project_id', $second->id)->needed_count);
    }

    public function test_staffing_demand_matrix_starts_with_previous_week_and_sums_roles(): void
    {
        $currentStart = Carbon::parse('2026-09-21')->startOfWeek();
        $prevStart = $currentStart->copy()->subWeek();
        $roleA = Role::factory()->create(['name' => 'Spawacz']);
        $roleB = Role::factory()->create(['name' => 'Monter']);
        $project = Project::factory()->create(['name' => 'Hala A']);
        $idle = Project::factory()->create(['name' => 'Bez popytu']);

        ProjectDemand::factory()->create([
            'project_id' => $project->id,
            'role_id' => $roleA->id,
            'required_count' => 10,
            'start_date' => $prevStart,
            'end_date' => $currentStart->copy()->endOfWeek(),
        ]);
        ProjectDemand::factory()->create([
            'project_id' => $project->id,
            'role_id' => $roleB->id,
            'required_count' => 5,
            'start_date' => $currentStart,
            'end_date' => $currentStart->copy()->endOfWeek(),
        ]);

        $employee = Employee::factory()->create();
        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $employee->id,
            'role_id' => $roleA->id,
            'start_date' => $currentStart,
            'end_date' => $currentStart->copy()->endOfWeek(),
        ]);

        ProjectAssignment::factory()->create([
            'project_id' => $idle->id,
            'employee_id' => Employee::factory()->create()->id,
            'role_id' => $roleA->id,
            'start_date' => $currentStart,
            'end_date' => $currentStart->copy()->endOfWeek(),
        ]);

        $service = app(WeeklyDashboardKpiService::class);
        $matrix = $service->staffingDemandMatrix($currentStart, 2);

        $this->assertCount(4, $matrix['weeks']);
        $this->assertTrue($matrix['weeks'][0]['is_previous']);
        $this->assertTrue($matrix['weeks'][1]['is_current']);
        $this->assertSame($prevStart->toDateString(), $matrix['weeks'][0]['start']->toDateString());
        $this->assertSame($currentStart->toDateString(), $matrix['weeks'][1]['start']->toDateString());

        $this->assertCount(1, $matrix['rows']);
        $row = $matrix['rows'][0];
        $this->assertSame($project->id, $row['project_id']);
        $this->assertSame(10, $row['cells'][0]['needed']);
        $this->assertSame(0, $row['cells'][0]['assigned']);
        $this->assertSame(15, $row['cells'][1]['needed']);
        $this->assertSame(1, $row['cells'][1]['assigned']);
    }

    public function test_rotations_ending_include_the_wednesday_after_the_week(): void
    {
        $weekStart = Carbon::parse('2026-09-21')->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();
        $service = app(WeeklyDashboardKpiService::class);
        $cutoff = $service->rotationCutoffAfterWeek($weekEnd);

        $this->assertTrue($cutoff->isWednesday());
        $this->assertTrue($cutoff->greaterThan($weekEnd->copy()->startOfDay()));

        $includedThisWeek = Employee::factory()->create();
        $includedOnWednesday = Employee::factory()->create();
        $tooLate = Employee::factory()->create();
        $alreadyFinished = Employee::factory()->create();
        $samePerson = Employee::factory()->create();

        Rotation::factory()->create([
            'employee_id' => $includedThisWeek->id,
            'start_date' => $weekStart->copy()->subWeeks(3),
            'end_date' => $weekStart->copy()->addDays(2),
        ]);
        Rotation::factory()->create([
            'employee_id' => $includedOnWednesday->id,
            'start_date' => $weekStart->copy()->subWeeks(2),
            'end_date' => $cutoff->copy(),
        ]);
        Rotation::factory()->create([
            'employee_id' => $tooLate->id,
            'start_date' => $weekStart->copy()->subWeek(),
            'end_date' => $cutoff->copy()->addDay(),
        ]);
        Rotation::factory()->create([
            'employee_id' => $alreadyFinished->id,
            'start_date' => $weekStart->copy()->subWeeks(6),
            'end_date' => $weekStart->copy()->subDay(),
        ]);
        Rotation::factory()->create([
            'employee_id' => $samePerson->id,
            'start_date' => $weekStart->copy()->subWeeks(4),
            'end_date' => $weekStart->copy()->addDay(),
        ]);
        Rotation::factory()->create([
            'employee_id' => $samePerson->id,
            'start_date' => $weekStart->copy()->addDays(3),
            'end_date' => $cutoff->copy(),
        ]);

        $ending = $service->rotationsEndingThrough($weekStart, $cutoff);

        $this->assertEqualsCanonicalizing(
            [$includedThisWeek->id, $includedOnWednesday->id, $samePerson->id],
            $ending->pluck('employee_id')->all()
        );
    }

    public function test_housing_sums_people_per_house_and_keeps_empty_beds(): void
    {
        $weekStart = Carbon::parse('2026-09-21')->startOfDay();
        $weekEnd = $weekStart->copy()->endOfWeek();
        $full = Accommodation::factory()->create(['capacity' => 4]);
        $empty = Accommodation::factory()->create(['capacity' => 3]);
        $ended = Accommodation::factory()->create(['capacity' => 10]);
        AccommodationLease::create([
            'accommodation_id' => $ended->id,
            'type' => 'wynajmowany',
            'start_date' => '2026-01-01',
            'end_date' => '2026-09-01',
        ]);

        $first = Employee::factory()->create();
        $second = Employee::factory()->create();
        AccommodationAssignment::factory()->create([
            'accommodation_id' => $full->id,
            'employee_id' => $first->id,
            'start_date' => $weekStart,
            'end_date' => $weekEnd,
        ]);
        AccommodationAssignment::factory()->create([
            'accommodation_id' => $full->id,
            'employee_id' => $second->id,
            'start_date' => $weekStart,
            'end_date' => $weekEnd,
        ]);
        AccommodationAssignment::factory()->create([
            'accommodation_id' => $empty->id,
            'employee_id' => $first->id,
            'start_date' => $weekStart,
            'end_date' => $weekEnd,
        ]);

        $housing = app(WeeklyDashboardKpiService::class)->housingForWeek($weekStart, $weekEnd);

        $this->assertSame(3, $housing['occupied']);
        $this->assertSame(7, $housing['capacity']);
    }

    public function test_vehicles_count_field_assignments_and_skip_service_and_logistics(): void
    {
        $weekStart = Carbon::parse('2026-09-21')->startOfDay();
        $weekEnd = $weekStart->copy()->endOfWeek();
        $field = Vehicle::factory()->create(['capacity' => 5, 'outside_base' => true, 'technical_condition' => 'workshop']);
        $emptyField = Vehicle::factory()->create(['capacity' => 3, 'outside_base' => true, 'technical_condition' => 'good']);
        $atBase = Vehicle::factory()->create(['capacity' => 8, 'outside_base' => false, 'technical_condition' => 'good']);
        $inService = Vehicle::factory()->create(['capacity' => 4, 'outside_base' => true, 'technical_condition' => 'good']);
        $onDeparture = Vehicle::factory()->create(['capacity' => 6, 'outside_base' => true, 'technical_condition' => 'good']);

        $rider = Employee::factory()->create();
        $returning = Employee::factory()->create();
        $other = Employee::factory()->create();

        VehicleAssignment::factory()->create([
            'vehicle_id' => $field->id,
            'employee_id' => $rider->id,
            'start_date' => $weekStart,
            'end_date' => $weekEnd,
            'is_return_trip' => false,
        ]);
        VehicleAssignment::factory()->create([
            'vehicle_id' => $field->id,
            'employee_id' => $returning->id,
            'start_date' => $weekStart,
            'end_date' => $weekEnd,
            'is_return_trip' => true,
        ]);
        VehicleAssignment::factory()->create([
            'vehicle_id' => $atBase->id,
            'employee_id' => $other->id,
            'start_date' => $weekStart,
            'end_date' => $weekEnd,
            'is_return_trip' => false,
        ]);
        VehicleAssignment::factory()->create([
            'vehicle_id' => $inService->id,
            'employee_id' => $other->id,
            'start_date' => $weekStart,
            'end_date' => $weekEnd,
            'is_return_trip' => false,
        ]);
        VehicleAssignment::factory()->create([
            'vehicle_id' => $onDeparture->id,
            'employee_id' => $rider->id,
            'start_date' => $weekStart,
            'end_date' => $weekEnd,
            'is_return_trip' => false,
        ]);

        VehicleRepair::create([
            'vehicle_id' => $inService->id,
            'action_type' => ServiceActionType::REPAIR,
            'start_date' => $weekStart->toDateString(),
            'end_date' => $weekEnd->toDateString(),
        ]);
        $location = Location::factory()->create();
        LogisticsEvent::create([
            'type' => LogisticsEventType::DEPARTURE,
            'event_date' => $weekStart,
            'end_date' => $weekEnd,
            'status' => LogisticsEventStatus::PLANNED,
            'vehicle_id' => $onDeparture->id,
            'from_location_id' => $location->id,
            'to_location_id' => $location->id,
            'created_by' => User::factory()->create()->id,
        ]);

        $vehicles = app(WeeklyDashboardKpiService::class)->vehiclesForWeek($weekStart, $weekEnd);

        $this->assertSame(1, $vehicles['occupied']);
        $this->assertSame(8, $vehicles['capacity']);
    }

    public function test_bench_is_split_on_saturday_between_rotation_and_free(): void
    {
        $weekStart = Carbon::parse('2026-09-21')->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();
        $service = app(WeeklyDashboardKpiService::class);
        $saturday = $service->dispatchDayOfWeek($weekStart);
        $cutoff = $service->rotationCutoffAfterWeek($weekEnd);

        $this->assertTrue($saturday->isSaturday());
        $this->assertSame('sobotę', $service->dispatchDayLabel($saturday));

        $hired = ['hired_at' => $weekStart->copy()->subMonths(2)];
        $onRotation = Employee::factory()->create($hired);
        $free = Employee::factory()->create($hired);
        $leavingSoon = Employee::factory()->create($hired);
        $onProject = Employee::factory()->create($hired);
        $gone = Employee::factory()->create([
            'hired_at' => $weekStart->copy()->subMonths(2),
            'terminated_at' => $saturday->copy()->subDay(),
        ]);
        $finishedBeforeSaturday = Employee::factory()->create($hired);

        Rotation::factory()->create([
            'employee_id' => $onRotation->id,
            'start_date' => $weekStart->copy()->subWeeks(2),
            'end_date' => $weekEnd->copy()->addWeek(),
        ]);
        Rotation::factory()->create([
            'employee_id' => $leavingSoon->id,
            'start_date' => $saturday->copy()->addDays(3),
            'end_date' => $cutoff->copy()->addWeeks(3),
        ]);
        Rotation::factory()->create([
            'employee_id' => $finishedBeforeSaturday->id,
            'start_date' => $weekStart->copy()->subWeeks(4),
            'end_date' => $saturday->copy()->subDay(),
        ]);
        ProjectAssignment::factory()->create([
            'employee_id' => $onProject->id,
            'start_date' => $saturday->copy(),
            'end_date' => $saturday->copy()->addWeeks(2),
        ]);

        $bench = $service->benchOnDispatchDay($weekStart, $weekEnd);

        $this->assertSame(2, $bench['with_rotation']);
        $this->assertSame(2, $bench['without_rotation']);
    }

    public function test_bench_on_dispatch_day_handles_empty_roster_with_rotations(): void
    {
        $weekStart = Carbon::parse('2020-01-06')->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        Rotation::factory()->create([
            'employee_id' => Employee::factory()->create([
                'hired_at' => $weekStart->copy()->addYears(2),
            ])->id,
            'start_date' => $weekStart->copy()->subWeek(),
            'end_date' => $weekEnd->copy()->addWeeks(4),
        ]);

        $bench = app(WeeklyDashboardKpiService::class)->benchOnDispatchDay($weekStart, $weekEnd);

        $this->assertSame(0, $bench['with_rotation']);
        $this->assertSame(0, $bench['without_rotation']);
        $this->assertCount(0, $bench['with_rotation_people']);
        $this->assertCount(0, $bench['without_rotation_people']);
    }

    public function test_to_send_drops_people_whose_rotation_ends_by_next_wednesday(): void
    {
        $weekStart = Carbon::parse('2026-09-21')->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();
        $nextStart = $weekStart->copy()->addWeek();
        $cutoff = app(WeeklyDashboardKpiService::class)->rotationCutoffAfterWeek($weekEnd);
        $role = Role::factory()->create();
        $project = Project::factory()->create();

        ProjectDemand::factory()->create([
            'project_id' => $project->id,
            'role_id' => $role->id,
            'required_count' => 10,
            'start_date' => $nextStart,
            'end_date' => $nextStart->copy()->endOfWeek(),
        ]);

        $ending = Employee::factory()->create();
        $staying = Employee::factory()->create();
        $alreadyPlaced = Employee::factory()->create();

        foreach ([$ending, $staying, $alreadyPlaced] as $employee) {
            ProjectAssignment::factory()->create([
                'project_id' => $project->id,
                'employee_id' => $employee->id,
                'role_id' => $role->id,
                'start_date' => $nextStart,
                'end_date' => $nextStart->copy()->addWeeks(2),
            ]);
        }

        Rotation::factory()->create([
            'employee_id' => $ending->id,
            'start_date' => $weekStart->copy()->subWeeks(3),
            'end_date' => $cutoff->copy(),
        ]);
        Rotation::factory()->create([
            'employee_id' => $staying->id,
            'start_date' => $weekStart->copy()->subWeek(),
            'end_date' => $cutoff->copy()->addWeeks(4),
        ]);

        $result = app(WeeklyDashboardKpiService::class)->toSendForNextWeek($weekStart, $weekEnd);

        $this->assertSame(10, $result['next_demand']);
        $this->assertSame(2, $result['staying']);
        $this->assertSame(8, $result['to_send']);
    }
}
