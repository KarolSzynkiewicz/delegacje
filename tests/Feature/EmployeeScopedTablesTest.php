<?php

namespace Tests\Feature;

use App\Livewire\AssignmentsTable;
use App\Livewire\EmployeeTabs;
use App\Livewire\RotationsTable;
use App\Livewire\TimeLogsTable;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Role;
use App\Models\Rotation;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeScopedTablesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create();
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->user->assignRole($adminRole);
        }
        $this->actingAs($this->user);
    }

    public function test_time_logs_index_and_employee_tab_share_the_same_table(): void
    {
        $anna = Employee::factory()->create(['first_name' => 'Anna', 'last_name' => 'Nowak']);
        $bartek = Employee::factory()->create(['first_name' => 'Bartek', 'last_name' => 'Kowalski']);
        $project = Project::factory()->create(['name' => 'Most Północny']);
        $role = Role::factory()->create();

        $annaAssignment = ProjectAssignment::factory()->create([
            'employee_id' => $anna->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
        ]);
        $bartekAssignment = ProjectAssignment::factory()->create([
            'employee_id' => $bartek->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
        ]);

        TimeLog::factory()->create([
            'project_assignment_id' => $annaAssignment->id,
            'notes' => 'wpis-anny',
        ]);
        TimeLog::factory()->create([
            'project_assignment_id' => $bartekAssignment->id,
            'notes' => 'wpis-bartka',
        ]);

        $this->get(route('time-logs.index'))
            ->assertOk()
            ->assertSee('Most Północny')
            ->assertSee('wpis-anny')
            ->assertSee('wpis-bartka');

        Livewire::test(TimeLogsTable::class, ['employeeId' => $anna->id])
            ->assertSee('wpis-anny')
            ->assertDontSee('wpis-bartka')
            ->assertDontSee('Pracownik: wszyscy');

        Livewire::test(EmployeeTabs::class, ['employee' => $anna])
            ->set('activeTab', 'time-logs')
            ->assertSee('wpis-anny')
            ->assertDontSee('wpis-bartka');
    }

    public function test_assignments_table_scopes_to_employee(): void
    {
        $anna = Employee::factory()->create();
        $bartek = Employee::factory()->create();
        $role = Role::factory()->create();
        $annaProject = Project::factory()->create(['name' => 'Hala Anny']);
        $bartekProject = Project::factory()->create(['name' => 'Hala Bartka']);

        ProjectAssignment::factory()->create([
            'employee_id' => $anna->id,
            'project_id' => $annaProject->id,
            'role_id' => $role->id,
        ]);
        ProjectAssignment::factory()->create([
            'employee_id' => $bartek->id,
            'project_id' => $bartekProject->id,
            'role_id' => $role->id,
        ]);

        Livewire::test(AssignmentsTable::class, ['employeeId' => $anna->id])
            ->assertSee('Hala Anny')
            ->assertDontSee('Hala Bartka');
    }

    public function test_rotations_table_scopes_to_employee(): void
    {
        $anna = Employee::factory()->create();
        $bartek = Employee::factory()->create();

        Rotation::factory()->create([
            'employee_id' => $anna->id,
            'notes' => 'rotacja-anny',
        ]);
        Rotation::factory()->create([
            'employee_id' => $bartek->id,
            'notes' => 'rotacja-bartka',
        ]);

        Livewire::test(RotationsTable::class, ['employeeId' => $anna->id])
            ->assertSee('rotacja-anny')
            ->assertDontSee('rotacja-bartka');
    }
}
