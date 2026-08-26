<?php

namespace Tests\Feature;

use App\Contracts\Llm\LlmCredentialRepository;
use App\Livewire\TaskSubtasks;
use App\Models\ProjectTask;
use App\Models\TaskSubtask;
use App\Models\User;
use App\Services\Llm\SubtaskSuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaskSubtaskAiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);
        $user = User::factory()->create();
        $user->assignRole(Role::where('name', 'administrator')->first());

        return $user;
    }

    public function test_ai_button_is_visible_when_llm_is_configured(): void
    {
        app(LlmCredentialRepository::class)->store('gemini', 'AIzaTESTKEY1234567890', 'gemini-2.5-flash');
        app(LlmCredentialRepository::class)->activate('gemini');

        $task = ProjectTask::query()->create([
            'name' => 'Rekrutacja malarzy',
            'description' => 'Domknąć rekrutację do końca sprintu.',
            'status' => \App\Enums\TaskStatus::PENDING,
            'created_by' => $this->admin()->id,
        ]);

        Livewire::actingAs($this->admin())
            ->test(TaskSubtasks::class, ['task' => $task])
            ->assertSee('AskChrono')
            ->assertSee('Rozbij na podzadania');
    }

    public function test_subtask_rows_render_quiet_icon_actions(): void
    {
        $user = $this->admin();
        $task = ProjectTask::query()->create([
            'name' => 'Rekrutacja malarzy',
            'status' => \App\Enums\TaskStatus::PENDING,
            'created_by' => $user->id,
        ]);
        TaskSubtask::query()->create([
            'task_id' => $task->id,
            'name' => 'Rozesłać ogłoszenia',
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(TaskSubtasks::class, ['task' => $task])
            ->assertSee('st-item', false)
            ->assertSee('st-action', false)
            ->assertSee('Edytuj podzadanie')
            ->assertSee('Przypisz osobę')
            ->assertSee('Usuń podzadanie')
            ->assertDontSee('form-check-input', false)
            ->assertDontSee('btn-outline-danger', false);
    }

    public function test_opening_modal_shows_thinking_state_before_the_model_answers(): void
    {
        app(LlmCredentialRepository::class)->store('gemini', 'AIzaTESTKEY1234567890', 'gemini-2.5-flash');
        app(LlmCredentialRepository::class)->activate('gemini');

        $user = $this->admin();
        $task = ProjectTask::query()->create([
            'name' => 'Rekrutacja malarzy',
            'status' => \App\Enums\TaskStatus::PENDING,
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(TaskSubtasks::class, ['task' => $task])
            ->call('openAiModal')
            ->assertSet('showAiModal', true)
            ->assertSet('aiLoading', true)
            ->assertSee('ac-bot--thinking', false)
            ->assertSee('Czytam zadanie i układam kroki');
    }

    public function test_request_ai_subtasks_opens_modal_with_proposals(): void
    {
        app(LlmCredentialRepository::class)->store('gemini', 'AIzaTESTKEY1234567890', 'gemini-2.5-flash');
        app(LlmCredentialRepository::class)->activate('gemini');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => '{"subtasks":["Utrzymywać tablicę pipeline","Codzienny checkpoint lejka"]}',
                        ]],
                    ],
                    'finishReason' => 'STOP',
                ]],
                'modelVersion' => 'gemini-2.5-flash',
            ], 200),
        ]);

        $user = $this->admin();
        $task = ProjectTask::query()->create([
            'name' => 'Rekrutacja malarzy',
            'description' => 'Domknąć rekrutację do końca sprintu.',
            'status' => \App\Enums\TaskStatus::PENDING,
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(TaskSubtasks::class, ['task' => $task])
            ->call('openAiModal')
            ->call('fetchAiProposals')
            ->assertSet('showAiModal', true)
            ->assertSet('aiLoading', false)
            // Wartości pól wire:model wstawia dopiero przeglądarka, więc
            // treść propozycji sprawdzamy na stanie, a nie w HTML-u.
            ->assertSet('aiProposals', ['Utrzymywać tablicę pipeline', 'Codzienny checkpoint lejka'])
            ->assertSee('Mam 2 propozycji')
            ->assertSee('ac-bot--done', false);
    }

    public function test_confirm_selected_ai_proposals_creates_only_checked_subtasks(): void
    {
        $user = $this->admin();
        $task = ProjectTask::query()->create([
            'name' => 'Rekrutacja malarzy',
            'status' => \App\Enums\TaskStatus::PENDING,
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(TaskSubtasks::class, ['task' => $task])
            ->set('showAiModal', true)
            ->set('aiProposals', ['Krok A', 'Krok B', 'Krok C'])
            ->set('aiSelected', [0, 2])
            ->call('confirmSelectedAiProposals')
            ->assertSet('showAiModal', true)
            ->assertSet('aiProposals', ['Krok B']);

        $this->assertSame(['Krok A', 'Krok C'], TaskSubtask::query()->orderBy('id')->pluck('name')->all());
    }

    public function test_confirm_selected_with_all_checked_closes_the_modal(): void
    {
        $user = $this->admin();
        $task = ProjectTask::query()->create([
            'name' => 'Rekrutacja malarzy',
            'status' => \App\Enums\TaskStatus::PENDING,
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(TaskSubtasks::class, ['task' => $task])
            ->set('showAiModal', true)
            ->set('aiProposals', ['Krok A', 'Krok B'])
            ->set('aiSelected', [0, 1])
            ->call('confirmSelectedAiProposals')
            ->assertSet('showAiModal', false);

        $this->assertSame(['Krok A', 'Krok B'], TaskSubtask::query()->orderBy('id')->pluck('name')->all());
    }

    public function test_confirm_single_ai_proposal_creates_one_subtask(): void
    {
        $user = $this->admin();
        $task = ProjectTask::query()->create([
            'name' => 'Rekrutacja malarzy',
            'status' => \App\Enums\TaskStatus::PENDING,
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(TaskSubtasks::class, ['task' => $task])
            ->set('showAiModal', true)
            ->set('aiProposals', ['Krok A', 'Krok B'])
            ->call('confirmAiProposal', 0)
            ->assertSet('showAiModal', true)
            ->assertCount('aiProposals', 1);

        $this->assertSame(['Krok A'], TaskSubtask::query()->pluck('name')->all());
    }

    public function test_subtask_suggestion_service_parses_json_response(): void
    {
        app(LlmCredentialRepository::class)->store('gemini', 'AIzaTESTKEY1234567890', 'gemini-2.5-flash');
        app(LlmCredentialRepository::class)->activate('gemini');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => '{"subtasks":["Krok 1","Krok 2"]}',
                        ]],
                    ],
                    'finishReason' => 'STOP',
                ]],
            ], 200),
        ]);

        $task = ProjectTask::query()->create([
            'name' => 'Test',
            'description' => 'Opis zadania',
            'status' => \App\Enums\TaskStatus::PENDING,
            'created_by' => User::factory()->create()->id,
        ]);

        $subtasks = app(SubtaskSuggestionService::class)->suggest($task);

        $this->assertSame(['Krok 1', 'Krok 2'], $subtasks);
    }
}
