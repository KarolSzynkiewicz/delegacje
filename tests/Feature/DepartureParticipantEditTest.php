<?php

namespace Tests\Feature;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Models\Accommodation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\ProjectDemand;
use App\Models\Role;
use App\Models\Rotation;
use App\Models\TimeLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DepartureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DepartureParticipantEditTest extends TestCase
{
    use RefreshDatabase;

    protected DepartureService $service;

    protected Location $base;

    protected Location $field;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->service = app(DepartureService::class);
        $this->base = Location::factory()->create(['is_base' => true, 'name' => 'Baza']);
        $this->field = Location::factory()->create(['is_base' => false, 'name' => 'Budowa']);
    }

    public function test_can_add_participant_with_project_and_accommodation(): void
    {
        $departure = $this->makeDeparture();
        $existing = $this->seedAssignableEmployee('2026-07-01', '2026-07-31');
        $this->addExistingParticipant($departure, $existing['employee'], $existing['role'], $existing['project']);

        $newbie = $this->seedAssignableEmployee('2026-07-01', '2026-07-31');
        $house = Accommodation::factory()->create(['capacity' => 4]);

        $this->service->addParticipant($departure, [
            'employee_id' => $newbie['employee']->id,
            'project_id' => $newbie['project']->id,
            'role_id' => $newbie['role']->id,
            'project_start_date' => '2026-07-10',
            'project_end_date' => '2026-07-31',
            'accommodation_id' => $house->id,
            'accommodation_start_date' => '2026-07-10',
            'accommodation_end_date' => '2026-07-31',
            'ticket_amount' => 120,
            'ticket_currency' => 'PLN',
        ]);

        $this->assertTrue(
            $departure->participants()->where('employee_id', $newbie['employee']->id)->exists()
        );
        $this->assertDatabaseHas('project_assignments', [
            'employee_id' => $newbie['employee']->id,
            'project_id' => $newbie['project']->id,
            'logistics_event_id' => $departure->id,
        ]);
        $this->assertDatabaseHas('accommodation_assignments', [
            'employee_id' => $newbie['employee']->id,
            'accommodation_id' => $house->id,
            'logistics_event_id' => $departure->id,
        ]);
        $this->assertDatabaseHas('transport_costs', [
            'logistics_event_id' => $departure->id,
            'cost_type' => 'ticket',
        ]);
    }

    public function test_cannot_add_the_same_participant_twice(): void
    {
        $departure = $this->makeDeparture();
        $existing = $this->seedAssignableEmployee('2026-07-01', '2026-07-31');
        $this->addExistingParticipant($departure, $existing['employee'], $existing['role'], $existing['project']);

        $this->expectException(ValidationException::class);
        $this->service->addParticipant($departure, [
            'employee_id' => $existing['employee']->id,
            'project_id' => $existing['project']->id,
            'role_id' => $existing['role']->id,
            'project_start_date' => '2026-07-10',
            'project_end_date' => '2026-07-31',
            'ticket_amount' => 50,
            'ticket_currency' => 'PLN',
        ]);
    }

    public function test_can_change_participant_project_when_there_are_no_time_logs(): void
    {
        $departure = $this->makeDeparture();
        $bundle = $this->seedAssignableEmployee('2026-07-01', '2026-07-31');
        $this->addExistingParticipant($departure, $bundle['employee'], $bundle['role'], $bundle['project']);

        $other = Project::factory()->create(['location_id' => $this->field->id]);
        ProjectDemand::create([
            'project_id' => $other->id,
            'role_id' => $bundle['role']->id,
            'required_count' => 2,
            'start_date' => '2026-06-01',
            'end_date' => '2026-12-31',
        ]);

        $this->service->updateParticipantAssignments($departure, $bundle['employee']->id, [
            'project_id' => $other->id,
            'role_id' => $bundle['role']->id,
            'project_start_date' => '2026-07-10',
            'project_end_date' => '2026-07-31',
        ]);

        $this->assertDatabaseHas('project_assignments', [
            'employee_id' => $bundle['employee']->id,
            'project_id' => $other->id,
            'logistics_event_id' => $departure->id,
        ]);
    }

    public function test_time_logs_block_changing_project(): void
    {
        $departure = $this->makeDeparture();
        $bundle = $this->seedAssignableEmployee('2026-07-01', '2026-07-31');
        $assignment = $this->addExistingParticipant($departure, $bundle['employee'], $bundle['role'], $bundle['project']);

        TimeLog::factory()->create([
            'project_assignment_id' => $assignment->id,
            'start_time' => '2026-07-12 08:00:00',
            'end_time' => '2026-07-12 16:00:00',
            'hours_worked' => 8,
        ]);

        $other = Project::factory()->create(['location_id' => $this->field->id]);
        ProjectDemand::create([
            'project_id' => $other->id,
            'role_id' => $bundle['role']->id,
            'required_count' => 2,
            'start_date' => '2026-06-01',
            'end_date' => '2026-12-31',
        ]);

        try {
            $this->service->updateParticipantAssignments($departure, $bundle['employee']->id, [
                'project_id' => $other->id,
                'role_id' => $bundle['role']->id,
                'project_start_date' => '2026-07-10',
                'project_end_date' => '2026-07-31',
            ]);
            $this->fail('Expected time-log lock.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('project_id', $e->errors());
        }
    }

    public function test_create_form_is_reachable_for_an_active_departure(): void
    {
        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->user->assignRole($adminRole);
        }

        $departure = $this->makeDeparture();

        $this->actingAs($this->user)
            ->get(route('departures.participants.create', $departure))
            ->assertOk()
            ->assertSee('Dopisz uczestnika')
            ->assertSee('Szczegóły wyjazdu')
            ->assertSee('Krok 1: Przypisania do projektów')
            ->assertSee('Krok 2: Przypisania do mieszkań')
            ->assertSee('Jakich ludzi brakuje po przyjeździe?');
    }

    public function test_edit_form_reuses_planner_steps_for_that_person(): void
    {
        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->user->assignRole($adminRole);
        }

        $departure = $this->makeDeparture();
        $bundle = $this->seedAssignableEmployee('2026-07-01', '2026-07-31');
        $this->addExistingParticipant($departure, $bundle['employee'], $bundle['role'], $bundle['project']);

        $this->actingAs($this->user)
            ->get(route('departures.participants.edit', [$departure, $bundle['employee']]))
            ->assertOk()
            ->assertSee('Edycja uczestnika wyjazdu')
            ->assertSee('Szczegóły wyjazdu')
            ->assertSee($bundle['employee']->full_name)
            ->assertSee('Krok 1: Przypisania do projektów')
            ->assertSee('Wybierz projekt i rolę');
    }

    public function test_apply_planner_participants_adds_person_with_house(): void
    {
        $departure = $this->makeDeparture();
        $existing = $this->seedAssignableEmployee('2026-07-01', '2026-07-31');
        $this->addExistingParticipant($departure, $existing['employee'], $existing['role'], $existing['project']);

        $newbie = $this->seedAssignableEmployee('2026-07-01', '2026-07-31');
        $house = Accommodation::factory()->create(['capacity' => 4]);

        $this->service->applyPlannerParticipants($departure, [
            'assignment_ranges' => [
                [
                    'employee_id' => $newbie['employee']->id,
                    'project_id' => $newbie['project']->id,
                    'role_id' => $newbie['role']->id,
                    'start_date' => '2026-07-10',
                    'end_date' => '2026-07-31',
                ],
            ],
            'accommodation_assignments' => [
                $newbie['employee']->id => [
                    'accommodation_id' => $house->id,
                    'start_date' => '2026-07-10',
                    'end_date' => '2026-07-31',
                ],
            ],
            'ticket_costs_by_employee' => [
                $newbie['employee']->id => [
                    'amount' => 120,
                    'currency' => 'PLN',
                ],
            ],
        ]);

        $this->assertTrue(
            $departure->participants()->where('employee_id', $newbie['employee']->id)->exists()
        );
        $this->assertDatabaseHas('accommodation_assignments', [
            'employee_id' => $newbie['employee']->id,
            'accommodation_id' => $house->id,
            'logistics_event_id' => $departure->id,
        ]);
    }

    public function test_apply_planner_can_set_added_person_as_driver(): void
    {
        $vehicle = Vehicle::factory()->create(['capacity' => 5]);
        $departure = $this->makeDeparture();
        $departure->update(['vehicle_id' => $vehicle->id]);

        $existing = $this->seedAssignableEmployee('2026-07-01', '2026-07-31');
        $this->addExistingParticipant($departure, $existing['employee'], $existing['role'], $existing['project']);

        $newbie = $this->seedAssignableEmployee('2026-07-01', '2026-07-31');

        $this->service->applyPlannerParticipants($departure, [
            'assignment_ranges' => [
                [
                    'employee_id' => $newbie['employee']->id,
                    'project_id' => $newbie['project']->id,
                    'role_id' => $newbie['role']->id,
                    'start_date' => '2026-07-10',
                    'end_date' => '2026-07-31',
                ],
            ],
            'driver_employee_id' => $newbie['employee']->id,
        ]);

        $this->assertDatabaseHas('adjustments', [
            'logistics_event_id' => $departure->id,
            'employee_id' => $newbie['employee']->id,
            'type' => 'bonus',
        ]);
    }

    /**
     * @return array{employee: Employee, role: Role, project: Project}
     */
    protected function seedAssignableEmployee(string $rotationStart, string $rotationEnd): array
    {
        $role = Role::factory()->create();
        $employee = Employee::factory()->create();
        $employee->roles()->sync([$role->id]);

        $project = Project::factory()->create(['location_id' => $this->field->id]);

        Rotation::create([
            'employee_id' => $employee->id,
            'start_date' => $rotationStart,
            'end_date' => $rotationEnd,
        ]);

        ProjectDemand::create([
            'project_id' => $project->id,
            'role_id' => $role->id,
            'required_count' => 5,
            'start_date' => $rotationStart,
            'end_date' => $rotationEnd,
        ]);

        return compact('employee', 'role', 'project');
    }

    protected function makeDeparture(): LogisticsEvent
    {
        return LogisticsEvent::query()->create([
            'type' => LogisticsEventType::DEPARTURE,
            'event_date' => '2026-07-09',
            'end_date' => '2026-07-10',
            'from_location_id' => $this->base->id,
            'to_location_id' => $this->field->id,
            'status' => LogisticsEventStatus::COMPLETED,
            'created_by' => $this->user->id,
        ]);
    }

    protected function addExistingParticipant(
        LogisticsEvent $departure,
        Employee $employee,
        Role $role,
        Project $project
    ): ProjectAssignment {
        $departure->participants()->create(['employee_id' => $employee->id]);

        return ProjectAssignment::create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => '2026-07-10',
            'end_date' => '2026-07-31',
            'logistics_event_id' => $departure->id,
        ]);
    }
}
