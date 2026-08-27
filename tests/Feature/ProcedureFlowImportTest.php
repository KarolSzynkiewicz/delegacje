<?php

namespace Tests\Feature;

use App\Livewire\ProcedureTemplatesIndex;
use App\Models\ProcedureTemplate;
use App\Models\User;
use App\Services\Llm\ProcedureFlowSuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProcedureFlowImportTest extends TestCase
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

    public function test_import_button_is_visible_in_new_template_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProcedureTemplatesIndex::class)
            ->call('openNewModal')
            ->assertSee('Importuj z tekstu');
    }

    public function test_opening_import_requires_a_name(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProcedureTemplatesIndex::class)
            ->call('openNewModal')
            ->call('openImportModal')
            ->assertHasErrors(['newName' => 'required'])
            ->assertSet('showImportModal', false)
            ->set('newName', 'Onboarding pracownika')
            ->call('openImportModal')
            ->assertSet('showImportModal', true)
            ->assertSee('Oczekiwany format');
    }

    public function test_imported_flow_lands_in_the_editor_as_an_unsaved_draft(): void
    {
        $json = json_encode([
            'steps' => [
                ['type' => 'task', 'name' => 'Przygotuj umowę'],
                ['type' => 'checklist', 'name' => 'Sprzęt', 'checklist' => ['Laptop', 'Telefon']],
            ],
        ], JSON_UNESCAPED_UNICODE);

        Livewire::actingAs($this->user)
            ->test(ProcedureTemplatesIndex::class)
            ->call('openNewModal')
            ->set('newName', 'Onboarding pracownika')
            ->call('openImportModal')
            ->set('importText', $json)
            ->call('importFromText')
            ->assertRedirect();

        $template = ProcedureTemplate::query()->firstOrFail();

        $this->assertSame([], $template->definition['nodes'] ?? null);
        $this->assertSame([], $template->definition['edges'] ?? null);

        $proposal = session('chrono_proposal');
        $this->assertSame(
            ['Start', 'Przygotuj umowę', 'Sprzęt', 'Koniec'],
            array_column($proposal['nodes'], 'name'),
        );
    }

    public function test_import_accepts_json_wrapped_in_markdown_fence(): void
    {
        $service = app(ProcedureFlowSuggestionService::class);

        $text = "```json\n".json_encode([
            'steps' => [['type' => 'task', 'name' => 'Krok z markdown']],
        ], JSON_UNESCAPED_UNICODE)."\n```";

        $steps = $service->importStepsFromText($text);

        $this->assertSame('Krok z markdown', $steps[0]['name']);
    }

    public function test_import_error_keeps_user_in_modal_without_creating_template(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProcedureTemplatesIndex::class)
            ->call('openNewModal')
            ->set('newName', 'Onboarding pracownika')
            ->call('openImportModal')
            ->set('importText', 'to nie jest json')
            ->call('importFromText')
            ->assertNoRedirect()
            ->assertSet('showImportModal', true)
            ->assertSee('niepoprawny JSON');

        $this->assertSame(0, ProcedureTemplate::query()->count());
    }

    public function test_editor_import_flow_endpoint_returns_definition_from_steps(): void
    {
        $template = ProcedureTemplate::query()->create([
            'name' => 'Onboarding pracownika',
            'created_by' => $this->user->id,
            'definition' => ['nodes' => [], 'edges' => []],
        ]);

        $json = json_encode([
            'steps' => [
                ['type' => 'task', 'name' => 'Przygotuj umowę'],
                ['type' => 'wait', 'name' => 'Czekaj', 'wait' => ['duration' => 10, 'unit' => 'min']],
            ],
        ], JSON_UNESCAPED_UNICODE);

        $response = $this->actingAs($this->user)
            ->postJson(route('procedure-templates.import-flow', $template), ['text' => $json]);

        $response->assertOk()->assertJsonPath('steps', 2);
        $this->assertSame(
            ['Start', 'Przygotuj umowę', 'Czekaj', 'Koniec'],
            array_column($response->json('definition.nodes'), 'name'),
        );
    }
}
