<?php

namespace Tests\Feature;

use App\Models\Employee;
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

    public function test_delta_splits_left_arrived_ending_and_arriving(): void
    {
        Carbon::setTestNow('2026-09-30 10:00:00');

        $project = Project::factory()->create(['status' => 'active']);
        $role = Role::factory()->create(['name' => 'Szlifierz']);

        $left = Employee::factory()->create(['first_name' => 'Marek', 'last_name' => 'Lewy']);
        $stayed = Employee::factory()->create(['first_name' => 'Anna', 'last_name' => 'Stala']);
        $arrived = Employee::factory()->create(['first_name' => 'Tomek', 'last_name' => 'Nowy']);
        $ending = Employee::factory()->create(['first_name' => 'Kasia', 'last_name' => 'Koniec']);
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
            'employee_id' => $ending->id,
            'role_id' => $role->id,
            'start_date' => '2026-09-21',
            'end_date' => '2026-10-12',
        ]);
        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $arriving->id,
            'role_id' => $role->id,
            'start_date' => '2026-10-06',
            'end_date' => '2026-10-12',
        ]);

        Rotation::factory()->create([
            'employee_id' => $ending->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-02',
        ]);

        $delta = app(ProjectStaffingDeltaService::class)
            ->forProjectWeek($project, Carbon::parse('2026-09-28'));

        $this->assertTrue($delta['left']->contains(fn (array $row) => $row['employee']->id === $left->id));
        $this->assertTrue($delta['arrived']->contains(fn (array $row) => $row['employee']->id === $arrived->id));
        $this->assertTrue($delta['ending']->contains(fn (array $row) => $row['employee']->id === $ending->id));
        $this->assertTrue($delta['arriving']->contains(fn (array $row) => $row['employee']->id === $arriving->id));
        $this->assertFalse($delta['left']->contains(fn (array $row) => $row['employee']->id === $stayed->id));
        $this->assertFalse($delta['arrived']->contains(fn (array $row) => $row['employee']->id === $stayed->id));
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
            ->assertDontSee('Odeszli ostatnio')
            ->call('openModal')
            ->assertSet('show', true)
            ->assertSee('Zmiany w obsadzie')
            ->assertSee('Odeszli ostatnio')
            ->assertSee('Przybyli')
            ->assertSee('Koniec rotacji')
            ->assertSee('Przyjeżdżają w przyszłym tyg.');
    }
}
