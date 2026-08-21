<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Enums\WorkItemStatus;
use App\Enums\WorkItemType;
use App\Livewire\BacklogGrid;
use App\Livewire\TaskSubtasks;
use App\Models\ProcedureTemplate;
use App\Models\ProjectTask;
use App\Models\TaskSubtask;
use App\Models\User;
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

        Livewire::actingAs($this->user)
            ->test(BacklogGrid::class)
            ->assertDontSee('Oddzwonić do Jan Kowalski #12')
            ->set('hideCallbacks', false)
            ->assertSee('Oddzwonić do Jan Kowalski #12');
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
            ->assertSee('Backlog')
            ->assertSee('DR do Berlina')
            ->assertSeeLivewire(BacklogGrid::class);
    }
}
