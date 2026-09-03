<?php

namespace Tests\Feature;

use App\Models\ProcedureTemplate;
use App\Models\ProcedureTemplateVersion;
use App\Models\User;
use App\Services\ProcedureRunService;
use App\Services\ProcedureTemplateVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcedureVersioningAndParallelTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create();
        $this->user->assignRole(\Spatie\Permission\Models\Role::where('name', 'administrator')->first());
    }

    public function test_save_creates_new_version_and_runs_use_latest(): void
    {
        $this->actingAs($this->user);

        $template = ProcedureTemplate::query()->create([
            'name' => 'Powrót do domu',
            'created_by' => $this->user->id,
            'definition' => $this->parallelDefinitionV1(),
        ]);

        $this->assertSame(1, $template->versions()->count());

        $runV1 = app(ProcedureRunService::class)->startRun($template, ['task_name' => 'Run v1']);
        $this->assertSame(1, $runV1->version?->version_number);

        $v2Definition = $this->parallelDefinitionV2();
        $template->update(['definition' => $v2Definition]);
        app(ProcedureTemplateVersionService::class)->publishDefinition($template, $v2Definition);

        $this->assertSame(2, $template->fresh()->versions()->count());

        $runV2 = app(ProcedureRunService::class)->startRun($template->fresh(), ['task_name' => 'Run v2']);
        $this->assertSame(2, $runV2->version?->version_number);
        $this->assertSame(1, $runV1->fresh()->version?->version_number);
    }

    public function test_parallel_fork_activates_all_branches_at_once(): void
    {
        $this->actingAs($this->user);

        $template = ProcedureTemplate::query()->create([
            'name' => 'Powrót do domu',
            'created_by' => $this->user->id,
            'definition' => $this->parallelDefinitionV1(),
        ]);

        $service = app(ProcedureRunService::class);
        $run = $service->startRun($template, ['task_name' => 'Dom']);

        $this->assertSame(['start-1'], $run->activeNodeIds());

        $service->advanceNode($run->fresh(), 'start-1');

        $active = $run->fresh()->activeNodeIds();
        sort($active);

        $this->assertSame(['task-1', 'task-2', 'task-3', 'task-4'], $active);
    }

    public function test_advancing_one_parallel_branch_does_not_complete_others(): void
    {
        $this->actingAs($this->user);

        $template = ProcedureTemplate::query()->create([
            'name' => 'Powrót do domu',
            'created_by' => $this->user->id,
            'definition' => $this->parallelDefinitionV1(),
        ]);

        $service = app(ProcedureRunService::class);
        $run = $service->startRun($template, ['task_name' => 'Dom']);
        $service->advanceNode($run->fresh(), 'start-1');

        $service->advanceNode($run->fresh(), 'task-1');

        $active = $run->fresh()->activeNodeIds();
        $this->assertContains('task-2', $active);
        $this->assertContains('task-3', $active);
        $this->assertContains('task-4', $active);
        $this->assertNotContains('task-1', $active);
        $this->assertNotContains('end-1', $active);
        $this->assertSame('in_progress', $run->fresh()->status->value);
    }

    public function test_and_join_waits_for_all_parallel_branches_before_reward(): void
    {
        $this->actingAs($this->user);

        $template = ProcedureTemplate::query()->create([
            'name' => 'Powrót do domu',
            'created_by' => $this->user->id,
            'definition' => $this->rewardDefinition(),
        ]);

        $service = app(ProcedureRunService::class);
        $run = $service->startRun($template, ['task_name' => 'Dom']);
        $service->advanceNode($run->fresh(), 'start-1');

        $service->advanceNode($run->fresh(), 'obiad');
        $active = $run->fresh()->activeNodeIds();
        $this->assertContains('sprzatanie', $active);
        $this->assertContains('praca', $active);
        $this->assertNotContains('piwo', $active);

        $service->advanceNode($run->fresh(), 'sprzatanie');
        $active = $run->fresh()->activeNodeIds();
        $this->assertSame(['praca'], $active);

        $service->advanceNode($run->fresh(), 'praca');
        $this->assertSame(['piwo'], $run->fresh()->activeNodeIds());
    }

    public function test_xor_join_does_not_wait_for_untaken_decision_branch(): void
    {
        $this->actingAs($this->user);

        $template = ProcedureTemplate::query()->create([
            'name' => 'XOR',
            'created_by' => $this->user->id,
            'definition' => $this->xorMergeDefinition(),
        ]);

        $service = app(ProcedureRunService::class);
        $run = $service->startRun($template, ['task_name' => 'XOR']);
        $service->advanceNode($run->fresh(), 'start-1');
        $service->advanceNode($run->fresh(), 'decision-1', 'e-yes', [
            'option_id' => 'opt-yes',
            'label' => 'Tak',
        ]);

        $this->assertSame(['task-a'], $run->fresh()->activeNodeIds());

        $service->advanceNode($run->fresh(), 'task-a');
        $this->assertSame(['merge-1'], $run->fresh()->activeNodeIds());
    }

    public function test_merge_node_appears_once_when_two_branches_reach_it(): void
    {
        $this->actingAs($this->user);

        $template = ProcedureTemplate::query()->create([
            'name' => 'Merge test',
            'created_by' => $this->user->id,
            'definition' => $this->mergeDefinition(),
        ]);

        $service = app(ProcedureRunService::class);
        $run = $service->startRun($template, ['task_name' => 'Merge']);
        $service->advanceNode($run->fresh(), 'start-1');

        $service->advanceNode($run->fresh(), 'task-a');
        $this->assertSame(['task-b'], $run->fresh()->activeNodeIds());

        $service->advanceNode($run->fresh(), 'task-b');

        $active = $run->fresh()->activeNodeIds();
        $this->assertSame(['merge-1'], $active);
    }

    public function test_unused_version_can_be_deleted_but_used_version_cannot(): void
    {
        $this->actingAs($this->user);

        $template = ProcedureTemplate::query()->create([
            'name' => 'Wersje',
            'created_by' => $this->user->id,
            'definition' => $this->parallelDefinitionV1(),
        ]);

        app(ProcedureRunService::class)->startRun($template, ['task_name' => 'Run']);

        $v2Definition = $this->parallelDefinitionV2();
        $template->update(['definition' => $v2Definition]);
        $unusedVersion = app(ProcedureTemplateVersionService::class)->publishDefinition($template, $v2Definition);

        $usedVersion = $template->versions()->where('version_number', 1)->firstOrFail();

        $this->expectException(\RuntimeException::class);
        app(ProcedureTemplateVersionService::class)->deleteVersion($usedVersion);

        app(ProcedureTemplateVersionService::class)->deleteVersion($unusedVersion);

        $this->assertSame(1, $template->fresh()->versions()->count());
    }

    /** @return array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>} */
    private function parallelDefinitionV1(): array
    {
        return [
            'nodes' => [
                ['id' => 'start-1', 'type' => 'start', 'name' => 'Powrót do domu'],
                ['id' => 'task-1', 'type' => 'task', 'name' => 'Sprzątanie', 'assigned_user_id' => null],
                ['id' => 'task-2', 'type' => 'task', 'name' => 'Obiad', 'assigned_user_id' => null],
                ['id' => 'task-3', 'type' => 'task', 'name' => 'Praca domowa', 'assigned_user_id' => null],
                ['id' => 'task-4', 'type' => 'task', 'name' => 'Śmieci', 'assigned_user_id' => null],
                ['id' => 'end-1', 'type' => 'end', 'name' => 'Koniec'],
            ],
            'edges' => [
                ['id' => 'e1', 'from' => 'start-1', 'to' => 'task-1'],
                ['id' => 'e2', 'from' => 'start-1', 'to' => 'task-2'],
                ['id' => 'e3', 'from' => 'start-1', 'to' => 'task-3'],
                ['id' => 'e4', 'from' => 'start-1', 'to' => 'task-4'],
                ['id' => 'e5', 'from' => 'task-1', 'to' => 'end-1'],
                ['id' => 'e6', 'from' => 'task-2', 'to' => 'end-1'],
                ['id' => 'e7', 'from' => 'task-3', 'to' => 'end-1'],
                ['id' => 'e8', 'from' => 'task-4', 'to' => 'end-1'],
            ],
        ];
    }

    /** @return array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>} */
    private function parallelDefinitionV2(): array
    {
        $definition = $this->parallelDefinitionV1();
        $definition['nodes'][] = ['id' => 'task-5', 'type' => 'task', 'name' => 'Nowy krok'];

        return $definition;
    }

    /** @return array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>} */
    private function mergeDefinition(): array
    {
        return [
            'nodes' => [
                ['id' => 'start-1', 'type' => 'start', 'name' => 'Start'],
                ['id' => 'task-a', 'type' => 'task', 'name' => 'A'],
                ['id' => 'task-b', 'type' => 'task', 'name' => 'B'],
                ['id' => 'merge-1', 'type' => 'task', 'name' => 'Merge'],
                ['id' => 'end-1', 'type' => 'end', 'name' => 'Koniec'],
            ],
            'edges' => [
                ['id' => 'e1', 'from' => 'start-1', 'to' => 'task-a'],
                ['id' => 'e2', 'from' => 'start-1', 'to' => 'task-b'],
                ['id' => 'e3', 'from' => 'task-a', 'to' => 'merge-1'],
                ['id' => 'e4', 'from' => 'task-b', 'to' => 'merge-1'],
                ['id' => 'e5', 'from' => 'merge-1', 'to' => 'end-1'],
            ],
        ];
    }

    /** @return array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>} */
    private function rewardDefinition(): array
    {
        return [
            'nodes' => [
                ['id' => 'start-1', 'type' => 'start', 'name' => 'Powrót do domu'],
                ['id' => 'obiad', 'type' => 'task', 'name' => 'Obiad'],
                ['id' => 'sprzatanie', 'type' => 'task', 'name' => 'Sprzątanie'],
                ['id' => 'praca', 'type' => 'task', 'name' => 'Praca domowa'],
                ['id' => 'piwo', 'type' => 'task', 'name' => 'Piwo w nagrodę'],
                ['id' => 'end-1', 'type' => 'end', 'name' => 'Koniec'],
            ],
            'edges' => [
                ['id' => 'e1', 'from' => 'start-1', 'to' => 'obiad'],
                ['id' => 'e2', 'from' => 'start-1', 'to' => 'sprzatanie'],
                ['id' => 'e3', 'from' => 'start-1', 'to' => 'praca'],
                ['id' => 'e4', 'from' => 'obiad', 'to' => 'piwo'],
                ['id' => 'e5', 'from' => 'sprzatanie', 'to' => 'piwo'],
                ['id' => 'e6', 'from' => 'praca', 'to' => 'piwo'],
                ['id' => 'e7', 'from' => 'piwo', 'to' => 'end-1'],
            ],
        ];
    }

    /** @return array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>} */
    private function xorMergeDefinition(): array
    {
        return [
            'nodes' => [
                ['id' => 'start-1', 'type' => 'start', 'name' => 'Start'],
                ['id' => 'decision-1', 'type' => 'decision', 'name' => 'Czy tak?', 'decision' => [
                    'mode' => 'yesno',
                    'options' => [
                        ['id' => 'opt-yes', 'label' => 'Tak'],
                        ['id' => 'opt-no', 'label' => 'Nie'],
                    ],
                ]],
                ['id' => 'task-a', 'type' => 'task', 'name' => 'Ścieżka tak'],
                ['id' => 'merge-1', 'type' => 'task', 'name' => 'Merge'],
                ['id' => 'end-1', 'type' => 'end', 'name' => 'Koniec'],
            ],
            'edges' => [
                ['id' => 'e-start', 'from' => 'start-1', 'to' => 'decision-1'],
                ['id' => 'e-yes', 'from' => 'decision-1', 'to' => 'task-a', 'optionId' => 'opt-yes', 'label' => 'Tak'],
                ['id' => 'e-no', 'from' => 'decision-1', 'to' => 'merge-1', 'optionId' => 'opt-no', 'label' => 'Nie'],
                ['id' => 'e-a', 'from' => 'task-a', 'to' => 'merge-1'],
                ['id' => 'e-end', 'from' => 'merge-1', 'to' => 'end-1'],
            ],
        ];
    }
}
