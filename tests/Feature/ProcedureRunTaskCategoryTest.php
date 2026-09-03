<?php

namespace Tests\Feature;

use App\Livewire\ProcedureRunStepper;
use App\Livewire\TaskSubtasks;
use App\Models\ProcedureTemplate;
use App\Models\ProjectTask;
use App\Models\User;
use App\Services\ProcedureRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcedureRunTaskCategoryTest extends TestCase
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
    }

    public function test_starting_a_procedure_sets_task_category_to_procedura(): void
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
            'name_suffix' => 'Jan',
        ]);

        $task = ProjectTask::query()->where('procedure_run_id', $run->id)->first();

        $this->assertNotNull($task);
        $this->assertSame('Onboarding · Jan', $task->name);
        $this->assertSame('Procedura', $task->category);
    }

    public function test_procedure_task_show_page_does_not_render_task_subtasks(): void
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
            'name_suffix' => 'Jan',
        ]);
        $task = ProjectTask::query()->where('procedure_run_id', $run->id)->first();

        $this->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSeeLivewire(ProcedureRunStepper::class)
            ->assertSee('Aktywny')
            ->assertSee('Czeka na zbieg')
            ->assertDontSeeLivewire(TaskSubtasks::class)
            ->assertDontSee('Wpisz nazwę podzadania');
    }

    public function test_procedure_show_page_is_not_a_task_workspace(): void
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
            'name_suffix' => 'Jan',
        ]);
        $task = ProjectTask::query()->where('procedure_run_id', $run->id)->first();

        $html = $this->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSeeLivewire(ProcedureRunStepper::class)
            ->assertDontSeeLivewire(TaskSubtasks::class)
            ->assertSee('Onboarding · Jan')
            ->assertSee('Procedura')
            ->assertSee('Szablon')
            ->assertSee('Onboarding')
            ->assertSee('Komentarze')
            ->assertDontSee('Zadanie:')
            ->assertDontSee('Nazwa zadania')
            ->assertDontSee('Dziennik operacyjny')
            ->assertDontSee('Dodaj raport z działania')
            ->assertDontSee('Wpisz nazwę podzadania')
            ->assertDontSee('Start progress')
            ->assertDontSee('Rozpocznij')
            ->getContent();

        $this->assertSame(1, substr_count($html, 'Komentarze'));
        $this->assertStringNotContainsString('placeholder="Dodaj komentarz…"', $html);
        $this->assertStringNotContainsString('wire:click="addComment"', $html);
    }
}
