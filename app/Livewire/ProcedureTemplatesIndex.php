<?php

namespace App\Livewire;

use App\Contracts\Llm\LlmClient;
use App\Enums\ProcedureSubjectType;
use App\Exceptions\LlmException;
use App\Models\ProcedureTemplate;
use App\Models\User;
use App\Services\Llm\ProcedureFlowSuggestionService;
use App\Services\ProcedureRunService;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ProcedureTemplatesIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $categoryFilter = '';

    // Start-run modal
    public bool $showStartModal = false;

    public ?int $startTemplateId = null;

    public string $startTaskName = '';

    public ?int $startAssignedTo = null;

    public string $startDueDate = '';

    public string $startSubjectType = '';

    public string $startSubjectId = '';

    // New-template modal
    public bool $showNewModal = false;

    public string $newName = '';

    public string $newCategory = '';

    public string $newSubjectType = '';

    public string $newDescription = '';

    // Chrono: ekran pracy bota; propozycja ląduje w edytorze jako niezapisany szkic
    public bool $showChronoModal = false;

    public bool $chronoLoading = false;

    public ?string $chronoError = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function openStartModal(int $templateId): void
    {
        $template = ProcedureTemplate::findOrFail($templateId);
        $this->startTemplateId = $templateId;
        $this->startTaskName = $template->name;
        $this->startDueDate = '';
        $this->startAssignedTo = null;
        $this->startSubjectType = (string) ($template->subject_type ?? '');
        $this->startSubjectId = '';
        $this->showStartModal = true;
        $this->resetErrorBag();
    }

    /** @return list<array{id: int, label: string}> */
    public function startSubjectOptions(): array
    {
        $type = ProcedureSubjectType::tryFrom($this->startSubjectType);

        return $type?->dropdownOptions() ?? [];
    }

    public function startSubjectTypeLabel(): ?string
    {
        return ProcedureSubjectType::tryFrom($this->startSubjectType)?->label();
    }

    public function selectedSubjectLabel(): string
    {
        if ($this->startSubjectId === '') {
            return '';
        }

        foreach ($this->startSubjectOptions() as $option) {
            if ((string) $option['id'] === $this->startSubjectId) {
                return $option['label'];
            }
        }

        return '';
    }

    /** Live preview of the final task name (base name + selected entity). */
    public function getStartFinalTaskNameProperty(): string
    {
        $detail = $this->selectedSubjectLabel();

        return trim($detail === '' ? $this->startTaskName : $this->startTaskName.' '.$detail);
    }

    public function startRun(ProcedureRunService $service): mixed
    {
        $subjectType = ProcedureSubjectType::tryFrom($this->startSubjectType);
        $subjectTable = null;
        if ($subjectType) {
            $modelClass = $subjectType->modelClass();
            $subjectTable = (new $modelClass)->getTable();
        }

        $this->validate([
            'startTaskName' => ['required', 'string', 'max:255'],
            'startAssignedTo' => ['nullable', 'integer', 'exists:users,id'],
            'startDueDate' => ['nullable', 'date'],
            'startSubjectId' => array_values(array_filter([
                'nullable',
                'integer',
                $subjectTable ? Rule::exists($subjectTable, 'id') : null,
            ])),
        ], [], [
            'startTaskName' => 'nazwa zadania',
            'startAssignedTo' => 'przypisany do',
            'startDueDate' => 'termin',
            'startSubjectId' => 'dotyczy',
        ]);

        $template = ProcedureTemplate::findOrFail($this->startTemplateId);

        $run = $service->startRun($template, [
            'task_name' => $this->startFinalTaskName,
            'assigned_to' => $this->startAssignedTo ?: null,
            'due_date' => $this->startDueDate ?: null,
            'subject_type' => $subjectType && $this->startSubjectId !== '' ? $subjectType->value : null,
            'subject_id' => $this->startSubjectId !== '' ? (int) $this->startSubjectId : null,
        ]);

        $this->showStartModal = false;

        return redirect()->route('tasks.show', $run->task)
            ->with('success', 'Procedura "'.$template->name.'" została uruchomiona.');
    }

    public function openNewModal(): void
    {
        $this->newName = '';
        $this->newCategory = '';
        $this->newSubjectType = '';
        $this->newDescription = '';
        $this->showNewModal = true;
        $this->showChronoModal = false;
        $this->chronoLoading = false;
        $this->chronoError = null;
        $this->resetErrorBag();
    }

    public function createTemplate(): mixed
    {
        $this->validateNewTemplate();

        return $this->storeTemplate(['nodes' => [], 'edges' => []], 'Szablon "%s" został utworzony.');
    }

    /**
     * Otwiera okno Chrono od razu, jeszcze przed odpytaniem modelu —
     * użytkownik widzi pracującego bota zamiast zamrożonego przycisku.
     */
    public function openChronoModal(): void
    {
        $this->validateNewTemplate();

        $this->chronoError = null;
        $this->showNewModal = false;
        $this->showChronoModal = true;
        $this->chronoLoading = true;
    }

    /**
     * Tworzy szablon i przekazuje propozycję do edytora jako niezapisany szkic —
     * zatwierdzeniem jest kliknięcie „Zapisz" na canvasie, odrzuceniem wyjście z edytora.
     */
    public function fetchChronoFlow(ProcedureFlowSuggestionService $service): mixed
    {
        // Alpine odpala to raz po wyrenderowaniu okna; flaga chroni przed
        // powtórnym strzałem, gdyby Livewire przemorfował ten węzeł.
        if (! $this->chronoLoading) {
            return null;
        }

        try {
            $steps = $service->suggest([
                'name' => $this->newName,
                'category' => $this->newCategory ?: null,
                'subject_type' => $this->newSubjectType ?: null,
                'description' => $this->newDescription ?: null,
            ]);
        } catch (LlmException $e) {
            $this->chronoError = $e->getMessage();
            $this->chronoLoading = false;

            return null;
        } catch (\Throwable $e) {
            $this->chronoError = 'Nie udało się uzyskać propozycji od modelu: '.$e->getMessage();
            $this->chronoLoading = false;

            return null;
        }

        $this->chronoLoading = false;
        $this->showChronoModal = false;

        return $this->storeTemplate(
            ['nodes' => [], 'edges' => []],
            'Chrono zaproponował przepływ dla "%s" — sprawdź go i kliknij Zapisz.',
            $service->buildDefinition($steps),
        );
    }

    /** Zamknięcie okna wraca do formularza — dane z modalu nie znikają. */
    public function closeChronoModal(): void
    {
        $this->showChronoModal = false;
        $this->chronoLoading = false;
        $this->chronoError = null;
        $this->showNewModal = true;
    }

    private function validateNewTemplate(): void
    {
        $this->validate([
            'newName' => ['required', 'string', 'max:255'],
            'newCategory' => ['nullable', 'string', 'max:100'],
            'newSubjectType' => ['nullable', Rule::in(ProcedureSubjectType::values())],
            'newDescription' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'newName' => 'nazwa',
            'newCategory' => 'kategoria',
            'newSubjectType' => 'dotyczy',
            'newDescription' => 'opis',
        ]);
    }

    /**
     * @param  array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>}  $definition
     * @param  array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>}|null  $proposal  szkic wrzucany na canvas bez zapisu
     */
    private function storeTemplate(array $definition, string $message, ?array $proposal = null): mixed
    {
        $template = ProcedureTemplate::create([
            'name' => $this->newName,
            'category' => $this->newCategory ?: null,
            'subject_type' => $this->newSubjectType ?: null,
            'description' => $this->newDescription ?: null,
            'definition' => $definition,
            'created_by' => auth()->id(),
        ]);

        $this->showNewModal = false;

        $redirect = redirect()->route('procedure-templates.editor', $template)
            ->with('success', sprintf($message, $template->name));

        return $proposal === null ? $redirect : $redirect->with('chrono_proposal', $proposal);
    }

    public function deleteTemplate(int $templateId): void
    {
        ProcedureTemplate::findOrFail($templateId)->delete();

        session()->flash('success', 'Szablon został usunięty.');
    }

    public function duplicateTemplate(int $templateId): void
    {
        $original = ProcedureTemplate::findOrFail($templateId);

        $copy = $original->replicate();
        $copy->name = $original->name.' (kopia)';
        $copy->created_by = auth()->id();
        $copy->save();

        session()->flash('success', 'Szablon został zduplikowany.');
    }

    public function paginationView(): string
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function render()
    {
        $query = ProcedureTemplate::with('createdBy')
            ->withCount('runs');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->categoryFilter !== '') {
            $query->where('category', $this->categoryFilter);
        }

        $templates = $query->latest()->paginate(12);
        $categories = ProcedureTemplate::distinct()->whereNotNull('category')
            ->orderBy('category')->pluck('category');
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('livewire.procedure-templates-index', [
            'templates' => $templates,
            'categories' => $categories,
            'users' => $users,
            'subjectTypes' => ProcedureSubjectType::formOptions(),
            'llmConfigured' => app(LlmClient::class)->isConfigured(),
            'startSubjectOptions' => $this->startSubjectOptions(),
            'startSubjectTypeLabel' => $this->startSubjectTypeLabel(),
        ]);
    }
}
