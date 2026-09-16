<?php

namespace Tests\Feature;

use App\Enums\DocumentPlannerIcon;
use App\Enums\RoleSeniority;
use App\Livewire\EmployeeTabs;
use App\Models\Document;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\ProjectDemand;
use App\Models\Role;
use App\Models\User;
use App\Services\WeeklyOverviewService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RoleSeniorityAndPlannerDocumentsTest extends TestCase
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

    public function test_employee_edit_saves_seniority_on_trade(): void
    {
        $painter = Role::factory()->create(['name' => 'Malarz']);
        $fitter = Role::factory()->create(['name' => 'Monter']);
        $employee = Employee::factory()->create();
        $employee->roles()->sync([$painter->id, $fitter->id]);

        $this->put(route('employees.update', $employee), [
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'roles' => [$painter->id, $fitter->id],
            'role_seniority' => [
                $painter->id => 3,
                $fitter->id => 2,
            ],
        ])->assertRedirect(route('employees.show', $employee));

        $employee->refresh()->load('roles');
        $this->assertSame(3, (int) $employee->roles->firstWhere('id', $painter->id)->pivot->seniority);
        $this->assertSame(2, (int) $employee->roles->firstWhere('id', $fitter->id)->pivot->seniority);
        $this->assertSame(RoleSeniority::Independent, $employee->seniorityFor($painter->id));

        $this->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee('Malarz · 3', false)
            ->assertSee('Monter · 2', false);
    }

    public function test_unset_seniority_is_not_level_one(): void
    {
        $role = Role::factory()->create(['name' => 'Spawacz']);
        $employee = Employee::factory()->create();
        $employee->roles()->sync([$role->id]);

        $this->assertNull($employee->fresh()->seniorityFor($role->id));

        $this->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee('Spawacz · ?', false)
            ->assertSee('Nieustalone');
    }

    public function test_livewire_saves_seniority_with_history(): void
    {
        $role = Role::factory()->create(['name' => 'Malarz']);
        $employee = Employee::factory()->create();
        $employee->roles()->sync([$role->id]);

        Livewire::test(EmployeeTabs::class, ['employee' => $employee])
            ->set("seniorityLevels.{$role->id}", '4')
            ->set("seniorityComments.{$role->id}", 'Prowadzi ekipę')
            ->call('saveSeniority', $role->id)
            ->assertHasNoErrors();

        $employee->refresh()->load('roles');
        $this->assertSame(RoleSeniority::Expert, $employee->seniorityFor($role->id));
        $this->assertDatabaseHas('employee_role_seniority_changes', [
            'employee_id' => $employee->id,
            'role_id' => $role->id,
            'from_seniority' => null,
            'to_seniority' => 4,
            'comment' => 'Prowadzi ekipę',
            'changed_by' => $this->user->id,
        ]);
    }

    public function test_planner_shows_seniority_mix_and_valid_document_icons(): void
    {
        Carbon::setTestNow('2026-09-15');
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $role = Role::factory()->create(['name' => 'Malarz']);
        $project = Project::factory()->create([
            'start_date' => $weekStart->copy()->subWeek(),
            'end_date' => $weekEnd->copy()->addWeek(),
        ]);

        $expert = Employee::factory()->create();
        $trainee = Employee::factory()->create();
        $unset = Employee::factory()->create();
        $expert->roles()->sync([$role->id => ['seniority' => 4]]);
        $trainee->roles()->sync([$role->id => ['seniority' => 1]]);
        $unset->roles()->sync([$role->id]);

        foreach ([$expert, $trainee, $unset] as $employee) {
            ProjectAssignment::factory()->create([
                'project_id' => $project->id,
                'employee_id' => $employee->id,
                'role_id' => $role->id,
                'start_date' => $weekStart,
                'end_date' => $weekEnd,
            ]);
        }

        ProjectDemand::factory()->create([
            'project_id' => $project->id,
            'role_id' => $role->id,
            'required_count' => 3,
            'start_date' => $weekStart,
            'end_date' => $weekEnd,
        ]);

        $licence = Document::factory()->create([
            'name' => 'Prawo jazdy B',
            'planner_icon' => DocumentPlannerIcon::DrivingLicence->value,
        ]);
        $expiredType = Document::factory()->create([
            'name' => 'Operator liftów',
            'planner_icon' => DocumentPlannerIcon::Lift->value,
        ]);
        Document::factory()->create([
            'name' => 'Dowód bez ikony',
            'planner_icon' => null,
        ]);

        EmployeeDocument::factory()->create([
            'employee_id' => $expert->id,
            'document_id' => $licence->id,
            'kind' => 'okresowy',
            'valid_from' => '2026-01-01',
            'valid_to' => '2027-01-01',
        ]);
        EmployeeDocument::factory()->create([
            'employee_id' => $trainee->id,
            'document_id' => $expiredType->id,
            'kind' => 'okresowy',
            'valid_from' => '2024-01-01',
            'valid_to' => '2026-01-01',
        ]);

        $weeks = app(WeeklyOverviewService::class)->getWeeks($weekStart);
        $projects = app(WeeklyOverviewService::class)->getProjectsWithWeeklyData($weeks);
        $weekData = collect($projects)->firstWhere(fn ($row) => $row['project']->id === $project->id)['weeks_data'][0];

        $mix = collect($weekData['requirements_summary']['role_details'])
            ->firstWhere(fn ($detail) => $detail['role']->id === $role->id)['seniority_mix'];

        $this->assertSame(1, $mix[4]);
        $this->assertSame(0, $mix[3]);
        $this->assertSame(0, $mix[2]);
        $this->assertSame(1, $mix[1]);
        $this->assertSame(1, $mix[0]);

        $byId = $weekData['assigned_employees']->keyBy(fn ($row) => $row['employee']->id);
        $this->assertSame(RoleSeniority::Expert, $byId[$expert->id]['seniority']);
        $this->assertSame(RoleSeniority::Trainee, $byId[$trainee->id]['seniority']);
        $this->assertNull($byId[$unset->id]['seniority']);

        $this->assertTrue(
            $byId[$expert->id]['planner_documents']->contains(fn ($doc) => $doc->document_id === $licence->id)
        );
        $this->assertTrue($byId[$trainee->id]['planner_documents']->isEmpty());
        $this->assertTrue($byId[$unset->id]['planner_documents']->isEmpty());

        $this->get(route('weekly-overview.index', [
            'start_date' => $weekStart->format('Y-m-d'),
            'project_id' => $project->id,
        ]))
            ->assertOk()
            ->assertSee('role-ribbon', false)
            ->assertSee('Malarz · 4', false)
            ->assertSee('Malarz · 1', false)
            ->assertSee('Malarz · ?', false)
            ->assertSee('wo-demand-chart', false)
            ->assertSee('wo-fulfill-chart', false)
            ->assertSee('3/3', false)
            ->assertSee('4 Ekspert', false)
            ->assertSee('1 Przyuczenie', false)
            ->assertSee('? Nieustalone', false)
            ->assertDontSee('wo-gauge-chart', false);

        Carbon::setTestNow();
    }

    public function test_document_icon_can_be_saved_on_dictionary(): void
    {
        $this->post(route('documents.store'), [
            'name' => 'Prawo jazdy B',
            'description' => 'Kat. B',
            'is_periodic' => '1',
            'is_required' => '1',
            'planner_icon' => DocumentPlannerIcon::DrivingLicence->value,
        ])->assertRedirect(route('documents.index'));

        $this->assertDatabaseHas('documents', [
            'name' => 'Prawo jazdy B',
            'is_required' => 1,
            'planner_icon' => DocumentPlannerIcon::DrivingLicence->value,
        ]);
    }
}
