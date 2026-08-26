<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Livewire\TaskActivity;
use App\Models\ProjectTask;
use App\Models\TaskSubtask;
use App\Models\User;
use App\Services\SprintActivityFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaskActivityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);
        $user = User::factory()->create();
        $user->assignRole(Role::where('name', 'administrator')->first());

        return $user;
    }

    public function test_task_show_page_renders_merged_card_actions_and_history(): void
    {
        $user = $this->admin();
        $task = ProjectTask::query()->create([
            'name' => 'Zadanie z historią',
            'description' => 'Opis testowy',
            'status' => TaskStatus::PENDING,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('tasks.show', $task));

        $response->assertOk();
        $response->assertSee('dt-card__label', false);
        $response->assertSee('Historia');
        $response->assertSee('task-desc-composer', false);
        $response->assertSee('Szczegóły');
        $response->assertSee('Opis');
        $response->assertSee('st-head__tools', false);
        $response->assertDontSee('form-check-input', false);
        $response->assertDontSee('>Utworzył<', false);
        $response->assertDontSee('>Zakończono<', false);
    }

    public function test_activity_feed_includes_task_and_subtask_creation(): void
    {
        $user = $this->admin();
        $task = ProjectTask::query()->create([
            'name' => 'Pipeline',
            'status' => TaskStatus::PENDING,
            'created_by' => $user->id,
        ]);
        TaskSubtask::query()->create([
            'task_id' => $task->id,
            'name' => 'Krok pierwszy',
            'created_by' => $user->id,
        ]);

        $entries = app(SprintActivityFeed::class)->forTask($task->fresh());

        $kinds = $entries->pluck('kind')->all();
        $this->assertContains('task.created', $kinds);
        $this->assertContains('subtask.created', $kinds);

        Livewire::actingAs($user)
            ->test(TaskActivity::class, ['task' => $task])
            ->assertSee('Historia')
            ->assertSee('dodał zadanie')
            ->assertSee('dodał podzadanie');
    }
}
