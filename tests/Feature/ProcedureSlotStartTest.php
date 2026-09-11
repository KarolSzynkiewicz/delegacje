<?php

namespace Tests\Feature;

use App\Enums\ProcedureRunStatus;
use App\Enums\RecruitmentStatus;
use App\Livewire\ProcedureRunStepper;
use App\Livewire\ProcedureSlot;
use App\Livewire\RecruitmentProcessesTable;
use App\Models\ProcedureRun;
use App\Models\ProcedureTemplate;
use App\Models\ProjectTask;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentLead;
use App\Models\RecruitmentProcess;
use App\Models\User;
use App\Services\ProcedureRunService;
use App\Services\ProcedureSlotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProcedureSlotStartTest extends TestCase
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

    public function test_onboarding_slot_binds_the_recruitment_process_even_if_template_is_typed_as_candidate(): void
    {
        $process = $this->createOnboardingProcess();
        $template = $this->createTemplate('SLOT ONBOARDING', 'recruitment_candidate');

        app(ProcedureSlotService::class)->bind('recruitment_process.onboarding', $template->id);

        $run = app(ProcedureSlotService::class)->startOrGetRun(
            'recruitment_process.onboarding',
            $process,
        );

        $this->assertSame('recruitment_candidate', $run->subject_type);
        $this->assertSame($process->candidate_id, $run->subject_id);
        $this->assertSame('recruitment_process.onboarding', $run->slot_key);

        $task = ProjectTask::query()->where('procedure_run_id', $run->id)->first();
        $this->assertNotNull($task);
        $this->assertSame($process->id, $task->recruitment_process_id);
    }

    public function test_livewire_slot_start_does_not_look_up_the_process_id_as_a_candidate(): void
    {
        $process = $this->createOnboardingProcess();
        $template = $this->createTemplate('SLOT ONBOARDING', 'recruitment_candidate');

        app(ProcedureSlotService::class)->bind('recruitment_process.onboarding', $template->id);

        Livewire::actingAs($this->user)
            ->test(ProcedureSlot::class, [
                'slotKey' => 'recruitment_process.onboarding',
                'subject' => $process,
                'subjectLabel' => 'Jan Kowalski #'.$process->id,
            ])
            ->call('start');

        $run = ProcedureRun::query()->latest('id')->first();
        $this->assertNotNull($run);
        $this->assertSame('recruitment_candidate', $run->subject_type);
        $this->assertSame($process->candidate_id, $run->subject_id);
    }

    public function test_starting_from_the_grid_still_binds_the_template_subject_record(): void
    {
        $process = $this->createOnboardingProcess();
        $template = $this->createTemplate('SLOT ONBOARDING', 'recruitment_candidate');

        $run = app(ProcedureRunService::class)->startRun($template, [
            'subject_type' => 'recruitment_candidate',
            'subject_id' => $process->candidate_id,
        ]);

        $this->assertSame('recruitment_candidate', $run->subject_type);
        $this->assertSame($process->candidate_id, $run->subject_id);
    }

    public function test_status_change_starts_the_bound_slot_when_it_has_never_run(): void
    {
        $process = $this->createOnboardingProcess(RecruitmentStatus::Nowy);
        $template = $this->createTemplate('SLOT ONBOARDING', 'recruitment_candidate');
        app(ProcedureSlotService::class)->bind('recruitment_process.onboarding', $template->id);

        $process->transitionTo(RecruitmentStatus::Onboarding, $this->user->id);

        $run = ProcedureRun::query()->where('slot_key', 'recruitment_process.onboarding')->first();
        $this->assertNotNull($run);
        $this->assertSame($process->candidate_id, $run->subject_id);
        $this->assertSame('recruitment_candidate', $run->subject_type);
    }

    public function test_status_change_does_not_start_a_slot_that_already_ran(): void
    {
        $process = $this->createOnboardingProcess(RecruitmentStatus::Nowy);
        $template = $this->createTemplate('SLOT ONBOARDING', 'recruitment_candidate');
        app(ProcedureSlotService::class)->bind('recruitment_process.onboarding', $template->id);

        $existing = app(ProcedureSlotService::class)->startOrGetRun(
            'recruitment_process.onboarding',
            $process,
        );
        $existing->update(['status' => ProcedureRunStatus::FINISHED]);

        $process->transitionTo(RecruitmentStatus::Onboarding, $this->user->id);

        $this->assertSame(1, ProcedureRun::query()->where('slot_key', 'recruitment_process.onboarding')->count());
        $this->assertSame($existing->id, ProcedureRun::query()->where('slot_key', 'recruitment_process.onboarding')->value('id'));
    }

    public function test_status_change_without_a_bound_template_does_not_create_a_run(): void
    {
        $process = $this->createOnboardingProcess(RecruitmentStatus::Nowy);

        $process->transitionTo(RecruitmentStatus::Onboarding, $this->user->id);

        $this->assertSame(0, ProcedureRun::query()->count());
        $this->assertSame(RecruitmentStatus::Onboarding, $process->fresh()->status);
    }

    public function test_livewire_status_change_starts_onboarding_slot(): void
    {
        $process = $this->createOnboardingProcess(RecruitmentStatus::Nowy);
        $template = $this->createTemplate('SLOT ONBOARDING', 'recruitment_candidate');
        app(ProcedureSlotService::class)->bind('recruitment_process.onboarding', $template->id);

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class)
            ->call('updateStatus', $process->id, RecruitmentStatus::Onboarding->value);

        $this->assertSame(RecruitmentStatus::Onboarding, $process->fresh()->status);
        $this->assertTrue(
            ProcedureRun::query()
                ->where('slot_key', 'recruitment_process.onboarding')
                ->where('subject_id', $process->id)
                ->exists()
        );
    }

    public function test_procedure_stage_card_puts_the_procedure_in_focus(): void
    {
        $process = $this->createOnboardingProcess();

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->assertSee('Procedura: Onboarding')
            ->assertDontSeeHtml('rp-doc-section--candidate')
            ->assertDontSeeHtml('rp-doc-section--contact');
    }

    public function test_early_stage_card_does_not_collapse_around_a_procedure(): void
    {
        $process = $this->createOnboardingProcess(RecruitmentStatus::Nowy);

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->assertSee('Historia kontaktu')
            ->assertSeeHtml('rp-doc-section--candidate')
            ->assertDontSeeHtml('rp-doc-section--slot');
    }

    public function test_clicking_a_stage_previews_it_without_changing_the_status(): void
    {
        $process = $this->createOnboardingProcess(RecruitmentStatus::WTrakcieKontaktu);
        $template = $this->createTemplate('SLOT ONBOARDING', 'recruitment_candidate');
        app(ProcedureSlotService::class)->bind('recruitment_process.onboarding', $template->id);

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->call('previewStage', RecruitmentStatus::Onboarding->value)
            ->assertSet('reviewStage', RecruitmentStatus::Onboarding->value)
            ->assertSeeHtml('is-reviewed')
            ->assertSeeHtml('bi-eye')
            ->assertSeeHtml('rp-stageflow__next')
            ->assertSeeHtml('rp-stageflow__abort')
            ->assertDontSeeHtml('rp-stageflow__peek')
            ->assertDontSeeHtml('bi-three-dots')
            ->assertSee('SLOT ONBOARDING')
            ->assertSee('Podgląd — procedura uruchomi się, gdy proces dojdzie do tego etapu.')
            ->assertDontSeeHtml('bi-play-fill')
            ->assertDontSeeHtml('rp-doc-section--candidate')
            ->assertDontSeeHtml('rp-doc-section--contact');

        $this->assertSame(RecruitmentStatus::WTrakcieKontaktu, $process->fresh()->status);
        $this->assertSame(0, ProcedureRun::query()->count());
    }

    public function test_preview_cannot_start_an_onboarding_slot_before_the_process_arrives(): void
    {
        $process = $this->createOnboardingProcess(RecruitmentStatus::Nowy);
        $template = $this->createTemplate('SLOT ONBOARDING', 'recruitment_candidate');
        app(ProcedureSlotService::class)->bind('recruitment_process.onboarding', $template->id);

        Livewire::actingAs($this->user)
            ->test(ProcedureSlot::class, [
                'slotKey' => 'recruitment_process.onboarding',
                'subject' => $process,
                'subjectLabel' => 'Jan Kowalski #'.$process->id,
            ])
            ->assertSee('Podgląd — procedura uruchomi się, gdy proces dojdzie do tego etapu.')
            ->assertDontSeeHtml('bi-play-fill')
            ->call('start');

        $this->assertSame(0, ProcedureRun::query()->count());
        $this->assertSame(RecruitmentStatus::Nowy, $process->fresh()->status);
    }

    public function test_previewing_the_current_stage_clears_the_review(): void
    {
        $process = $this->createOnboardingProcess(RecruitmentStatus::WTrakcieKontaktu);

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->call('previewStage', RecruitmentStatus::Zaakceptowany->value)
            ->assertSet('reviewStage', RecruitmentStatus::Zaakceptowany->value)
            ->call('previewStage', RecruitmentStatus::WTrakcieKontaktu->value)
            ->assertSet('reviewStage', '');
    }

    public function test_pipeline_icon_actions_return_from_preview_or_open_rejection(): void
    {
        $process = $this->createOnboardingProcess(RecruitmentStatus::WTrakcieKontaktu);

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->assertSeeHtml('rp-stageflow__next')
            ->assertSeeHtml('rp-stageflow__abort is-reject')
            ->assertDontSeeHtml('bi-eye')
            ->call('previewStage', RecruitmentStatus::Zaakceptowany->value)
            ->assertSeeHtml('bi-eye')
            ->assertDontSeeHtml('rp-stageflow__abort is-reject')
            ->call('resetStageReview')
            ->assertSet('reviewStage', '')
            ->assertSeeHtml('rp-stageflow__abort is-reject')
            ->call('updateStatus', $process->id, RecruitmentStatus::Odrzucony->value)
            ->assertSet('showRejectionPrompt', true);

        $this->assertSame(RecruitmentStatus::WTrakcieKontaktu, $process->fresh()->status);
    }

    public function test_action_bar_moves_the_process_one_stage_at_a_time(): void
    {
        $process = $this->createOnboardingProcess(RecruitmentStatus::Zaakceptowany);

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->call('previewStage', RecruitmentStatus::Nowy->value)
            ->call('advanceStatus')
            ->assertSet('reviewStage', '');

        $this->assertSame(RecruitmentStatus::Onboarding, $process->fresh()->status);
    }

    public function test_action_bar_can_step_the_process_back(): void
    {
        $process = $this->createOnboardingProcess(RecruitmentStatus::Onboarding);

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->call('regressStatus');

        $this->assertSame(RecruitmentStatus::Zaakceptowany, $process->fresh()->status);
    }

    public function test_advancing_from_a_side_exit_reopens_the_funnel(): void
    {
        $process = $this->createOnboardingProcess(RecruitmentStatus::Odrzucony);

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->call('advanceStatus');

        $this->assertSame(RecruitmentStatus::WTrakcieKontaktu, $process->fresh()->status);
    }

    public function test_rejected_stage_shows_the_rejection_reason_instead_of_the_candidate(): void
    {
        $process = $this->createOnboardingProcess(RecruitmentStatus::Odrzucony);
        $process->update([
            'rejection_reason' => \App\Enums\RecruitmentRejectionReason::Stawka,
            'rejection_reason_note' => 'Za niska na ten projekt',
        ]);

        Livewire::actingAs($this->user)
            ->test(RecruitmentProcessesTable::class, ['processId' => $process->id])
            ->assertSee('Powód odrzucenia')
            ->assertSee('Stawka (zbyt niska/wysoka)')
            ->assertSee('Za niska na ten projekt')
            ->assertSee('Historia procesu')
            ->assertSee('Komentarze procesu')
            ->assertDontSeeHtml('rp-doc-section--candidate')
            ->assertDontSeeHtml('rp-doc-section--slot');
    }

    public function test_finishing_the_stepper_dispatches_a_page_reload(): void
    {
        $run = $this->startShortRun();

        Livewire::actingAs($this->user)
            ->test(ProcedureRunStepper::class, ['run' => $run->fresh()])
            ->call('advanceNode', 'step-1')
            ->assertDispatched('procedure-run-updated');

        $this->assertTrue($run->fresh()->status->isTerminal());
    }

    public function test_advancing_a_middle_step_does_not_reload_the_page(): void
    {
        $template = ProcedureTemplate::query()->create([
            'name' => 'Dłuższa',
            'created_by' => $this->user->id,
            'definition' => [
                'nodes' => [
                    ['id' => 'start-1', 'type' => 'start', 'name' => 'Start'],
                    ['id' => 'step-1', 'type' => 'task', 'name' => 'Krok 1'],
                    ['id' => 'step-2', 'type' => 'task', 'name' => 'Krok 2'],
                    ['id' => 'end-1', 'type' => 'end', 'name' => 'Koniec'],
                ],
                'edges' => [
                    ['id' => 'e1', 'from' => 'start-1', 'to' => 'step-1'],
                    ['id' => 'e2', 'from' => 'step-1', 'to' => 'step-2'],
                    ['id' => 'e3', 'from' => 'step-2', 'to' => 'end-1'],
                ],
            ],
        ]);
        $run = app(ProcedureRunService::class)->startRun($template, ['task_name' => 'Dłuższa']);

        Livewire::actingAs($this->user)
            ->test(ProcedureRunStepper::class, ['run' => $run->fresh()])
            ->call('advanceNode', 'step-1')
            ->assertNotDispatched('procedure-run-updated');

        $this->assertSame(['step-2'], $run->fresh()->activeNodeIds());
        $this->assertSame(ProcedureRunStatus::IN_PROGRESS, $run->fresh()->status);
    }

    public function test_start_run_skips_the_start_node(): void
    {
        $run = $this->startShortRun();

        $this->assertSame(['step-1'], $run->activeNodeIds());
        $this->assertFalse($run->isParkedOnStart());
        $this->assertTrue(
            $run->steps()->where('node_id', 'start-1')->whereNotNull('completed_at')->exists()
        );
    }

    public function test_stepper_leaves_start_when_parked_on_start(): void
    {
        $run = $this->parkOnStart($this->startShortRun());

        $this->assertTrue($run->needsBegin());

        Livewire::actingAs($this->user)
            ->test(ProcedureRunStepper::class, ['run' => $run])
            ->assertDontSee('Rozpocznij')
            ->assertSee('Krok');

        $this->assertSame(['step-1'], $run->fresh()->activeNodeIds());
        $this->assertFalse($run->fresh()->needsBegin());
    }

    public function test_stepper_recovers_a_run_with_empty_active_nodes(): void
    {
        $run = $this->stripActiveNodes($this->startShortRun());

        $this->assertTrue($run->needsBegin());
        $this->assertSame([], $run->activeNodeIds());

        Livewire::actingAs($this->user)
            ->test(ProcedureRunStepper::class, ['run' => $run])
            ->assertSee('Krok')
            ->assertDontSee('Rozpocznij');

        $this->assertSame(['step-1'], $run->fresh()->activeNodeIds());
        $this->assertFalse($run->fresh()->needsBegin());
    }

    private function startShortRun(): ProcedureRun
    {
        $template = ProcedureTemplate::query()->create([
            'name' => 'Krótka',
            'created_by' => $this->user->id,
            'definition' => [
                'nodes' => [
                    ['id' => 'start-1', 'type' => 'start', 'name' => 'Start'],
                    ['id' => 'step-1', 'type' => 'task', 'name' => 'Krok'],
                    ['id' => 'end-1', 'type' => 'end', 'name' => 'Koniec'],
                ],
                'edges' => [
                    ['id' => 'e1', 'from' => 'start-1', 'to' => 'step-1'],
                    ['id' => 'e2', 'from' => 'step-1', 'to' => 'end-1'],
                ],
            ],
        ]);

        return app(ProcedureRunService::class)->startRun($template, ['task_name' => 'Krótka']);
    }

    private function createOnboardingProcess(RecruitmentStatus $status = RecruitmentStatus::Onboarding): RecruitmentProcess
    {
        $candidate = RecruitmentCandidate::query()->create([
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'phone' => '600111222',
        ]);
        $lead = RecruitmentLead::query()->create(['candidate_id' => $candidate->id]);

        return RecruitmentProcess::query()->create([
            'lead_id' => $lead->id,
            'candidate_id' => $candidate->id,
            'status' => $status,
        ]);
    }

    private function createTemplate(string $name, string $subjectType): ProcedureTemplate
    {
        return ProcedureTemplate::query()->create([
            'name' => $name,
            'subject_type' => $subjectType,
            'created_by' => $this->user->id,
            'definition' => [
                'nodes' => [
                    ['id' => 'start-1', 'type' => 'start', 'name' => 'Start'],
                    ['id' => 'step-1', 'type' => 'task', 'name' => 'Krok'],
                    ['id' => 'end-1', 'type' => 'end', 'name' => 'Koniec'],
                ],
                'edges' => [
                    ['id' => 'e1', 'from' => 'start-1', 'to' => 'step-1'],
                    ['id' => 'e2', 'from' => 'step-1', 'to' => 'end-1'],
                ],
            ],
        ]);
    }

    private function parkOnStart(ProcedureRun $run): ProcedureRun
    {
        $startId = collect($run->definition()['nodes'] ?? [])
            ->first(fn (array $node) => ($node['type'] ?? '') === 'start')['id'] ?? 'start-1';

        $run->steps()->where('node_id', '!=', $startId)->delete();
        $run->steps()->where('node_id', $startId)->update(['completed_at' => null]);
        $run->update([
            'status' => ProcedureRunStatus::IN_PROGRESS,
            'finished_at' => null,
            'active_node_ids' => [$startId],
            'path' => [$startId],
            'join_tokens' => [],
        ]);

        $run->task?->markInProgress();

        return $run->fresh()->load(['steps.approvalRequest.approver', 'steps.performedBy', 'task', 'subject', 'version']);
    }

    private function stripActiveNodes(ProcedureRun $run): ProcedureRun
    {
        $run->steps()->whereNull('completed_at')->delete();
        $run->update([
            'active_node_ids' => [],
            'join_tokens' => [],
        ]);

        return $run->fresh()->load(['steps.approvalRequest.approver', 'steps.performedBy', 'task', 'subject', 'version']);
    }
}
