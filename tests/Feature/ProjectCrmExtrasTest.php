<?php

namespace Tests\Feature;

use App\Livewire\ProjectDemandTimeline;
use App\Livewire\ProjectTabs;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Location;
use App\Models\Project;
use App\Models\ProjectDemand;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectCrmExtrasTest extends TestCase
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
    }

    public function test_can_set_project_contact_and_arrangements(): void
    {
        $client = Client::factory()->create();
        $contact = ClientContact::factory()->create([
            'client_id' => $client->id,
            'name' => 'Anna Kowalska',
            'is_active' => true,
        ]);
        $location = Location::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('projects.store'), [
                'location_id' => $location->id,
                'client_id' => $client->id,
                'client_contact_id' => $contact->id,
                'name' => 'Projekt z kontaktem',
                'description' => 'Opis',
                'arrangements' => "BHP + narzędzia\nDojazd firmowy",
                'status' => 'active',
                'type' => 'contract',
                'start_date' => '2026-11-01',
            ]);

        $project = Project::query()->where('name', 'Projekt z kontaktem')->first();
        $this->assertNotNull($project);
        $response->assertRedirect(route('projects.index'));
        $this->assertSame($contact->id, $project->client_contact_id);
        $this->assertStringContainsString('BHP', (string) $project->arrangements);
    }

    public function test_rejects_contact_from_another_client(): void
    {
        $client = Client::factory()->create();
        $other = Client::factory()->create();
        $foreignContact = ClientContact::factory()->create([
            'client_id' => $other->id,
            'is_active' => true,
        ]);
        $location = Location::factory()->create();

        $this->actingAs($this->user)
            ->post(route('projects.store'), [
                'location_id' => $location->id,
                'client_id' => $client->id,
                'client_contact_id' => $foreignContact->id,
                'name' => 'Zły kontakt',
                'status' => 'active',
                'type' => 'contract',
            ])
            ->assertSessionHasErrors('client_contact_id');
    }

    public function test_milestones_and_reports_tab_and_demand_axis(): void
    {
        $project = Project::factory()->create([
            'name' => 'Milestone Project',
            'start_date' => '2026-11-01',
            'end_date' => '2026-12-15',
        ]);
        $role = Role::query()->first() ?? Role::factory()->create(['name' => 'Spawacz']);

        ProjectDemand::query()->create([
            'project_id' => $project->id,
            'role_id' => $role->id,
            'required_count' => 4,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ]);

        Livewire::actingAs($this->user)
            ->test(ProjectTabs::class, ['project' => $project])
            ->assertSee('Raporty z działania')
            ->assertSee('Oś zapotrzebowania')
            ->assertSee('Historia kierownictwa')
            ->set('newMilestoneTitle', 'Kick-off')
            ->call('addMilestone')
            ->assertHasNoErrors()
            ->assertSee('Kick-off');

        $milestone = $project->milestones()->first();
        $this->assertNotNull($milestone);
        $this->assertFalse($milestone->is_done);

        Livewire::actingAs($this->user)
            ->test(ProjectTabs::class, ['project' => $project->fresh()])
            ->call('toggleMilestone', $milestone->id)
            ->assertHasNoErrors();

        $this->assertTrue($milestone->fresh()->is_done);

        $demand = $project->demands()->first();
        $this->assertNotNull($demand);

        Livewire::actingAs($this->user)
            ->test(ProjectDemandTimeline::class, ['project' => $project->fresh()])
            ->assertSee('Oś zapotrzebowania')
            ->assertSee($role->name)
            ->assertSee('numery tygodni')
            ->assertSee('T')
            ->call('propose', $demand->id, '2026-11-10', '2026-11-30')
            ->call('confirm')
            ->assertHasNoErrors();

        $demand->refresh();
        // 10.11.2026 = wtorek → pon. 9.11; 30.11.2026 = poniedziałek → niedz. 6.12
        $this->assertSame('2026-11-09', $demand->start_date->toDateString());
        $this->assertSame('2026-12-06', $demand->end_date->toDateString());

        // Rysowanie nowego zakresu na wolnym torze + ilość w popupie.
        Livewire::actingAs($this->user)
            ->test(ProjectDemandTimeline::class, ['project' => $project->fresh()])
            ->call('proposeDraw', 'role-'.$role->id, '2026-11-01', '2026-11-08')
            ->assertSet('proposal.mode', 'create')
            ->set('draftCount', 2)
            ->call('confirm')
            ->assertHasNoErrors();

        $created = $project->demands()
            ->where('role_id', $role->id)
            ->where('required_count', 2)
            ->first();
        $this->assertNotNull($created);
        $this->assertSame('2026-10-26', $created->start_date->toDateString());
        $this->assertSame('2026-11-08', $created->end_date->toDateString());

        // Agregat: przesunięcie startu projektu z opcją przeniesienia zapotrzebowań.
        $project->refresh();
        $beforeDemandStart = $demand->fresh()->start_date->toDateString();

        Livewire::actingAs($this->user)
            ->test(ProjectDemandTimeline::class, ['project' => $project->fresh()])
            ->assertSee('Razem')
            ->call('proposeBound', 'start', '2026-11-16')
            ->assertSet('boundProposal.edge', 'start')
            ->call('confirmBound', true)
            ->assertHasNoErrors();

        $project->refresh();
        $this->assertSame('2026-11-16', $project->start_date->toDateString());
        // 9.11 → +1 tydzień (start projektu z 1.11/pon. okolice na 16.11)
        $this->assertNotSame($beforeDemandStart, $demand->fresh()->start_date->toDateString());

        Livewire::actingAs($this->user)
            ->test(ProjectDemandTimeline::class, ['project' => $project->fresh()])
            ->call('proposeBound', 'end', '2026-12-27')
            ->assertSet('boundProposal.edge', 'end')
            ->call('confirmBound', false)
            ->assertHasNoErrors();

        // 27.12.2026 = niedziela tygodnia → zapisana jako niedziela końca tygodnia.
        $this->assertSame('2026-12-27', $project->fresh()->end_date->toDateString());
    }
}
