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
use App\Models\RecruitmentContactAttempt;
use App\Models\RecruitmentGridView;
use App\Models\RecruitmentProcess;
use App\Models\Role;
use App\Models\User;
use App\Support\PhoneNormalizer;
use App\Support\RecruitmentBacklog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class RecruitmentProcessesTable extends Component
{
    use WithPagination;

    public string $status = '';

    /** Candidate flag filter: '' | wartosciowy | czarna_lista */
    public string $flag = '';

    /** When true, only candidates with a process assigned to the logged-in recruiter. */
    public bool $mine = false;

    /** '' | unassigned | user id — filter by assigned recruiter on matching processes. */
    public string $recruiter = '';

    /** '' | none | rejection reason value — filter by rejection reason on matching processes. */
    public string $rejectionFilter = '';

    /** '' | referral source value (or employee_lifecycle:reinstate|terminate). */
    public string $referralSource = '';

    /** When true, only candidates with a process in status „Były pracownik”. */
    public bool $formerEmployee = false;

    /** '' | hired | former — employment link on candidate. */
    public string $employment = '';

    /** Stawka: więcej niż / mniej niż (€/h). */
    public string $rateMin = '';

    public string $rateMax = '';

    /** '' | brak | 1_3 | 4_10 | 10_plus */
    public string $shipyardExperience = '';

    /** Dostępny od — później niż / wcześniej niż (Y-m-d). */
    public string $availableAfter = '';

    public string $availableBefore = '';

    public bool $skillEnglish = false;

    public bool $skillFrench = false;

    public bool $skillGerman = false;

    public bool $skillDriving = false;

    /** '' | 2 | 3 | 4 — minimalna liczba procesów kandydata. */
    public string $minProcesses = '';

    public bool $hasTask = false;

    /**
     * Preset backlogu z analityki (?backlog=…) — warunek, którego nie da się wyrazić
     * samym statusem. Dozwolone wartości: {@see RecruitmentBacklog::filterKeys()}.
     */
    public string $backlog = '';

    /**
     * Ostatni kontakt (próba kontaktu):
     * '' | none | today | yesterday | days_3 | last_week | month_plus | half_year_plus | year_plus | years_2_plus
     */
    public string $lastContact = '';

    public string $search = '';

    /** Saved view slug (?view=…) */
    public string $view = '';

    public string $saveViewName = '';

    /** Draft values for the SharePoint-style filter panel (applied on „Zastosuj”). */
    public string $draftStatus = '';

    public string $draftRecruiter = '';

    public string $draftReferralSource = '';

    public string $draftFlag = '';

    public bool $draftMine = false;

    public bool $draftFormerEmployee = false;

    public string $draftEmployment = '';

    public string $draftRateMin = '';

    public string $draftRateMax = '';

    public string $draftShipyardExperience = '';

    public string $draftAvailableAfter = '';

    public string $draftAvailableBefore = '';

    public bool $draftSkillEnglish = false;

    public bool $draftSkillFrench = false;

    public bool $draftSkillGerman = false;

    public bool $draftSkillDriving = false;

    public string $draftMinProcesses = '';

    public bool $draftHasTask = false;

    public string $draftLastContact = '';

    public string $draftRejectionFilter = '';

    public ?string $flash = null;

    private bool $batchingViewPersist = false;

    /** @var list<string> */
    protected array $persistableViewProperties = [
        'status',
        'flag',
        'mine',
        'formerEmployee',
        'employment',
        'rateMin',
        'rateMax',
        'shipyardExperience',
        'availableAfter',
        'availableBefore',
        'skillEnglish',
        'skillFrench',
        'skillGerman',
        'skillDriving',
        'minProcesses',
        'hasTask',
        'lastContact',
        'recruiter',
        'referralSource',
        'rejectionFilter',
        'search',
        'sortField',
        'sortDirection',
    ];

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public ?int $selectedId = null;

    /** Set from the show page (`/recruitment-processes/{id}`). */
    public ?int $processId = null;

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

    public bool $showContactModal = false;

    // Follow-up task modal (opened automatically after "Prosi o oddzwonienie")
    public bool $showTaskModal = false;

    public string $taskTitle = '';

    public string $taskDueDate = '';

    public ?int $taskAssignedTo = null;

    public string $taskDescription = '';

    // Inline edit of a single contact attempt (own attempts only)
    public ?int $editingAttemptId = null;

    public string $editAttemptComment = '';

    protected $queryString = [
        'status' => ['except' => '', 'history' => true],
        'flag' => ['except' => '', 'history' => true],
        'mine' => ['except' => false, 'history' => true],
        'formerEmployee' => ['except' => false, 'history' => true],
        'employment' => ['except' => '', 'history' => true],
        'rateMin' => ['except' => '', 'as' => 'rate_min', 'history' => true],
        'rateMax' => ['except' => '', 'as' => 'rate_max', 'history' => true],
        'shipyardExperience' => ['except' => '', 'as' => 'exp', 'history' => true],
        'availableAfter' => ['except' => '', 'as' => 'avail_after', 'history' => true],
        'availableBefore' => ['except' => '', 'as' => 'avail_before', 'history' => true],
        'skillEnglish' => ['except' => false, 'as' => 'en', 'history' => true],
        'skillFrench' => ['except' => false, 'as' => 'fr', 'history' => true],
        'skillGerman' => ['except' => false, 'as' => 'de', 'history' => true],
        'skillDriving' => ['except' => false, 'as' => 'license_b', 'history' => true],
        'minProcesses' => ['except' => '', 'as' => 'min_proc', 'history' => true],
        'hasTask' => ['except' => false, 'as' => 'has_task', 'history' => true],
        'backlog' => ['except' => '', 'history' => true],
        'lastContact' => ['except' => '', 'as' => 'last_contact', 'history' => true],
        'recruiter' => ['except' => '', 'history' => true],
        'referralSource' => ['except' => '', 'as' => 'source', 'history' => true],
        'rejectionFilter' => ['except' => '', 'as' => 'rejection', 'history' => true],
        'search' => ['except' => '', 'as' => 'q', 'history' => true],
        'sortField' => ['except' => 'created_at', 'as' => 'sort', 'history' => true],
        'sortDirection' => ['except' => 'desc', 'as' => 'dir', 'history' => true],
        'view' => ['except' => '', 'history' => true],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingRecruiter(): void
    {
        $this->resetPage();
    }

    public function updatingRejectionFilter(): void
    {
        $this->resetPage();
    }

    public function updatingReferralSource(): void
    {
        $this->resetPage();
    }

    public function updatingFormerEmployee(): void
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

    /** Show only recruitments where the logged-in user is the assigned recruiter. */
    public function toggleMine(): void
    {
        $this->mine = ! $this->mine;
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
        $this->backlog = RecruitmentBacklog::sanitizeFilterKey($this->backlog);

        if ($this->view !== '' && $this->gridViewsTableExists()) {
            $this->loadViewFromSlug($this->view, flash: false);
        }

        $this->syncDraftFilters();

        if ($this->processId) {
            $this->selectedId = $this->processId;
        }

        if ($this->selectedId) {
            $this->loadCandidateEditFields();
        }
    }

    /**
     * @return array<string, scalar>
     */
    public function currentFilterQuery(): array
    {
        $query = [];

        foreach ($this->queryString as $property => $config) {
            $as = $config['as'] ?? $property;
            $except = $config['except'] ?? null;
            $value = $this->{$property};

            if ($value === $except) {
                continue;
            }

            $query[$as] = is_bool($value) ? (int) $value : $value;
        }

        return $query;
    }

    public function processUrl(int $id): string
    {
        return route('recruitment-processes.show', ['recruitmentProcess' => $id] + $this->currentFilterQuery());
    }

    public function listUrl(): string
    {
        return route('recruitment-processes.index', $this->currentFilterQuery());
    }

    public function syncDraftFilters(): void
    {
        $this->draftStatus = $this->status;
        $this->draftRecruiter = $this->recruiter;
        $this->draftReferralSource = $this->referralSource;
        $this->draftFlag = $this->flag;
        $this->draftMine = $this->mine;
        $this->draftFormerEmployee = $this->formerEmployee;
        $this->draftEmployment = $this->employment !== ''
            ? $this->employment
            : ($this->formerEmployee ? 'former' : '');
        $this->draftRateMin = $this->rateMin;
        $this->draftRateMax = $this->rateMax;
        $this->draftShipyardExperience = $this->shipyardExperience;
        $this->draftAvailableAfter = $this->availableAfter;
        $this->draftAvailableBefore = $this->availableBefore;
        $this->draftSkillEnglish = $this->skillEnglish;
        $this->draftSkillFrench = $this->skillFrench;
        $this->draftSkillGerman = $this->skillGerman;
        $this->draftSkillDriving = $this->skillDriving;
        $this->draftMinProcesses = $this->minProcesses;
        $this->draftHasTask = $this->hasTask;
        $this->draftLastContact = $this->lastContact;
        $this->draftRejectionFilter = $this->rejectionFilter;
    }

    public function applyDraftFilters(): void
    {
        $this->status = $this->draftStatus;
        $this->recruiter = $this->draftRecruiter;
        $this->referralSource = $this->draftReferralSource;
        $this->flag = $this->draftFlag;
        $this->mine = $this->draftMine;
        $this->employment = in_array($this->draftEmployment, ['hired', 'former'], true)
            ? $this->draftEmployment
            : '';
        $this->formerEmployee = $this->employment === 'former';
        $this->rateMin = $this->sanitizeDecimal($this->draftRateMin);
        $this->rateMax = $this->sanitizeDecimal($this->draftRateMax);
        $this->shipyardExperience = in_array($this->draftShipyardExperience, array_column(RecruitmentShipyardExperience::cases(), 'value'), true)
            ? $this->draftShipyardExperience
            : '';
        $this->availableAfter = $this->sanitizeDate($this->draftAvailableAfter);
        $this->availableBefore = $this->sanitizeDate($this->draftAvailableBefore);
        $this->skillEnglish = $this->draftSkillEnglish;
        $this->skillFrench = $this->draftSkillFrench;
        $this->skillGerman = $this->draftSkillGerman;
        $this->skillDriving = $this->draftSkillDriving;
        $this->minProcesses = in_array($this->draftMinProcesses, ['2', '3', '4'], true)
            ? $this->draftMinProcesses
            : '';
        $this->hasTask = $this->draftHasTask;
        $this->lastContact = $this->sanitizeLastContact($this->draftLastContact);
        $this->rejectionFilter = $this->draftRejectionFilter;
        $this->resetPage();
        $this->persistActiveView();
    }

    public function clearFilters(): void
    {
        $this->batchingViewPersist = true;
        $this->status = '';
        $this->recruiter = '';
        $this->referralSource = '';
        $this->flag = '';
        $this->mine = false;
        $this->formerEmployee = false;
        $this->employment = '';
        $this->rateMin = '';
        $this->rateMax = '';
        $this->shipyardExperience = '';
        $this->availableAfter = '';
        $this->availableBefore = '';
        $this->skillEnglish = false;
        $this->skillFrench = false;
        $this->skillGerman = false;
        $this->skillDriving = false;
        $this->minProcesses = '';
        $this->hasTask = false;
        $this->backlog = '';
        $this->lastContact = '';
        $this->rejectionFilter = '';
        $this->batchingViewPersist = false;
        $this->syncDraftFilters();
        $this->resetPage();
        $this->persistActiveView();
    }

    public function applyQuickFilter(string $type): void
    {
        $this->view = '';
        $this->clearFilters();
        $this->batchingViewPersist = true;

        match ($type) {
            'mine' => $this->mine = true,
            'wartosciowy' => $this->flag = RecruitmentCandidateFlag::Wartosciowy->value,
            'czarna_lista' => $this->flag = RecruitmentCandidateFlag::CzarnaLista->value,
            'byly_pracownik' => $this->setEmployment('former'),
            default => null,
        };

        $this->batchingViewPersist = false;
        $this->syncDraftFilters();
        $this->resetPage();
    }

    protected function setEmployment(string $value): void
    {
        $this->employment = in_array($value, ['hired', 'former'], true) ? $value : '';
        $this->formerEmployee = $this->employment === 'former';
    }

    public function activeFilterCount(): int
    {
        return count($this->activeFilterLabels());
    }

    /**
     * Dyskretne etykiety aktywnych filtrów (do paska pod toolbarem).
     *
     * @return list<string>
     */
    public function activeFilterLabels(): array
    {
        $labels = [];

        if ($this->status !== '') {
            $labels[] = 'Etap: '.(RecruitmentStatus::tryFrom($this->status)?->label() ?? $this->status);
        }

        if ($this->flag !== '') {
            $labels[] = RecruitmentCandidateFlag::tryFrom($this->flag)?->label() ?? $this->flag;
        }

        if ($this->employment === 'hired') {
            $labels[] = 'Zatrudniony';
        } elseif ($this->employment === 'former' || $this->formerEmployee) {
            $labels[] = 'Były pracownik';
        }

        if ($this->shipyardExperience !== '') {
            $labels[] = 'Doświadczenie: '.(RecruitmentShipyardExperience::tryFrom($this->shipyardExperience)?->label() ?? $this->shipyardExperience);
        }

        $skills = [];
        if ($this->skillEnglish) {
            $skills[] = 'EN';
        }
        if ($this->skillFrench) {
            $skills[] = 'FR';
        }
        if ($this->skillGerman) {
            $skills[] = 'DE';
        }
        if ($this->skillDriving) {
            $skills[] = 'Kat. B';
        }
        if ($skills !== []) {
            $labels[] = 'Umiejętności: '.implode(', ', $skills);
        }

        if ($this->rateMin !== '' && $this->rateMax !== '') {
            $labels[] = "Stawka: {$this->rateMin}–{$this->rateMax} €/h";
        } elseif ($this->rateMin !== '') {
            $labels[] = "Stawka > {$this->rateMin} €/h";
        } elseif ($this->rateMax !== '') {
            $labels[] = "Stawka < {$this->rateMax} €/h";
        }

        if ($this->availableAfter !== '' && $this->availableBefore !== '') {
            $labels[] = 'Dostępny: '.$this->formatFilterDate($this->availableAfter).' – '.$this->formatFilterDate($this->availableBefore);
        } elseif ($this->availableAfter !== '') {
            $labels[] = 'Dostępny od '.$this->formatFilterDate($this->availableAfter);
        } elseif ($this->availableBefore !== '') {
            $labels[] = 'Dostępny do '.$this->formatFilterDate($this->availableBefore);
        }

        if ($this->recruiter === 'unassigned') {
            $labels[] = 'Rekruter: nieprzypisany';
        } elseif ($this->recruiter !== '') {
            $name = User::query()->whereKey((int) $this->recruiter)->value('name');
            $labels[] = 'Rekruter: '.($name ?: '#'.$this->recruiter);
        }

        if ($this->mine) {
            $labels[] = 'Moje';
        }

        if ($this->minProcesses !== '') {
            $labels[] = 'Procesy: '.$this->minProcesses.'+';
        }

        if ($this->referralSource !== '') {
            $sourceLabel = collect($this->referralSourceFilterOptions())
                ->firstWhere('key', $this->referralSource)['label'] ?? $this->referralSource;
            $labels[] = 'Źródło: '.$sourceLabel;
        }

        if ($this->hasTask) {
            $labels[] = 'Ma zadanie';
        }

        if ($this->backlog !== '') {
            $labels[] = 'Backlog: '.(RecruitmentBacklog::label($this->backlog) ?? $this->backlog);
        }

        if ($this->lastContact !== '') {
            $labels[] = 'Ost. kontakt: '.($this->lastContactFilterOptions()[$this->lastContact] ?? $this->lastContact);
        }

        if ($this->rejectionFilter === 'none') {
            $labels[] = 'Bez powodu odrzucenia';
        } elseif ($this->rejectionFilter !== '') {
            $labels[] = 'Powód: '.(RecruitmentRejectionReason::tryFrom($this->rejectionFilter)?->label() ?? $this->rejectionFilter);
        }

        return $labels;
    }

    protected function formatFilterDate(string $ymd): string
    {
        try {
            return \Carbon\Carbon::createFromFormat('Y-m-d', $ymd)->format('d.m.Y');
        } catch (\Throwable) {
            return $ymd;
        }
    }

    protected function sanitizeDecimal(string $value): string
    {
        $value = trim(str_replace(',', '.', $value));
        if ($value === '' || ! is_numeric($value)) {
            return '';
        }

        return (string) max(0, (float) $value);
    }

    protected function sanitizeDate(string $value): string
    {
        $value = trim($value);
        if ($value === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }

        return $value;
    }

    /**
     * @return array<string, string>
     */
    protected function lastContactFilterOptions(): array
    {
        return [
            'none' => 'Brak',
            'today' => 'Dziś',
            'yesterday' => 'Wczoraj',
            'days_3' => '3 dni temu',
            'last_week' => 'W zeszłym tygodniu',
            'month_plus' => 'Ponad miesiąc temu',
            'half_year_plus' => 'Ponad pół roku temu',
            'year_plus' => 'Ponad rok temu',
            'years_2_plus' => 'Ponad 2 lata temu',
        ];
    }

    protected function sanitizeLastContact(string $value): string
    {
        return array_key_exists($value, $this->lastContactFilterOptions()) ? $value : '';
    }

    protected function lastCandidateContactSql(): string
    {
        return '(SELECT MAX(rca.created_at)'
            .' FROM recruitment_contact_attempts rca'
            .' JOIN recruitment_processes rp2 ON rp2.id = rca.recruitment_process_id'
            .' WHERE rp2.candidate_id = recruitment_candidates.id)';
    }

    /**
     * @param  Builder<\App\Models\RecruitmentCandidate>  $query
     */
    protected function applyLastContactFilter(Builder $query, string $lastContact): void
    {
        $lastContact = $this->sanitizeLastContact($lastContact);
        if ($lastContact === '') {
            return;
        }

        $expr = $this->lastCandidateContactSql();

        match ($lastContact) {
            'none' => $query->whereRaw("{$expr} IS NULL"),
            'today' => $query->whereRaw("{$expr} >= ? AND {$expr} < ?", [
                now()->startOfDay()->toDateTimeString(),
                now()->addDay()->startOfDay()->toDateTimeString(),
            ]),
            'yesterday' => $query->whereRaw("{$expr} >= ? AND {$expr} < ?", [
                now()->subDay()->startOfDay()->toDateTimeString(),
                now()->startOfDay()->toDateTimeString(),
            ]),
            'days_3' => $query->whereRaw("{$expr} >= ? AND {$expr} < ?", [
                now()->subDays(3)->startOfDay()->toDateTimeString(),
                now()->subDays(2)->startOfDay()->toDateTimeString(),
            ]),
            'last_week' => $query->whereRaw("{$expr} >= ? AND {$expr} < ?", [
                now()->copy()->startOfWeek()->subWeek()->toDateTimeString(),
                now()->copy()->startOfWeek()->toDateTimeString(),
            ]),
            'month_plus' => $query->whereRaw("{$expr} IS NOT NULL AND {$expr} < ?", [
                now()->subMonth()->toDateTimeString(),
            ]),
            'half_year_plus' => $query->whereRaw("{$expr} IS NOT NULL AND {$expr} < ?", [
                now()->subMonths(6)->toDateTimeString(),
            ]),
            'year_plus' => $query->whereRaw("{$expr} IS NOT NULL AND {$expr} < ?", [
                now()->subYear()->toDateTimeString(),
            ]),
            'years_2_plus' => $query->whereRaw("{$expr} IS NOT NULL AND {$expr} < ?", [
                now()->subYears(2)->toDateTimeString(),
            ]),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function advancedFiltersPayload(): array
    {
        return [
            'employment' => $this->employment,
            'rate_min' => $this->rateMin,
            'rate_max' => $this->rateMax,
            'shipyard_experience' => $this->shipyardExperience,
            'available_after' => $this->availableAfter,
            'available_before' => $this->availableBefore,
            'skill_english' => $this->skillEnglish,
            'skill_french' => $this->skillFrench,
            'skill_german' => $this->skillGerman,
            'skill_driving' => $this->skillDriving,
            'min_processes' => $this->minProcesses,
            'has_task' => $this->hasTask,
            'last_contact' => $this->lastContact,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    protected function applyAdvancedFiltersPayload(?array $filters): void
    {
        $filters = $filters ?? [];
        $this->employment = in_array($filters['employment'] ?? '', ['hired', 'former'], true)
            ? $filters['employment']
            : '';
        $this->rateMin = $this->sanitizeDecimal((string) ($filters['rate_min'] ?? ''));
        $this->rateMax = $this->sanitizeDecimal((string) ($filters['rate_max'] ?? ''));
        $this->shipyardExperience = in_array($filters['shipyard_experience'] ?? '', array_column(RecruitmentShipyardExperience::cases(), 'value'), true)
            ? $filters['shipyard_experience']
            : '';
        $this->availableAfter = $this->sanitizeDate((string) ($filters['available_after'] ?? ''));
        $this->availableBefore = $this->sanitizeDate((string) ($filters['available_before'] ?? ''));
        $this->skillEnglish = (bool) ($filters['skill_english'] ?? false);
        $this->skillFrench = (bool) ($filters['skill_french'] ?? false);
        $this->skillGerman = (bool) ($filters['skill_german'] ?? false);
        $this->skillDriving = (bool) ($filters['skill_driving'] ?? false);
        $this->minProcesses = in_array((string) ($filters['min_processes'] ?? ''), ['2', '3', '4'], true)
            ? (string) $filters['min_processes']
            : '';
        $this->hasTask = (bool) ($filters['has_task'] ?? false);
        $this->lastContact = $this->sanitizeLastContact((string) ($filters['last_contact'] ?? ''));
    }

    public function saveView(): void
    {
        if (! $this->gridViewsTableExists()) {
            $this->flash = 'Brak tabeli widoków — uruchom migracje (php artisan migrate).';

            return;
        }

        $name = trim($this->saveViewName);
        if ($name === '') {
            return;
        }

        $slug = Str::slug($name) ?: 'widok';

        $existing = RecruitmentGridView::query()
            ->where('user_id', auth()->id())
            ->where(fn ($q) => $q->where('name', $name)->orWhere('slug', $slug))
            ->first();

        if ($existing) {
            $slug = $existing->slug;
        } elseif (RecruitmentGridView::query()->where('user_id', auth()->id())->where('slug', $slug)->exists()) {
            $slug = $this->uniqueViewSlug($name);
        }

        RecruitmentGridView::updateOrCreate(
            ['user_id' => auth()->id(), 'slug' => $slug],
            array_merge(['name' => $name], $this->viewPayload()),
        );

        $this->view = $slug;
        $this->saveViewName = '';
        $this->flash = "Widok „{$name}” zapisany.";
    }

    public function loadView(string $slug): void
    {
        $this->loadViewFromSlug($slug);
    }

    public function deleteView(string $slug): void
    {
        RecruitmentGridView::query()
            ->where('user_id', auth()->id())
            ->where('slug', $slug)
            ->delete();

        if ($this->view === $slug) {
            $this->view = '';
        }
    }

    public function clearView(): void
    {
        $this->view = '';
        $this->flash = 'Widok domyślny.';
    }

    protected function gridViewsTableExists(): bool
    {
        return Schema::hasTable('recruitment_grid_views');
    }

    protected function loadViewFromSlug(string $slug, bool $flash = true): void
    {
        $record = RecruitmentGridView::query()
            ->where('user_id', auth()->id())
            ->where('slug', $slug)
            ->first();

        if (! $record) {
            if ($flash) {
                $this->flash = 'Nie znaleziono widoku.';
            }
            $this->view = '';

            return;
        }

        $this->view = $slug;
        $this->applyViewRecord($record);

        if ($flash) {
            $this->flash = "Załadowano „{$record->name}”.";
        }
    }

    protected function applyViewRecord(RecruitmentGridView $record): void
    {
        $this->batchingViewPersist = true;
        $this->status = $record->status ?? '';
        $this->flag = $record->flag ?? '';
        $this->mine = (bool) ($record->mine ?? false);
        $this->formerEmployee = (bool) ($record->former_employee ?? false);
        $this->recruiter = $record->recruiter ?? '';
        $this->referralSource = $record->referral_source ?? '';
        $this->rejectionFilter = $record->rejection_filter ?? '';
        $this->search = $record->search ?? '';
        $this->sortField = $record->sort_field ?: 'created_at';
        $this->sortDirection = $record->sort_direction ?: 'desc';
        $this->applyAdvancedFiltersPayload($record->advanced_filters);
        if ($this->employment === '' && $this->formerEmployee) {
            $this->employment = 'former';
        }
        $this->formerEmployee = $this->employment === 'former' || $this->formerEmployee;
        $this->batchingViewPersist = false;
        $this->syncDraftFilters();
        $this->resetPage();
    }

    /**
     * @return array<string, mixed>
     */
    protected function viewPayload(): array
    {
        return [
            'status' => $this->status,
            'flag' => $this->flag,
            'mine' => $this->mine,
            'former_employee' => $this->formerEmployee || $this->employment === 'former',
            'recruiter' => $this->recruiter,
            'referral_source' => $this->referralSource,
            'rejection_filter' => $this->rejectionFilter,
            'advanced_filters' => $this->advancedFiltersPayload(),
            'search' => $this->search,
            'sort_field' => $this->sortField,
            'sort_direction' => $this->sortDirection,
        ];
    }

    protected function persistActiveView(): void
    {
        if ($this->view === '' || ! $this->gridViewsTableExists()) {
            return;
        }

        RecruitmentGridView::query()
            ->where('user_id', auth()->id())
            ->where('slug', $this->view)
            ->update($this->viewPayload());
    }

    protected function uniqueViewSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'widok';
        $slug = $base;
        $i = 2;

        while (RecruitmentGridView::query()
            ->where('user_id', auth()->id())
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\RecruitmentProcess>  $query
     */
    protected function applyReferralSourceFilter($query, string $referralSource): void
    {
        if ($referralSource === '') {
            return;
        }

        $query->whereHas('lead', function ($leadQuery) use ($referralSource) {
            if (str_starts_with($referralSource, 'employee_lifecycle:')) {
                $subtype = substr($referralSource, strlen('employee_lifecycle:'));
                $leadQuery->where('referral_source', 'employee_lifecycle');

                if ($subtype === 'reinstate') {
                    $leadQuery->where('referral_source_detail', 'like', 'Przywrócenie%');
                } elseif ($subtype === 'terminate') {
                    $leadQuery->where('referral_source_detail', 'like', 'Zwolnienie%');
                }

                return;
            }

            $leadQuery->where('referral_source', $referralSource);
        });
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    protected function referralSourceFilterOptions(): array
    {
        return [
            ['key' => 'meta_business_suite', 'label' => 'Meta Business Suite (FB/IG)'],
            ['key' => 'historical_import', 'label' => 'Import historyczny (baza kandydatów)'],
            ['key' => 'employee_lifecycle:reinstate', 'label' => 'Cykl życia pracownika — Przywrócenie'],
            ['key' => 'employee_lifecycle:terminate', 'label' => 'Cykl życia pracownika — Zwolnienie'],
            ['key' => 'employee_referral', 'label' => 'Polecenie przez pracownika'],
            ['key' => 'olx', 'label' => 'OLX'],
            ['key' => 'trojmiasto', 'label' => 'Trojmiasto.pl'],
            ['key' => 'pracuj_pl', 'label' => 'Pracuj.pl'],
            ['key' => 'linkedin', 'label' => 'LinkedIn'],
            ['key' => 'job_portal_other', 'label' => 'Portal z ogłoszeniem o pracę (inny)'],
            ['key' => 'messenger', 'label' => 'Messenger / wiadomość bezpośrednia'],
            ['key' => 'contact_center', 'label' => 'Contact center'],
            ['key' => 'system_backfill', 'label' => 'Backfill systemowy (pracownik → kandydat)'],
        ];
    }

    /**
     * Licznik opcji w panelu filtrów — procesy (nie kandydaci), spójnie z badge’ami statusów.
     *
     * @param  callable(\Illuminate\Database\Eloquent\Builder<\App\Models\RecruitmentProcess>): void  $processConstraint
     */
    protected function countProcessesForFilter(callable $processConstraint): int
    {
        $query = RecruitmentProcess::query()
            ->when($this->status, fn ($q) => $q->where('status', $this->status));

        $processConstraint($query);

        return $query->count();
    }

    /**
     * Aktualny stan filtrów listy (bez sortowania).
     *
     * @return array<string, mixed>
     */
    protected function currentListFilters(): array
    {
        return [
            'status' => $this->status,
            'flag' => $this->flag,
            'mine' => $this->mine,
            'former_employee' => $this->formerEmployee,
            'employment' => $this->employment,
            'rate_min' => $this->rateMin,
            'rate_max' => $this->rateMax,
            'shipyard_experience' => $this->shipyardExperience,
            'available_after' => $this->availableAfter,
            'available_before' => $this->availableBefore,
            'skill_english' => $this->skillEnglish,
            'skill_french' => $this->skillFrench,
            'skill_german' => $this->skillGerman,
            'skill_driving' => $this->skillDriving,
            'min_processes' => $this->minProcesses,
            'has_task' => $this->hasTask,
            'backlog' => $this->backlog,
            'last_contact' => $this->lastContact,
            'recruiter' => $this->recruiter,
            'referral_source' => $this->referralSource,
            'rejection_filter' => $this->rejectionFilter,
            'search' => $this->search,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function filtersFromView(RecruitmentGridView $view): array
    {
        $advanced = $view->advanced_filters ?? [];

        return [
            'status' => $view->status ?? '',
            'flag' => $view->flag ?? '',
            'mine' => (bool) ($view->mine ?? false),
            'former_employee' => (bool) ($view->former_employee ?? false),
            'employment' => (string) ($advanced['employment'] ?? ''),
            'rate_min' => (string) ($advanced['rate_min'] ?? ''),
            'rate_max' => (string) ($advanced['rate_max'] ?? ''),
            'shipyard_experience' => (string) ($advanced['shipyard_experience'] ?? ''),
            'available_after' => (string) ($advanced['available_after'] ?? ''),
            'available_before' => (string) ($advanced['available_before'] ?? ''),
            'skill_english' => (bool) ($advanced['skill_english'] ?? false),
            'skill_french' => (bool) ($advanced['skill_french'] ?? false),
            'skill_german' => (bool) ($advanced['skill_german'] ?? false),
            'skill_driving' => (bool) ($advanced['skill_driving'] ?? false),
            'min_processes' => (string) ($advanced['min_processes'] ?? ''),
            'has_task' => (bool) ($advanced['has_task'] ?? false),
            'last_contact' => $this->sanitizeLastContact((string) ($advanced['last_contact'] ?? '')),
            'recruiter' => $view->recruiter ?? '',
            'referral_source' => $view->referral_source ?? '',
            'rejection_filter' => $view->rejection_filter ?? '',
            'search' => $view->search ?? '',
        ];
    }

    /**
     * Zapytanie kandydatów z nałożonymi filtrami (bez select/with/order/paginate).
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<\App\Models\RecruitmentCandidate>
     */
    protected function filteredCandidatesQuery(array $filters): Builder
    {
        $status = (string) ($filters['status'] ?? '');
        $flag = (string) ($filters['flag'] ?? '');
        $mine = (bool) ($filters['mine'] ?? false);
        $employment = (string) ($filters['employment'] ?? '');
        if ($employment === '' && ($filters['former_employee'] ?? false)) {
            $employment = 'former';
        }
        $recruiter = (string) ($filters['recruiter'] ?? '');
        $referralSource = (string) ($filters['referral_source'] ?? '');
        $rejectionFilter = (string) ($filters['rejection_filter'] ?? '');
        $rateMin = (string) ($filters['rate_min'] ?? '');
        $rateMax = (string) ($filters['rate_max'] ?? '');
        $shipyardExperience = (string) ($filters['shipyard_experience'] ?? '');
        $availableAfter = (string) ($filters['available_after'] ?? '');
        $availableBefore = (string) ($filters['available_before'] ?? '');
        $skillEnglish = (bool) ($filters['skill_english'] ?? false);
        $skillFrench = (bool) ($filters['skill_french'] ?? false);
        $skillGerman = (bool) ($filters['skill_german'] ?? false);
        $skillDriving = (bool) ($filters['skill_driving'] ?? false);
        $minProcesses = ($filters['min_processes'] ?? '') !== '' ? (int) $filters['min_processes'] : 0;
        $hasTask = (bool) ($filters['has_task'] ?? false);
        $backlog = RecruitmentBacklog::sanitizeFilterKey((string) ($filters['backlog'] ?? ''));
        $lastContact = $this->sanitizeLastContact((string) ($filters['last_contact'] ?? ''));
        $search = (string) ($filters['search'] ?? '');
        $userId = auth()->id();

        $searchDigits = preg_replace('/\D+/', '', $search);
        $phoneSearch = strlen($searchDigits) >= 3
            ? PhoneNormalizer::normalize($search)
            : null;

        $query = RecruitmentCandidate::query()
            ->withCount('processes')
            ->whereHas('processes', function ($q) use ($status, $mine, $userId, $recruiter, $referralSource, $rejectionFilter, $hasTask, $backlog) {
                $q->when($status, fn ($q) => $q->where('status', $status))
                    ->when($mine && $userId, fn ($q) => $q->where('assigned_recruiter_id', $userId))
                    ->when($recruiter === 'unassigned', fn ($q) => $q->whereNull('assigned_recruiter_id'))
                    ->when($recruiter !== '' && $recruiter !== 'unassigned', fn ($q) => $q->where('assigned_recruiter_id', (int) $recruiter))
                    ->when($rejectionFilter === 'none', fn ($q) => $q->whereNull('rejection_reason'))
                    ->when($rejectionFilter !== '' && $rejectionFilter !== 'none', fn ($q) => $q->where('rejection_reason', $rejectionFilter))
                    ->when($hasTask, fn ($q) => $q->whereHas('tasks'));

                if ($backlog !== '') {
                    RecruitmentBacklog::constrain($q, $backlog);
                }

                $this->applyReferralSourceFilter($q, $referralSource);
            })
            ->when($flag, fn ($q) => $q->where('recruitment_candidates.rating', $flag))
            ->when($rateMin !== '', fn ($q) => $q->where('recruitment_candidates.expected_rate_eur', '>=', (float) $rateMin))
            ->when($rateMax !== '', fn ($q) => $q->where('recruitment_candidates.expected_rate_eur', '<=', (float) $rateMax))
            ->when($shipyardExperience !== '', fn ($q) => $q->where('recruitment_candidates.shipyard_experience', $shipyardExperience))
            ->when($availableAfter !== '', fn ($q) => $q->whereDate('recruitment_candidates.available_from', '>=', $availableAfter))
            ->when($availableBefore !== '', fn ($q) => $q->whereDate('recruitment_candidates.available_from', '<=', $availableBefore))
            ->when($skillEnglish, fn ($q) => $q->where('recruitment_candidates.speaks_english', true))
            ->when($skillFrench, fn ($q) => $q->where('recruitment_candidates.speaks_french', true))
            ->when($skillGerman, fn ($q) => $q->where('recruitment_candidates.speaks_german', true))
            ->when($skillDriving, fn ($q) => $q->where('recruitment_candidates.has_driving_license_b', true))
            ->when($employment === 'hired', function ($q) {
                $q->whereNotNull('recruitment_candidates.employee_id')
                    ->whereHas('employee', fn ($eq) => $eq->whereNull('terminated_at'));
            })
            ->when($employment === 'former', function ($q) {
                $q->where(function ($q) {
                    $q->whereHas('employee', fn ($eq) => $eq->whereNotNull('terminated_at'))
                        ->orWhereHas('processes', fn ($pq) => $pq->where('status', RecruitmentStatus::BylyPracownik->value));
                });
            })
            ->when($minProcesses >= 2, fn ($q) => $q->having('processes_count', '>=', $minProcesses));

        $this->applyLastContactFilter($query, $lastContact);

        return $query->when($search, function ($q) use ($search, $phoneSearch) {
            $q->where(function ($q) use ($search, $phoneSearch) {
                $q->where('recruitment_candidates.first_name', 'like', "%{$search}%")
                    ->orWhere('recruitment_candidates.last_name', 'like', "%{$search}%")
                    ->orWhere('recruitment_candidates.phone', 'like', "%{$search}%")
                    ->orWhere('recruitment_candidates.email', 'like', "%{$search}%")
                    ->orWhereHas('roles', fn ($rq) => $rq->where('name', 'like', "%{$search}%"))
                    ->when($phoneSearch, fn ($q) => $q->orWhere('recruitment_candidates.phone', 'like', "%{$phoneSearch}%"));
            });
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function countCandidatesForFilters(array $filters): int
    {
        return (int) $this->filteredCandidatesQuery($filters)->toBase()->getCountForPagination();
    }

    public function selectProcess(int $id): void
    {
        if ($this->selectedId === $id) {
            return;
        }

        $this->redirect($this->processUrl($id), navigate: false);
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
        $this->redirect($this->listUrl(), navigate: false);
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

        $process->assignRecruiter(
            $recruiterId !== '' ? (int) $recruiterId : null,
            auth()->id()
        );

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

        if (! $this->batchingViewPersist && in_array($property, $this->persistableViewProperties, true)) {
            $this->persistActiveView();
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

        $process->assignRecruiter($recruiterId ?: null, auth()->id());
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
        $this->showContactModal = false;

        if ($shouldOpenTaskModal) {
            $this->openTaskModal($process);
        }
    }

    public function openContactModal(): void
    {
        $this->showContactModal = true;
        $this->newOutcome = '';
        $this->newComment = '';
        $this->contactSaved = false;
    }

    public function closeContactModal(): void
    {
        $this->showContactModal = false;
    }

    public function startEditAttempt(int $attemptId): void
    {
        $attempt = RecruitmentContactAttempt::find($attemptId);
        if (! $attempt || $attempt->user_id !== auth()->id()) {
            return;
        }

        $this->editingAttemptId = $attempt->id;
        $this->editAttemptComment = $attempt->comment ?? '';
    }

    public function cancelEditAttempt(): void
    {
        $this->editingAttemptId = null;
        $this->editAttemptComment = '';
    }

    public function saveEditAttempt(): void
    {
        $attempt = RecruitmentContactAttempt::find($this->editingAttemptId);
        if (! $attempt || $attempt->user_id !== auth()->id()) {
            $this->cancelEditAttempt();

            return;
        }

        $this->validate(['editAttemptComment' => 'nullable|string|max:2000']);

        $attempt->update(['comment' => $this->editAttemptComment ?: null]);

        $this->cancelEditAttempt();
    }

    public function deleteAttempt(int $attemptId): void
    {
        $attempt = RecruitmentContactAttempt::find($attemptId);
        if (! $attempt || $attempt->user_id !== auth()->id()) {
            return;
        }

        $attempt->delete();
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
            'candidate.employee',
            'candidate.allContactAttempts.user',
            'candidate.allContactAttempts.recruitmentProcess',
            'candidate.processes.lead',
            'candidate.processes.assignedRecruiter',
            'lead',
            'employee',
            'assignedRecruiter',
            'statusHistory.changedBy',
            'assignmentHistory.fromRecruiter',
            'assignmentHistory.toRecruiter',
            'assignmentHistory.changedBy',
            'tasks.assignedTo',
        ])->find($this->selectedId);
    }

    #[On('leads-imported')]
    public function refreshAfterLeadsImport(): void
    {
        $this->resetPage();
    }

    #[On('workload-distributed')]
    public function refreshAfterWorkloadDistribution(): void
    {
        $this->resetPage();
    }

    /**
     * @return array<int, array{type: string, created_at: \Carbon\Carbon, entry: mixed}>
     */
    protected function buildProcessTimeline(?RecruitmentProcess $process): array
    {
        if (! $process) {
            return [];
        }

        $entries = collect();

        foreach ($process->statusHistory as $entry) {
            $entries->push([
                'type' => 'status',
                'created_at' => $entry->created_at,
                'entry' => $entry,
            ]);
        }

        foreach ($process->assignmentHistory as $entry) {
            $entries->push([
                'type' => 'assignment',
                'created_at' => $entry->created_at,
                'entry' => $entry,
            ]);
        }

        return $entries->sortByDesc(fn ($item) => $item['created_at']->timestamp)->values()->all();
    }

    public function render()
    {
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
            ->whereHas('processes', fn ($q) => $q->when($this->status, fn ($q) => $q->where('status', $this->status)))
            ->whereNotNull('rating')
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $userId = auth()->id();
        $mineCount = $userId
            ? RecruitmentCandidate::query()
                ->whereHas('processes', fn ($q) => $q
                    ->when($this->status, fn ($q) => $q->where('status', $this->status))
                    ->where('assigned_recruiter_id', $userId))
                ->count()
            : 0;

        $sortColumn = match ($this->sortField) {
            'last_contact_at' => 'last_candidate_contact_at',
            'created_at' => 'recruitment_candidates.created_at',
            'expected_rate_eur' => 'recruitment_candidates.expected_rate_eur',
            default => 'recruitment_candidates.last_name',
        };

        // Each row in the main table = one candidate. Their processes are sub-rows.
        // Status / mine filters only decide which candidates appear; all of their
        // processes are loaded so sibling pipelines stay visible informatively.
        $applications = $this->filteredCandidatesQuery($this->currentListFilters())
            ->addSelect($lastCandidateContactSubquery)
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
            ->orderBy($sortColumn, $this->sortDirection)
            ->orderBy('recruitment_candidates.last_name')
            ->paginate(20);

        $recruiters = User::orderBy('name')->get();
        $selected = $this->getSelectedProcess();

        $recruiterCounts = [
            'unassigned' => $this->countProcessesForFilter(fn ($q) => $q->whereNull('assigned_recruiter_id')),
        ];
        foreach ($recruiters as $recruiterUser) {
            $recruiterCounts[(string) $recruiterUser->id] = $this->countProcessesForFilter(
                fn ($q) => $q->where('assigned_recruiter_id', $recruiterUser->id)
            );
        }

        $referralSourceCounts = [];
        foreach ($this->referralSourceFilterOptions() as $option) {
            $referralSourceCounts[$option['key']] = $this->countProcessesForFilter(
                fn ($q) => $this->applyReferralSourceFilter($q, $option['key'])
            );
        }

        $formerEmployeeCount = $this->countProcessesForFilter(
            fn ($q) => $q->where('status', RecruitmentStatus::BylyPracownik->value)
        );

        $savedViews = $this->gridViewsTableExists()
            ? RecruitmentGridView::query()
                ->where('user_id', auth()->id())
                ->orderBy('name')
                ->get()
            : collect();

        $viewCounts = [];
        foreach ($savedViews as $savedView) {
            $viewCounts[$savedView->slug] = $this->countCandidatesForFilters(
                $this->filtersFromView($savedView)
            );
        }

        // Left drawer list must mirror the main table page (same filters/sort/page).
        // The open lead's candidate is lifted out of the list and rendered above it,
        // so scrolling the list never hides the record being worked on.
        $listCandidates = $applications->getCollection()->values();
        $pinnedCandidate = null;

        if ($selected?->candidate) {
            $selectedCandidateId = $selected->candidate_id;
            $pinnedCandidate = $listCandidates->firstWhere('id', $selectedCandidateId) ?? $selected->candidate;

            // Newest processes first — the active one is highlighted, no need to pin it on top.
            $pinnedCandidate->setRelation(
                'processes',
                $pinnedCandidate->processes->sortByDesc('created_at')->values()
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
            'mineCount' => $mineCount,
            'formerEmployeeCount' => $formerEmployeeCount,
            'recruiterCounts' => $recruiterCounts,
            'referralSourceCounts' => $referralSourceCounts,
            'referralSourceOptions' => $this->referralSourceFilterOptions(),
            'activeFilterCount' => $this->activeFilterCount(),
            'activeFilterLabels' => $this->activeFilterLabels(),
            'savedViews' => $savedViews,
            'viewCounts' => $viewCounts,
            'activeViewName' => $this->view !== ''
                ? ($savedViews->firstWhere('slug', $this->view)?->name ?? $this->view)
                : null,
            'total' => RecruitmentCandidate::whereHas('processes')->count(),
            'roles' => Role::orderBy('name')->get(),
            'recruiters' => $recruiters,
            'selected' => $selected,
            'processTimeline' => $this->buildProcessTimeline($selected),
        ]);
    }
}
