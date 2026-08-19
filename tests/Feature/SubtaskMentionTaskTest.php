<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Livewire\TaskSubtasks;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\TaskSubtask;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class SubtaskMentionTaskTest extends TestCase
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

    public function test_mention_in_subtask_creates_task_for_that_user(): void
    {
        Notification::fake();

        $robert = User::factory()->create(['name' => 'robert']);
        $parent = $this->parentTask();

        Livewire::actingAs($this->user)
            ->test(TaskSubtasks::class, ['task' => $parent])
            ->set('newSubtaskName', '@robert weź klucze')
            ->call('addSubtask');

        $subtask = TaskSubtask::query()->where('task_id', $parent->id)->first();
        $this->assertNotNull($subtask);

        $task = ProjectTask::query()
            ->where('subject_type', 'task_subtask')
            ->where('subject_id', $subtask->id)
            ->first();

        $this->assertNotNull($task);
        $this->assertSame($robert->id, $task->assigned_to);
        $this->assertSame('Podzadanie', $task->category);
        $this->assertSame('Wzmianka od karol', $task->name);
        $this->assertSame(
            "Zadanie „Przygotowanie wyjazdu” (karol) z podzadaniem dla Ciebie\n\nweź klucze",
            $task->description
        );
        $this->assertSame($parent->project_id, $task->project_id);
        $this->assertSame(route('tasks.show', $parent), $task->sourceCard()['url']);

        Notification::assertSentTo($robert, TaskAssigned::class, function (TaskAssigned $notification) use ($robert, $parent): bool {
            $data = $notification->toDatabase($robert);

            return $data['task_url'] === route('tasks.show', $parent)
                && str_contains((string) $data['excerpt'], 'weź klucze');
        });
    }

    public function test_everyone_mention_in_subtask_does_not_create_tasks(): void
    {
        User::factory()->create(['name' => 'robert']);
        $parent = $this->parentTask();

        Livewire::actingAs($this->user)
            ->test(TaskSubtasks::class, ['task' => $parent])
            ->set('newSubtaskName', '@wszyscy pilne')
            ->call('addSubtask');

        $this->assertSame(0, ProjectTask::query()->where('category', 'Podzadanie')->count());
    }

    public function test_self_mention_in_subtask_creates_task_without_assign_notification(): void
    {
        Notification::fake();
        $parent = $this->parentTask();

        Livewire::actingAs($this->user)
            ->test(TaskSubtasks::class, ['task' => $parent])
            ->set('newSubtaskName', '@karol sobie')
            ->call('addSubtask');

        $this->assertDatabaseHas('project_tasks', [
            'assigned_to' => $this->user->id,
            'category' => 'Podzadanie',
            'created_by' => $this->user->id,
        ]);
        Notification::assertNotSentTo($this->user, TaskAssigned::class);
    }

    public function test_checking_subtask_completes_linked_mention_task(): void
    {
        $robert = User::factory()->create(['name' => 'robert']);
        $parent = $this->parentTask();

        Livewire::actingAs($this->user)
            ->test(TaskSubtasks::class, ['task' => $parent])
            ->set('newSubtaskName', '@robert weź klucze')
            ->call('addSubtask');

        $subtask = TaskSubtask::query()->where('task_id', $parent->id)->first();
        $task = $subtask->tasks()->first();
        $this->assertSame(TaskStatus::PENDING, $task->status);

        Livewire::actingAs($this->user)
            ->test(TaskSubtasks::class, ['task' => $parent])
            ->call('toggleSubtask', $subtask->id);

        $this->assertTrue($subtask->fresh()->is_completed);
        $this->assertSame(TaskStatus::COMPLETED, $task->fresh()->status);
        $this->assertSame($robert->id, $task->assigned_to);
    }

    public function test_completing_mention_task_checks_off_subtask(): void
    {
        User::factory()->create(['name' => 'robert']);
        $parent = $this->parentTask();

        Livewire::actingAs($this->user)
            ->test(TaskSubtasks::class, ['task' => $parent])
            ->set('newSubtaskName', '@robert weź klucze')
            ->call('addSubtask');

        $subtask = TaskSubtask::query()->where('task_id', $parent->id)->first();
        $task = $subtask->tasks()->first();

        $task->markCompleted();

        $this->assertTrue($subtask->fresh()->is_completed);
        $this->assertSame(TaskStatus::COMPLETED, $task->fresh()->status);
    }

    private function parentTask(): ProjectTask
    {
        $project = Project::factory()->create();

        return ProjectTask::query()->create([
            'name' => 'Przygotowanie wyjazdu',
            'status' => TaskStatus::PENDING,
            'created_by' => $this->user->id,
            'project_id' => $project->id,
        ]);
    }
}
