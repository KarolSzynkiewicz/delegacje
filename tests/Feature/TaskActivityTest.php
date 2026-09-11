<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Livewire\TaskActivity;
use App\Livewire\TaskShowQuickEdit;
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

    public function test_activity_feed_records_task_and_subtask_content_edits(): void
    {
        $user = $this->admin();
        $this->actingAs($user);

        $task = ProjectTask::query()->create([
            'name' => 'Stara nazwa',
            'description' => 'Stary opis',
            'status' => TaskStatus::PENDING,
            'created_by' => $user->id,
        ]);
        $subtask = TaskSubtask::query()->create([
            'task_id' => $task->id,
            'name' => 'Stary krok',
            'created_by' => $user->id,
        ]);

        $task->update(['name' => 'Nowa nazwa']);
        $task->update(['description' => 'Nowy opis zadania']);
        $subtask->update(['name' => 'Nowy krok']);

        $entries = app(SprintActivityFeed::class)->forTask($task->fresh());
        $kinds = $entries->pluck('kind')->all();

        $this->assertContains('task.renamed', $kinds);
        $this->assertContains('task.description', $kinds);
        $this->assertContains('subtask.renamed', $kinds);

        $rename = $entries->firstWhere('kind', 'task.renamed');
        $this->assertSame($user->name, $rename['actor']);
        $this->assertSame('Nowa nazwa', $rename['subject']);
        $this->assertStringContainsString('Stara nazwa', (string) $rename['detail']);

        $desc = $entries->firstWhere('kind', 'task.description');
        $this->assertSame($user->name, $desc['actor']);
        $this->assertStringContainsString('Nowy opis zadania', (string) $desc['detail']);

        Livewire::actingAs($user)
            ->test(TaskActivity::class, ['task' => $task->fresh()])
            ->assertSee('zmienił nazwę zadania')
            ->assertSee('zmienił opis zadania')
            ->assertSee('zmienił nazwę podzadania')
            ->assertSee('Nowy krok');
    }

    public function test_saving_description_from_the_card_leaves_history(): void
    {
        $user = $this->admin();
        $this->actingAs($user);

        $task = ProjectTask::query()->create([
            'name' => 'Karta z opisem',
            'description' => null,
            'status' => TaskStatus::PENDING,
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(TaskShowQuickEdit::class, ['task' => $task])
            ->set('descriptionDraft', 'Pierwszy opis z karty')
            ->call('saveDescription')
            ->assertDispatched('task-history-changed');

        $added = app(SprintActivityFeed::class)->forTask($task->fresh())
            ->firstWhere('kind', 'task.description');
        $this->assertNotNull($added);
        $this->assertSame('dodał opis zadania', $added['verb']);
        $this->assertSame($user->name, $added['actor']);
        $this->assertStringContainsString('Pierwszy opis z karty', (string) $added['detail']);

        Livewire::actingAs($user)
            ->test(TaskShowQuickEdit::class, ['task' => $task->fresh()])
            ->set('descriptionDraft', 'Poprawiony opis')
            ->call('saveDescription');

        $changed = app(SprintActivityFeed::class)->forTask($task->fresh())
            ->first(fn (array $entry) => $entry['kind'] === 'task.description'
                && $entry['verb'] === 'zmienił opis zadania');
        $this->assertNotNull($changed);
        $this->assertStringContainsString('Poprawiony opis', (string) $changed['detail']);
    }
}
