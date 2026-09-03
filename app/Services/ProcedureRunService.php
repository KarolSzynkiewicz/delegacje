<?php

namespace App\Services;

use App\Enums\ApprovalDecision;
use App\Enums\ProcedureRunStatus;
use App\Enums\TaskStatus;
use App\Events\ProcedureRunStepCompleted;
use App\Events\ProcedureRunStepEntered;
use App\Models\ApprovalRequest;
use App\Models\ProcedureRun;
use App\Models\ProcedureRunStep;
use App\Models\ProcedureTemplate;
use App\Models\ProjectTask;
use App\Models\User;
use App\Notifications\ProcedureWaitElapsed;
use App\Notifications\TaskAssigned;
use App\ProcedureActions\ActionCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProcedureRunService
{
    public function __construct(private ProcedureTemplateVersionService $versions) {}

    /**
     * Create a new procedure run and a linked ProjectTask.
     *
     * @param  array{
     *     task_name: string,
     *     description: string|null,
     *     category: string|null,
     *     assigned_to: int|null,
     *     due_date: string|null,
     *     subject_type: string|null,
     *     subject_id: int|null,
     *     slot_key: string|null,
     *     variables: array|null,
     *     recruitment_process_id: int|null,
     * } $params
     */
    public function startRun(ProcedureTemplate $template, array $params): ProcedureRun
    {
        $version = $this->versions->resolveVersionForRun($template);
        $definition = $version->definition;
        $startNode = $this->findNodeByType($definition, 'start');

        if ($startNode === null) {
            throw new RuntimeException('Procedura nie ma węzła startowego.');
        }

        $createdTask = null;
        $run = DB::transaction(function () use ($template, $version, $startNode, $params, &$createdTask) {
            $now = now();

            $run = ProcedureRun::create([
                'procedure_template_id' => $template->id,
                'procedure_template_version_id' => $version->id,
                'active_node_ids' => [$startNode['id']],
                'join_tokens' => [],
                'subject_type' => $params['subject_type'] ?? null,
                'subject_id' => $params['subject_id'] ?? null,
                'slot_key' => $params['slot_key'] ?? null,
                'path' => [$startNode['id']],
                'status' => ProcedureRunStatus::IN_PROGRESS,
                'variables' => $params['variables'] ?? null,
                'started_by' => Auth::id(),
                'started_at' => $now,
            ]);

            ProcedureRunStep::create([
                'procedure_run_id' => $run->id,
                'node_id' => $startNode['id'],
                'node_name' => $startNode['name'] ?? 'Start',
                'node_type' => $startNode['type'],
                'entered_at' => $now,
                'completed_at' => null,
                'performed_by' => Auth::id(),
                'data' => null,
            ]);

            $recruitmentProcessId = $params['recruitment_process_id']
                ?? (($params['subject_type'] ?? null) === 'recruitment_process'
                    ? ($params['subject_id'] ?? null)
                    : null);

            $createdTask = ProjectTask::create([
                'name' => $params['task_name'],
                'description' => $params['description'] ?? null,
                'category' => ($params['category'] ?? null) ?: 'Procedura',
                'status' => TaskStatus::IN_PROGRESS,
                'assigned_to' => $params['assigned_to'] ?? null,
                'due_date' => $params['due_date'] ?? null,
                'created_by' => Auth::id(),
                'procedure_run_id' => $run->id,
                'recruitment_process_id' => $recruitmentProcessId,
            ]);

            return $run;
        });

        $actor = Auth::user();
        $assigneeId = $createdTask?->assigned_to;
        if ($createdTask && $assigneeId && $actor && (int) $assigneeId !== (int) $actor->id) {
            User::query()->find($assigneeId)?->notify(new TaskAssigned($createdTask, $actor));
        }

        $this->dispatchStepEntered($run->load(['task', 'version']), $startNode, null);

        return $run;
    }

    /**
     * Advance a single active node in the run (supports parallel branches).
     *
     * @param  array<string, mixed>  $stepData
     */
    public function advanceNode(
        ProcedureRun $run,
        string $nodeId,
        ?string $edgeId = null,
        array $stepData = [],
    ): void {
        if ($run->status !== ProcedureRunStatus::IN_PROGRESS) {
            return;
        }

        $run->loadMissing('version');

        if (! in_array($nodeId, $run->activeNodeIds(), true)) {
            return;
        }

        $definition = $run->definition();
        $node = $this->findNodeById($definition, $nodeId);

        if ($node === null) {
            throw new RuntimeException("Węzeł '{$nodeId}' nie istnieje w definicji.");
        }

        $now = now();
        $userId = Auth::id();

        $currentStep = ProcedureRunStep::query()
            ->where('procedure_run_id', $run->id)
            ->where('node_id', $nodeId)
            ->whereNull('completed_at')
            ->latest('entered_at')
            ->first();

        $stepData = $this->applyNodeSideEffects($run, $node, $stepData, $userId);

        if ($currentStep) {
            $currentStep->update([
                'completed_at' => $now,
                'performed_by' => $userId,
                'data' => $stepData ?: null,
                'resume_at' => null,
            ]);
        }

        $outgoing = $run->outgoingEdges($nodeId);

        if (empty($outgoing)) {
            $this->dispatchStepCompleted($run, $node, null);
            $this->removeActiveNode($run, $nodeId);

            if ($run->fresh()->activeNodeIds() === []) {
                $this->finishRun($run->fresh(), $now);
            }

            return;
        }

        if (in_array($node['type'] ?? '', ['decision', 'approval'], true)) {
            $chosen = collect($outgoing)->first(fn ($e) => ($e['id'] ?? null) === $edgeId
                || ($e['optionId'] ?? null) === ($stepData['option_id'] ?? $stepData['approval_decision'] ?? null));

            if ($chosen === null) {
                $label = ($node['type'] ?? '') === 'approval'
                    ? 'Zatwierdzenie nie ma gałęzi dla tej decyzji.'
                    : 'Wybierz opcję decyzji przed kontynuacją.';
                throw new RuntimeException($label);
            }

            $outgoing = [$chosen];
        }

        $nextNodeIds = [];

        foreach ($outgoing as $edge) {
            $nextNodeId = (string) $edge['to'];
            $nextNode = $this->findNodeById($definition, $nextNodeId);

            if ($nextNode === null) {
                throw new RuntimeException("Następny węzeł '{$nextNodeId}' nie istnieje w definicji.");
            }

            $this->registerToken($run, $nodeId, $nextNodeId);
            $this->dispatchStepCompleted($run, $node, $nextNode);

            if (! $this->joinReady($run, $nextNodeId)) {
                continue;
            }

            if (($nextNode['type'] ?? '') === 'end') {
                $this->recordEndStep($run, $nextNode, $now, $userId, $currentStep?->id);

                continue;
            }

            $nextNodeIds[] = $nextNodeId;
        }

        $this->removeActiveNode($run, $nodeId);
        $run->refresh();

        if ($nextNodeIds !== []) {
            $this->activateNodes($run, $nextNodeIds, $currentStep?->id, $node, $now);
        }

        if ($run->fresh()->activeNodeIds() === []) {
            $this->finishRun($run->fresh(), $now);
        }
    }

    /** @deprecated Use advanceNode() — kept for HTTP/tests that omit node id on linear flows. */
    public function advance(ProcedureRun $run, ?string $edgeId = null, array $stepData = []): void
    {
        $active = $run->activeNodeIds();

        if ($active === []) {
            return;
        }

        $this->advanceNode($run, $active[0], $edgeId, $stepData);
    }

    /**
     * Revert a specific active or recently completed node.
     */
    public function goBackNode(ProcedureRun $run, string $nodeId): void
    {
        if ($run->status !== ProcedureRunStatus::IN_PROGRESS) {
            return;
        }

        $run->loadMissing('version');
        $now = now();

        $openStep = ProcedureRunStep::query()
            ->where('procedure_run_id', $run->id)
            ->where('node_id', $nodeId)
            ->whereNull('completed_at')
            ->latest('entered_at')
            ->first();

        if ($openStep !== null) {
            $this->discardPendingApproval($openStep);
            $this->cancelSpawnedSteps($run, $openStep->id);
            $openStep->delete();
            $this->removeActiveNode($run, $nodeId);

            return;
        }

        $completedStep = ProcedureRunStep::query()
            ->where('procedure_run_id', $run->id)
            ->where('node_id', $nodeId)
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->first();

        if ($completedStep === null) {
            return;
        }

        $this->revokeTokensFrom($run, $nodeId);
        $this->cancelSpawnedSteps($run, $completedStep->id);

        $completedStep->update([
            'completed_at' => null,
            'performed_by' => null,
            'data' => null,
        ]);

        $this->addActiveNode($run, $nodeId);

        $reopened = ProcedureRunStep::create([
            'procedure_run_id' => $run->id,
            'node_id' => $nodeId,
            'node_name' => $completedStep->node_name,
            'node_type' => $completedStep->node_type,
            'entered_at' => $now,
            'completed_at' => null,
            'performed_by' => null,
            'data' => null,
            'spawned_from_step_id' => $completedStep->spawned_from_step_id,
        ]);

        $node = $this->findNodeById($run->definition(), $nodeId);
        if ($node !== null) {
            $this->afterActivate($run, $reopened, $node);
        }
    }

    /** @deprecated Use goBackNode() */
    public function goBack(ProcedureRun $run): void
    {
        $active = $run->activeNodeIds();

        if ($active === []) {
            return;
        }

        $this->goBackNode($run, $active[0]);
    }

    public function abandon(ProcedureRun $run): void
    {
        if ($run->status !== ProcedureRunStatus::IN_PROGRESS) {
            return;
        }

        DB::transaction(function () use ($run) {
            $run->update([
                'status' => ProcedureRunStatus::ABANDONED,
                'finished_at' => now(),
                'active_node_ids' => [],
                'join_tokens' => [],
            ]);

            $run->task?->cancel();
        });
    }

    /**
     * @param  list<string>  $nodeIds
     * @param  array<string, mixed>  $previousNode
     */
    protected function activateNodes(
        ProcedureRun $run,
        array $nodeIds,
        ?int $spawnedFromStepId,
        array $previousNode,
        \DateTimeInterface $now,
    ): void {
        $uniqueIds = array_values(array_unique($nodeIds));
        $active = $run->activeNodeIds();
        $path = $run->path ?? [];

        foreach ($uniqueIds as $nextNodeId) {
            if (! in_array($nextNodeId, $active, true)) {
                $active[] = $nextNodeId;
            }

            if (! in_array($nextNodeId, $path, true)) {
                $path[] = $nextNodeId;
            }

            $nextNode = $this->findNodeById($run->definition(), $nextNodeId);

            if ($nextNode === null) {
                continue;
            }

            $alreadyOpen = ProcedureRunStep::query()
                ->where('procedure_run_id', $run->id)
                ->where('node_id', $nextNodeId)
                ->whereNull('completed_at')
                ->exists();

            if (! $alreadyOpen) {
                $opened = ProcedureRunStep::create([
                    'procedure_run_id' => $run->id,
                    'spawned_from_step_id' => $spawnedFromStepId,
                    'node_id' => $nextNodeId,
                    'node_name' => $nextNode['name'] ?? '',
                    'node_type' => $nextNode['type'],
                    'entered_at' => $now,
                    'completed_at' => null,
                    'performed_by' => null,
                    'data' => null,
                ]);

                $this->afterActivate($run, $opened, $nextNode);
                $this->dispatchStepEntered($run->load(['task', 'version']), $nextNode, $previousNode);
            }
        }

        $run->update([
            'active_node_ids' => $active,
            'path' => $path,
        ]);
    }

    protected function cancelSpawnedSteps(ProcedureRun $run, int $parentStepId): void
    {
        $queue = ProcedureRunStep::query()
            ->where('procedure_run_id', $run->id)
            ->where('spawned_from_step_id', $parentStepId)
            ->pluck('id')
            ->all();

        while ($queue !== []) {
            $stepId = array_shift($queue);
            $step = ProcedureRunStep::query()->find($stepId);

            if ($step === null) {
                continue;
            }

            $childIds = ProcedureRunStep::query()
                ->where('procedure_run_id', $run->id)
                ->where('spawned_from_step_id', $stepId)
                ->pluck('id')
                ->all();

            $queue = array_merge($queue, $childIds);

            $this->discardPendingApproval($step);
            $this->removeActiveNode($run, $step->node_id);
            $step->delete();
        }
    }

    protected function removeActiveNode(ProcedureRun $run, string $nodeId): void
    {
        $active = array_values(array_filter(
            $run->activeNodeIds(),
            fn (string $id) => $id !== $nodeId
        ));

        $run->update(['active_node_ids' => $active]);
    }

    protected function addActiveNode(ProcedureRun $run, string $nodeId): void
    {
        $active = $run->activeNodeIds();

        if (! in_array($nodeId, $active, true)) {
            $active[] = $nodeId;
        }

        $run->update(['active_node_ids' => $active]);
    }

    /**
     * BPMN AND-join: a node activates only after every live incoming token has arrived.
     * XOR branches that were never taken are not waited on.
     */
    protected function registerToken(ProcedureRun $run, string $fromNodeId, string $toNodeId): void
    {
        $tokens = $run->join_tokens ?? [];
        $arrived = array_values($tokens[$toNodeId] ?? []);

        if (! in_array($fromNodeId, $arrived, true)) {
            $arrived[] = $fromNodeId;
        }

        $tokens[$toNodeId] = $arrived;
        $run->update(['join_tokens' => $tokens]);
        $run->refresh();
    }

    /** @param  array<string, bool>  $visited */
    protected function revokeTokensFrom(ProcedureRun $run, string $fromNodeId, array &$visited = []): void
    {
        $run->refresh();
        $tokens = $run->join_tokens ?? [];
        $targets = [];

        foreach ($tokens as $toNodeId => $fromIds) {
            $fromIds = array_values(array_map('strval', $fromIds ?? []));
            if (! in_array($fromNodeId, $fromIds, true)) {
                continue;
            }

            $fromIds = array_values(array_filter($fromIds, fn ($id) => $id !== $fromNodeId));
            if ($fromIds === []) {
                unset($tokens[$toNodeId]);
            } else {
                $tokens[$toNodeId] = $fromIds;
            }
            $targets[] = (string) $toNodeId;
        }

        $run->update(['join_tokens' => $tokens]);

        foreach ($targets as $toNodeId) {
            $this->unwindIfJoinIncomplete($run, $toNodeId, $visited);
        }
    }

    /** @param  array<string, bool>  $visited */
    protected function unwindIfJoinIncomplete(ProcedureRun $run, string $nodeId, array &$visited = []): void
    {
        if (isset($visited[$nodeId])) {
            return;
        }
        $visited[$nodeId] = true;

        $run->refresh();
        if ($this->joinReady($run, $nodeId)) {
            return;
        }

        $steps = ProcedureRunStep::query()
            ->where('procedure_run_id', $run->id)
            ->where('node_id', $nodeId)
            ->get();

        foreach ($steps as $step) {
            $this->cancelSpawnedSteps($run, $step->id);
            $step->delete();
        }

        $this->removeActiveNode($run, $nodeId);
        $this->revokeTokensFrom($run, $nodeId, $visited);
    }

    protected function joinReady(ProcedureRun $run, string $toNodeId): bool
    {
        $arrived = $this->arrivedSources($run, $toNodeId);
        $expected = $this->expectedIncomingSources($run, $toNodeId);

        if ($expected === []) {
            return $arrived !== [];
        }

        foreach ($expected as $fromId) {
            if (! in_array($fromId, $arrived, true)) {
                return false;
            }
        }

        return true;
    }

    /** @return list<string> */
    protected function arrivedSources(ProcedureRun $run, string $toNodeId): array
    {
        return array_values($run->join_tokens[$toNodeId] ?? []);
    }

    /**
     * Incoming edges that already delivered a token, are still active, or can still
     * be reached from an active node (so a token is still in flight).
     *
     * @return list<string>
     */
    protected function expectedIncomingSources(ProcedureRun $run, string $toNodeId): array
    {
        $arrived = $this->arrivedSources($run, $toNodeId);
        $active = $run->activeNodeIds();
        $reachable = $this->nodesReachableFromActive($run);
        $expected = [];

        foreach ($run->incomingEdges($toNodeId) as $edge) {
            $fromId = (string) ($edge['from'] ?? '');
            if ($fromId === '') {
                continue;
            }

            if (in_array($fromId, $arrived, true)
                || in_array($fromId, $active, true)
                || in_array($fromId, $reachable, true)
            ) {
                $expected[] = $fromId;
            }
        }

        return array_values(array_unique($expected));
    }

    /**
     * Nodes that can still be visited from currently active nodes (future path).
     *
     * @return list<string>
     */
    protected function nodesReachableFromActive(ProcedureRun $run): array
    {
        $reachable = [];
        $visited = [];
        $queue = $run->activeNodeIds();

        while ($queue !== []) {
            $nodeId = array_shift($queue);
            if (isset($visited[$nodeId])) {
                continue;
            }
            $visited[$nodeId] = true;

            foreach ($run->outgoingEdges($nodeId) as $edge) {
                $to = (string) ($edge['to'] ?? '');
                if ($to === '' || isset($visited[$to])) {
                    continue;
                }
                $reachable[] = $to;
                $queue[] = $to;
            }
        }

        return array_values(array_unique($reachable));
    }

    /**
     * @param  array<string, mixed>  $endNode
     */
    protected function recordEndStep(
        ProcedureRun $run,
        array $endNode,
        \DateTimeInterface $now,
        ?int $userId,
        ?int $spawnedFromStepId,
    ): void {
        $endId = (string) $endNode['id'];

        $alreadyRecorded = ProcedureRunStep::query()
            ->where('procedure_run_id', $run->id)
            ->where('node_id', $endId)
            ->where('node_type', 'end')
            ->exists();

        if ($alreadyRecorded) {
            return;
        }

        $path = $run->path ?? [];

        if (! in_array($endId, $path, true)) {
            $path[] = $endId;
        }

        ProcedureRunStep::create([
            'procedure_run_id' => $run->id,
            'spawned_from_step_id' => $spawnedFromStepId,
            'node_id' => $endId,
            'node_name' => $endNode['name'] ?? 'Koniec',
            'node_type' => 'end',
            'entered_at' => $now,
            'completed_at' => $now,
            'performed_by' => $userId,
            'data' => null,
        ]);

        $run->update(['path' => $path]);
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>|null  $previousNode
     */
    protected function dispatchStepEntered(ProcedureRun $run, array $node, ?array $previousNode): void
    {
        ProcedureRunStepEntered::dispatch($run, $node, $previousNode);
    }

    /**
     * @param  array<string, mixed>|null  $leavingNode
     * @param  array<string, mixed>|null  $nextNode
     */
    protected function dispatchStepCompleted(ProcedureRun $run, ?array $leavingNode, ?array $nextNode): void
    {
        ProcedureRunStepCompleted::dispatch($run, $leavingNode, $nextNode);
    }

    private function finishRun(ProcedureRun $run, \DateTimeInterface $at): void
    {
        DB::transaction(function () use ($run, $at) {
            $run->update([
                'status' => ProcedureRunStatus::FINISHED,
                'finished_at' => $at,
                'active_node_ids' => [],
                'join_tokens' => [],
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

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $stepData
     * @return array<string, mixed>
     */
    protected function applyNodeSideEffects(ProcedureRun $run, array $node, array $stepData, ?int $userId): array
    {
        $type = $node['type'] ?? '';
        $actor = Auth::user() ?? $run->startedBy;

        if ($type === 'approval' && empty($stepData['approval_decision'])) {
            throw new RuntimeException('Ten krok czeka na zatwierdzenie.');
        }

        if ($type === 'comment') {
            if (! $actor) {
                throw new RuntimeException('Brak użytkownika do zapisania komentarza.');
            }
            $comment = app(ProcedureSubjectComment::class)->write(
                $run,
                $node,
                (string) ($stepData['body'] ?? ''),
                $actor,
            );
            $stepData['comment_id'] = $comment->id;
        }

        if ($type === 'action') {
            if (! $actor) {
                throw new RuntimeException('Brak użytkownika do wykonania akcji.');
            }
            $key = (string) ($node['action'] ?? '');
            if ($key === '') {
                throw new RuntimeException('Węzeł akcji nie ma wybranej operacji.');
            }
            $stepData['result'] = app(ActionCatalog::class)->execute($key, $run, $stepData, $actor);
            $stepData['action'] = $key;
        }

        return $stepData;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    protected function afterActivate(ProcedureRun $run, ProcedureRunStep $step, array $node): void
    {
        $type = $node['type'] ?? '';

        if ($type === 'wait') {
            $step->update(['resume_at' => ProcedureWait::resumeAt($node['wait'] ?? [], $step->entered_at)]);
        }

        if ($type === 'approval') {
            $this->openApproval($run, $step, $node);
        }
    }

    /**
     * @param  array<string, mixed>  $node
     */
    protected function openApproval(ProcedureRun $run, ProcedureRunStep $step, array $node): void
    {
        $approverId = (int) ($node['assigned_user_id'] ?? 0);
        if ($approverId <= 0) {
            throw new RuntimeException('Krok zatwierdzenia musi mieć odpowiedzialnego (zatwierdzającego).');
        }

        $run->loadMissing(['template', 'task', 'startedBy']);

        $approval = ApprovalRequest::query()->create([
            'name' => $node['name'] ?? ('Zatwierdzenie: '.($run->template?->name ?? 'procedura')),
            'description' => trim((string) ($node['instructions'] ?? $node['description'] ?? '')) ?: null,
            'approver_id' => $approverId,
            'created_by' => Auth::id() ?? $run->started_by,
            'procedure_run_id' => $run->id,
            'sprint_id' => $run->task?->sprint_id,
            'category' => $run->template?->category ?: $run->task?->category,
        ]);

        $step->update(['approval_request_id' => $approval->id]);
    }

    protected function discardPendingApproval(ProcedureRunStep $step): void
    {
        if (! $step->approval_request_id) {
            return;
        }

        $approval = ApprovalRequest::query()->find($step->approval_request_id);
        if ($approval && ! $approval->isDecided()) {
            $approval->delete();
        }
    }

    public function resumeFromApproval(ApprovalRequest $approval): void
    {
        $approval->loadMissing('procedureRun.version');
        $run = $approval->procedureRun;
        if ($run === null || $run->status !== ProcedureRunStatus::IN_PROGRESS) {
            return;
        }

        $step = ProcedureRunStep::query()
            ->where('procedure_run_id', $run->id)
            ->where('approval_request_id', $approval->id)
            ->whereNull('completed_at')
            ->first();

        if ($step === null) {
            return;
        }

        $decision = $approval->decision?->value;
        $outgoing = $run->outgoingEdges($step->node_id);
        $wantedLabel = $decision === ApprovalDecision::Approved->value ? 'Zatwierdzone' : 'Odrzucone';
        $edge = collect($outgoing)->first(function ($e) use ($decision, $wantedLabel) {
            return ($e['optionId'] ?? null) === $decision
                || strcasecmp((string) ($e['label'] ?? ''), $wantedLabel) === 0;
        });

        if ($edge === null && $outgoing !== []) {
            $edge = $decision === ApprovalDecision::Approved->value
                ? $outgoing[0]
                : ($outgoing[1] ?? $outgoing[0]);
        }

        $this->advanceNode($run, $step->node_id, $edge['id'] ?? null, [
            'approval_decision' => $decision,
            'option_id' => $decision,
            'approval_request_id' => $approval->id,
        ]);
    }

    public function resumeExpiredWaits(?ProcedureRun $only = null): int
    {
        $query = ProcedureRunStep::query()
            ->with(['run.version', 'run.template', 'run.task', 'run.startedBy'])
            ->where('node_type', 'wait')
            ->whereNull('completed_at');

        if ($only) {
            $query->where('procedure_run_id', $only->id);
        }

        $steps = $query->get();
        $count = 0;

        foreach ($steps as $step) {
            $run = $step->run;
            if ($run === null || $run->status !== ProcedureRunStatus::IN_PROGRESS) {
                continue;
            }
            if (! in_array($step->node_id, $run->activeNodeIds(), true)) {
                continue;
            }

            if ($step->resume_at === null) {
                $node = $run->findNodeById($step->node_id) ?? [];
                $step->update(['resume_at' => ProcedureWait::resumeAt($node['wait'] ?? [], $step->entered_at)]);
                $step->refresh();
            }

            if ($step->resume_at === null || $step->resume_at->gt(now())) {
                continue;
            }

            $this->advanceNode($run, $step->node_id, null, ['wait_elapsed' => true]);
            $this->notifyWaitElapsed($run->fresh(), $step);
            $count++;
        }

        return $count;
    }

    protected function notifyWaitElapsed(ProcedureRun $run, ProcedureRunStep $step): void
    {
        $run->loadMissing(['task', 'startedBy', 'version']);
        $node = $run->findNodeById($step->node_id);
        $ids = array_filter([
            (int) ($node['assigned_user_id'] ?? 0),
            (int) ($run->task?->assigned_to ?? 0),
        ]);

        foreach (array_unique($ids) as $userId) {
            if ($userId <= 0) {
                continue;
            }
            User::query()->find($userId)?->notify(new ProcedureWaitElapsed($run, $step, $run->startedBy));
        }
    }
}
