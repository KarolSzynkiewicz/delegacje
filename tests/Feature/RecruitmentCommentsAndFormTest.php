<?php

namespace Tests\Feature;

use App\Enums\RecruitmentContactOutcome;
use App\Enums\RecruitmentStatus;
use App\Enums\TaskStatus;
use App\Enums\WorkItemType;
use App\Livewire\RecruitmentForm;
use App\Livewire\RecruitmentProcessesTable;
use App\Models\ProjectTask;
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
            ->assertSee('Kat. B')
            ->assertDontSee('Komentarze procesu')
            ->assertSeeHtml('rp-attr-pop');
    }

    public function test_quick_language_edit_saves_without_opening_the_identity_form(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');
        $process = $this->createProcess();

        Livewire::actingAs($admin)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->assertSet('editingCandidateIdentity', false)
            ->call('saveLanguages', true, false, true)
            ->assertSet('editingCandidateIdentity', false);

        $candidate = $process->candidate->fresh();
        $this->assertTrue($candidate->speaks_english);
        $this->assertFalse($candidate->speaks_french);
        $this->assertTrue($candidate->speaks_german);
    }

    public function test_process_comments_can_no_longer_be_created(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');
        $process = $this->createProcess();

        $this->actingAs($admin)
            ->post(route('comments.store'), [
                'commentable_type' => 'recruitment_process',
                'commentable_id' => $process->id,
                'body' => 'To już nie powinno powstać.',
            ])
            ->assertNotFound();

        $this->assertSame(0, $process->comments()->count());
    }

    public function test_previous_and_next_follow_the_current_filter(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        $older = $this->createProcess();
        $older->update(['status' => RecruitmentStatus::WTrakcieKontaktu]);
        $older->candidate->forceFill(['created_at' => now()->subDays(3)])->save();

        $skipped = $this->createProcess();
        $skipped->candidate->forceFill(['created_at' => now()->subDays(2)])->save();

        $newer = $this->createProcess();
        $newer->update(['status' => RecruitmentStatus::WTrakcieKontaktu]);
        $newer->candidate->forceFill(['created_at' => now()->subDay()])->save();

        Livewire::actingAs($admin)
            ->test(RecruitmentProcessesTable::class, ['processId' => $older->id])
            ->set('status', RecruitmentStatus::WTrakcieKontaktu->value)
            ->assertSee('2 / 2')
            ->assertSee('Poprzednie')
            ->assertSee('Następne')
            ->assertSeeHtml('id="rp-quick-email"')
            ->assertSeeHtml('id="rp-quick-city"')
            ->call('openListNeighbor', 'prev')
            ->assertRedirect(route('recruitment-processes.show', [
                'recruitmentProcess' => $newer,
                'status' => RecruitmentStatus::WTrakcieKontaktu->value,
            ]));
    }

    public function test_neighbors_stay_when_the_open_record_falls_out_of_the_filter(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        $before = $this->createProcess();
        $before->update(['status' => RecruitmentStatus::WTrakcieKontaktu]);
        $before->candidate->forceFill(['created_at' => now()->subDays(3)])->save();

        $shown = $this->createProcess();
        $shown->candidate->forceFill(['created_at' => now()->subDays(2)])->save();

        $after = $this->createProcess();
        $after->update(['status' => RecruitmentStatus::WTrakcieKontaktu]);
        $after->candidate->forceFill(['created_at' => now()->subDay()])->save();

        Livewire::actingAs($admin)
            ->test(RecruitmentProcessesTable::class, ['processId' => $shown->id])
            ->set('status', RecruitmentStatus::WTrakcieKontaktu->value)
            ->assertSee('poza filtrem')
            ->assertSee('Poprzednie')
            ->assertSee('Następne')
            ->call('openListNeighbor', 'prev')
            ->assertRedirect(route('recruitment-processes.show', [
                'recruitmentProcess' => $after,
                'status' => RecruitmentStatus::WTrakcieKontaktu->value,
            ]));

        Livewire::actingAs($admin)
            ->test(RecruitmentProcessesTable::class, ['processId' => $shown->id])
            ->set('status', RecruitmentStatus::WTrakcieKontaktu->value)
            ->call('openListNeighbor', 'next')
            ->assertRedirect(route('recruitment-processes.show', [
                'recruitmentProcess' => $before,
                'status' => RecruitmentStatus::WTrakcieKontaktu->value,
            ]));
    }

    public function test_left_list_continues_with_the_candidates_after_the_open_one(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        $earlier = $this->createProcess();
        $earlier->update(['status' => RecruitmentStatus::WTrakcieKontaktu]);
        $earlier->candidate->forceFill([
            'first_name' => 'Anna',
            'last_name' => 'Wczesna',
            'created_at' => now(),
        ])->save();

        $open = $this->createProcess();
        $open->update(['status' => RecruitmentStatus::WTrakcieKontaktu]);
        $open->candidate->forceFill([
            'first_name' => 'Beata',
            'last_name' => 'Srodkowa',
            'created_at' => now()->subDay(),
        ])->save();

        $later = $this->createProcess();
        $later->update(['status' => RecruitmentStatus::WTrakcieKontaktu]);
        $later->candidate->forceFill([
            'first_name' => 'Celina',
            'last_name' => 'Pozniejsza',
            'created_at' => now()->subDays(2),
        ])->save();

        $html = Livewire::actingAs($admin)
            ->test(RecruitmentProcessesTable::class, ['processId' => $open->id])
            ->set('status', RecruitmentStatus::WTrakcieKontaktu->value)
            ->html();

        $list = $this->leftListHtml($html);
        $this->assertStringContainsString('Beata', $list);
        $this->assertStringContainsString('Celina', $list);
        $this->assertStringNotContainsString('Anna', $list);
        $this->assertLessThan(strpos($list, 'Celina'), strpos($list, 'Beata'));
    }

    public function test_left_list_continues_after_an_open_record_that_left_the_filter(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        $earlier = $this->createProcess();
        $earlier->update(['status' => RecruitmentStatus::WTrakcieKontaktu]);
        $earlier->candidate->forceFill([
            'first_name' => 'Anna',
            'last_name' => 'Wczesna',
            'created_at' => now(),
        ])->save();

        $open = $this->createProcess();
        $open->candidate->forceFill([
            'first_name' => 'Beata',
            'last_name' => 'Odrzucona',
            'created_at' => now()->subDay(),
        ])->save();

        $later = $this->createProcess();
        $later->update(['status' => RecruitmentStatus::WTrakcieKontaktu]);
        $later->candidate->forceFill([
            'first_name' => 'Celina',
            'last_name' => 'Pozniejsza',
            'created_at' => now()->subDays(2),
        ])->save();

        $html = Livewire::actingAs($admin)
            ->test(RecruitmentProcessesTable::class, ['processId' => $open->id])
            ->set('status', RecruitmentStatus::WTrakcieKontaktu->value)
            ->html();

        $list = $this->leftListHtml($html);
        $this->assertStringContainsString('Beata', $list);
        $this->assertStringContainsString('Celina', $list);
        $this->assertStringNotContainsString('Anna', $list);
    }

    private function leftListHtml(string $html): string
    {
        $start = strpos($html, 'rp-modal-left__list');
        $end = strpos($html, 'rp-modal-left__pager');
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        return substr($html, $start, $end - $start);
    }

    public function test_tasks_sit_above_contact_history_and_distinguish_callbacks_and_meetings(): void
    {
        $admin = User::factory()->create(['name' => 'cursor']);
        $admin->assignRole('administrator');
        $process = $this->createProcess();

        ProjectTask::createIntended(WorkItemType::Callback, [
            'name' => 'Oddzwonić do Jan',
            'description' => 'Ustalić zjazd.',
            'category' => 'Rekrutacja',
            'status' => TaskStatus::PENDING,
            'due_date' => '2026-09-29',
            'assigned_to' => $admin->id,
            'created_by' => $admin->id,
            'recruitment_process_id' => $process->id,
        ]);
        ProjectTask::createIntended(WorkItemType::Meeting, [
            'name' => 'Spotkanie z Jan',
            'description' => 'Rozmowa na miejscu.',
            'category' => 'Rekrutacja',
            'status' => TaskStatus::PENDING,
            'starts_at' => '2026-09-30 10:00:00',
            'assigned_to' => $admin->id,
            'created_by' => $admin->id,
            'recruitment_process_id' => $process->id,
        ]);
        $plain = ProjectTask::createIntended(WorkItemType::Task, [
            'name' => 'Sprawdzić dokumenty',
            'description' => 'Paszport i prawo jazdy.',
            'category' => 'Rekrutacja',
            'status' => TaskStatus::PENDING,
            'due_date' => '2026-10-01',
            'assigned_to' => $admin->id,
            'created_by' => $admin->id,
            'recruitment_process_id' => $process->id,
        ]);

        $html = Livewire::actingAs($admin)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->assertSee('Oddzwonienie')
            ->assertSee('Spotkanie')
            ->assertSee('Ustalić zjazd.')
            ->assertSee('Rozmowa na miejscu.')
            ->assertSee('Paszport i prawo jazdy.')
            ->assertSeeHtml('rp-task--callback')
            ->assertSeeHtml('rp-task--meeting')
            ->assertSeeHtml('rp-task--task')
            ->assertSeeHtml('rp-task__check')
            ->html();

        $this->assertLessThan(
            strpos($html, 'Historia kontaktu'),
            strpos($html, 'rp-doc-section--tasks'),
        );

        Livewire::actingAs($admin)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->call('toggleTaskDone', $plain->id)
            ->assertSeeHtml('rp-task--task is-done');

        $this->assertSame(TaskStatus::COMPLETED, $plain->fresh()->status);
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
