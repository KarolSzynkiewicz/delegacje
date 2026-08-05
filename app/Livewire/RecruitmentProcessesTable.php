<?php

namespace App\Livewire;

use App\Enums\RecruitmentCandidateFlag;
use App\Enums\RecruitmentContactOutcome;
use App\Enums\RecruitmentRejectionReason;
use App\Enums\RecruitmentShipyardExperience;
use App\Enums\RecruitmentStatus;
use App\Enums\TaskStatus;
use App\Models\Employee;
use App\Models\ProjectTask;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentConsent;
use App\Models\RecruitmentProcess;
use App\Models\Role;
use App\Models\User;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class RecruitmentProcessesTable extends Component
{
    use WithPagination;

    public string $status = '';

    /** Candidate flag filter: '' | wartosciowy | czarna_lista */
    public string $flag = '';

    public string $search = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public ?int $selectedId = null;

    public string $newOutcome = '';

    public string $newComment = '';

    /** @var array<int, int> */
    public array $editRoles = [];

    public ?string $editRate = null;

    public string $editCity = '';

    public bool $editDrivingLicense = false;

    public bool $editSpeaksEnglish = false;

    public bool $editSpeaksFrench = false;

    public bool $editSpeaksGerman = false;

    public string $editShipyardExperience = '';

    public string $editAvailableFrom = '';

    public ?int $editAssignedRecruiterId = null;

    public bool $skillsetSaved = false;

    public bool $contactSaved = false;

    /** @var array<int, int> */
    public array $hireRoles = [];

    // Candidate identity edit (name / phone / email)
    public bool $editingCandidateIdentity = false;

    public string $editFirstName = '';

    public string $editLastName = '';

    public string $editEmail = '';

    public string $editPhone = '';

    // Rejection reason prompt (shown before a process can move to Odrzucony)
    public bool $showRejectionPrompt = false;

    public ?int $pendingRejectionId = null;

    public string $rejectionReason = '';

    public string $rejectionNote = '';

    // Blacklist prompt (shown before a candidate can be flagged czarna_lista)
    public bool $showBlacklistPrompt = false;

    public ?int $pendingFlagCandidateId = null;

    public string $blacklistNote = '';

    // Follow-up task modal (opened automatically after "Prosi o oddzwonienie")
    public bool $showTaskModal = false;

    public string $taskTitle = '';

    public string $taskDueDate = '';

    public ?int $taskAssignedTo = null;

    public string $taskDescription = '';

    // Comment modal (process-level or candidate-level)
    public bool $showCommentModal = false;

    public string $commentModalTarget = ''; // 'process' | 'candidate'

    protected $queryString = [
        'status' => ['except' => ''],
        'flag' => ['except' => ''],
        'search' => ['except' => '', 'as' => 'q'],
        'sortField' => ['except' => 'created_at', 'as' => 'sort'],
        'sortDirection' => ['except' => 'desc', 'as' => 'dir'],
        'selectedId' => ['except' => null, 'as' => 'process'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    /** Star / blacklist filter — clicking the active flag clears it. */
    public function toggleFlag(string $flag): void
    {
        if (! in_array($flag, array_column(RecruitmentCandidateFlag::cases(), 'value'), true)) {
            return;
        }

        $this->flag = $this->flag === $flag ? '' : $flag;
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        $allowed = ['created_at', 'last_name', 'expected_rate_eur', 'last_contact_at'];

        if (! in_array($field, $allowed, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            // Dates are most useful newest-first, names A→Z.
            $this->sortDirection = in_array($field, ['created_at', 'last_contact_at'], true) ? 'desc' : 'asc';
        }

        $this->resetPage();
    }

    public function mount(): void
    {
        // When the page loads from a shared URL (?process=X) or after a full-page
        // redirect (e.g. after saving a comment via the standard POST form), Livewire
        // hydrates selectedId from the query string but never calls selectProcess().
        // We must populate the edit fields here so they reflect the actual DB values.
        if ($this->selectedId) {
            $this->loadCandidateEditFields();
        }
    }

    public function selectProcess(int $id): void
    {
        if ($this->selectedId === $id) {
            $this->closeDrawer();

            return;
        }

        $this->selectedId = $id;
        $this->resetDraft();
        $this->editingCandidateIdentity = false;
        $this->skillsetSaved = false;
        $this->contactSaved = false;
        $this->loadCandidateEditFields();
    }

    protected function loadCandidateEditFields(): void
    {
        $process = $this->getSelectedProcess();
        if (! $process || ! $process->candidate) {
            return;
        }

        $this->editRoles = $process->candidate->roles->pluck('id')->all();
        $this->editRate = $process->candidate->expected_rate_eur !== null ? (string) $process->candidate->expected_rate_eur : null;
        $this->editCity = $process->candidate->city ?? '';
        $this->editDrivingLicense = (bool) $process->candidate->has_driving_license_b;
        $this->editSpeaksEnglish = (bool) $process->candidate->speaks_english;
        $this->editSpeaksFrench = (bool) $process->candidate->speaks_french;
        $this->editSpeaksGerman = (bool) $process->candidate->speaks_german;
        $this->editShipyardExperience = $process->candidate->shipyard_experience?->value ?? '';
        $this->editAvailableFrom = $process->candidate->available_from?->format('Y-m-d') ?? '';
        $this->editAssignedRecruiterId = $process->assigned_recruiter_id;
        $this->hireRoles = $this->editRoles;
        $this->editFirstName = $process->candidate->first_name;
        $this->editLastName = $process->candidate->last_name;
        $this->editEmail = $process->candidate->email ?? '';
        $this->editPhone = $process->candidate->phone ?? '';
    }

    public function closeDrawer(): void
    {
        $this->selectedId = null;
        $this->resetDraft();
        $this->cancelRejection();
        $this->cancelBlacklist();
        $this->closeTaskModal();
    }

    protected function resetDraft(): void
    {
        $this->newOutcome = '';
        $this->newComment = '';
    }

    public function updateStatus(int $id, string $status): void
    {
        if (! in_array($status, array_column(RecruitmentStatus::cases(), 'value'), true)) {
            return;
        }

        $process = RecruitmentProcess::find($id);
        if (! $process) {
            return;
        }

        if ($status === RecruitmentStatus::Odrzucony->value && $process->status !== RecruitmentStatus::Odrzucony) {
            $this->pendingRejectionId = $id;
            $this->rejectionReason = '';
            $this->rejectionNote = '';
            $this->showRejectionPrompt = true;

            return;
        }

        $process->transitionTo(RecruitmentStatus::from($status), auth()->id());
    }

    public function updateAssignedRecruiter(int $id, string $recruiterId = ''): void
    {
        $process = RecruitmentProcess::find($id);
        if (! $process) {
            return;
        }

        if ($recruiterId !== '' && ! User::whereKey($recruiterId)->exists()) {
            return;
        }

        $process->update([
            'assigned_recruiter_id' => $recruiterId !== '' ? (int) $recruiterId : null,
        ]);

        if ($this->selectedId === $id) {
            $this->editAssignedRecruiterId = $process->assigned_recruiter_id;
        }
    }

    public function confirmRejection(): void
    {
        $this->validate([
            'rejectionReason' => 'required|in:'.implode(',', array_column(RecruitmentRejectionReason::cases(), 'value')),
            'rejectionNote' => 'nullable|string|max:1000',
        ], [
            'rejectionReason.required' => 'Wybierz powód odrzucenia.',
        ]);

        $process = RecruitmentProcess::find($this->pendingRejectionId);
        if ($process) {
            $process->transitionTo(
                RecruitmentStatus::Odrzucony,
                auth()->id(),
                RecruitmentRejectionReason::from($this->rejectionReason),
                $this->rejectionNote ?: null
            );
        }

        $this->cancelRejection();
    }

    public function cancelRejection(): void
    {
        $this->showRejectionPrompt = false;
        $this->pendingRejectionId = null;
        $this->rejectionReason = '';
        $this->rejectionNote = '';
    }

    public function setCandidateFlag(int $processId, string $flag): void
    {
        $process = RecruitmentProcess::with('candidate')->find($processId);
        if (! $process || ! $process->candidate) {
            return;
        }

        $flagEnum = RecruitmentCandidateFlag::tryFrom($flag);
        $candidate = $process->candidate;

        // Clearing an already-active flag never needs a prompt.
        if ($candidate->rating === $flagEnum) {
            $candidate->setFlag(null);

            return;
        }

        if ($flagEnum === RecruitmentCandidateFlag::CzarnaLista) {
            $this->pendingFlagCandidateId = $candidate->id;
            $this->blacklistNote = '';
            $this->showBlacklistPrompt = true;

            return;
        }

        $candidate->setFlag($flagEnum);
    }

    public function confirmBlacklist(): void
    {
        $this->validate([
            'blacklistNote' => 'required|string|max:1000',
        ], [
            'blacklistNote.required' => 'Podaj powód wpisania na czarną listę.',
        ]);

        $candidate = RecruitmentCandidate::find($this->pendingFlagCandidateId);
        if ($candidate) {
            $candidate->setFlag(RecruitmentCandidateFlag::CzarnaLista, $this->blacklistNote);
        }

        $this->cancelBlacklist();
    }

    public function cancelBlacklist(): void
    {
        $this->showBlacklistPrompt = false;
        $this->pendingFlagCandidateId = null;
        $this->blacklistNote = '';
    }

    public function updated($property): void
    {
        $skillsetProps = [
            'editRate',
            'editAvailableFrom',
            'editShipyardExperience',
            'editDrivingLicense',
            'editSpeaksEnglish',
            'editSpeaksFrench',
            'editSpeaksGerman',
            'editRoles',
        ];

        $isSkillset = in_array($property, $skillsetProps, true)
            || str_starts_with((string) $property, 'editRoles.');

        if ($isSkillset) {
            $this->saveSkillset();
        }

        if ($property === 'editAssignedRecruiterId') {
            $this->saveAssignedRecruiterFromDraft();
        }
    }

    public function saveAssignedRecruiterFromDraft(): void
    {
        $process = $this->getSelectedProcess();
        if (! $process) {
            return;
        }

        $recruiterId = $this->editAssignedRecruiterId;

        if ($recruiterId !== null && ! User::whereKey($recruiterId)->exists()) {
            return;
        }

        $process->update([
            'assigned_recruiter_id' => $recruiterId ?: null,
        ]);
    }

    public function saveSkillset(): void
    {
        $process = $this->getSelectedProcess();
        if (! $process || ! $process->candidate) {
            return;
        }

        $validExperienceValues = array_merge([''], array_column(RecruitmentShipyardExperience::cases(), 'value'));

        $this->validate([
            'editRate' => 'nullable|numeric|min:0|max:9999.99',
            'editRoles' => 'nullable|array',
            'editRoles.*' => 'exists:roles,id',
            'editShipyardExperience' => ['nullable', 'in:'.implode(',', $validExperienceValues)],
            'editAvailableFrom' => 'nullable|date',
        ]);

        $process->candidate->update([
            'expected_rate_eur' => $this->editRate !== null && $this->editRate !== '' ? $this->editRate : null,
            'shipyard_experience' => $this->editShipyardExperience ?: null,
            'available_from' => $this->editAvailableFrom ?: null,
            'has_driving_license_b' => $this->editDrivingLicense,
            'speaks_english' => $this->editSpeaksEnglish,
            'speaks_french' => $this->editSpeaksFrench,
            'speaks_german' => $this->editSpeaksGerman,
        ]);
        $process->candidate->roles()->sync($this->editRoles);

        $this->skillsetSaved = true;
    }

    public function toggleCandidateIdentityEdit(): void
    {
        $this->editingCandidateIdentity = ! $this->editingCandidateIdentity;
        $this->resetValidation(['editFirstName', 'editLastName', 'editEmail', 'editPhone', 'editCity']);

        if (! $this->editingCandidateIdentity) {
            $process = $this->getSelectedProcess();
            if ($process?->candidate) {
                $this->editFirstName = $process->candidate->first_name;
                $this->editLastName = $process->candidate->last_name;
                $this->editEmail = $process->candidate->email ?? '';
                $this->editPhone = $process->candidate->phone ?? '';
                $this->editCity = $process->candidate->city ?? '';
            }
        }
    }

    public function saveCandidateIdentity(): void
    {
        $process = $this->getSelectedProcess();
        if (! $process || ! $process->candidate) {
            return;
        }

        $this->validate([
            'editFirstName' => 'required|string|max:100',
            'editLastName' => 'required|string|max:100',
            'editEmail' => 'nullable|email|max:255',
            'editPhone' => 'nullable|string|max:30',
            'editCity' => 'nullable|string|max:100',
        ], [
            'editFirstName.required' => 'Imię jest wymagane.',
            'editLastName.required' => 'Nazwisko jest wymagane.',
            'editEmail.email' => 'Podaj poprawny adres e-mail.',
        ]);

        $normalizedPhone = PhoneNormalizer::normalize($this->editPhone);

        if ($normalizedPhone) {
            $conflict = RecruitmentCandidate::where('phone', $normalizedPhone)
                ->where('id', '!=', $process->candidate->id)
                ->exists();

            if ($conflict) {
                $this->addError('editPhone', 'Ten numer jest już przypisany do innego kandydata.');

                return;
            }
        }

        $process->candidate->update([
            'first_name' => trim($this->editFirstName),
            'last_name' => trim($this->editLastName),
            'email' => $this->editEmail ? mb_strtolower(trim($this->editEmail)) : null,
            'phone' => $normalizedPhone,
            'city' => $this->editCity ?: null,
        ]);

        $this->editingCandidateIdentity = false;
    }

    public function logContactAttempt(string $outcome = ''): void
    {
        $process = $this->getSelectedProcess();
        if (! $process) {
            return;
        }

        if ($outcome !== '') {
            $this->newOutcome = $outcome;
        }

        $this->validate([
            'newOutcome' => 'required|in:'.implode(',', array_column(RecruitmentContactOutcome::cases(), 'value')),
            'newComment' => 'nullable|string|max:2000',
        ], [
            'newOutcome.required' => 'Wybierz efekt rozmowy.',
        ]);

        $process->contactAttempts()->create([
            'user_id' => auth()->id(),
            'outcome' => $this->newOutcome,
            'comment' => $this->newComment ?: null,
        ]);

        // First contact attempt advances a fresh lead into active outreach.
        if ($process->status === RecruitmentStatus::Nowy) {
            $process->transitionTo(RecruitmentStatus::WTrakcieKontaktu, auth()->id());
        }

        $shouldOpenTaskModal = $this->newOutcome === RecruitmentContactOutcome::ProsiOOddzwonienie->value;

        $this->resetDraft();
        $this->contactSaved = true;

        if ($shouldOpenTaskModal) {
            $this->openTaskModal($process);
        }
    }

    protected function openTaskModal(RecruitmentProcess $process): void
    {
        $this->showTaskModal = true;
        $this->taskTitle = 'Oddzwonić do '.$process->full_name.' #'.$process->id;
        $this->taskDueDate = now()->addDay()->format('Y-m-d');
        $this->taskAssignedTo = $process->assigned_recruiter_id ?: auth()->id();
        $this->taskDescription = '';
    }

    public function openTaskModalManual(): void
    {
        $process = $this->getSelectedProcess();
        if (! $process) {
            return;
        }

        $this->showTaskModal = true;
        $this->taskTitle = $process->full_name.' #'.$process->id;
        $this->taskDueDate = now()->addDay()->format('Y-m-d');
        $this->taskAssignedTo = $process->assigned_recruiter_id ?: auth()->id();
        $this->taskDescription = '';
    }

    public function saveFollowUpTask(): void
    {
        $process = $this->getSelectedProcess();
        if (! $process) {
            $this->closeTaskModal();

            return;
        }

        $this->validate([
            'taskTitle' => 'required|string|max:255',
            'taskDueDate' => 'required|date',
            'taskAssignedTo' => 'nullable|exists:users,id',
        ], [
            'taskTitle.required' => 'Podaj tytuł zadania.',
            'taskDueDate.required' => 'Wybierz termin (due date).',
        ]);

        ProjectTask::create([
            'name' => $this->taskTitle,
            'description' => $this->taskDescription ?: null,
            'category' => 'Rekrutacja',
            'status' => TaskStatus::PENDING->value,
            'due_date' => $this->taskDueDate,
            'assigned_to' => $this->taskAssignedTo ?: auth()->id(),
            'created_by' => auth()->id(),
            'recruitment_process_id' => $process->id,
        ]);

        session()->flash('success', 'Zadanie z przypomnieniem zostało dodane.');

        $this->closeTaskModal();
    }

    public function closeTaskModal(): void
    {
        $this->showTaskModal = false;
        $this->taskTitle = '';
        $this->taskDueDate = '';
        $this->taskAssignedTo = null;
        $this->taskDescription = '';
    }

    public function openCommentModal(string $target): void
    {
        $this->commentModalTarget = $target;
        $this->showCommentModal = true;
    }

    public function closeCommentModal(): void
    {
        $this->showCommentModal = false;
        $this->commentModalTarget = '';
    }

    public function withdrawConsent(int $consentId): void
    {
        RecruitmentConsent::whereKey($consentId)->first()?->withdraw();
    }

    public function toggleTaskDone(int $taskId): void
    {
        $task = ProjectTask::where('recruitment_process_id', $this->selectedId)->find($taskId);
        if (! $task) {
            return;
        }

        $task->status === TaskStatus::COMPLETED ? $task->markInProgress() : $task->markCompleted();
    }

    public function convertToEmployee(): void
    {
        $process = $this->getSelectedProcess();
        if (! $process || $process->employee_id || ! $process->candidate) {
            return;
        }

        $this->validate([
            'hireRoles' => 'required|array|min:1',
            'hireRoles.*' => 'exists:roles,id',
        ], [
            'hireRoles.required' => 'Wybierz przynajmniej jedną rolę pracownika.',
            'hireRoles.min' => 'Wybierz przynajmniej jedną rolę pracownika.',
        ]);

        $candidate = $process->candidate;

        $imagePath = null;
        if ($candidate->photo_path) {
            $oldPath = $candidate->photo_path;
            $filename = basename($oldPath);
            $newPath = 'employees/'.$filename;

            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->copy($oldPath, $newPath);
            }

            $imagePath = $newPath;
        }

        $employee = Employee::create([
            'first_name' => $candidate->first_name,
            'last_name' => $candidate->last_name,
            'email' => $candidate->email,
            'phone' => $candidate->phone,
            'notes' => null,
            'image_path' => $imagePath,
        ]);

        $employee->roles()->attach($this->hireRoles);

        $candidate->update(['employee_id' => $employee->id]);

        $process->transitionTo(RecruitmentStatus::Zatrudniony, auth()->id());
        $process->update(['employee_id' => $employee->id]);

        session()->flash('success', "Kandydat {$employee->full_name} został zatrudniony i dodany do bazy pracowników.");

        $this->closeDrawer();
    }

    protected function getSelectedProcess(): ?RecruitmentProcess
    {
        if (! $this->selectedId) {
            return null;
        }

        return RecruitmentProcess::with([
            'candidate.consents',
            'candidate.roles',
            'candidate.allContactAttempts.user',
            'candidate.allContactAttempts.recruitmentProcess',
            'candidate.processes.lead',
            'candidate.processes.assignedRecruiter',
            'candidate.comments.user',
            'lead',
            'employee',
            'assignedRecruiter',
            'statusHistory.changedBy',
            'tasks.assignedTo',
            'comments.user',
        ])->find($this->selectedId);
    }

    #[On('leads-imported')]
    public function refreshAfterLeadsImport(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $searchDigits = preg_replace('/\D+/', '', $this->search);
        $phoneSearch = strlen($searchDigits) >= 3
            ? PhoneNormalizer::normalize($this->search)
            : null;

        $baseQuery = RecruitmentProcess::query();

        $counts = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $lastCandidateContactSubquery = DB::raw(
            '(SELECT MAX(rca.created_at)'
            .' FROM recruitment_contact_attempts rca'
            .' JOIN recruitment_processes rp2 ON rp2.id = rca.recruitment_process_id'
            .' WHERE rp2.candidate_id = recruitment_candidates.id) as last_candidate_contact_at'
        );

        $flagCounts = RecruitmentCandidate::query()
            ->whereHas('processes')
            ->whereNotNull('rating')
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $sortColumn = match ($this->sortField) {
            'last_contact_at' => 'last_candidate_contact_at',
            'created_at' => 'recruitment_candidates.created_at',
            'expected_rate_eur' => 'recruitment_candidates.expected_rate_eur',
            default => 'recruitment_candidates.last_name',
        };

        // Each row in the main table = one candidate. Their processes are sub-rows.
        // Status filter only decides which candidates appear; all of their processes
        // are loaded so sibling pipelines stay visible informatively.
        $status = $this->status;
        $search = $this->search;
        $flag = $this->flag;
        $applications = RecruitmentCandidate::query()
            ->select(['recruitment_candidates.*', $lastCandidateContactSubquery])
            ->whereHas('processes', function ($q) use ($status) {
                $q->when($status, fn ($q) => $q->where('status', $status));
            })
            ->when($flag, fn ($q) => $q->where('recruitment_candidates.rating', $flag))
            ->with([
                'roles',
                'processes' => function ($q) {
                    $q->select(['recruitment_processes.*',
                        DB::raw('(SELECT outcome FROM recruitment_contact_attempts rca WHERE rca.recruitment_process_id = recruitment_processes.id ORDER BY rca.created_at DESC LIMIT 1) as last_contact_outcome'),
                    ])
                        ->withMax('contactAttempts as last_contact_at', 'created_at')
                        ->withCount('contactAttempts')
                        ->with(['lead', 'assignedRecruiter'])
                        ->orderBy('created_at', 'desc');
                },
            ])
            ->when($search, function ($q) use ($search, $phoneSearch) {
                $q->where(function ($q) use ($search, $phoneSearch) {
                    $q->where('recruitment_candidates.first_name', 'like', "%{$search}%")
                        ->orWhere('recruitment_candidates.last_name', 'like', "%{$search}%")
                        ->orWhere('recruitment_candidates.phone', 'like', "%{$search}%")
                        ->orWhere('recruitment_candidates.email', 'like', "%{$search}%")
                        ->orWhereHas('roles', fn ($rq) => $rq->where('name', 'like', "%{$search}%"))
                        ->when($phoneSearch, fn ($q) => $q->orWhere('recruitment_candidates.phone', 'like', "%{$phoneSearch}%"));
                });
            })
            ->orderBy($sortColumn, $this->sortDirection)
            ->orderBy('recruitment_candidates.last_name')
            ->paginate(20);

        $selected = $this->getSelectedProcess();

        // Left drawer list must mirror the main table page (same filters/sort/page).
        // The open lead's candidate is lifted out of the list and rendered above it,
        // so scrolling the list never hides the record being worked on.
        $listCandidates = $applications->getCollection()->values();
        $pinnedCandidate = null;

        if ($selected?->candidate) {
            $selectedCandidateId = $selected->candidate_id;
            $pinnedCandidate = $listCandidates->firstWhere('id', $selectedCandidateId) ?? $selected->candidate;

            $pinnedCandidate->setRelation(
                'processes',
                $pinnedCandidate->processes
                    ->sortBy(fn ($p) => $p->id === $this->selectedId ? 0 : 1)
                    ->values()
            );

            $listCandidates = $listCandidates
                ->reject(fn ($c) => $c->id === $selectedCandidateId)
                ->values();
        }

        return view('livewire.recruitment-processes-table', [
            'applications' => $applications,
            'listCandidates' => $listCandidates,
            'pinnedCandidate' => $pinnedCandidate,
            'counts' => $counts,
            'flagCounts' => $flagCounts,
            'total' => RecruitmentCandidate::whereHas('processes')->count(),
            'roles' => Role::orderBy('name')->get(),
            'recruiters' => User::orderBy('name')->get(),
            'selected' => $selected,
        ]);
    }
}
