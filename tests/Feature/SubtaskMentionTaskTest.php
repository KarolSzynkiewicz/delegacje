<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Livewire\TaskSubtasks;
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

    public function test_mention_in_subtask_assigns_that_user(): void
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
        $this->assertSame($robert->id, $subtask->assigned_to);
        $this->assertSame('weź klucze', $subtask->name);
        $this->assertSame(0, ProjectTask::query()->where('subject_type', 'task_subtask')->count());

        Notification::assertSentTo($robert, TaskAssigned::class, function (TaskAssigned $notification) use ($robert, $parent): bool {
            $data = $notification->toDatabase($robert);

            return $data['task_url'] === route('tasks.show', $parent)
                && str_contains((string) $data['excerpt'], 'weź klucze');
        });
    }

    public function test_everyone_mention_in_subtask_does_not_assign(): void
    {
        User::factory()->create(['name' => 'robert']);
        $parent = $this->parentTask();

        Livewire::actingAs($this->user)
            ->test(TaskSubtasks::class, ['task' => $parent])
            ->set('newSubtaskName', '@wszyscy pilne')
            ->call('addSubtask');

        $subtask = TaskSubtask::query()->where('task_id', $parent->id)->first();
        $this->assertNull($subtask->assigned_to);
        $this->assertSame(0, ProjectTask::query()->where('category', 'Podzadanie')->count());
    }

    public function test_self_mention_in_subtask_assigns_without_notification(): void
    {
        Notification::fake();
        $parent = $this->parentTask();

        Livewire::actingAs($this->user)
            ->test(TaskSubtasks::class, ['task' => $parent])
            ->set('newSubtaskName', '@karol sobie')
            ->call('addSubtask');

        $subtask = TaskSubtask::query()->where('task_id', $parent->id)->first();
        $this->assertSame($this->user->id, $subtask->assigned_to);
        $this->assertSame(0, ProjectTask::query()->where('category', 'Podzadanie')->count());
        Notification::assertNotSentTo($this->user, TaskAssigned::class);
    }

    public function test_checking_assigned_subtask_marks_it_complete(): void
    {
        $robert = User::factory()->create(['name' => 'robert']);
        $parent = $this->parentTask();

        Livewire::actingAs($this->user)
            ->test(TaskSubtasks::class, ['task' => $parent])
            ->set('newSubtaskName', '@robert weź klucze')
            ->call('addSubtask');

        $subtask = TaskSubtask::query()->where('task_id', $parent->id)->first();
        $this->assertFalse($subtask->is_completed);

        Livewire::actingAs($this->user)
            ->test(TaskSubtasks::class, ['task' => $parent])
            ->call('toggleSubtask', $subtask->id);

        $this->assertTrue($subtask->fresh()->is_completed);
        $this->assertSame($robert->id, $subtask->fresh()->assigned_to);
    }

    private function parentTask(): ProjectTask
    {
        return ProjectTask::query()->create([
            'name' => 'Przygotowanie wyjazdu',
            'status' => TaskStatus::PENDING,
            'created_by' => $this->user->id,
        ]);
    }
}
