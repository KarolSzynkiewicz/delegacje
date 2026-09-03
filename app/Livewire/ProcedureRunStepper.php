<?php

namespace App\Livewire;

use App\Enums\ProcedureRunStatus;
use App\Models\ProcedureRun;
use App\Models\User;
use App\Services\ProcedureRunService;
use Livewire\Component;

class ProcedureRunStepper extends Component
{
    public ProcedureRun $run;

    /** When true, the parent page already shows title/status — stepper is only the current step. */
    public bool $compact = false;

    /** Checklist state: [node_id => [item_id => bool]] */
    public array $checklistState = [];

    /** Decision choice per node: [node_id => edge_id] */
    public array $selectedEdgeIds = [];

    public function mount(ProcedureRun $run): void
    {
        $this->run = $run->load(['steps', 'task', 'subject', 'version']);
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
        }

        $this->resetErrorBag();

        app(ProcedureRunService::class)->advanceNode($this->run, $nodeId, $edgeId, $stepData);

        $this->run->refresh()->load(['steps', 'task', 'subject', 'version']);
        unset($this->selectedEdgeIds[$nodeId], $this->checklistState[$nodeId]);
        $this->initChecklistState();
    }

    public function goBackNode(string $nodeId): void
    {
        if ($this->run->status !== ProcedureRunStatus::IN_PROGRESS) {
            return;
        }

        app(ProcedureRunService::class)->goBackNode($this->run, $nodeId);

        $this->run->refresh()->load(['steps', 'task', 'subject', 'version']);
        unset($this->selectedEdgeIds[$nodeId], $this->checklistState[$nodeId]);
        $this->initChecklistState();
    }

    public function abandon(): void
    {
        app(ProcedureRunService::class)->abandon($this->run);
        $this->run->refresh()->load(['steps', 'task', 'subject', 'version']);
    }

    public function nodeAssigneeName(?array $node): ?string
    {
        $id = (int) ($node['assigned_user_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }

        return User::query()->whereKey($id)->value('name');
    }

    public function render()
    {
        return view('livewire.procedure-run-stepper');
    }
}
