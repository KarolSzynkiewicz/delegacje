<?php

namespace Tests\Feature;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Models\Employee;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Role;
use App\Models\Rotation;
use App\Models\User;
use App\Services\ProjectStaffingDeltaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectStaffingDeltaTest extends TestCase
{
    use RefreshDatabase;

    public function test_delta_splits_left_arrived_leaving_reasons_and_arriving(): void
    {
        Carbon::setTestNow('2026-09-30 10:00:00');

        $project = Project::factory()->create(['status' => 'active']);
        $other = Project::factory()->create(['status' => 'active']);
        $role = Role::factory()->create(['name' => 'Szlifierz']);
        $base = Location::factory()->create();
        $field = Location::factory()->create();
        $actor = User::factory()->create();

        $left = Employee::factory()->create(['first_name' => 'Marek', 'last_name' => 'Lewy']);
        $stayed = Employee::factory()->create(['first_name' => 'Anna', 'last_name' => 'Stala']);
        $arrived = Employee::factory()->create(['first_name' => 'Tomek', 'last_name' => 'Nowy']);
        $rotationEnds = Employee::factory()->create(['first_name' => 'Kasia', 'last_name' => 'Koniec']);
        $noProject = Employee::factory()->create(['first_name' => 'Bartek', 'last_name' => 'Wisi']);
        $moved = Employee::factory()->create(['first_name' => 'Ewa', 'last_name' => 'Przenies']);
        $withReturn = Employee::factory()->create(['first_name' => 'Jan', 'last_name' => 'Zjazd']);
        $arriving = Employee::factory()->create(['first_name' => 'Ola', 'last_name' => 'Jutro']);

        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $left->id,
            'role_id' => $role->id,
            'start_date' => '2026-09-21',
            'end_date' => '2026-09-27',
        ]);
        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $stayed->id,
            'role_id' => $role->id,
            'start_date' => '2026-09-21',
            'end_date' => '2026-10-12',
        ]);
        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $arrived->id,
            'role_id' => $role->id,
            'start_date' => '2026-09-29',
            'end_date' => '2026-10-12',
        ]);
        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $rotationEnds->id,
            'role_id' => $role->id,
            'start_date' => '2026-09-21',
            'end_date' => '2026-10-04',
        ]);
        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $noProject->id,
            'role_id' => $role->id,
            'start_date' => '2026-09-21',
            'end_date' => '2026-10-03',
        ]);
        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $moved->id,
            'role_id' => $role->id,
            'start_date' => '2026-09-21',
            'end_date' => '2026-10-03',
        ]);
        ProjectAssignment::factory()->create([
            'project_id' => $other->id,
            'employee_id' => $moved->id,
            'role_id' => $role->id,
            'start_date' => '2026-10-06',
            'end_date' => '2026-10-12',
        ]);
        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $withReturn->id,
            'role_id' => $role->id,
            'start_date' => '2026-09-21',
            'end_date' => '2026-10-02',
        ]);
        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $arriving->id,
            'role_id' => $role->id,
            'start_date' => '2026-10-06',
            'end_date' => '2026-10-12',
        ]);

        Rotation::factory()->create([
            'employee_id' => $rotationEnds->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-02',
        ]);
        Rotation::factory()->create([
            'employee_id' => $noProject->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-11-30',
        ]);
        Rotation::factory()->create([
            'employee_id' => $moved->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-11-30',
        ]);
        Rotation::factory()->create([
            'employee_id' => $withReturn->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-02',
        ]);

        $return = LogisticsEvent::query()->create([
            'type' => LogisticsEventType::RETURN,
            'event_date' => '2026-10-02',
            'end_date' => '2026-10-04',
            'from_location_id' => $field->id,
            'to_location_id' => $base->id,
            'status' => LogisticsEventStatus::PLANNED,
            'created_by' => $actor->id,
        ]);
        $return->participants()->create(['employee_id' => $withReturn->id]);

        $delta = app(ProjectStaffingDeltaService::class)
            ->forProjectWeek($project, Carbon::parse('2026-09-28'));

        $this->assertTrue($delta['left']->contains(fn (array $row) => $row['employee']->id === $left->id));
        $this->assertTrue($delta['arrived']->contains(fn (array $row) => $row['employee']->id === $arrived->id));
        $this->assertTrue($delta['arriving']->contains(fn (array $row) => $row['employee']->id === $arriving->id));
        $this->assertFalse($delta['left']->contains(fn (array $row) => $row['employee']->id === $stayed->id));
        $this->assertFalse($delta['ending']->contains(fn (array $row) => $row['employee']->id === $stayed->id));

        $endingById = $delta['ending']->keyBy(fn (array $row) => $row['employee']->id);
        $this->assertSame('rotation', $endingById[$rotationEnds->id]['reason']);
        $this->assertSame('assignment', $endingById[$noProject->id]['reason']);
        $this->assertSame('bez projektu', $endingById[$noProject->id]['badge']);
        $this->assertSame('assignment', $endingById[$moved->id]['reason']);
        $this->assertSame('inny projekt', $endingById[$moved->id]['badge']);
        $this->assertSame('return', $endingById[$withReturn->id]['reason']);
        $this->assertSame('zjazd', $endingById[$withReturn->id]['badge']);
        $this->assertCount(4, $delta['ending']);
        $this->assertSame($withReturn->id, $delta['ending'][0]['employee']->id);
        $this->assertSame($rotationEnds->id, $delta['ending'][1]['employee']->id);
        $this->assertSame(
            [$moved->id, $noProject->id],
            $delta['ending']->slice(2)->pluck('employee.id')->all()
        );
    }

    public function test_modal_loads_delta_only_after_open(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['status' => 'active', 'name' => 'Piriou']);

        Livewire::actingAs($user)
            ->test(\App\Livewire\ProjectStaffingDelta::class, [
                'projectId' => $project->id,
                'weekStart' => '2026-09-28',
                'projectName' => 'Piriou',
            ])
            ->assertSet('show', false)
            ->assertDontSee('Zeszły tydzień')
            ->call('openModal')
            ->assertSet('show', true)
            ->assertSee('Zmiany w obsadzie')
            ->assertSee('Odeszli')
            ->assertSee('Przybyli')
            ->assertSee('Nie będzie w przyszłym')
            ->assertSee('Przyjeżdżają')
            ->assertSee('Zeszły tydzień')
            ->assertSee('Ten tydzień')
            ->assertSee('Przyszły tydzień');
    }
}
