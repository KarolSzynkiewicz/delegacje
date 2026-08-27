<?php

namespace Tests\Feature;

use App\Contracts\Llm\LlmCredentialRepository;
use App\Livewire\TasksGrid;
use App\Models\ProjectTask;
use App\Models\User;
use App\Services\Llm\TasksFilterImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TasksGridChronoFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::where('name', 'administrator')->first());
    }

    public function test_chrono_trigger_is_visible_on_tasks_grid(): void
    {
        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->assertSeeHtml('wire:click="openChronoModal"');
    }

    public function test_import_from_json_creates_selected_tasks_with_filter_category(): void
    {
        $json = json_encode([
            'tasks' => [
                ['name' => 'Task A', 'subtasks' => ['Krok 1']],
                ['name' => 'Task B'],
            ],
        ], JSON_UNESCAPED_UNICODE);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->set('searchCategory', 'AI / Sprint')
            ->call('openChronoModal')
            ->call('chronoChooseImport')
            ->set('importText', $json)
            ->call('parseImportText')
            ->assertSet('importProposals.0.name', 'Task A')
            ->assertSet('importProposals.0.category', 'AI / Sprint')
            ->set('importSelected', [0])
            ->call('confirmImportProposals')
            ->assertSet('showChronoModal', false);

        $this->assertDatabaseHas('project_tasks', [
            'name' => 'Task A',
            'category' => 'AI / Sprint',
            'created_by' => $this->user->id,
        ]);
        $this->assertDatabaseMissing('project_tasks', ['name' => 'Task B']);
        $this->assertSame(1, ProjectTask::query()->where('name', 'Task A')->first()->subtasks()->count());
    }

    public function test_import_service_parses_plain_lines_without_llm(): void
    {
        $service = app(TasksFilterImportService::class);

        $tasks = $service->parse("Pierwsze\nDrugie zadanie", ['category' => 'Backlog']);

        $this->assertCount(2, $tasks);
        $this->assertSame('Pierwsze', $tasks[0]['name']);
        $this->assertSame('Backlog', $tasks[0]['category']);
    }

    public function test_summary_uses_llm_and_stores_result(): void
    {
        app(LlmCredentialRepository::class)->store('gemini', 'AIzaTESTKEY1234567890', 'gemini-2.5-flash');
        app(LlmCredentialRepository::class)->activate('gemini');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode([
                        'headline' => 'Backlog pod kontrolą',
                        'summary' => 'Są otwarte zadania w filtrze.',
                        'highlights' => ['Jedno ważne'],
                        'risks' => ['Brak terminów'],
                    ], JSON_UNESCAPED_UNICODE)]]],
                    'finishReason' => 'STOP',
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('openChronoModal')
            ->call('chronoChooseSummary')
            ->assertSet('chronoLoading', true)
            ->call('fetchChronoSummary')
            ->assertSet('chronoLoading', false)
            ->assertSet('chronoError', null)
            ->assertSet('chronoSummary.headline', 'Backlog pod kontrolą');
    }
}
