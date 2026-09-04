<?php

namespace Tests\Feature;

use App\Enums\RecruitmentStatus;
use App\Livewire\RecruitmentProcessesTable;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentLead;
use App\Models\RecruitmentProcess;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecruitmentProcessesRoleFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create();
        $this->user->assignRole(\Spatie\Permission\Models\Role::where('name', 'administrator')->first());
    }

    public function test_profession_filter_limits_candidates_to_selected_role(): void
    {
        $welder = Role::factory()->create(['name' => 'Spawacz filtr']);
        $electrician = Role::factory()->create(['name' => 'Elektryk filtr']);

        $welderCandidate = $this->createCandidateWithProcess('Aurelia', 'SpawackaUnikat');
        $welderCandidate->roles()->attach($welder->id);

        $electricianCandidate = $this->createCandidateWithProcess('Bogdan', 'ElektrycznyUnikat');
        $electricianCandidate->roles()->attach($electrician->id);

        $this->createCandidateWithProcess('Czeslaw', 'BezProfesjiUnikat');

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class)
            ->assertSee('SpawackaUnikat')
            ->assertSee('ElektrycznyUnikat')
            ->assertSee('BezProfesjiUnikat')
            ->assertSee('Profesja')
            ->set('draftRole', (string) $welder->id)
            ->call('applyDraftFilters')
            ->assertSet('role', (string) $welder->id)
            ->assertSee('SpawackaUnikat')
            ->assertDontSee('ElektrycznyUnikat')
            ->assertDontSee('BezProfesjiUnikat')
            ->assertSee('Profesja: Spawacz filtr');
    }

    public function test_profession_filter_none_shows_candidates_without_roles(): void
    {
        $welder = Role::factory()->create(['name' => 'Spawacz pusty']);
        $withRole = $this->createCandidateWithProcess('Dorota', 'ZProfesjaUnikat');
        $withRole->roles()->attach($welder->id);
        $this->createCandidateWithProcess('Edward', 'PustaProfesjaUnikat');

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class)
            ->set('role', 'none')
            ->assertSee('PustaProfesjaUnikat')
            ->assertDontSee('ZProfesjaUnikat')
            ->assertSee('Bez profesji');
    }

    public function test_clear_filters_removes_profession_filter(): void
    {
        $welder = Role::factory()->create(['name' => 'Spawacz czysc']);
        $welderCandidate = $this->createCandidateWithProcess('Filip', 'CzyscSpawaczUnikat');
        $welderCandidate->roles()->attach($welder->id);
        $this->createCandidateWithProcess('Grazyna', 'CzyscInnyUnikat');

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class)
            ->set('role', (string) $welder->id)
            ->assertDontSee('CzyscInnyUnikat')
            ->call('clearFilters')
            ->assertSet('role', '')
            ->assertSee('CzyscSpawaczUnikat')
            ->assertSee('CzyscInnyUnikat');
    }

    private function createCandidateWithProcess(string $firstName, string $lastName): RecruitmentCandidate
    {
        $candidate = RecruitmentCandidate::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => '600'.random_int(100000, 999999),
        ]);
        $lead = RecruitmentLead::create(['candidate_id' => $candidate->id]);
        RecruitmentProcess::create([
            'lead_id' => $lead->id,
            'candidate_id' => $candidate->id,
            'status' => RecruitmentStatus::Nowy,
        ]);

        return $candidate;
    }
}
