<?php

namespace App\Services;

use App\Enums\ProcedureRunStatus;
use App\Enums\TaskStatus;
use App\Models\ProcedureRun;
use App\Models\ProcedureRunStep;
use App\Models\ProcedureTemplate;
use App\Models\ProjectTask;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProcedureRunService
{
    /**
     * Create a new procedure run and a linked ProjectTask.
     *
     * @param  array{
     *     task_name: string,
     *     assigned_to: int|null,
     *     due_date: string|null,
     *     subject_type: string|null,
     *     subject_id: int|null,
     *     slot_key: string|null,
     *     variables: array|null,
     * } $params
     */
    public function startRun(ProcedureTemplate $template, array $params): ProcedureRun
    {
        $definition = $template->definition;
        $startNode  = $this->findNodeByType($definition, 'start');

        if ($startNode === null) {
            throw new RuntimeException('Procedura nie ma węzła startowego.');
        }

        return DB::transaction(function () use ($template, $definition, $startNode, $params) {
            $now = now();

            $run = ProcedureRun::create([
                'procedure_template_id' => $template->id,
                'definition_snapshot'   => $definition,
                'subject_type'          => $params['subject_type'] ?? null,
                'subject_id'            => $params['subject_id'] ?? null,
                'slot_key'              => $params['slot_key'] ?? null,
                'current_node_id'       => $startNode['id'],
                'path'                  => [$startNode['id']],
                'status'                => ProcedureRunStatus::IN_PROGRESS,
                'variables'             => $params['variables'] ?? null,
                'started_by'            => Auth::id(),
                'started_at'            => $now,
            ]);

            ProcedureRunStep::create([
                'procedure_run_id' => $run->id,
                'node_id'          => $startNode['id'],
                'node_name'        => $startNode['name'] ?? 'Start',
                'node_type'        => $startNode['type'],
                'entered_at'       => $now,
                'completed_at'     => null,
                'performed_by'     => Auth::id(),
                'data'             => null,
            ]);

            ProjectTask::create([
                'name'              => $params['task_name'],
                'description'       => null,
                'status'            => TaskStatus::IN_PROGRESS,
                'assigned_to'       => $params['assigned_to'] ?? null,
                'due_date'          => $params['due_date'] ?? null,
                'created_by'        => Auth::id(),
                'procedure_run_id'  => $run->id,
            ]);

            return $run;
        });
    }

    /**
     * Advance the run to the next node.
     *
     * @param  string|null  $edgeId  Required when current node is a decision with multiple outgoing edges.
     * @param  array        $stepData  Checklist state or decision choice to store in the current step.
     */
    public function advance(ProcedureRun $run, ?string $edgeId = null, array $stepData = []): void
    {
        if ($run->status !== ProcedureRunStatus::IN_PROGRESS) {
            return;
        }

        $now    = now();
        $userId = Auth::id();

        // Close current step
        $currentStep = ProcedureRunStep::where('procedure_run_id', $run->id)
            ->where('node_id', $run->current_node_id)
            ->whereNull('completed_at')
            ->latest('entered_at')
            ->first();

        if ($currentStep) {
            $currentStep->update([
                'completed_at' => $now,
                'performed_by' => $userId,
                'data'         => $stepData ?: null,
            ]);
        }

        // Determine next node
        $outgoing = $run->outgoingEdges($run->current_node_id);

        if (empty($outgoing)) {
            // Dead-end — treat as finished
            $this->finishRun($run, $now);
            return;
        }

        if (count($outgoing) === 1) {
            $nextNodeId = $outgoing[0]['to'];
        } else {
            // Multiple edges — pick by edgeId or by optionId match
            $chosen = collect($outgoing)->first(fn ($e) => ($e['id'] ?? null) === $edgeId
                || ($e['optionId'] ?? null) === ($stepData['option_id'] ?? null));
            $nextNodeId = ($chosen ?? $outgoing[0])['to'];
        }

        $nextNode = $this->findNodeById($run->definition_snapshot, $nextNodeId);

        if ($nextNode === null) {
            throw new RuntimeException("Następny węzeł '{$nextNodeId}' nie istnieje w definicji.");
        }

        // Finish if we hit end node
        if ($nextNode['type'] === 'end') {
            $run->update([
                'current_node_id' => $nextNodeId,
                'path'            => array_merge($run->path, [$nextNodeId]),
            ]);

            // Record end step as immediately completed
            ProcedureRunStep::create([
                'procedure_run_id' => $run->id,
                'node_id'          => $nextNodeId,
                'node_name'        => $nextNode['name'] ?? 'Koniec',
                'node_type'        => 'end',
                'entered_at'       => $now,
                'completed_at'     => $now,
                'performed_by'     => $userId,
                'data'             => null,
            ]);

            $this->finishRun($run, $now);
            return;
        }

        // Move to next node
        $run->update([
            'current_node_id' => $nextNodeId,
            'path'            => array_merge($run->path, [$nextNodeId]),
        ]);

        ProcedureRunStep::create([
            'procedure_run_id' => $run->id,
            'node_id'          => $nextNodeId,
            'node_name'        => $nextNode['name'] ?? '',
            'node_type'        => $nextNode['type'],
            'entered_at'       => $now,
            'completed_at'     => null,
            'performed_by'     => null,
            'data'             => null,
        ]);
    }

    /**
     * Go back to the previous node in the path.
     */
    public function goBack(ProcedureRun $run): void
    {
        if ($run->status !== ProcedureRunStatus::IN_PROGRESS) {
            return;
        }

        $path = $run->path;
        if (count($path) <= 1) {
            return;
        }

        $now    = now();
        $userId = Auth::id();

        // Remove current node from path
        array_pop($path);
        $prevNodeId = end($path);
        $prevNode   = $this->findNodeById($run->definition_snapshot, $prevNodeId);

        // Close current step without completing
        ProcedureRunStep::where('procedure_run_id', $run->id)
            ->where('node_id', $run->current_node_id)
            ->whereNull('completed_at')
            ->latest('entered_at')
            ->first()
            ?->delete();

        $run->update([
            'current_node_id' => $prevNodeId,
            'path'            => $path,
        ]);

        // Re-open previous step (create new entry)
        ProcedureRunStep::create([
            'procedure_run_id' => $run->id,
            'node_id'          => $prevNodeId,
            'node_name'        => $prevNode['name'] ?? '',
            'node_type'        => $prevNode['type'] ?? 'task',
            'entered_at'       => $now,
            'completed_at'     => null,
            'performed_by'     => null,
            'data'             => null,
        ]);
    }

    /**
     * Abandon an in-progress run and cancel the linked task.
     */
    public function abandon(ProcedureRun $run): void
    {
        if ($run->status !== ProcedureRunStatus::IN_PROGRESS) {
            return;
        }

        DB::transaction(function () use ($run) {
            $run->update([
                'status'      => ProcedureRunStatus::ABANDONED,
                'finished_at' => now(),
            ]);

            $run->task?->cancel();
        });
    }

    // ──────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────

    private function finishRun(ProcedureRun $run, \DateTimeInterface $at): void
    {
        DB::transaction(function () use ($run, $at) {
            $run->update([
                'status'      => ProcedureRunStatus::FINISHED,
                'finished_at' => $at,
            ]);

            $run->task?->markCompleted();
        });
    }

    private function findNodeByType(array $definition, string $type): ?array
    {
        foreach ($definition['nodes'] ?? [] as $node) {
            if (($node['type'] ?? null) === $type) {
                return $node;
            }
        }
        return null;
    }

    private function findNodeById(array $definition, string $id): ?array
    {
        foreach ($definition['nodes'] ?? [] as $node) {
            if (($node['id'] ?? null) === $id) {
                return $node;
            }
        }
        return null;
    }
}
