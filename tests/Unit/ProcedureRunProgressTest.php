<?php

namespace Tests\Unit;

use App\Enums\ProcedureRunStatus;
use App\Models\ProcedureRun;
use App\Models\ProcedureRunStep;
use App\Models\ProcedureTemplateVersion;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ProcedureRunProgressTest extends TestCase
{
    /** Graf z gałęziami: realna ścieżka ~9 kroków, ale w definicji jest 31 węzłów. */
    private function branchingDefinition(): array
    {
        $nodes = [
            ['id' => 'start-1', 'type' => 'start', 'name' => 'Start'],
            ['id' => 'step-1', 'type' => 'task', 'name' => 'Przygotuj dane'],
            ['id' => 'step-2', 'type' => 'decision', 'name' => 'Typ sprawy'],
            ['id' => 'step-3', 'type' => 'task', 'name' => 'Ścieżka A'],
            ['id' => 'step-4', 'type' => 'task', 'name' => 'Ścieżka B'],
            ['id' => 'step-5', 'type' => 'checklist', 'name' => 'Analiza'],
            ['id' => 'step-6', 'type' => 'decision', 'name' => 'Poziom ryzyka'],
            ['id' => 'step-7', 'type' => 'wait', 'name' => 'Oczekiwanie'],
            ['id' => 'step-8', 'type' => 'checklist', 'name' => 'Kontrola'],
            ['id' => 'end-1', 'type' => 'end', 'name' => 'Proces zakończony'],
        ];

        for ($i = 10; $i <= 30; $i++) {
            $nodes[] = ['id' => "alt-{$i}", 'type' => 'task', 'name' => "Alternatywa {$i}"];
        }

        $edges = [
            ['id' => 'e1', 'from' => 'start-1', 'to' => 'step-1'],
            ['id' => 'e2', 'from' => 'step-1', 'to' => 'step-2'],
            ['id' => 'e3', 'from' => 'step-2', 'to' => 'step-3', 'label' => 'Prosta'],
            ['id' => 'e4', 'from' => 'step-2', 'to' => 'step-4', 'label' => 'Złożona'],
            ['id' => 'e5', 'from' => 'step-3', 'to' => 'step-5'],
            ['id' => 'e6', 'from' => 'step-4', 'to' => 'alt-10'],
            ['id' => 'e7', 'from' => 'step-5', 'to' => 'step-6'],
            ['id' => 'e8', 'from' => 'step-6', 'to' => 'step-7', 'label' => 'Niskie'],
            ['id' => 'e9', 'from' => 'step-6', 'to' => 'alt-11', 'label' => 'Wysokie'],
            ['id' => 'e10', 'from' => 'step-7', 'to' => 'step-8'],
            ['id' => 'e11', 'from' => 'step-8', 'to' => 'end-1'],
        ];

        for ($i = 10; $i < 30; $i++) {
            $edges[] = ['id' => "alt-e-{$i}", 'from' => "alt-{$i}", 'to' => 'alt-'.($i + 1)];
        }
        $edges[] = ['id' => 'alt-e-30', 'from' => 'alt-30', 'to' => 'end-1'];

        return compact('nodes', 'edges');
    }

    /** @param  list<array{node_id: string, completed: bool}>  $steps */
    private function makeRun(array $attributes, array $steps): ProcedureRun
    {
        $definition = $this->branchingDefinition();

        $version = ProcedureTemplateVersion::make([
            'version_number' => 1,
            'definition' => $definition,
        ]);

        $run = ProcedureRun::make(array_merge([
            'active_node_ids' => ['step-5'],
        ], $attributes));

        $run->setRelation('version', $version);

        $run->setRelation('steps', Collection::make(array_map(function (array $step) use ($run) {
            return ProcedureRunStep::make([
                'procedure_run_id' => 1,
                'node_id' => $step['node_id'],
                'node_name' => $step['node_id'],
                'node_type' => 'task',
                'entered_at' => now(),
                'completed_at' => $step['completed'] ? now() : null,
            ]);
        }, $steps)));

        return $run;
    }

    public function test_finished_run_reports_full_progress_even_with_many_unused_nodes(): void
    {
        $run = $this->makeRun([
            'active_node_ids' => [],
            'status' => ProcedureRunStatus::FINISHED,
        ], array_map(fn (string $id) => ['node_id' => $id, 'completed' => true], [
            'start-1', 'step-1', 'step-2', 'step-3', 'step-5', 'step-6', 'step-7', 'step-8', 'end-1',
        ]));

        $metrics = $run->progressMetrics();

        $this->assertSame(100, $metrics['percent']);
        $this->assertSame(1.0, $metrics['fraction']);
        $this->assertSame(9, $metrics['completed']);
        $this->assertStringContainsString('ukończono', $metrics['label']);
    }

    public function test_in_progress_run_estimates_total_from_shortest_path_not_all_nodes(): void
    {
        $run = $this->makeRun([
            'active_node_ids' => ['step-5'],
            'status' => ProcedureRunStatus::IN_PROGRESS,
        ], [
            ['node_id' => 'start-1', 'completed' => true],
            ['node_id' => 'step-1', 'completed' => true],
            ['node_id' => 'step-2', 'completed' => true],
            ['node_id' => 'step-3', 'completed' => true],
            ['node_id' => 'step-5', 'completed' => false],
        ]);

        $metrics = $run->progressMetrics();

        $this->assertSame(4, $metrics['completed']);
        $this->assertLessThan(15, $metrics['total'], 'Total nie powinien liczyć wszystkich 31 węzłów w grafie.');
        $this->assertGreaterThan($metrics['completed'], $metrics['total']);
        $this->assertGreaterThan(0, $metrics['percent']);
        $this->assertLessThan(100, $metrics['percent']);
    }

    public function test_old_logic_would_have_underreported_finished_branching_run(): void
    {
        $run = $this->makeRun([
            'active_node_ids' => [],
            'status' => ProcedureRunStatus::FINISHED,
        ], array_map(fn (string $id) => ['node_id' => $id, 'completed' => true], [
            'start-1', 'step-1', 'step-2', 'step-3', 'step-5', 'step-6', 'step-7', 'step-8', 'end-1',
        ]));

        $allNodes = count($run->definition()['nodes']);
        $completed = 9;
        $legacyPercent = (int) round(($completed / $allNodes) * 100);

        $this->assertSame(31, $allNodes);
        $this->assertLessThan(50, $legacyPercent);
        $this->assertSame(100, $run->progressMetrics()['percent']);
    }
}
