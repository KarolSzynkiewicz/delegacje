<?php

namespace Tests\Feature;

use App\Enums\RecruitmentContactOutcome;
use App\Enums\RecruitmentStatus;
use App\Livewire\RecruitmentForm;
use App\Livewire\RecruitmentProcessesTable;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentContactAttempt;
use App\Models\RecruitmentLead;
use App\Models\RecruitmentProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecruitmentCommentsAndFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);
    }

    public function test_public_form_accepts_application_without_email(): void
    {
        Livewire::test(RecruitmentForm::class)
            ->set('first_name', 'Anna')
            ->set('last_name', 'Nowak')
            ->set('email', '')
            ->set('phone', '600123456')
            ->set('consent_rodo', true)
            ->set('consent_recruitment_processing', true)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('recruitment_candidates', [
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'phone' => '48600123456',
            'email' => null,
            'has_driving_license_b' => null,
        ]);
    }

    public function test_only_author_can_edit_contact_attempt_comment_even_as_admin(): void
    {
        $author = User::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        $process = $this->createProcess();
        $attempt = RecruitmentContactAttempt::create([
            'recruitment_process_id' => $process->id,
            'user_id' => $author->id,
            'outcome' => RecruitmentContactOutcome::Odebrano,
            'comment' => 'Notatka Emilki',
        ]);

        Livewire::actingAs($admin)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->call('startEditAttempt', $attempt->id)
            ->assertSet('editingAttemptId', null)
            ->set('editingAttemptId', $attempt->id)
            ->set('editAttemptComment', 'Zmiana admina')
            ->call('saveEditAttempt');

        $this->assertSame('Notatka Emilki', $attempt->fresh()->comment);

        Livewire::actingAs($author)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->call('startEditAttempt', $attempt->id)
            ->assertSet('editingAttemptId', $attempt->id)
            ->set('editAttemptComment', 'Moja poprawka')
            ->call('saveEditAttempt')
            ->assertSet('editingAttemptId', null);

        $this->assertSame('Moja poprawka', $attempt->fresh()->comment);
    }

    public function test_admin_cannot_update_someone_elses_comment(): void
    {
        $author = User::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        $process = $this->createProcess();
        $comment = $process->addComment('Komentarz autora', $author);

        $this->actingAs($admin)
            ->put(route('comments.update', $comment), ['body' => 'Podmiana'])
            ->assertForbidden();

        $this->assertSame('Komentarz autora', $comment->fresh()->body);
    }

    public function test_unassigned_recruiter_glows_red_until_someone_is_picked(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');
        $process = $this->createProcess();

        Livewire::actingAs($admin)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->assertSeeHtml('rp-recruiter is-empty')
            ->set('editAssignedRecruiterId', $admin->id)
            ->assertDontSeeHtml('rp-recruiter is-empty');

        $this->assertSame($admin->id, $process->fresh()->assigned_recruiter_id);
    }

    public function test_missing_contact_stage_fields_glow_until_filled_and_comments_sit_below(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');
        $process = $this->createProcess();

        Livewire::actingAs($admin)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->assertSeeHtml('rp-attr is-empty')
            ->assertSeeHtml('rp-profile__comments--below')
            ->assertSee('uzupełnij')
            ->assertSee('Komentarze o kandydacie')
            ->assertSee('Prawko')
            ->assertDontSee('Kategoria')
            ->assertSeeHtml('rp-profile__meta-item is-empty')
            ->assertSeeHtml('rp-profile__phone')
            ->assertDontSeeHtml('rp-skill-chip--empty')
            ->assertSee('Role')
            ->set('editRate', '15')
            ->assertSee('15.00 €/h')
            ->call('setDrivingLicense', false)
            ->assertDontSee('Prawko')
            ->call('setDrivingLicense', true)
            ->assertSee('Kat. B');
    }

    private function createProcess(): RecruitmentProcess
    {
        $candidate = RecruitmentCandidate::create([
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'phone' => '600'.random_int(100000, 999999),
        ]);
        $lead = RecruitmentLead::create(['candidate_id' => $candidate->id]);

        return RecruitmentProcess::create([
            'lead_id' => $lead->id,
            'candidate_id' => $candidate->id,
            'status' => RecruitmentStatus::Nowy,
        ]);
    }
}
