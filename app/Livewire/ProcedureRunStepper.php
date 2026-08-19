<?php

namespace App\Livewire;

use App\Enums\ProcedureRunStatus;
use App\Models\ProcedureRun;
use App\Models\ProcedureRunComment;
use App\Models\User;
use App\Services\ProcedureRunService;
use Livewire\Component;

class ProcedureRunStepper extends Component
{
    public ProcedureRun $run;

    // Checklist state: [node_id => [item_id => bool]]
    public array $checklistState = [];

    // Decision choice: edge_id selected
    public ?string $selectedEdgeId = null;

    // Comment form
    public string $newComment = '';

    public function mount(ProcedureRun $run): void
    {
        $this->run = $run->load(['steps', 'comments.user', 'task', 'subject']);
        $this->initChecklistState();
    }

    private function initChecklistState(): void
    {
        $node = $this->run->currentNode();
        if (! $node || ($node['type'] ?? '') !== 'checklist') {
            return;
        }
        $nodeId = $node['id'];
        if (! isset($this->checklistState[$nodeId])) {
            foreach ($node['checklist'] ?? [] as $item) {
                $this->checklistState[$nodeId][$item['id']] = false;
            }
        }
    }

    public function advance(): void
    {
        if ($this->run->status !== ProcedureRunStatus::IN_PROGRESS) {
            return;
        }

        $node = $this->run->currentNode();
        $nodeType = $node['type'] ?? '';
        $stepData = [];
        $edgeId = null;

        if ($nodeType === 'checklist') {
            $nodeId = $node['id'];
            $items = $node['checklist'] ?? [];
            $state = $this->checklistState[$nodeId] ?? [];

            // Validate required items
            foreach ($items as $item) {
                if (! ($item['optional'] ?? false) && empty($state[$item['id']])) {
                    $this->addError('checklist', 'Zaznacz wszystkie wymagane pozycje przed kontynuacją.');

                    return;
                }
            }

            $stepData = array_map(fn ($id, $checked) => ['item_id' => $id, 'checked' => (bool) $checked], array_keys($state), $state);
        } elseif ($nodeType === 'decision') {
            if (! $this->selectedEdgeId) {
                $this->addError('decision', 'Wybierz opcję przed kontynuacją.');

                return;
            }
            $edgeId = $this->selectedEdgeId;
            // Find chosen option label
            $edge = collect($this->run->outgoingEdges($node['id']))->firstWhere('id', $edgeId);
            $stepData = ['option_id' => $edge['optionId'] ?? null, 'label' => $edge['label'] ?? ''];
        }

        $this->resetErrorBag();

        app(ProcedureRunService::class)->advance($this->run, $edgeId, $stepData);

        $this->run->refresh()->load(['steps', 'comments.user', 'task', 'subject']);
        $this->selectedEdgeId = null;
        $this->checklistState = [];
        $this->initChecklistState();
    }

    public function goBack(): void
    {
        if ($this->run->status !== ProcedureRunStatus::IN_PROGRESS) {
            return;
        }
        app(ProcedureRunService::class)->goBack($this->run);
        $this->run->refresh()->load(['steps', 'comments.user', 'task', 'subject']);
        $this->selectedEdgeId = null;
        $this->checklistState = [];
        $this->initChecklistState();
    }

    public function abandon(): void
    {
        app(ProcedureRunService::class)->abandon($this->run);
        $this->run->refresh()->load(['steps', 'task', 'subject']);
    }

    public function addComment(): void
    {
        $this->validate(['newComment' => ['required', 'string', 'max:2000']], [], ['newComment' => 'komentarz']);

        ProcedureRunComment::create([
            'procedure_run_id' => $this->run->id,
            'user_id' => auth()->id(),
            'body' => $this->newComment,
        ]);

        $this->newComment = '';
        $this->run->refresh()->load(['steps', 'comments.user', 'task', 'subject']);
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
