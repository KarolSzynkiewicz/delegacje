<?php

namespace App\Livewire;

use App\Models\ProcedureRun;
use App\Models\ProcedureSlotBinding;
use App\Models\ProcedureTemplate;
use App\Models\RecruitmentProcess;
use App\Services\ProcedureSlotService;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Reusable UI integration point for the procedure runner. Embed anywhere with
 * a stable `slotKey` + a `subject` model; the component resolves the
 * slot -> ProcedureTemplate binding (editable in the DB, never hardcoded
 * here), and reuses ProcedureRunStepper/ProcedureRunService for everything
 * related to actually running the procedure.
 */
class ProcedureSlot extends Component
{
    public string $slotKey;

    public Model $subject;

    public array $variables = [];

    public ?string $label = null;

    public ?string $subjectLabel = null;

    public bool $showBindModal = false;

    public ?int $bindTemplateId = null;

    public function mount(
        string $slotKey,
        Model $subject,
        array $variables = [],
        ?string $label = null,
        ?string $subjectLabel = null,
    ): void {
        $this->slotKey = $slotKey;
        $this->subject = $subject;
        $this->variables = $variables;
        $this->label = $label;
        $this->subjectLabel = $subjectLabel;
    }

    #[Computed]
    public function binding(): ?ProcedureSlotBinding
    {
        return app(ProcedureSlotService::class)->binding($this->slotKey);
    }

    #[Computed]
    public function activeRun(): ?ProcedureRun
    {
        return app(ProcedureSlotService::class)->findActiveRun($this->slotKey, $this->subject);
    }

    /** Most recent run for this slot+subject, whatever its status — lets the slot remember "already finished". */
    #[Computed]
    public function lastRun(): ?ProcedureRun
    {
        return app(ProcedureSlotService::class)->lastRun($this->slotKey, $this->subject);
    }

    #[Computed]
    public function availableTemplates()
    {
        return ProcedureTemplate::orderBy('name')->get(['id', 'name', 'category']);
    }

    public function openBindModal(): void
    {
        if ($this->isPreview()) {
            return;
        }

        unset($this->binding, $this->availableTemplates);
        $this->bindTemplateId = $this->binding?->procedure_template_id;
        $this->showBindModal = true;
    }

    public function closeBindModal(): void
    {
        $this->showBindModal = false;
        $this->bindTemplateId = null;
    }

    public function saveBinding(): void
    {
        if ($this->isPreview()) {
            return;
        }

        $this->validate([
            'bindTemplateId' => ['required', 'integer', 'exists:procedure_templates,id'],
        ], [], ['bindTemplateId' => 'szablon procedury']);

        app(ProcedureSlotService::class)->bind($this->slotKey, $this->bindTemplateId);

        unset($this->binding);
        $this->closeBindModal();
    }

    public function start(): void
    {
        if ($this->isPreview()) {
            return;
        }

        app(ProcedureSlotService::class)->startOrGetRun(
            $this->slotKey,
            $this->subject,
            $this->variables,
            $this->taskNameForRun(),
        );

        unset($this->activeRun, $this->lastRun);
    }

    /**
     * A recruitment slot can only be started on the process's current stage.
     * Previewing a later stage (e.g. Onboarding while still Nowy) is read-only;
     * transitionTo() starts the bound slot when the process actually arrives.
     */
    public function isPreview(): bool
    {
        if (! $this->subject instanceof RecruitmentProcess) {
            return false;
        }

        $status = $this->subject->fresh()?->status;

        return $status?->procedureSlotKey() !== $this->slotKey;
    }

    private function taskNameForRun(): string
    {
        $templateName = $this->binding?->template?->name ?? 'Procedura';

        return $this->subjectLabel !== null
            ? $templateName.' — '.$this->subjectLabel
            : $templateName;
    }

    public function render()
    {
        return view('livewire.procedure-slot', [
            'binding' => $this->binding,
            'activeRun' => $this->activeRun,
            'lastRun' => $this->activeRun ? null : $this->lastRun,
            'availableTemplates' => $this->availableTemplates,
            'isPreview' => $this->isPreview(),
        ]);
    }
}
