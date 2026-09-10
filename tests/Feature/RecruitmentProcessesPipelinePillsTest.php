<?php

namespace Tests\Feature;

use App\Enums\RecruitmentContactOutcome;
use App\Enums\RecruitmentStatus;
use App\Livewire\RecruitmentProcessesTable;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentContactAttempt;
use App\Models\RecruitmentLead;
use App\Models\RecruitmentProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecruitmentProcessesPipelinePillsTest extends TestCase
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

    public function test_pipeline_pills_filter_candidates_by_status_and_toggle_off(): void
    {
        $this->createCandidateWithProcess('Ala', 'NowyUnikatPigułka', RecruitmentStatus::Nowy);
        $this->createCandidateWithProcess('Bartek', 'OnboardUnikatPigułka', RecruitmentStatus::Onboarding);

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class)
            ->assertSee('Nowy')
            ->assertSee('W trakcie kontaktu')
            ->assertSee('Weryfikacja')
            ->assertSee('Onboarding')
            ->assertSee('Zatrudniony')
            ->assertSee('NowyUnikatPigułka')
            ->assertSee('OnboardUnikatPigułka')
            ->call('toggleStatus', RecruitmentStatus::Onboarding->value)
            ->assertSet('status', RecruitmentStatus::Onboarding->value)
            ->assertSee('OnboardUnikatPigułka')
            ->assertDontSee('NowyUnikatPigułka')
            ->call('toggleStatus', RecruitmentStatus::Onboarding->value)
            ->assertSet('status', '')
            ->assertSee('NowyUnikatPigułka')
            ->assertSee('OnboardUnikatPigułka');
    }

    public function test_phone_stat_counts_logged_in_users_calls_from_today_only(): void
    {
        $mine = $this->createCandidateWithProcess('Celina', 'MojTelefonUnikat', RecruitmentStatus::WTrakcieKontaktu);
        $other = $this->createCandidateWithProcess('Dawid', 'CudzyTelefonUnikat', RecruitmentStatus::WTrakcieKontaktu);
        $otherUser = User::factory()->create();

        RecruitmentContactAttempt::create([
            'recruitment_process_id' => $mine->processes()->first()->id,
            'user_id' => $this->user->id,
            'outcome' => RecruitmentContactOutcome::Odebrano,
        ]);
        RecruitmentContactAttempt::create([
            'recruitment_process_id' => $mine->processes()->first()->id,
            'user_id' => $this->user->id,
            'outcome' => RecruitmentContactOutcome::BrakOdpowiedzi,
        ]);
        RecruitmentContactAttempt::create([
            'recruitment_process_id' => $other->processes()->first()->id,
            'user_id' => $otherUser->id,
            'outcome' => RecruitmentContactOutcome::Odebrano,
        ]);

        $yesterday = RecruitmentContactAttempt::create([
            'recruitment_process_id' => $mine->processes()->first()->id,
            'user_id' => $this->user->id,
            'outcome' => RecruitmentContactOutcome::Odebrano,
        ]);
        $yesterday->created_at = now()->subDay();
        $yesterday->save();

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class)
            ->assertSeeHtml('bi-telephone-fill')
            ->assertSee('dziś')
            ->assertViewHas('todayCallCount', 2);
    }

    public function test_process_card_splits_list_and_candidate_with_identity_bar(): void
    {
        $candidate = $this->createCandidateWithProcess('Ewa', 'KartUnikatOnboard', RecruitmentStatus::Onboarding);
        $candidate->update(['available_from' => now()->addWeek()->toDateString()]);
        $processId = $candidate->processes()->first()->id;
        $secondLead = RecruitmentLead::create(['candidate_id' => $candidate->id]);
        $secondProcess = RecruitmentProcess::create([
            'lead_id' => $secondLead->id,
            'candidate_id' => $candidate->id,
            'status' => RecruitmentStatus::Nowy,
        ]);
        RecruitmentContactAttempt::create([
            'recruitment_process_id' => $processId,
            'user_id' => $this->user->id,
            'outcome' => RecruitmentContactOutcome::Odebrano,
        ]);

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $processId])
            ->assertSeeHtml('rp-modal-left__tools')
            ->assertSeeHtml('rp-status-select')
            ->assertSeeHtml('rp-list-hamburger')
            ->assertSeeHtml('rp-modal-left__search')
            ->assertSeeHtml('rp-cand-group')
            ->assertSeeHtml('rp-cand-proc')
            ->assertSee('#'.$processId)
            ->assertSee('#'.$secondProcess->id)
            ->assertSee('Inne procesy tego kandydata')
            ->assertSeeHtml('rp-identity-bar')
            ->assertSeeHtml('rp-modal-center__identity')
            ->assertSeeHtml('rp-modal-center__process')
            ->assertSeeHtml('rp-list-filter-popover')
            ->assertDontSeeHtml('rp-pipeline-pills--topbar')
            ->assertSee('Ost. kontakt')
            ->assertSee('Dodano')
            ->assertSee('Szukaj kandydata…')
            ->assertSee('Wyświetlam')
            ->call('setStatusFilter', RecruitmentStatus::Onboarding->value)
            ->assertSet('status', RecruitmentStatus::Onboarding->value)
            ->call('setStatusFilter', '')
            ->assertSet('status', '');
    }

    private function createCandidateWithProcess(string $firstName, string $lastName, RecruitmentStatus $status): RecruitmentCandidate
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
            'status' => $status,
        ]);

        return $candidate;
    }
}
