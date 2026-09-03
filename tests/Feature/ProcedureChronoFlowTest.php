<?php

namespace Tests\Feature;

use App\Contracts\Llm\LlmCredentialRepository;
use App\Livewire\ProcedureTemplatesIndex;
use App\Models\ProcedureTemplate;
use App\Models\User;
use App\Services\Llm\ProcedureFlowSuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProcedureChronoFlowTest extends TestCase
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

    private function configureLlm(): void
    {
        app(LlmCredentialRepository::class)->store('gemini', 'AIzaTESTKEY1234567890', 'gemini-2.5-flash');
        app(LlmCredentialRepository::class)->activate('gemini');
    }

    private function fakeModelResponse(string $text): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => $text]]],
                    'finishReason' => 'STOP',
                ]],
            ], 200),
        ]);
    }

    public function test_chrono_trigger_is_visible_in_new_template_modal_when_llm_is_configured(): void
    {
        $this->configureLlm();

        Livewire::actingAs($this->user)
            ->test(ProcedureTemplatesIndex::class)
            ->call('openNewModal')
            ->assertSee('Chrono Assist')
            ->assertSee('Zaproponuj przepływ');
    }

    public function test_opening_chrono_requires_a_name_and_shows_thinking_state(): void
    {
        $this->configureLlm();

        Livewire::actingAs($this->user)
            ->test(ProcedureTemplatesIndex::class)
            ->call('openNewModal')
            ->call('openChronoModal')
            ->assertHasErrors(['newName' => 'required'])
            ->assertSet('showChronoModal', false)
            ->set('newName', 'Onboarding pracownika')
            ->call('openChronoModal')
            ->assertSet('showChronoModal', true)
            ->assertSet('chronoLoading', true)
            ->assertSee('ac-bot--thinking', false)
            ->assertSee('Projektuję kroki procedury');
    }

    public function test_generated_flow_lands_in_the_editor_as_an_unsaved_draft(): void
    {
        $this->configureLlm();
        $this->fakeModelResponse(json_encode([
            'steps' => [
                ['type' => 'task', 'name' => 'Przygotuj umowę'],
                ['type' => 'checklist', 'name' => 'Sprzęt', 'checklist' => ['Laptop', 'Telefon']],
            ],
        ], JSON_UNESCAPED_UNICODE));

        Livewire::actingAs($this->user)
            ->test(ProcedureTemplatesIndex::class)
            ->call('openNewModal')
            ->set('newName', 'Onboarding pracownika')
            ->set('newSubjectType', 'employee')
            ->call('openChronoModal')
            ->call('fetchChronoFlow')
            ->assertRedirect();

        $template = ProcedureTemplate::query()->firstOrFail();

        // Szablon zapisuje się pusty — propozycja czeka na canvasie na „Zapisz".
        $this->assertSame([], $template->definition['nodes'] ?? null);
        $this->assertSame([], $template->definition['edges'] ?? null);

        $proposal = session('chrono_proposal');
        $this->assertSame(
            ['Start', 'Przygotuj umowę', 'Sprzęt', 'Koniec'],
            array_column($proposal['nodes'], 'name'),
        );

        $this->get(route('procedure-templates.editor', $template))
            ->assertOk()
            ->assertSee('chronoProposal', false)
            ->assertSee('Przygotuj umowę');
    }

    public function test_llm_error_keeps_the_user_in_the_modal_without_creating_a_template(): void
    {
        $this->configureLlm();
        $this->fakeModelResponse('to nie jest json');

        Livewire::actingAs($this->user)
            ->test(ProcedureTemplatesIndex::class)
            ->call('openNewModal')
            ->set('newName', 'Onboarding pracownika')
            ->call('openChronoModal')
            ->call('fetchChronoFlow')
            ->assertNoRedirect()
            ->assertSet('showChronoModal', true)
            ->assertSet('chronoLoading', false)
            ->assertSee('Nie udało się przygotować propozycji');

        $this->assertSame(0, ProcedureTemplate::query()->count());
    }

    public function test_editor_endpoint_returns_a_flow_for_an_existing_template(): void
    {
        $this->configureLlm();
        $this->fakeModelResponse('{"steps":[{"type":"task","name":"Zbierz dokumenty"}]}');

        $template = ProcedureTemplate::query()->create([
            'name' => 'Onboarding pracownika',
            'created_by' => $this->user->id,
            'definition' => ['nodes' => [], 'edges' => []],
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('procedure-templates.chrono-flow', $template));

        $response->assertOk()->assertJsonPath('steps', 1);
        $this->assertSame(
            ['Start', 'Zbierz dokumenty', 'Koniec'],
            array_column($response->json('definition.nodes'), 'name'),
        );

        // Endpoint tylko proponuje — zapis robi dopiero „Zapisz" w edytorze.
        $fresh = $template->fresh();
        $this->assertSame([], $fresh->definition['nodes'] ?? null);
        $this->assertSame([], $fresh->definition['edges'] ?? null);
    }

    public function test_build_definition_wires_a_decision_branch_to_the_next_step_and_to_the_end(): void
    {
        $service = app(ProcedureFlowSuggestionService::class);

        $definition = $service->buildDefinition([
            ['type' => 'decision', 'name' => 'Czy komplet dokumentów?', 'options' => ['Tak', 'Nie'], 'checklist' => [], 'wait' => null],
            ['type' => 'wait', 'name' => 'Poczekaj na skan', 'options' => [], 'checklist' => [], 'wait' => ['duration' => 2, 'unit' => 'godz']],
        ]);

        $this->assertSame(['start', 'decision', 'wait', 'end'], array_column($definition['nodes'], 'type'));

        $pairs = array_map(fn (array $edge) => $edge['from'].'→'.$edge['to'], $definition['edges']);
        $this->assertSame([
            'start-1→step-1',
            'step-1→step-2',
            'step-1→end-1',
            'step-2→end-1',
        ], $pairs);

        $decision = collect($definition['nodes'])->firstWhere('type', 'decision');
        $this->assertSame(['Tak', 'Nie'], array_column($decision['decision']['options'], 'label'));

        $wait = collect($definition['nodes'])->firstWhere('type', 'wait');
        $this->assertSame(['duration' => 2, 'unit' => 'godz'], $wait['wait']);
    }
}
