<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Enums\WorkItemStatus;
use App\Enums\WorkItemType;
use App\Livewire\TasksGrid;
use App\Livewire\TaskSubtasks;
use App\Models\ProcedureTemplate;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Sprint;
use App\Models\TaskSubtask;
use App\Models\User;
use App\Models\WarehouseDispatch;
use App\Models\WorkItem;
use App\Notifications\TaskAssigned;
use App\Services\ProcedureRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class WorkItemBacklogTest extends TestCase
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

    public function test_creating_a_task_indexes_a_work_item(): void
    {
        $this->actingAs($this->user);

        $task = ProjectTask::query()->create([
            'name' => 'DR do Berlina',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        $item = WorkItem::query()->where('source_type', 'project_task')->where('source_id', $task->id)->first();

        $this->assertNotNull($item);
        $this->assertSame(WorkItemType::Task, $item->type);
        $this->assertSame('DR do Berlina', $item->title);
        $this->assertSame($this->user->id, $item->assignee_id);
        $this->assertSame(WorkItemStatus::Pending, $item->status);
    }

    public function test_procedure_run_indexes_as_procedure_not_as_task(): void
    {
        $this->actingAs($this->user);

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

        $run = app(ProcedureRunService::class)->startRun($template, [
            'task_name' => 'Onboarding Jan',
            'assigned_to' => $this->user->id,
        ]);

        $this->assertSame(1, WorkItem::query()->where('type', WorkItemType::ProcedureRun)->count());
        $this->assertSame(0, WorkItem::query()->where('source_type', 'project_task')->where('source_id', $run->task->id)->count());

        $item = WorkItem::query()->where('type', WorkItemType::ProcedureRun)->first();
        $this->assertSame('Onboarding Jan', $item->title);
        $this->assertSame($this->user->id, $item->assignee_id);
        $this->assertSame($run->id, $item->source_id);
        $this->assertFalse($item->expandable());
        $this->assertSame('W trakcie', $item->statusLabel());
    }

    public function test_procedure_and_mention_are_one_liners_with_task_status_pills(): void
    {
        $this->actingAs($this->user);

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
            'task_name' => 'Onboarding Jan',
            'assigned_to' => $this->user->id,
        ]);
        $procedureItem = WorkItem::query()->where('type', WorkItemType::ProcedureRun)->first();

        $robert = User::factory()->create(['name' => 'robert']);
        $project = Project::factory()->create();
        $this->post(route('comments.store'), [
            'commentable_type' => 'project',
            'commentable_id' => $project->id,
            'body' => '@robert! sprawdź to',
        ]);
        $mentionItem = WorkItem::query()->where('type', WorkItemType::FollowUp)->first();

        $this->assertNotNull($procedureItem);
        $this->assertNotNull($mentionItem);
        $this->assertFalse($procedureItem->expandable());
        $this->assertFalse($mentionItem->expandable());
        $this->assertSame('comment_mention', $mentionItem->source_type);
        $this->assertStringContainsString('#comment-', $mentionItem->openUrl());
        $this->assertStringContainsString(route('projects.show', $project), $mentionItem->openUrl());
        $this->assertSame(route('projects.show', $project), $mentionItem->sourceCard()['url']);
        $this->assertSame($project->name, $mentionItem->sourceCard()['label']);
        $this->assertSame(\App\WorkItems\StatusWidget::BinarySelect, $mentionItem->statusWidget());

        $html = Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->assertSee('Onboarding Jan')
            ->assertSee('sprawdź to')
            ->assertSee('Wzmianka')
            ->assertSee('Oczekujące')
            ->assertDontSee('Zakończona')
            ->html();

        $this->assertStringNotContainsString("toggleExpand({$procedureItem->id})", $html);
        $this->assertStringNotContainsString("toggleExpand({$mentionItem->id})", $html);
        $this->assertStringContainsString('tg-status-badge', $html);
        $this->assertStringNotContainsString("quickStatusChange({$procedureItem->id}", $html);
        $this->assertStringContainsString("quickStatusChange({$mentionItem->id}, 'pending')", $html);
        $this->assertStringContainsString("quickStatusChange({$mentionItem->id}, 'completed')", $html);
        $this->assertStringNotContainsString("quickStatusChange({$mentionItem->id}, 'in_progress')", $html);
        $this->assertStringNotContainsString("quickStatusChange({$mentionItem->id}, 'cancelled')", $html);
        $this->assertStringContainsString(route('projects.show', $project).'#comment-', $html);
        $this->assertStringContainsString('bi-folder', $html);
    }

    public function test_grid_completes_a_mention_without_creating_a_task(): void
    {
        $this->actingAs($this->user);

        $robert = User::factory()->create(['name' => 'robert']);
        $project = Project::factory()->create();
        $this->post(route('comments.store'), [
            'commentable_type' => 'project',
            'commentable_id' => $project->id,
            'body' => '@robert! sprawdź to',
        ]);

        $item = WorkItem::query()->where('type', WorkItemType::FollowUp)->first();
        $this->assertNotNull($item);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('quickStatusChange', $item->id, 'completed');

        $this->assertSame(WorkItemStatus::Completed, $item->fresh()->status);
        $this->assertSame(0, ProjectTask::query()->where('subject_type', 'comment')->count());
        $this->assertTrue($item->source->fresh()->isCompleted());
    }

    public function test_callback_task_is_typed_separately_and_hidden_by_default(): void
    {
        $this->actingAs($this->user);

        ProjectTask::query()->create([
            'name' => 'Oddzwonić do Jan Kowalski #12',
            'category' => 'Rekrutacja',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        $item = WorkItem::query()->first();
        $this->assertSame(WorkItemType::Callback, $item->type);
        $this->assertFalse($item->expandable());
        $this->assertSame(\App\WorkItems\StatusWidget::BinarySelect, $item->statusWidget());

        $html = Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->assertDontSee('Oddzwonić do Jan Kowalski #12')
            ->call('toggleType', WorkItemType::Callback->value)
            ->assertSee('Oddzwonić do Jan Kowalski #12')
            ->assertSee('Oddzwonienie')
            ->assertSee('Oczekujące')
            ->html();

        $this->assertStringContainsString('tg-status-badge', $html);
        $this->assertStringContainsString("quickStatusChange({$item->id}, 'pending')", $html);
        $this->assertStringContainsString("quickStatusChange({$item->id}, 'completed')", $html);
        $this->assertStringNotContainsString("quickStatusChange({$item->id}, 'in_progress')", $html);
        $this->assertStringNotContainsString("quickStatusChange({$item->id}, 'cancelled')", $html);
        $this->assertStringNotContainsString("toggleExpand({$item->id})", $html);
    }

    public function test_callback_show_page_is_not_a_task_workspace(): void
    {
        $this->actingAs($this->user);

        $candidate = \App\Models\RecruitmentCandidate::query()->create([
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'email' => 'jan.kowalski@example.test',
        ]);
        $lead = \App\Models\RecruitmentLead::query()->create([
            'candidate_id' => $candidate->id,
        ]);
        $process = \App\Models\RecruitmentProcess::query()->create([
            'lead_id' => $lead->id,
            'candidate_id' => $candidate->id,
            'status' => \App\Enums\RecruitmentStatus::WTrakcieKontaktu,
            'assigned_recruiter_id' => $this->user->id,
        ]);

        $task = ProjectTask::query()->create([
            'name' => 'Oddzwonić do Jan Kowalski #'.$process->id,
            'description' => 'Po 17:00',
            'category' => 'Rekrutacja',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
            'recruitment_process_id' => $process->id,
            'due_date' => now()->addDay()->toDateString(),
        ]);

        $this->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('Oddzwonienie')
            ->assertSee('zlecił Ci oddzwonienie')
            ->assertSee('Jan Kowalski')
            ->assertSee('prosi o kontakt')
            ->assertSee('Po 17:00')
            ->assertSee('Oznacz jako zrobione')
            ->assertSee('Notatka na karcie kandydata')
            ->assertDontSeeLivewire(TaskSubtasks::class)
            ->assertDontSee('Dziennik operacyjny')
            ->assertDontSee('Wpisz nazwę podzadania')
            ->assertDontSee('Rozpocznij');

        $this->from(route('tasks.show', $task))
            ->post(route('comments.store'), [
                'commentable_type' => 'recruitment_process',
                'commentable_id' => $process->id,
                'body' => 'Oddzwoniłem, oddzwoni jutro.',
            ])
            ->assertRedirect();

        $this->assertSame(0, $task->comments()->count());
        $this->assertSame(1, $process->comments()->count());
        $this->assertSame('Oddzwoniłem, oddzwoni jutro.', $process->comments()->first()->body);

        $this->from(route('tasks.show', $task))
            ->post(route('tasks.toggle-done', $task))
            ->assertRedirect();

        $this->assertSame(TaskStatus::COMPLETED, $task->fresh()->status);
    }

    public function test_subtask_mention_assigns_instead_of_cloning_a_task(): void
    {
        Notification::fake();

        $robert = User::factory()->create(['name' => 'robert']);
        $parent = ProjectTask::query()->create([
            'name' => 'Przygotowanie wyjazdu',
            'status' => TaskStatus::PENDING,
            'created_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(TaskSubtasks::class, ['task' => $parent])
            ->set('newSubtaskName', '@robert weź klucze')
            ->call('addSubtask');

        $subtask = TaskSubtask::query()->where('task_id', $parent->id)->first();
        $this->assertSame($robert->id, $subtask->assigned_to);
        $this->assertSame('weź klucze', $subtask->name);
        $this->assertSame(0, ProjectTask::query()->where('subject_type', 'task_subtask')->count());

        $item = WorkItem::query()->where('type', WorkItemType::Subtask)->first();
        $this->assertNotNull($item);
        $this->assertSame($robert->id, $item->assignee_id);
        $this->assertSame('weź klucze', $item->title);

        Notification::assertSentTo($robert, TaskAssigned::class, function (TaskAssigned $notification) use ($robert, $parent): bool {
            $data = $notification->toDatabase($robert);

            return $data['task_url'] === route('tasks.show', $parent)
                && str_contains((string) $data['excerpt'], 'weź klucze');
        });
    }

    public function test_completing_assigned_subtask_closes_work_item(): void
    {
        $robert = User::factory()->create(['name' => 'robert']);
        $parent = ProjectTask::query()->create([
            'name' => 'Przygotowanie wyjazdu',
            'status' => TaskStatus::PENDING,
            'created_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(TaskSubtasks::class, ['task' => $parent])
            ->set('newSubtaskName', '@robert weź klucze')
            ->call('addSubtask');

        $subtask = TaskSubtask::query()->where('task_id', $parent->id)->first();

        Livewire::actingAs($this->user)
            ->test(TaskSubtasks::class, ['task' => $parent])
            ->call('toggleSubtask', $subtask->id);

        $this->assertTrue($subtask->fresh()->is_completed);
        $this->assertSame(WorkItemStatus::Completed, WorkItem::query()->where('type', WorkItemType::Subtask)->first()->status);
    }

    public function test_tasks2_lists_the_backlog(): void
    {
        $this->actingAs($this->user);

        ProjectTask::query()->create([
            'name' => 'DR do Berlina',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        $this->get(route('tasks.grid'))
            ->assertOk()
            ->assertSee('Zadania')
            ->assertSee('DR do Berlina')
            ->assertSeeLivewire(TasksGrid::class);
    }

    public function test_tasks2_shows_the_type_column(): void
    {
        $this->actingAs($this->user);

        ProjectTask::query()->create([
            'name' => 'DR do Berlina',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        $item = WorkItem::query()->where('type', WorkItemType::Task)->first();

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->assertSee('Typ')
            ->assertSee('Zadanie')
            ->assertSeeHtml('bi-check2-square')
            ->assertSeeHtml("toggleExpand({$item->id})")
            ->assertSeeHtml('href="'.e(route('tasks.show', $item->source_id)).'"')
            ->assertDontSeeHtml('title="Podgląd"')
            ->assertDontSeeHtml('>Akcje</th>');
    }

    public function test_tasks2_render_query_count_does_not_scale_with_row_count(): void
    {
        // Regression guard for the N+1 where every writable/relocatable check on a row
        // re-fetched the WorkItem from the DB by id instead of reusing the already
        // eager-loaded row object (canEditRow() called once per editable column, per row).
        $this->actingAs($this->user);

        $makeTasks = function (int $count, string $prefix): void {
            for ($i = 0; $i < $count; $i++) {
                ProjectTask::query()->create([
                    'name' => "{$prefix} {$i}",
                    'status' => TaskStatus::PENDING,
                    'assigned_to' => $this->user->id,
                    'created_by' => $this->user->id,
                ]);
            }
        };

        $makeTasks(3, 'Small batch');

        \Illuminate\Support\Facades\DB::flushQueryLog();
        \Illuminate\Support\Facades\DB::enableQueryLog();
        Livewire::actingAs($this->user)->test(TasksGrid::class);
        $smallRenderQueries = count(\Illuminate\Support\Facades\DB::getQueryLog());

        // Row creation triggers its own observers/queries (audit log, WorkItem sync, …) —
        // flush those out so only the render() call itself is measured below.
        $makeTasks(15, 'Big batch');
        \Illuminate\Support\Facades\DB::flushQueryLog();

        Livewire::actingAs($this->user)->test(TasksGrid::class);
        $bigRenderQueries = count(\Illuminate\Support\Facades\DB::getQueryLog());
        \Illuminate\Support\Facades\DB::disableQueryLog();

        $this->assertLessThan(
            5,
            abs($bigRenderQueries - $smallRenderQueries),
            "Rendering with 18 rows used {$bigRenderQueries} queries vs {$smallRenderQueries} for 3 rows — looks like an N+1 regression in TasksGrid::render()."
        );
    }

    public function test_sprint_name_in_the_grid_links_to_the_sprint(): void
    {
        $sprint = Sprint::factory()->create(['name' => 'Sprint HQ', 'created_by' => $this->user->id]);
        $task = ProjectTask::query()->create([
            'name' => 'DR do Berlina',
            'sprint_id' => $sprint->id,
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);
        $item = WorkItem::query()
            ->where('source_type', $task->getMorphClass())
            ->where('source_id', $task->id)
            ->first();

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->assertSeeHtml('href="'.e(route('sprints.show', $sprint)).'"')
            ->assertSee('Sprint HQ')
            ->assertDontSeeHtml("startEdit({$item->id}, 'name'")
            ->assertDontSeeHtml("startEdit({$item->id}, 'sprint'");
    }

    public function test_empty_sprint_cell_opens_sprint_picker(): void
    {
        $task = ProjectTask::query()->create([
            'name' => 'DR do Berlina',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);
        $item = WorkItem::query()
            ->where('source_type', $task->getMorphClass())
            ->where('source_id', $task->id)
            ->first();

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->assertSeeHtml("startEdit({$item->id}, 'sprint'")
            ->call('startEdit', $item->id, 'sprint')
            ->assertSet('editingField', 'sprint')
            ->assertSee('Poza sprintem');
    }

    public function test_subtask_row_uses_binary_status_without_in_progress(): void
    {
        $parent = ProjectTask::query()->create([
            'name' => 'Przygotowanie wyjazdu',
            'status' => TaskStatus::PENDING,
            'created_by' => $this->user->id,
        ]);

        TaskSubtask::query()->create([
            'task_id' => $parent->id,
            'name' => 'weź klucze',
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        $item = WorkItem::query()->where('type', WorkItemType::Subtask)->first();
        $this->assertNotNull($item);
        $this->assertSame(\App\WorkItems\StatusWidget::BinarySelect, $item->statusWidget());

        $html = Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->assertSee('Oczekujące')
            ->assertSee('Podzadanie')
            ->html();

        $this->assertStringContainsString('tg-status-badge', $html);
        $this->assertStringContainsString("quickStatusChange({$item->id}, 'pending')", $html);
        $this->assertStringContainsString("quickStatusChange({$item->id}, 'completed')", $html);
        $this->assertStringNotContainsString("quickStatusChange({$item->id}, 'in_progress')", $html);
        $this->assertStringNotContainsString("quickStatusChange({$item->id}, 'cancelled')", $html);
        $this->assertStringNotContainsString("startEdit({$item->id}, 'sprint')", $html);
        $this->assertStringNotContainsString("startEdit({$item->id}, 'category')", $html);
        $this->assertStringNotContainsString("toggleExpand({$item->id})", $html);
        $this->assertStringNotContainsString('W zadaniu:', $html);
    }

    public function test_subtask_inherits_sprint_and_category_from_parent(): void
    {
        $sprint = Sprint::factory()->create(['name' => 'Sprint HQ', 'created_by' => $this->user->id]);
        $parent = ProjectTask::query()->create([
            'name' => 'Przygotowanie wyjazdu',
            'category' => 'Logistyka',
            'sprint_id' => $sprint->id,
            'status' => TaskStatus::PENDING,
            'created_by' => $this->user->id,
        ]);

        TaskSubtask::query()->create([
            'task_id' => $parent->id,
            'name' => 'weź klucze',
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        $item = WorkItem::query()->where('type', WorkItemType::Subtask)->first();
        $this->assertNotNull($item);
        $this->assertSame('Logistyka', $item->category);
        $this->assertSame($sprint->id, $item->sprint_id);
        $this->assertTrue($item->supports('category'));
        $this->assertTrue($item->supports('sprint'));
        $this->assertFalse($item->writable('category'));
        $this->assertFalse($item->writable('sprint'));

        $parent->update(['category' => 'Magazyn', 'sprint_id' => null]);

        $item = $item->fresh();
        $this->assertSame('Magazyn', $item->category);
        $this->assertNull($item->sprint_id);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->assertSee('weź klucze')
            ->assertSee('Magazyn')
            ->assertDontSeeHtml("startEdit({$item->id}, 'category')")
            ->assertDontSeeHtml("startEdit({$item->id}, 'sprint')");
    }

    public function test_dispatch_status_is_a_badge_and_cannot_be_closed_from_the_grid(): void
    {
        $dispatch = WarehouseDispatch::factory()->create([
            'status' => WarehouseDispatch::STATUS_PARTIAL,
            'issued_at' => null,
            'issued_by' => null,
            'created_by' => $this->user->id,
        ]);

        $item = WorkItem::query()->where('type', WorkItemType::Dispatch)->first();
        $this->assertNotNull($item);
        $this->assertSame(WorkItemStatus::Pending, $item->status);

        $component = Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->assertSee('Częściowo wydane')
            ->assertSee('Kompletacja')
            ->assertDontSeeHtml("toggleExpand({$item->id})")
            ->assertDontSee('Brak opisu.')
            ->call('toggleExpand', $item->id)
            ->call('quickStatusChange', $item->id, 'completed')
            ->assertDontSee('Status zaktualizowany.');

        $this->assertSame([], $component->get('expandedTasks'));

        $this->assertSame(WorkItemStatus::Pending, $item->fresh()->status);
        $this->assertSame(WarehouseDispatch::STATUS_PARTIAL, $dispatch->fresh()->status);
    }

    public function test_category_drag_updates_a_task_and_noops_a_dispatch(): void
    {
        $task = ProjectTask::query()->create([
            'name' => 'Do przeniesienia',
            'category' => 'A',
            'status' => TaskStatus::PENDING,
            'created_by' => $this->user->id,
        ]);
        $taskItem = WorkItem::query()
            ->where('source_type', $task->getMorphClass())
            ->where('source_id', $task->id)
            ->first();

        $dispatch = WarehouseDispatch::factory()->create([
            'status' => WarehouseDispatch::STATUS_RESERVED,
            'issued_at' => null,
            'issued_by' => null,
            'created_by' => $this->user->id,
        ]);
        $dispatchItem = WorkItem::query()
            ->where('source_type', $dispatch->getMorphClass())
            ->where('source_id', $dispatch->id)
            ->first();

        $this->assertNotNull($taskItem);
        $this->assertNotNull($dispatchItem);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->set('groupBy', 'category')
            ->call('moveTaskToGroup', $taskItem->id, 'B')
            ->assertSee('Zadanie przeniesione.')
            ->call('moveTaskToGroup', $dispatchItem->id, 'B')
            ->assertSee('Tej pozycji nie przenosi się w tej grupie.');

        $this->assertSame('B', $task->fresh()->category);
        $this->assertSame('B', $taskItem->fresh()->category);
        $this->assertNull($dispatchItem->fresh()->category);
    }

    public function test_subtask_has_no_status_grip_and_dropping_in_progress_does_nothing(): void
    {
        $robert = User::factory()->create(['name' => 'robert']);
        $parent = ProjectTask::query()->create([
            'name' => 'Przygotowanie wyjazdu',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);
        TaskSubtask::query()->create([
            'task_id' => $parent->id,
            'name' => 'weź klucze',
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        $taskItem = WorkItem::query()->where('type', WorkItemType::Task)->first();
        $subtaskItem = WorkItem::query()->where('type', WorkItemType::Subtask)->first();
        $this->assertNotNull($taskItem);
        $this->assertNotNull($subtaskItem);
        $this->assertTrue($taskItem->relocatable('status'));
        $this->assertFalse($subtaskItem->relocatable('status'));
        $this->assertTrue($subtaskItem->relocatable('assigned_to'));

        $html = Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->set('groupBy', 'status')
            ->html();

        $this->assertStringContainsString('id: '.$taskItem->id.', fromGroup', $html);
        $this->assertStringNotContainsString('id: '.$subtaskItem->id.', fromGroup', $html);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->set('groupBy', 'status')
            ->call('moveTaskToGroup', $subtaskItem->id, TaskStatus::IN_PROGRESS->value)
            ->assertSee('Tej pozycji nie przenosi się w tej grupie.');

        $this->assertSame(WorkItemStatus::Pending, $subtaskItem->fresh()->status);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->set('groupBy', 'assigned_to')
            ->call('moveTaskToGroup', $subtaskItem->id, (string) $robert->id)
            ->assertSee('Zadanie przeniesione.');

        $this->assertSame($robert->id, $subtaskItem->fresh()->assignee_id);
    }
}
