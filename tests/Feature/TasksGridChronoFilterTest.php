<?php

namespace Tests\Feature;

use App\Contracts\Llm\LlmCredentialRepository;
use App\Enums\TaskStatus;
use App\Livewire\ChronoAssist;
use App\Livewire\TasksGrid;
use App\Models\ProjectTask;
use App\Models\TaskSubtask;
use App\Models\User;
use App\Models\WorkItem;
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

    public function test_opening_chrono_shows_the_assist_stepper(): void
    {
        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('openChronoModal')
            ->assertSet('chronoMode', 'menu')
            ->assertSeeLivewire(ChronoAssist::class);
    }

    public function test_assist_pick_opens_existing_import_flow(): void
    {
        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('openChronoModal')
            ->dispatch('chrono-assist-picked', key: 'import-json')
            ->assertSet('chronoMode', 'import')
            ->assertSee('Wklej JSON');
    }

    public function test_assist_pick_opens_list_import_flow(): void
    {
        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('openChronoModal')
            ->dispatch('chrono-assist-picked', key: 'import-list')
            ->assertSet('chronoMode', 'import')
            ->assertSet('importMode', 'list')
            ->assertSee('Lista linii')
            ->assertDontSee('Luźny opis');
    }

    public function test_import_service_parses_mention_and_description_notation(): void
    {
        $karol = User::factory()->create(['name' => 'karol']);
        $service = app(TasksFilterImportService::class);

        $tasks = $service->parseLines('zrób kolacje@karol -//ma być smaczna', ['category' => 'Kuchnia']);

        $this->assertCount(1, $tasks);
        $this->assertSame('zrób kolacje', $tasks[0]['name']);
        $this->assertSame('ma być smaczna', $tasks[0]['description']);
        $this->assertSame($karol->id, $tasks[0]['assigned_to']);
        $this->assertSame('create', $tasks[0]['action']);
    }

    public function test_import_json_with_existing_id_creates_a_new_task(): void
    {
        $task = ProjectTask::query()->create([
            'name' => 'Stara nazwa',
            'description' => 'Stary opis',
            'status' => TaskStatus::PENDING,
            'category' => 'Backlog',
            'created_by' => $this->user->id,
        ]);

        $json = json_encode([
            'tasks' => [[
                'id' => $task->id,
                'name' => 'Nowa nazwa',
                'description' => 'Poprawiony opis',
                'category' => 'AI / Sprint',
            ]],
        ], JSON_UNESCAPED_UNICODE);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('openChronoModal')
            ->call('chronoChooseImport', 'json')
            ->set('importText', $json)
            ->call('parseImportText')
            ->assertSet('importProposals.0.action', 'create')
            ->assertSet('importProposals.0.existing_task_id', null)
            ->call('confirmImportProposals');

        $task->refresh();
        $this->assertSame('Stara nazwa', $task->name);
        $this->assertSame('Stary opis', $task->description);
        $this->assertSame(2, ProjectTask::query()->count());
        $this->assertDatabaseHas('project_tasks', [
            'name' => 'Nowa nazwa',
            'category' => 'AI / Sprint',
        ]);
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
            ->call('chronoChooseImport', 'json')
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

        $tasks = $service->parseLines("Pierwsze\nDrugie zadanie", ['category' => 'Backlog']);

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

    public function test_assist_json_export_stays_on_preview_flow(): void
    {
        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('openChronoModal')
            ->dispatch('chrono-assist-picked', key: 'export-json')
            ->assertSet('chronoMode', 'export')
            ->assertSee('Kopiuj JSON');
    }

    public function test_assist_json_export_includes_grid_columns_types_and_subtasks(): void
    {
        $task = ProjectTask::query()->create([
            'name' => 'Eksport JSON Alpha',
            'description' => 'Pełny opis',
            'status' => TaskStatus::IN_PROGRESS,
            'priority' => 2,
            'category' => 'AI / Sprint',
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
            'due_date' => '2026-09-01',
        ]);

        TaskSubtask::query()->create([
            'task_id' => $task->id,
            'name' => 'Krok jeden',
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
            'sort_order' => 1,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('openChronoModal')
            ->dispatch('chrono-assist-picked', key: 'export-json')
            ->assertSet('chronoMode', 'export');

        $payload = json_decode((string) $component->get('exportJson'), true);

        $this->assertIsArray($payload);
        $this->assertSame(2, $payload['version']);
        $this->assertSame('tasks-filter-export', $payload['format']);

        $row = collect($payload['tasks'])->firstWhere('name', 'Eksport JSON Alpha');
        $this->assertNotNull($row);
        $this->assertSame('task', $row['type']);
        $this->assertSame('in_progress', $row['status']);
        $this->assertSame(2, $row['priority']);
        $this->assertSame('AI / Sprint', $row['category']);
        $this->assertSame($this->user->id, $row['assigned_to']);
        $this->assertSame($this->user->name, $row['assignee']);
        $this->assertSame($this->user->id, $row['created_by']);
        $this->assertSame('2026-09-01', $row['due_date']);
        $this->assertSame('Krok jeden', $row['subtasks'][0]['name'] ?? null);
        $this->assertFalse($row['subtasks'][0]['is_completed']);
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('url', $row);

        $subtaskRow = collect($payload['tasks'])->firstWhere('type', 'subtask');
        $this->assertNotNull($subtaskRow);
        $this->assertSame('Krok jeden', $subtaskRow['name']);
        $this->assertSame($task->id, $subtaskRow['parent']['id'] ?? null);

        $parsed = app(TasksFilterImportService::class)->parseJson(
            (string) $component->get('exportJson'),
            ['category' => 'Backlog'],
        );
        $imported = collect($parsed)->firstWhere('name', 'Eksport JSON Alpha');
        $this->assertNotNull($imported);
        $this->assertSame('create', $imported['action']);
        $this->assertNull($imported['existing_task_id']);
        $this->assertSame('Krok jeden', $imported['subtasks'][0]['name'] ?? $imported['subtasks'][0] ?? null);
    }

    public function test_assist_csv_export_downloads_current_filter(): void
    {
        $this->travelTo(now()->startOfDay());

        ProjectTask::query()->create([
            'name' => 'Eksport CSV Alpha',
            'status' => TaskStatus::PENDING,
            'priority' => 3,
            'category' => 'AI / Sprint',
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
            'due_date' => '2026-09-01',
        ]);

        $filename = 'zadania-filtr-'.now()->format('Y-m-d').'.csv';

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('openChronoModal')
            ->dispatch('chrono-assist-picked', key: 'export-csv')
            ->assertSet('chronoMode', 'menu');

        $component = Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('downloadChronoCsv')
            ->assertFileDownloaded($filename);

        $csv = base64_decode((string) data_get($component->effects, 'download.content'));

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('ID;Nazwa;Status;Priorytet;Osoba;Kategoria;Termin', $csv);
        $this->assertStringContainsString('Eksport CSV Alpha', $csv);
        $this->assertStringContainsString('AI / Sprint', $csv);
        $this->assertStringContainsString('2026-09-01', $csv);
        $this->assertStringContainsString($this->user->name, $csv);
        $this->assertStringNotContainsString('tasks-filter-export', $csv);
    }

    public function test_edi_category_preview_does_not_write_until_apply(): void
    {
        app(LlmCredentialRepository::class)->store('gemini', 'AIzaTESTKEY1234567890', 'gemini-2.5-flash');
        app(LlmCredentialRepository::class)->activate('gemini');

        $task = ProjectTask::query()->create([
            'name' => 'Bez kategorii',
            'status' => TaskStatus::PENDING,
            'created_by' => $this->user->id,
        ]);

        $rowId = WorkItem::query()
            ->where('source_type', 'project_task')
            ->where('source_id', $task->id)
            ->value('id') ?? $task->id;

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode([
                        'changes' => [[
                            'id' => $rowId,
                            'field' => 'category',
                            'value' => 'Transport',
                        ]],
                    ], JSON_UNESCAPED_UNICODE)]]],
                    'finishReason' => 'STOP',
                ]],
            ], 200),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->dispatch('chrono-assist-picked', key: 'mutate-category')
            ->assertSet('showChronoModal', false)
            ->assertSet('ediLoading', true)
            ->call('fetchEdiProposals')
            ->assertSet('ediLoading', false)
            ->assertSet('ediChanges.0.kind', 'add')
            ->assertSet('ediChanges.0.to', 'Transport')
            ->assertSee('Transport');

        $this->assertNull($task->fresh()->category);
        $component->assertSee('Zatwierdzanie zmian Ediego');
        $component->assertDontSee('Szukaj zadania');

        $component->call('acceptEdiChange', $rowId, 'category')
            ->assertSet('ediChanges', []);

        $this->assertSame('Transport', $task->fresh()->category);
    }

    public function test_edi_json_import_manual_revise_and_export_does_not_write(): void
    {
        $task = ProjectTask::query()->create([
            'name' => 'Alpha',
            'status' => TaskStatus::PENDING,
            'created_by' => $this->user->id,
        ]);

        $rowId = WorkItem::query()
            ->where('source_type', 'project_task')
            ->where('source_id', $task->id)
            ->value('id') ?? $task->id;

        $json = json_encode([
            'changes' => [[
                'id' => $rowId,
                'field' => 'category',
                'value' => 'Transport',
            ]],
        ], JSON_UNESCAPED_UNICODE);

        $component = Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('openChronoModal')
            ->dispatch('chrono-assist-picked', key: 'mutate-json')
            ->assertSet('chronoMode', 'edi-import')
            ->assertSet('showChronoModal', true)
            ->assertSee('Wklej JSON Ediego')
            ->set('importText', $json)
            ->call('parseEdiImportText')
            ->assertSet('showChronoModal', false)
            ->assertSet('ediIntent', 'mutate-json')
            ->assertSet('ediChanges.0.kind', 'add')
            ->assertSet('ediChanges.0.to', 'Transport')
            ->assertSee('Transport');

        $this->assertNull($task->fresh()->category);

        $component->call('closeChronoModal')
            ->assertSet('ediChanges.0.to', 'Transport');

        $component->call('reviseEdiChange', $rowId, 'category', 'Logistyka')
            ->assertSet('ediChanges.0.to', 'Logistyka')
            ->assertSee('Logistyka');

        $this->assertNull($task->fresh()->category);

        $component->call('chronoChooseEdiExport')
            ->assertSet('chronoMode', 'edi-export')
            ->assertSet('showChronoModal', true)
            ->assertSee('Paczka dla ChatGPT');

        $payload = json_decode((string) $component->get('exportJson'), true);
        $this->assertIsArray($payload);
        $this->assertSame('edi-task-edit', $payload['format']);
        $this->assertStringContainsString('Odpowiedz TYLKO JSON', (string) $payload['instruction']);
        $this->assertSame('Logistyka', $payload['changes'][0]['value'] ?? null);
        $this->assertNull($task->fresh()->category);
    }

    public function test_edi_export_from_assist_includes_filter_snapshot(): void
    {
        ProjectTask::query()->create([
            'name' => 'Snapshot Edi',
            'status' => TaskStatus::PENDING,
            'category' => 'Backlog',
            'created_by' => $this->user->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->call('openChronoModal')
            ->dispatch('chrono-assist-picked', key: 'mutate-export')
            ->assertSet('chronoMode', 'edi-export')
            ->assertSet('ediChanges', []);

        $payload = json_decode((string) $component->get('exportJson'), true);
        $this->assertSame('edi-task-edit', $payload['format']);
        $this->assertSame([], $payload['changes']);
        $this->assertNotEmpty($payload['tasks']);
        $this->assertSame('Snapshot Edi', collect($payload['tasks'])->firstWhere('name', 'Snapshot Edi')['name'] ?? null);
    }
}
