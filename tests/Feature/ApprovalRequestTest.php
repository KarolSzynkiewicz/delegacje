<?php

namespace Tests\Feature;

use App\Enums\ApprovalDecision;
use App\Enums\WorkItemStatus;
use App\Enums\WorkItemType;
use App\Livewire\ProcedureRunStepper;
use App\Livewire\TasksGrid;
use App\Models\ApprovalRequest;
use App\Models\Comment;
use App\Models\CommentMention;
use App\Models\Employee;
use App\Models\ProcedureTemplate;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Sprint;
use App\Models\User;
use App\Models\WorkItem;
use App\Notifications\ApprovalDecided;
use App\Notifications\ApprovalRequested;
use App\Notifications\CommentMentioned;
use App\Notifications\MentionCompleted;
use App\Notifications\TaskAssigned;
use App\Services\ProcedureRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ApprovalRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create(['name' => 'karol']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->user->assignRole($adminRole);
        }
    }

    public function test_question_mention_creates_approval_not_follow_up_or_mention_ping(): void
    {
        Notification::fake();

        $robert = User::factory()->create(['name' => 'robert']);
        $project = Project::factory()->create();

        $this->actingAs($this->user)
            ->post(route('comments.store'), [
                'commentable_type' => 'project',
                'commentable_id' => $project->id,
                'body' => '@robert? podpisz to',
            ])
            ->assertRedirect();

        $comment = Comment::query()->where('commentable_id', $project->id)->first();
        $this->assertNotNull($comment);
        $this->assertSame(0, CommentMention::query()->count());
        $this->assertSame(0, ProjectTask::query()->count());

        $approval = ApprovalRequest::query()->first();
        $this->assertNotNull($approval);
        $this->assertSame('podpisz to', $approval->name);
        $this->assertNull($approval->description);
        $this->assertSame($robert->id, $approval->approver_id);
        $this->assertSame($this->user->id, $approval->created_by);
        $this->assertSame($comment->id, $approval->comment_id);

        $item = WorkItem::query()->where('type', WorkItemType::Approval)->first();
        $this->assertNotNull($item);
        $this->assertSame($approval->id, $item->source_id);
        $this->assertSame('approval_request', $item->source_type);
        $this->assertSame(WorkItemStatus::Pending, $item->status);
        $this->assertSame(route('approval-requests.show', $approval), $item->openUrl());

        Notification::assertSentTo($robert, ApprovalRequested::class);
        Notification::assertNotSentTo($robert, CommentMentioned::class);
        Notification::assertNotSentTo($robert, TaskAssigned::class);
    }

    public function test_approver_can_decide_and_creator_cannot(): void
    {
        Notification::fake();

        $robert = User::factory()->create(['name' => 'robert']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $robert->assignRole($adminRole);
        }

        $approval = ApprovalRequest::query()->create([
            'name' => 'Faktura',
            'approver_id' => $robert->id,
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('approval-requests.decide', $approval), ['decision' => 'approved'])
            ->assertForbidden();

        $this->actingAs($robert)
            ->get(route('approval-requests.show', $approval))
            ->assertOk()
            ->assertSee('Zatwierdź')
            ->assertSee('Odrzuć');

        $this->actingAs($robert)
            ->post(route('approval-requests.decide', $approval), ['decision' => 'approved'])
            ->assertRedirect(route('approval-requests.show', $approval));

        $approval->refresh();
        $this->assertSame(ApprovalDecision::Approved, $approval->decision);
        $this->assertSame(WorkItemStatus::Completed, $approval->workItem->status);
        Notification::assertSentTo($this->user, ApprovalDecided::class);

        $this->actingAs($robert)
            ->post(route('approval-requests.decide', $approval), ['decision' => 'rejected'])
            ->assertRedirect(route('approval-requests.show', $approval));

        $this->assertSame(ApprovalDecision::Approved, $approval->fresh()->decision);
        $this->assertSame(WorkItemStatus::Completed, $approval->workItem()->first()->status);
    }

    public function test_question_mention_splits_title_and_description_on_double_slash(): void
    {
        $robert = User::factory()->create(['name' => 'robert']);
        $project = Project::factory()->create();

        $this->actingAs($this->user)
            ->post(route('comments.store'), [
                'commentable_type' => 'project',
                'commentable_id' => $project->id,
                'body' => '@robert? czy zgadzasz się na kota?? //plis zgodzisz się? kocham kotki bardzo są takie słodkie',
            ])
            ->assertRedirect();

        $approval = ApprovalRequest::query()->first();
        $this->assertNotNull($approval);
        $this->assertSame('czy zgadzasz się na kota??', $approval->name);
        $this->assertSame('plis zgodzisz się? kocham kotki bardzo są takie słodkie', $approval->description);
    }

    public function test_reject_also_completes_the_work_item(): void
    {
        $robert = User::factory()->create(['name' => 'robert']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $robert->assignRole($adminRole);
        }

        $approval = ApprovalRequest::query()->create([
            'name' => 'Urlop',
            'approver_id' => $robert->id,
            'created_by' => $this->user->id,
            'sprint_id' => Sprint::factory()->create()->id,
            'category' => 'HR',
            'priority' => 4,
            'due_at' => now()->addDay()->toDateString(),
        ]);

        $item = WorkItem::query()->where('type', WorkItemType::Approval)->first();
        $this->assertSame('HR', $item->category);
        $this->assertSame(4, $item->priority);
        $this->assertSame($approval->sprint_id, $item->sprint_id);
        $this->assertSame($approval->due_at?->toDateString(), $item->due_at?->toDateString());

        $this->actingAs($robert)
            ->post(route('approval-requests.decide', $approval), ['decision' => 'rejected']);

        $this->assertSame(ApprovalDecision::Rejected, $approval->fresh()->decision);
        $this->assertSame(WorkItemStatus::Completed, $item->fresh()->status);
    }

    public function test_completing_a_bang_mention_notifies_the_requester(): void
    {
        Notification::fake();

        $robert = User::factory()->create(['name' => 'robert']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $robert->assignRole($adminRole);
        }

        $project = Project::factory()->create();
        $this->actingAs($this->user)
            ->post(route('comments.store'), [
                'commentable_type' => 'project',
                'commentable_id' => $project->id,
                'body' => '@robert! sprawdź to',
            ]);

        $comment = Comment::query()->first();

        $this->actingAs($robert)
            ->post(route('comments.mention-task.toggle', $comment));

        Notification::assertSentTo($this->user, MentionCompleted::class);
    }

    public function test_add_task_from_grid_notifies_assignee(): void
    {
        Notification::fake();

        $robert = User::factory()->create(['name' => 'robert']);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->set('newTaskName', 'DR')
            ->set('newTaskAssignedTo', (string) $robert->id)
            ->call('addTask');

        Notification::assertSentTo($robert, TaskAssigned::class);
        $this->assertSame(1, ProjectTask::query()->where('name', 'DR')->count());
    }

    public function test_starting_a_procedure_notifies_assignee(): void
    {
        Notification::fake();
        $this->actingAs($this->user);

        $robert = User::factory()->create(['name' => 'robert']);
        $template = ProcedureTemplate::query()->create([
            'name' => 'Onboarding',
            'created_by' => $this->user->id,
            'definition' => [
                'nodes' => [
                    ['id' => 'start-1', 'type' => 'start', 'name' => 'Start'],
                ],
                'edges' => [],
            ],
        ]);

        app(ProcedureRunService::class)->startRun($template, [
            'name_suffix' => 'Jan',
            'assigned_to' => $robert->id,
        ]);

        Notification::assertSentTo($robert, TaskAssigned::class);
    }

    public function test_approval_appears_in_grid_as_zatwierdzenie(): void
    {
        ApprovalRequest::query()->create([
            'name' => 'Faktura X',
            'approver_id' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->assertSee('Faktura X')
            ->assertSee('Zatwierdzenie')
            ->assertSee('Poproś o zatwierdzenie')
            ->assertSee('Uruchom procedurę')
            ->assertSeeHtml('bi-hourglass-split');
    }

    public function test_grid_shows_decision_icons_instead_of_completed_for_approvals(): void
    {
        $approved = ApprovalRequest::query()->create([
            'name' => 'Faktura zatwierdzona XYZ',
            'approver_id' => $this->user->id,
            'created_by' => $this->user->id,
        ]);
        $rejected = ApprovalRequest::query()->create([
            'name' => 'Faktura odrzucona XYZ',
            'approver_id' => $this->user->id,
            'created_by' => $this->user->id,
        ]);
        $approved->decide(ApprovalDecision::Approved, $this->user);
        $rejected->decide(ApprovalDecision::Rejected, $this->user);

        $html = Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->set('status', 'all')
            ->assertSee('Faktura zatwierdzona XYZ')
            ->assertSee('Faktura odrzucona XYZ')
            ->assertSee('Zatwierdzone')
            ->assertSee('Odrzucone')
            ->html();

        $this->assertStringContainsString('bi-check-circle-fill', $html);
        $this->assertStringContainsString('bi-slash-circle', $html);
    }

    public function test_approval_show_page_has_a_large_decision_mark(): void
    {
        $approval = ApprovalRequest::query()->create([
            'name' => 'Faktura do podpisu',
            'approver_id' => $this->user->id,
            'created_by' => $this->user->id,
        ]);
        $approval->decide(ApprovalDecision::Approved, $this->user);

        $this->actingAs($this->user)
            ->get(route('approval-requests.show', $approval))
            ->assertOk()
            ->assertSee('Zatwierdzone')
            ->assertSee($this->user->name)
            ->assertDontSee('Decyzja: Zatwierdzone')
            ->assertSee('bi-check-circle-fill', false)
            ->assertSee('Komentarze')
            ->assertSee('Dodaj komentarz');
    }

    public function test_show_page_repeats_what_it_is_about_and_offers_decision_comment(): void
    {
        $approval = ApprovalRequest::query()->create([
            'name' => 'Potwierdzenie zmiany stawki XYZ',
            'description' => 'Sprawdź nową stawkę przed akceptacją',
            'approver_id' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('approval-requests.show', $approval))
            ->assertOk()
            ->assertSee('Potwierdzenie zmiany stawki XYZ')
            ->assertSee('Sprawdź nową stawkę przed akceptacją')
            ->assertSee('Uzasadnienie (opcjonalnie)')
            ->assertSee('Dlaczego zatwierdzasz albo odrzucasz?')
            ->assertSee('Komentarze');
    }

    public function test_procedure_approval_show_lists_the_subject_above_the_decision(): void
    {
        $employee = Employee::factory()->create([
            'first_name' => 'Jan',
            'last_name' => 'KowalskiUnikat',
        ]);

        $template = ProcedureTemplate::query()->create([
            'name' => 'Zmiana stawki Unikat',
            'subject_type' => 'employee',
            'created_by' => $this->user->id,
            'definition' => [
                'nodes' => [
                    ['id' => 'start-1', 'type' => 'start', 'name' => 'Start'],
                    [
                        'id' => 'approval-1',
                        'type' => 'approval',
                        'name' => 'Potwierdzenie zmiany stawki Unikat',
                        'instructions' => 'Instrukcja dla Darii',
                        'assigned_user_id' => $this->user->id,
                    ],
                    ['id' => 'end-1', 'type' => 'end', 'name' => 'Koniec'],
                ],
                'edges' => [
                    ['id' => 'e1', 'from' => 'start-1', 'to' => 'approval-1'],
                    ['id' => 'e-ok', 'from' => 'approval-1', 'to' => 'end-1', 'optionId' => 'approved'],
                    ['id' => 'e-no', 'from' => 'approval-1', 'to' => 'end-1', 'optionId' => 'rejected'],
                ],
            ],
        ]);

        $this->actingAs($this->user);
        $run = app(ProcedureRunService::class)->startRun($template, [
            'subject_id' => $employee->id,
        ]);
        app(ProcedureRunService::class)->advanceNode($run->fresh(), 'start-1');

        $approval = ApprovalRequest::query()->where('procedure_run_id', $run->id)->first();
        $this->assertNotNull($approval);

        $this->actingAs($this->user)
            ->get(route('approval-requests.show', $approval))
            ->assertOk()
            ->assertSee('Potwierdzenie zmiany stawki Unikat')
            ->assertSee('Dotyczy:')
            ->assertSee('KowalskiUnikat')
            ->assertSee('Instrukcja dla Darii')
            ->assertSee('Zmiana stawki Unikat')
            ->assertSee('Komentarze');
    }

    public function test_decision_comment_is_stored_on_the_approval(): void
    {
        $robert = User::factory()->create(['name' => 'robert']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $robert->assignRole($adminRole);
        }

        $approval = ApprovalRequest::query()->create([
            'name' => 'Urlop z uzasadnieniem',
            'approver_id' => $robert->id,
            'created_by' => $this->user->id,
        ]);

        $this->actingAs($robert)
            ->post(route('approval-requests.decide', $approval), [
                'decision' => 'rejected',
                'comment' => 'Za niska stawka',
            ])
            ->assertRedirect(route('approval-requests.show', $approval));

        $this->assertSame(ApprovalDecision::Rejected, $approval->fresh()->decision);
        $this->assertDatabaseHas('comments', [
            'commentable_type' => 'approval_request',
            'commentable_id' => $approval->id,
            'user_id' => $robert->id,
            'body' => 'Odrzucam: Za niska stawka',
        ]);

        $this->actingAs($robert)
            ->get(route('approval-requests.show', $approval))
            ->assertSee('Odrzucam: Za niska stawka')
            ->assertDontSee('Uzasadnienie (opcjonalnie)');
    }

    public function test_procedure_stepper_can_attach_a_decision_comment(): void
    {
        $template = ProcedureTemplate::query()->create([
            'name' => 'Playbook',
            'created_by' => $this->user->id,
            'definition' => [
                'nodes' => [
                    ['id' => 'start-1', 'type' => 'start', 'name' => 'Start'],
                    [
                        'id' => 'approval-1',
                        'type' => 'approval',
                        'name' => 'Akceptacja',
                        'assigned_user_id' => $this->user->id,
                    ],
                    ['id' => 'end-1', 'type' => 'end', 'name' => 'Koniec'],
                ],
                'edges' => [
                    ['id' => 'e1', 'from' => 'start-1', 'to' => 'approval-1'],
                    ['id' => 'e-ok', 'from' => 'approval-1', 'to' => 'end-1', 'optionId' => 'approved'],
                ],
            ],
        ]);

        $this->actingAs($this->user);
        $run = app(ProcedureRunService::class)->startRun($template, ['task_name' => 'Do akceptacji']);
        app(ProcedureRunService::class)->advanceNode($run->fresh(), 'start-1');

        $approval = ApprovalRequest::query()->where('procedure_run_id', $run->id)->first();
        $this->assertNotNull($approval);

        Livewire::actingAs($this->user)
            ->test(ProcedureRunStepper::class, ['run' => $run->fresh()])
            ->assertSee('Uzasadnienie (opcjonalnie)')
            ->set('approvalComments.approval-1', 'Zgadzam się ze stawką')
            ->call('decideApproval', 'approval-1', 'approved');

        $this->assertSame(ApprovalDecision::Approved, $approval->fresh()->decision);
        $this->assertDatabaseHas('comments', [
            'commentable_type' => 'approval_request',
            'commentable_id' => $approval->id,
            'body' => 'Zatwierdzam: Zgadzam się ze stawką',
            'procedure_run_id' => $run->id,
        ]);
    }

    public function test_grid_can_request_approval_from_footer_action(): void
    {
        $robert = User::factory()->create(['name' => 'robert']);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('startAdd', 'approval')
            ->set('newTaskName', 'Podpisz umowę XYZ')
            ->set('newTaskAssignedTo', (string) $robert->id)
            ->call('submitAdd')
            ->assertSee('Prośba o zatwierdzenie wysłana.')
            ->assertSee('Podpisz umowę XYZ');

        $approval = ApprovalRequest::query()->where('name', 'Podpisz umowę XYZ')->first();
        $this->assertNotNull($approval);
        $this->assertSame($robert->id, $approval->approver_id);
        $this->assertSame($this->user->id, $approval->created_by);
    }

    public function test_grid_can_start_procedure_from_footer_action(): void
    {
        $template = ProcedureTemplate::query()->create([
            'name' => 'Onboarding siatka',
            'created_by' => $this->user->id,
            'definition' => [
                'nodes' => [
                    ['id' => 'start-1', 'type' => 'start', 'name' => 'Start'],
                ],
                'edges' => [],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('startAdd', 'procedure')
            ->set('newProcedureTemplateId', (string) $template->id)
            ->set('newProcedureNameSuffix', 'Jan')
            ->call('submitAdd')
            ->assertSee('Procedura uruchomiona.')
            ->assertSee('Onboarding siatka · Jan');

        $this->assertSame(1, WorkItem::query()->where('type', WorkItemType::ProcedureRun)->count());
    }
}
