<?php

namespace Tests\Feature;

use App\Enums\ApprovalDecision;
use App\Enums\RecruitmentStatus;
use App\Enums\WorkItemType;
use App\Models\ApprovalRequest;
use App\Models\Comment;
use App\Models\Employee;
use App\Models\EmployeeEvaluation;
use App\Models\ProcedureTemplate;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentLead;
use App\Models\RecruitmentProcess;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkItem;
use App\Notifications\ProcedureWaitElapsed;
use App\Services\ProcedureRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProcedureDomainModelTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $approver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $admin = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        $this->user = User::factory()->create(['name' => 'Karol']);
        $this->approver = User::factory()->create(['name' => 'Anna']);
        $this->user->assignRole($admin);
        $this->approver->assignRole($admin);
        $this->actingAs($this->user);
    }

    public function test_comment_node_writes_attributed_comment_on_employee(): void
    {
        $employee = Employee::factory()->create(['first_name' => 'Jan', 'last_name' => 'Kowalski']);
        $run = $this->startLinearRun([
            'type' => 'comment',
            'name' => 'Notka z rozmowy',
        ], [
            'subject_type' => 'employee',
            'subject_id' => $employee->id,
        ]);

        app(ProcedureRunService::class)->advanceNode($run->fresh(), 'start-1');
        app(ProcedureRunService::class)->advanceNode($run->fresh(), 'step-1', null, [
            'body' => 'Sprawdzone dokumenty.',
        ]);

        $comment = Comment::query()->where('commentable_type', 'employee')->where('commentable_id', $employee->id)->first();
        $this->assertNotNull($comment);
        $this->assertSame('Sprawdzone dokumenty.', $comment->body);
        $this->assertSame($run->id, $comment->procedure_run_id);

        $link = $comment->procedureSourceCard();
        $this->assertNotNull($link);
        $this->assertSame('Playbook', $link['label']);
        $this->assertSame(route('tasks.show', $run->fresh()->task), $link['url']);

        $outcome = $run->fresh()->steps()->where('node_id', 'step-1')->first()?->historyOutcome();
        $this->assertNotNull($outcome);
        $this->assertSame('„Sprawdzone dokumenty.”', $outcome['text']);
        $this->assertNotNull($outcome['url']);
    }

    public function test_wait_node_auto_advances_when_time_elapses_and_notifies(): void
    {
        Notification::fake();

        $run = $this->startLinearRun([
            'type' => 'wait',
            'name' => 'Poczekaj',
            'wait' => ['duration' => 0, 'unit' => 'min'],
            'assigned_user_id' => $this->approver->id,
        ], [
            'assigned_to' => $this->approver->id,
        ]);

        app(ProcedureRunService::class)->advanceNode($run->fresh(), 'start-1');

        $wait = $run->fresh()->steps()->where('node_id', 'step-1')->whereNull('completed_at')->first();
        $this->assertNotNull($wait?->resume_at);
        $this->assertSame(['step-1'], $run->fresh()->activeNodeIds());

        $this->artisan('procedures:resume-waits')->assertSuccessful();

        $this->assertSame('finished', $run->fresh()->status->value);
        $this->assertTrue($run->fresh()->steps()->where('node_id', 'step-1')->whereNotNull('completed_at')->exists());

        Notification::assertSentTo($this->approver, ProcedureWaitElapsed::class);
    }

    public function test_wait_catchup_uses_entered_at_when_resume_at_is_missing(): void
    {
        Notification::fake();

        $run = $this->startLinearRun([
            'type' => 'wait',
            'name' => 'Poczekaj',
            'wait' => ['duration' => 5, 'unit' => 'min'],
            'assigned_user_id' => $this->approver->id,
        ], [
            'assigned_to' => $this->approver->id,
        ]);

        app(ProcedureRunService::class)->advanceNode($run->fresh(), 'start-1');

        $wait = $run->fresh()->steps()->where('node_id', 'step-1')->whereNull('completed_at')->first();
        $wait->update([
            'resume_at' => null,
            'entered_at' => now()->subMinutes(10),
        ]);

        $this->assertSame(1, app(ProcedureRunService::class)->resumeExpiredWaits($run->fresh()));
        $this->assertSame('finished', $run->fresh()->status->value);
        Notification::assertSentTo($this->approver, ProcedureWaitElapsed::class);
    }

    public function test_approval_node_creates_work_item_and_decision_advances_the_run(): void
    {
        $run = $this->startRun($this->approvalDefinition(), ['task_name' => 'Do akceptacji']);

        app(ProcedureRunService::class)->advanceNode($run->fresh(), 'start-1');

        $approval = ApprovalRequest::query()->where('procedure_run_id', $run->id)->first();
        $this->assertNotNull($approval);
        $this->assertSame($this->approver->id, $approval->approver_id);
        $this->assertTrue(
            WorkItem::query()->where('type', WorkItemType::Approval)->where('source_id', $approval->id)->exists()
        );
        $this->assertSame(['approval-1'], $run->fresh()->activeNodeIds());

        $this->actingAs($this->approver);
        $approval->decide(ApprovalDecision::Approved, $this->approver);

        $this->assertSame('finished', $run->fresh()->status->value);
        $this->assertSame(ApprovalDecision::Approved, $approval->fresh()->decision);

        $outcome = $run->fresh()->steps()->where('node_id', 'approval-1')->first()?->historyOutcome();
        $this->assertSame('Zatwierdzone', $outcome['text']);
        $this->assertSame('ok', $outcome['tone']);
    }

    public function test_employee_evaluation_action_creates_the_record(): void
    {
        $employee = Employee::factory()->create();
        $run = $this->startLinearRun([
            'type' => 'action',
            'name' => 'Ocena',
            'action' => 'employee.evaluation',
        ], [
            'subject_type' => 'employee',
            'subject_id' => $employee->id,
        ]);

        app(ProcedureRunService::class)->advanceNode($run->fresh(), 'start-1');
        app(ProcedureRunService::class)->advanceNode($run->fresh(), 'step-1', null, [
            'engagement' => 8,
            'skills' => 7,
            'orderliness' => 6,
            'behavior' => 9,
            'notes' => 'OK',
        ]);

        $evaluation = EmployeeEvaluation::query()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($evaluation);
        $this->assertSame(8, $evaluation->engagement);
        $this->assertSame($this->user->id, $evaluation->created_by);
        $this->assertSame('finished', $run->fresh()->status->value);

        $outcome = $run->fresh()->steps()->where('node_id', 'step-1')->first()?->historyOutcome();
        $this->assertNotNull($outcome);
        $this->assertStringContainsString('Wystaw ocenę', $outcome['text']);
        $this->assertStringContainsString('zaangażowanie 8', $outcome['text']);
        $this->assertStringContainsString('umiejętności 7', $outcome['text']);
        $this->assertStringContainsString('uwagi: OK', $outcome['text']);
    }

    public function test_hire_action_creates_employee_from_candidate(): void
    {
        $role = Role::factory()->create(['name' => 'Monter']);
        $candidate = RecruitmentCandidate::query()->create([
            'first_name' => 'Ewa',
            'last_name' => 'Nowak',
            'phone' => '600111222',
        ]);
        $lead = RecruitmentLead::query()->create(['candidate_id' => $candidate->id]);
        $process = RecruitmentProcess::query()->create([
            'lead_id' => $lead->id,
            'candidate_id' => $candidate->id,
            'status' => RecruitmentStatus::Onboarding,
        ]);

        $run = $this->startLinearRun([
            'type' => 'action',
            'name' => 'Zatrudnij',
            'action' => 'recruitment.hire',
        ], [
            'subject_type' => 'recruitment_process',
            'subject_id' => $process->id,
        ]);

        app(ProcedureRunService::class)->advanceNode($run->fresh(), 'start-1');
        app(ProcedureRunService::class)->advanceNode($run->fresh(), 'step-1', null, [
            'roles' => [$role->id],
        ]);

        $process->refresh();
        $this->assertNotNull($process->employee_id);
        $this->assertSame(RecruitmentStatus::Zatrudniony, $process->status);
        $this->assertSame($process->employee_id, $candidate->fresh()->employee_id);
        $this->assertTrue($process->employee->roles->contains('id', $role->id));

        $outcome = $run->fresh()->steps()->where('node_id', 'step-1')->first()?->historyOutcome();
        $this->assertNotNull($outcome);
        $this->assertStringContainsString('Zatrudnij kandydata', $outcome['text']);
        $this->assertStringContainsString('role: Monter', $outcome['text']);
    }

    public function test_decision_step_history_shows_chosen_option(): void
    {
        $run = $this->startRun([
            'nodes' => [
                ['id' => 'start-1', 'type' => 'start', 'name' => 'Start'],
                [
                    'id' => 'step-1',
                    'type' => 'decision',
                    'name' => 'Iść dalej?',
                    'decision' => [
                        'mode' => 'yesno',
                        'options' => [
                            ['id' => 'yes', 'label' => 'Idziemy dalej'],
                            ['id' => 'no', 'label' => 'Stop'],
                        ],
                    ],
                ],
                ['id' => 'end-1', 'type' => 'end', 'name' => 'Koniec'],
            ],
            'edges' => [
                ['id' => 'e1', 'from' => 'start-1', 'to' => 'step-1'],
                ['id' => 'e-yes', 'from' => 'step-1', 'to' => 'end-1', 'optionId' => 'yes', 'label' => 'Idziemy dalej'],
                ['id' => 'e-no', 'from' => 'step-1', 'to' => 'end-1', 'optionId' => 'no', 'label' => 'Stop'],
            ],
        ], ['task_name' => 'Playbook']);

        app(ProcedureRunService::class)->advanceNode($run->fresh(), 'start-1');
        app(ProcedureRunService::class)->advanceNode($run->fresh(), 'step-1', 'e-yes', [
            'option_id' => 'yes',
            'label' => 'Idziemy dalej',
        ]);

        $outcome = $run->fresh()->steps()->where('node_id', 'step-1')->first()?->historyOutcome();
        $this->assertSame('Wybrano: Idziemy dalej', $outcome['text']);

        $step = $run->fresh()->steps()->where('node_id', 'step-1')->first();
        $frame = $step->historyFrame([
            'name' => 'Iść dalej?',
            'type' => 'decision',
            'icon' => '◆',
            'color' => '#f0a84e',
        ]);
        $this->assertSame('Iść dalej?', $frame['name']);
        $this->assertTrue($frame['show_type']);
        $this->assertSame('Decyzja', $frame['type_label']);
        $this->assertSame('◆', $frame['icon']);
        $this->assertSame('bi-diamond-fill', $frame['bi']);
        $this->assertFalse($step->historyFrame(['name' => 'Decyzja', 'type' => 'decision'])['show_type']);
    }

    /**
     * @param  array<string, mixed>  $step
     * @param  array<string, mixed>  $params
     */
    private function startLinearRun(array $step, array $params = []): \App\Models\ProcedureRun
    {
        $definition = [
            'nodes' => [
                ['id' => 'start-1', 'type' => 'start', 'name' => 'Start'],
                array_merge(['id' => 'step-1', 'name' => 'Krok'], $step),
                ['id' => 'end-1', 'type' => 'end', 'name' => 'Koniec'],
            ],
            'edges' => [
                ['id' => 'e1', 'from' => 'start-1', 'to' => 'step-1'],
                ['id' => 'e2', 'from' => 'step-1', 'to' => 'end-1'],
            ],
        ];

        return $this->startRun($definition, array_merge(['task_name' => 'Playbook'], $params));
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $params
     */
    private function startRun(array $definition, array $params): \App\Models\ProcedureRun
    {
        $template = ProcedureTemplate::query()->create([
            'name' => 'Playbook',
            'subject_type' => $params['subject_type'] ?? null,
            'created_by' => $this->user->id,
            'definition' => $definition,
        ]);

        return app(ProcedureRunService::class)->startRun($template, $params);
    }

    /** @return array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>} */
    private function approvalDefinition(): array
    {
        return [
            'nodes' => [
                ['id' => 'start-1', 'type' => 'start', 'name' => 'Start'],
                [
                    'id' => 'approval-1',
                    'type' => 'approval',
                    'name' => 'Akceptacja stawki',
                    'assigned_user_id' => $this->approver->id,
                    'decision' => [
                        'mode' => 'yesno',
                        'options' => [
                            ['id' => 'approved', 'label' => 'Zatwierdzone'],
                            ['id' => 'rejected', 'label' => 'Odrzucone'],
                        ],
                    ],
                ],
                ['id' => 'end-1', 'type' => 'end', 'name' => 'Koniec'],
            ],
            'edges' => [
                ['id' => 'e1', 'from' => 'start-1', 'to' => 'approval-1'],
                ['id' => 'e-ok', 'from' => 'approval-1', 'to' => 'end-1', 'optionId' => 'approved', 'label' => 'Zatwierdzone'],
                ['id' => 'e-no', 'from' => 'approval-1', 'to' => 'end-1', 'optionId' => 'rejected', 'label' => 'Odrzucone'],
            ],
        ];
    }
}
