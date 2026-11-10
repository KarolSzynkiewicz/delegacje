<?php

namespace Tests\Unit;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Enums\ProjectStatus;
use App\Models\Employee;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\ProjectDemand;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Services\WeeklyOverviewService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WeeklyOverviewBatchLoadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_projects_with_weekly_data_query_count_does_not_scale_with_projects(): void
    {
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();
        $role = Role::factory()->create();

        foreach (range(1, 5) as $i) {
            $project = Project::factory()->create([
                'start_date' => $weekStart->copy()->subWeek(),
                'end_date' => $weekEnd->copy()->addWeek(),
            ]);

            ProjectDemand::factory()->create([
                'project_id' => $project->id,
                'role_id' => $role->id,
                'required_count' => 1,
                'start_date' => $weekStart,
                'end_date' => $weekEnd,
            ]);

            ProjectAssignment::factory()->create([
                'project_id' => $project->id,
                'role_id' => $role->id,
                'start_date' => $weekStart,
                'end_date' => $weekEnd,
            ]);
        }

        $service = app(WeeklyOverviewService::class);
        $weeks = $service->getWeeks($weekStart);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $result = $service->getProjectsWithWeeklyData($weeks);

        $queryCount = count(DB::getQueryLog());

        $this->assertCount(5, $result);
        // Batched path: fixed set of queries (~30), not ~27 × N projects.
        $this->assertLessThan(
            50,
            $queryCount,
            "Expected batched week load under 50 queries, got {$queryCount}"
        );
    }

    public function test_inactive_projects_stay_only_when_the_week_has_people_or_demand(): void
    {
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();
        $dates = [
            'start_date' => $weekStart->copy()->subWeek(),
            'end_date' => $weekEnd->copy()->addWeek(),
        ];
        $role = Role::factory()->create();

        $activeEmpty = Project::factory()->create($dates + ['status' => ProjectStatus::ACTIVE, 'name' => 'Aktywny pusty']);
        $cancelledEmpty = Project::factory()->create($dates + ['status' => ProjectStatus::CANCELLED, 'name' => 'Anulowany pusty']);
        $onHoldWithPerson = Project::factory()->create($dates + ['status' => ProjectStatus::ON_HOLD, 'name' => 'Wstrzymany z osobą']);
        $completedWithDemand = Project::factory()->create($dates + ['status' => ProjectStatus::COMPLETED, 'name' => 'Zakończony z popytem']);

        ProjectAssignment::factory()->create([
            'project_id' => $onHoldWithPerson->id,
            'role_id' => $role->id,
            'start_date' => $weekStart,
            'end_date' => $weekEnd,
        ]);
        ProjectDemand::factory()->create([
            'project_id' => $completedWithDemand->id,
            'role_id' => $role->id,
            'required_count' => 2,
            'start_date' => $weekStart,
            'end_date' => $weekEnd,
        ]);
        ProjectAssignment::factory()->create([
            'project_id' => $cancelledEmpty->id,
            'role_id' => $role->id,
            'start_date' => $weekEnd->copy()->addWeek(),
            'end_date' => $weekEnd->copy()->addWeeks(2),
        ]);

        $service = app(WeeklyOverviewService::class);
        $names = collect($service->getProjectsWithWeeklyData($service->getWeeks($weekStart)))
            ->map(fn (array $row) => $row['project']->name)
            ->all();

        $this->assertEqualsCanonicalizing(
            [$activeEmpty->name, $onHoldWithPerson->name, $completedWithDemand->name],
            $names
        );
    }

    public function test_accommodation_card_data_does_not_query_per_home_or_assignment(): void
    {
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();
        $role = Role::factory()->create();
        $project = Project::factory()->create([
            'start_date' => $weekStart->copy()->subWeek(),
            'end_date' => $weekEnd->copy()->addWeek(),
        ]);

        foreach (range(1, 5) as $i) {
            $employee = \App\Models\Employee::factory()->create();
            $accommodation = \App\Models\Accommodation::factory()->create(['capacity' => 4]);
            \App\Models\AccommodationLease::query()->create([
                'accommodation_id' => $accommodation->id,
                'type' => 'wynajmowany',
                'start_date' => $weekStart->copy()->subMonth()->toDateString(),
                'end_date' => $weekEnd->copy()->addMonth()->toDateString(),
                'monthly_rent' => 400,
                'currency' => 'EUR',
            ]);
            \App\Models\AccommodationAssignment::factory()->create([
                'employee_id' => $employee->id,
                'accommodation_id' => $accommodation->id,
                'start_date' => $weekStart,
                'end_date' => $weekEnd,
            ]);
            ProjectAssignment::factory()->create([
                'project_id' => $project->id,
                'employee_id' => $employee->id,
                'role_id' => $role->id,
                'start_date' => $weekStart,
                'end_date' => $weekEnd,
            ]);
        }

        $service = app(WeeklyOverviewService::class);
        $weeks = $service->getWeeks($weekStart);
        $result = $service->getProjectsWithWeeklyData($weeks);

        DB::flushQueryLog();
        DB::enableQueryLog();

        foreach ($result as $projectData) {
            foreach ($projectData['weeks_data'][0]['accommodations'] as $accommodationData) {
                $accommodation = $accommodationData['accommodation'];
                $this->assertNotNull($accommodation->type);
                $this->assertTrue(
                    $accommodation->lease_end_date === null || $accommodation->lease_end_date instanceof Carbon
                );
                foreach ($accommodationData['assignments'] as $assignment) {
                    $this->assertNotEmpty($assignment->employee->full_name);
                }
            }
        }

        $this->assertSame(
            0,
            count(DB::getQueryLog()),
            'Accommodation type, lease dates and assignment employees should already be eager-loaded'
        );
    }

    public function test_employees_without_project_include_outside_base_without_resources(): void
    {
        $weekStart = Carbon::parse('2026-09-21')->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();
        $base = Location::factory()->create();
        $field = Location::factory()->create();
        $actor = User::factory()->create();

        $outsideNoResources = Employee::factory()->create(['first_name' => 'Poza', 'last_name' => 'Bazą']);
        $withCarNoProject = Employee::factory()->create(['first_name' => 'Auto', 'last_name' => 'BezProjektu']);
        $outsideWithProject = Employee::factory()->create(['first_name' => 'Poza', 'last_name' => 'ZProjektem']);
        $inBaseNoResources = Employee::factory()->create(['first_name' => 'Baza', 'last_name' => 'BezNic']);
        $terminatedOutside = Employee::factory()->create([
            'first_name' => 'Były',
            'last_name' => 'PozaBazą',
            'terminated_at' => $weekStart->copy()->subWeek(),
        ]);

        $departureForOrphan = LogisticsEvent::query()->create([
            'type' => LogisticsEventType::DEPARTURE,
            'event_date' => $weekStart->copy()->subDays(3),
            'end_date' => $weekStart->copy()->subDay(),
            'from_location_id' => $base->id,
            'to_location_id' => $field->id,
            'status' => LogisticsEventStatus::COMPLETED,
            'created_by' => $actor->id,
        ]);
        $departureForOrphan->participants()->create(['employee_id' => $outsideNoResources->id]);

        $departureForTerminated = LogisticsEvent::query()->create([
            'type' => LogisticsEventType::DEPARTURE,
            'event_date' => $weekStart->copy()->subDays(3),
            'end_date' => $weekStart->copy()->subDay(),
            'from_location_id' => $base->id,
            'to_location_id' => $field->id,
            'status' => LogisticsEventStatus::COMPLETED,
            'created_by' => $actor->id,
        ]);
        $departureForTerminated->participants()->create(['employee_id' => $terminatedOutside->id]);

        $departureForAssigned = LogisticsEvent::query()->create([
            'type' => LogisticsEventType::DEPARTURE,
            'event_date' => $weekStart->copy()->subDays(3),
            'end_date' => $weekStart->copy()->subDay(),
            'from_location_id' => $base->id,
            'to_location_id' => $field->id,
            'status' => LogisticsEventStatus::COMPLETED,
            'created_by' => $actor->id,
        ]);
        $departureForAssigned->participants()->create(['employee_id' => $outsideWithProject->id]);

        $role = Role::factory()->create();
        $project = Project::factory()->create([
            'start_date' => $weekStart->copy()->subWeek(),
            'end_date' => $weekEnd->copy()->addWeek(),
        ]);
        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $outsideWithProject->id,
            'role_id' => $role->id,
            'start_date' => $weekStart,
            'end_date' => $weekEnd,
        ]);

        $vehicle = Vehicle::factory()->create();
        VehicleAssignment::factory()->create([
            'vehicle_id' => $vehicle->id,
            'employee_id' => $withCarNoProject->id,
            'start_date' => $weekStart,
            'end_date' => $weekEnd,
            'is_return_trip' => false,
        ]);

        $rows = app(WeeklyOverviewService::class)
            ->getEmployeesWithoutProjectButWithResources($weekStart, $weekEnd)
            ->keyBy(fn (array $row) => $row['employee']->id);

        $this->assertTrue($rows->has($outsideNoResources->id));
        $this->assertTrue($rows[$outsideNoResources->id]['is_outside_base']);
        $this->assertFalse($rows[$outsideNoResources->id]['has_vehicle']);
        $this->assertFalse($rows[$outsideNoResources->id]['has_accommodation']);

        $this->assertTrue($rows->has($withCarNoProject->id));
        $this->assertTrue($rows[$withCarNoProject->id]['has_vehicle']);

        $this->assertFalse($rows->has($outsideWithProject->id));
        $this->assertFalse($rows->has($inBaseNoResources->id));
        $this->assertFalse($rows->has($terminatedOutside->id));
    }
}
