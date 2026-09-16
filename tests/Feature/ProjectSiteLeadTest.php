<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectSiteLead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectSiteLeadTest extends TestCase
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

    public function test_can_assign_site_lead_to_project(): void
    {
        $project = Project::factory()->create();
        $employee = Employee::factory()->create();

        $this->from(route('projects.site-leads.create', $project))
            ->post(route('projects.site-leads.store', $project), [
                'employee_id' => $employee->id,
                'start_date' => '2026-03-01',
                'end_date' => '2026-06-30',
            ])
            ->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('project_site_leads', [
            'project_id' => $project->id,
            'employee_id' => $employee->id,
        ]);
        $lead = ProjectSiteLead::query()->first();
        $this->assertSame('2026-03-01', $lead->start_date->toDateString());
        $this->assertSame('2026-06-30', $lead->end_date->toDateString());
    }

    public function test_new_lead_closes_previous_open_ended_lead(): void
    {
        $project = Project::factory()->create();
        $first = Employee::factory()->create();
        $second = Employee::factory()->create();

        ProjectSiteLead::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $first->id,
            'start_date' => '2026-01-01',
            'end_date' => null,
        ]);

        $this->post(route('projects.site-leads.store', $project), [
            'employee_id' => $second->id,
            'start_date' => '2026-03-01',
        ])->assertRedirect(route('projects.show', $project));

        $this->assertSame(
            '2026-02-28',
            ProjectSiteLead::query()->where('employee_id', $first->id)->first()->end_date->toDateString()
        );
        $newLead = ProjectSiteLead::query()->where('employee_id', $second->id)->first();
        $this->assertSame('2026-03-01', $newLead->start_date->toDateString());
        $this->assertNull($newLead->end_date);
        $this->assertDatabaseCount('project_site_leads', 2);
    }

    public function test_rejects_overlapping_future_lead(): void
    {
        $project = Project::factory()->create();
        $first = Employee::factory()->create();
        $second = Employee::factory()->create();

        ProjectSiteLead::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $first->id,
            'start_date' => '2026-06-01',
            'end_date' => null,
        ]);

        $this->from(route('projects.site-leads.create', $project))
            ->post(route('projects.site-leads.store', $project), [
                'employee_id' => $second->id,
                'start_date' => '2026-03-01',
            ])
            ->assertSessionHasErrors('start_date');

        $this->assertDatabaseCount('project_site_leads', 1);
    }

    public function test_does_not_touch_system_project_managers(): void
    {
        $project = Project::factory()->create();
        $project->managers()->attach($this->user->id);
        $employee = Employee::factory()->create();

        $this->post(route('projects.site-leads.store', $project), [
            'employee_id' => $employee->id,
            'start_date' => now()->toDateString(),
        ])->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('project_managers', [
            'project_id' => $project->id,
            'user_id' => $this->user->id,
        ]);
        $this->assertTrue($project->fresh()->managers->contains($this->user));
    }

    public function test_project_show_lists_site_lead(): void
    {
        $project = Project::factory()->create();
        $employee = Employee::factory()->create(['first_name' => 'Jan', 'last_name' => 'Kowalski']);

        ProjectSiteLead::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $employee->id,
            'start_date' => now()->subDay(),
            'end_date' => null,
        ]);

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Kierownik')
            ->assertSee('Jan Kowalski');
    }

    public function test_project_show_empty_state_offers_this_week_crew(): void
    {
        Carbon::setTestNow('2026-04-15');

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $project = Project::factory()->create([
            'start_date' => $weekStart->copy()->subWeek(),
            'end_date' => $weekEnd->copy()->addWeek(),
        ]);
        $onSite = Employee::factory()->create(['first_name' => 'Ewa', 'last_name' => 'Nowak']);
        $role = \App\Models\Role::factory()->create();
        \App\Models\ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $onSite->id,
            'role_id' => $role->id,
            'start_date' => $weekStart->toDateString(),
            'end_date' => $weekEnd->toDateString(),
        ]);

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Brak kierownika. Przypisz kogoś z ekipy w tym tygodniu.')
            ->assertSee('Ewa')
            ->assertSee('Przypisz');

        $this->post(route('projects.site-leads.store', $project), [
            'employee_id' => $onSite->id,
            'start_date' => now()->toDateString(),
        ])->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('project_site_leads', [
            'project_id' => $project->id,
            'employee_id' => $onSite->id,
        ]);

        Carbon::setTestNow();
    }

    public function test_cannot_edit_site_lead_from_another_project(): void
    {
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $lead = ProjectSiteLead::factory()->create([
            'project_id' => $other->id,
            'start_date' => now(),
        ]);

        $this->get(route('projects.site-leads.edit', [$project, $lead]))
            ->assertNotFound();
    }

    public function test_weekly_overview_frames_assigned_site_lead_with_rating(): void
    {
        \Carbon\Carbon::setTestNow('2026-04-15');

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $project = Project::factory()->create([
            'start_date' => $weekStart->copy()->subWeek(),
            'end_date' => $weekEnd->copy()->addWeek(),
        ]);
        $employee = Employee::factory()->create([
            'first_name' => 'Magdalena',
            'last_name' => 'Jaworski',
        ]);
        $role = \App\Models\Role::factory()->create(['name' => 'Elektryk']);

        \App\Models\ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $employee->id,
            'role_id' => $role->id,
            'start_date' => $weekStart->toDateString(),
            'end_date' => $weekEnd->toDateString(),
        ]);
        ProjectSiteLead::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $employee->id,
            'start_date' => $weekStart->toDateString(),
            'end_date' => $weekEnd->toDateString(),
        ]);
        \App\Models\EmployeeEvaluation::query()->create([
            'employee_id' => $employee->id,
            'created_by' => $this->user->id,
            'engagement' => 9,
            'skills' => 9,
            'orderliness' => 8,
            'behavior' => 9,
        ]);

        $this->get(route('weekly-overview.index', [
            'start_date' => $weekStart->toDateString(),
            'project_id' => $project->id,
        ]))
            ->assertOk()
            ->assertSee('is-site-lead', false)
            ->assertSee('Kierownik')
            ->assertSee('Magdalena')
            ->assertSee('8,8')
            ->assertSee('wo-emp-meta', false)
            ->assertDontSee('Brak kierownika. Wyznacz kogoś z ekipy w tym tygodniu.');

        \Carbon\Carbon::setTestNow();
    }

    public function test_weekly_overview_asks_to_assign_lead_when_none(): void
    {
        Carbon::setTestNow('2026-04-15');

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $project = Project::factory()->create([
            'start_date' => $weekStart->copy()->subWeek(),
            'end_date' => $weekEnd->copy()->addWeek(),
        ]);
        $onSite = Employee::factory()->create(['first_name' => 'Tomasz', 'last_name' => 'Lis']);
        $role = \App\Models\Role::factory()->create();
        \App\Models\ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $onSite->id,
            'role_id' => $role->id,
            'start_date' => $weekStart->toDateString(),
            'end_date' => $weekEnd->toDateString(),
        ]);

        $this->get(route('weekly-overview.index', [
            'start_date' => $weekStart->toDateString(),
            'project_id' => $project->id,
        ]))
            ->assertOk()
            ->assertSee('Brak kierownika. Wyznacz kogoś z ekipy w tym tygodniu.')
            ->assertSee('Tomasz')
            ->assertSee('Przypisz')
            ->assertSee('Brak oceny')
            ->assertSee('Brak uprawnień');

        $this->post(route('projects.site-leads.store', $project), [
            'employee_id' => $onSite->id,
            'start_date' => $weekStart->toDateString(),
            'week_start' => $weekStart->toDateString(),
        ])->assertRedirect(route('weekly-overview.index', [
            'start_date' => $weekStart->toDateString(),
            'project_id' => $project->id,
        ]));

        $this->assertDatabaseHas('project_site_leads', [
            'project_id' => $project->id,
            'employee_id' => $onSite->id,
        ]);

        Carbon::setTestNow();
    }

    public function test_weekly_overview_warns_when_lead_is_not_on_the_project(): void
    {
        Carbon::setTestNow('2026-04-15');

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $project = Project::factory()->create([
            'start_date' => $weekStart->copy()->subWeek(),
            'end_date' => $weekEnd->copy()->addWeek(),
        ]);
        $absentLead = Employee::factory()->create(['first_name' => 'Karol', 'last_name' => 'Nieobecny']);
        $onSite = Employee::factory()->create(['first_name' => 'Ola', 'last_name' => 'Obecna']);
        $role = \App\Models\Role::factory()->create();

        ProjectSiteLead::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $absentLead->id,
            'start_date' => $weekStart->copy()->subMonth()->toDateString(),
            'end_date' => null,
        ]);
        \App\Models\ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'employee_id' => $onSite->id,
            'role_id' => $role->id,
            'start_date' => $weekStart->toDateString(),
            'end_date' => $weekEnd->toDateString(),
        ]);

        $this->get(route('weekly-overview.index', [
            'start_date' => $weekStart->toDateString(),
            'project_id' => $project->id,
        ]))
            ->assertOk()
            ->assertSee('Karol Nieobecny')
            ->assertSee('nie ma go w projekcie w tym tygodniu')
            ->assertSee('Ola')
            ->assertSee('Przypisz');

        $this->post(route('projects.site-leads.store', $project), [
            'employee_id' => $onSite->id,
            'start_date' => $weekStart->toDateString(),
            'week_start' => $weekStart->toDateString(),
        ])->assertRedirect(route('weekly-overview.index', [
            'start_date' => $weekStart->toDateString(),
            'project_id' => $project->id,
        ]));

        $this->assertDatabaseHas('project_site_leads', [
            'project_id' => $project->id,
            'employee_id' => $onSite->id,
        ]);
        $this->assertSame(
            $weekStart->copy()->subDay()->toDateString(),
            ProjectSiteLead::query()->where('employee_id', $absentLead->id)->first()->end_date->toDateString()
        );

        Carbon::setTestNow();
    }
}
