<?php

namespace App\Livewire;

use App\Enums\ApprovalDecision;
use App\Enums\ProcedureRunStatus;
use App\Models\ProcedureRun;
use App\Models\User;
use App\ProcedureActions\ActionCatalog;
use App\Services\ProcedureRunService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use RuntimeException;

class ProcedureRunStepper extends Component
{
    public ProcedureRun $run;

    /** When true, the parent page already shows title/status — stepper is only the current step. */
    public bool $compact = false;

    /** Checklist state: [node_id => [item_id => bool]] */
    public array $checklistState = [];

    /** Decision choice per node: [node_id => edge_id] */
    public array $selectedEdgeIds = [];

    /** Comment capture: [node_id => body] */
    public array $commentBodies = [];

    /** Domain action payload: [node_id => [field => value]] */
    public array $actionPayload = [];

    public function mount(ProcedureRun $run): void
    {
        $this->run = $run->load(['steps.approvalRequest.approver', 'steps.performedBy', 'task', 'subject', 'version']);
        $this->catchUpWaits();
        $this->initChecklistState();
    }

    private function initChecklistState(): void
    {
        foreach ($this->run->activeNodes() as $node) {
            if (($node['type'] ?? '') !== 'checklist') {
                continue;
            }

            $nodeId = $node['id'];

            if (isset($this->checklistState[$nodeId])) {
                continue;
            }

            foreach ($node['checklist'] ?? [] as $item) {
                $this->checklistState[$nodeId][$item['id']] = false;
            }
        }
    }

    public function advanceNode(string $nodeId): void
    {
        if ($this->run->status !== ProcedureRunStatus::IN_PROGRESS) {
            return;
        }

        if (! in_array($nodeId, $this->run->activeNodeIds(), true)) {
            return;
        }

        $node = $this->run->findNodeById($nodeId);

        if ($node === null) {
            return;
        }

        $nodeType = $node['type'] ?? '';
        $stepData = [];
        $edgeId = null;

        if (in_array($nodeType, ['comment', 'action'], true) && ! $this->run->hasBoundSubject()) {
            $this->addError(
                $nodeType.'.'.$nodeId,
                $nodeType === 'comment'
                    ? 'Ta procedura nie jest powiązana z kartą, na której można zostawić komentarz.'
                    : 'Ta procedura nie dotyczy żadnej karty — nie da się wykonać tej akcji.',
            );

            return;
        }

        if ($nodeType === 'checklist') {
            $items = $node['checklist'] ?? [];
            $state = $this->checklistState[$nodeId] ?? [];

            foreach ($items as $item) {
                if (! ($item['optional'] ?? false) && empty($state[$item['id']])) {
                    $this->addError('checklist.'.$nodeId, 'Zaznacz wszystkie wymagane pozycje przed kontynuacją.');

                    return;
                }
            }

            $stepData = array_map(
                fn ($id, $checked) => ['item_id' => $id, 'checked' => (bool) $checked],
                array_keys($state),
                $state
            );
        } elseif ($nodeType === 'decision') {
            $edgeId = $this->selectedEdgeIds[$nodeId] ?? null;

            if (! $edgeId) {
                $this->addError('decision.'.$nodeId, 'Wybierz opcję przed kontynuacją.');

                return;
            }

            $edge = collect($this->run->outgoingEdges($nodeId))->firstWhere('id', $edgeId);
            $stepData = ['option_id' => $edge['optionId'] ?? null, 'label' => $edge['label'] ?? ''];
        } elseif ($nodeType === 'comment') {
            $stepData = ['body' => $this->commentBodies[$nodeId] ?? ''];
        } elseif ($nodeType === 'action') {
            $stepData = $this->actionPayload[$nodeId] ?? [];
        } elseif ($nodeType === 'approval') {
            $this->addError('approval.'.$nodeId, 'Ten krok czeka na zatwierdzenie.');

            return;
        }

        $this->resetErrorBag();

        try {
            app(ProcedureRunService::class)->advanceNode($this->run, $nodeId, $edgeId, $stepData);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());

            return;
        } catch (RuntimeException $e) {
            $this->addError($nodeType.'.'.$nodeId, $e->getMessage());

            return;
        }

        $this->reloadRun();
        unset($this->selectedEdgeIds[$nodeId], $this->checklistState[$nodeId], $this->commentBodies[$nodeId], $this->actionPayload[$nodeId]);
        $this->initChecklistState();
    }

    public function decideApproval(string $nodeId, string $decision): void
    {
        $step = $this->run->steps
            ->first(fn ($s) => $s->node_id === $nodeId && $s->completed_at === null);
        $approval = $step?->approvalRequest;

        if (! $approval || $approval->isDecided() || ! $approval->isApprover(auth()->user())) {
            return;
        }

        $approval->decide(ApprovalDecision::from($decision), auth()->user());
        $this->reloadRun();
        $this->initChecklistState();
    }

    public function goBackNode(string $nodeId): void
    {
        if ($this->run->status !== ProcedureRunStatus::IN_PROGRESS) {
            return;
        }

        app(ProcedureRunService::class)->goBackNode($this->run, $nodeId);

        $this->reloadRun();
        unset($this->selectedEdgeIds[$nodeId], $this->checklistState[$nodeId], $this->commentBodies[$nodeId], $this->actionPayload[$nodeId]);
        $this->initChecklistState();
    }

    public function abandon(): void
    {
        app(ProcedureRunService::class)->abandon($this->run);
        $this->reloadRun();
    }

    public function catchUpWaits(): void
    {
        if ($this->run->status !== ProcedureRunStatus::IN_PROGRESS) {
            return;
        }

        $resumed = app(ProcedureRunService::class)->resumeExpiredWaits($this->run);
        if ($resumed > 0) {
            $this->reloadRun();
            $this->initChecklistState();
        }
    }

    public function nodeAssigneeName(?array $node): ?string
    {
        $id = (int) ($node['assigned_user_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }

        return User::query()->whereKey($id)->value('name');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function actionFields(array $node): array
    {
        $key = (string) ($node['action'] ?? '');
        if ($key === '') {
            return [];
        }

        try {
            return app(ActionCatalog::class)->find($key)->fields($this->run);
        } catch (RuntimeException) {
            return [];
        }
    }

    public function actionLabel(array $node): ?string
    {
        $key = (string) ($node['action'] ?? '');
        if ($key === '') {
            return null;
        }

        try {
            return app(ActionCatalog::class)->find($key)->label();
        } catch (RuntimeException) {
            return $key;
        }
    }

    public function openApprovalStep(string $nodeId): ?\App\Models\ApprovalRequest
    {
        return $this->run->steps
            ->first(fn ($s) => $s->node_id === $nodeId && $s->completed_at === null)
            ?->approvalRequest;
    }

    public function render()
    {
        return view('livewire.procedure-run-stepper', [
            'historyAssignees' => $this->historyAssigneeNames(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function historyAssigneeNames(): array
    {
        $ids = [];
        foreach ($this->run->steps as $step) {
            $node = $this->run->findNodeById($step->node_id);
            $id = (int) ($node['assigned_user_id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        if ($ids === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', array_unique($ids))
            ->pluck('name', 'id')
            ->all();
    }

    private function reloadRun(): void
    {
        $this->run->refresh()->load(['steps.approvalRequest.approver', 'steps.performedBy', 'task', 'subject', 'version', 'template']);
    }
}
