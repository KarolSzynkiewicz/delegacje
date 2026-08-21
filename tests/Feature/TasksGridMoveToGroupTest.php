<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Livewire\TasksGrid;
use App\Models\ProjectTask;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class TasksGridMoveToGroupTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create(['name' => 'Admin']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->user->assignRole($adminRole);
        }
    }

    public function test_moving_unassigned_task_to_person_group_assigns_the_user(): void
    {
        Notification::fake();

        $karol = User::factory()->create(['name' => 'Karol']);
        $task = $this->makeTask('Zadanie bez osoby', ['assigned_to' => null]);
        $this->makeTask('Zadanie Karola', ['assigned_to' => $karol->id]);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->set('groupBy', 'assigned_to')
            ->assertSee('Nieprzypisane')
            ->assertSee('Karol')
            ->assertSeeHtml('moveTaskToGroup')
            ->call('moveTaskToGroup', $task->id, (string) $karol->id)
            ->assertSee('Zadanie przeniesione.');

        $this->assertSame($karol->id, $task->fresh()->assigned_to);
        Notification::assertSentTo($karol, TaskAssigned::class);
    }

    public function test_moving_task_to_unassigned_group_clears_assignee(): void
    {
        $karol = User::factory()->create(['name' => 'Karol']);
        $task = $this->makeTask('Zadanie Karola', ['assigned_to' => $karol->id]);
        $this->makeTask('Zadanie bez osoby', ['assigned_to' => null]);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->set('groupBy', 'assigned_to')
            ->call('moveTaskToGroup', $task->id, '');

        $this->assertNull($task->fresh()->assigned_to);
    }

    public function test_moving_task_between_category_groups_updates_category(): void
    {
        $task = $this->makeTask('Do przeniesienia', ['category' => 'A']);
        $this->makeTask('Kotwica B', ['category' => 'B']);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->set('groupBy', 'category')
            ->call('moveTaskToGroup', $task->id, 'B');

        $this->assertSame('B', $task->fresh()->category);
    }

    public function test_moving_task_to_empty_category_group_clears_category(): void
    {
        $task = $this->makeTask('Z kategorią', ['category' => 'A']);
        $this->makeTask('Bez kategorii', ['category' => null]);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->set('groupBy', 'category')
            ->call('moveTaskToGroup', $task->id, '');

        $this->assertNull($task->fresh()->category);
    }

    public function test_moving_task_between_status_groups_updates_status(): void
    {
        $task = $this->makeTask('Oczekujące', ['status' => TaskStatus::PENDING]);
        $this->makeTask('W trakcie', ['status' => TaskStatus::IN_PROGRESS]);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->set('groupBy', 'status')
            ->call('moveTaskToGroup', $task->id, TaskStatus::IN_PROGRESS->value);

        $this->assertSame(TaskStatus::IN_PROGRESS, $task->fresh()->status);
    }

    public function test_moving_task_between_priority_groups_updates_priority(): void
    {
        $task = $this->makeTask('Niski', ['priority' => 1]);
        $this->makeTask('Wysoki', ['priority' => 4]);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->set('groupBy', 'priority')
            ->call('moveTaskToGroup', $task->id, '4');

        $this->assertSame(4, $task->fresh()->priority);
    }

    public function test_dropping_on_the_same_group_does_not_change_the_task(): void
    {
        $karol = User::factory()->create(['name' => 'Karol']);
        $task = $this->makeTask('Zadanie Karola', ['assigned_to' => $karol->id]);

        $component = Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->set('groupBy', 'assigned_to')
            ->call('moveTaskToGroup', $task->id, (string) $karol->id);

        $this->assertSame($karol->id, $task->fresh()->assigned_to);
        $this->assertNull($component->get('flash'));
    }

    public function test_move_is_ignored_when_not_grouped(): void
    {
        $karol = User::factory()->create(['name' => 'Karol']);
        $task = $this->makeTask('Bez grupy', ['assigned_to' => null]);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('moveTaskToGroup', $task->id, (string) $karol->id);

        $this->assertNull($task->fresh()->assigned_to);
    }

    public function test_user_without_permission_cannot_move_task_to_another_group(): void
    {
        $other = User::factory()->create();
        $karol = User::factory()->create(['name' => 'Karol']);
        $task = $this->makeTask('Cudze zadanie', [
            'assigned_to' => null,
            'created_by' => $other->id,
        ]);
        $this->makeTask('Kotwica Karola', [
            'assigned_to' => $karol->id,
            'created_by' => $other->id,
        ]);

        Livewire::actingAs($other)
            ->test(TasksGrid::class)
            ->set('groupBy', 'assigned_to')
            ->call('moveTaskToGroup', $task->id, (string) $karol->id);

        $this->assertNull($task->fresh()->assigned_to);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeTask(string $name, array $overrides = []): ProjectTask
    {
        return ProjectTask::query()->create(array_merge([
            'name' => $name,
            'category' => 'Ogólne',
            'status' => TaskStatus::PENDING,
            'created_by' => $this->user->id,
        ], $overrides));
    }
}
