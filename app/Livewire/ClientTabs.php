<?php

namespace App\Livewire;

use App\Enums\ClientContactChannel;
use App\Enums\ClientContactOutcome;
use App\Enums\ClientContactPurpose;
use App\Enums\ClientType;
use App\Enums\EuropeanCountry;
use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\ClientContactAttempt;
use App\Services\TimeLogService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ClientTabs extends Component
{
    public Client $client;

    public string $activeTab = 'info';

    public string $auditMonth = '';

    public ?int $attemptsProjectFilter = null;

    /** @var string all|due|no_answer|open */
    public string $attemptsScopeFilter = 'all';

    public array $availableTabs = [];

    public bool $showContactForm = false;

    public ?int $editingContactId = null;

    public string $contactName = '';

    public string $contactRole = '';

    public string $contactEmail = '';

    public string $contactPhone = '';

    public bool $contactIsPrimary = false;

    public bool $contactIsActive = true;

    public string $contactNotes = '';

    public bool $showAttemptForm = false;

    public ?int $editingAttemptId = null;

    public ?int $expandedAttemptId = null;

    public ?int $attemptProjectId = null;

    public ?int $attemptContactId = null;

    public string $attemptChannel = 'phone';

    public string $attemptPurpose = 'hours_settlement';

    public string $attemptOutcome = 'reached';

    public string $attemptContactedAt = '';

    public string $attemptNextActionAt = '';

    public string $attemptSummary = '';

    protected $queryString = [
        'activeTab' => ['except' => 'info', 'as' => 'tab'],
        'auditMonth' => ['except' => '', 'as' => 'month'],
        'attemptsProjectFilter' => ['except' => null, 'as' => 'project'],
        'attemptsScopeFilter' => ['except' => 'all', 'as' => 'scope'],
    ];

    public function mount(Client $client): void
    {
        $this->client = $client;
        $this->auditMonth = $this->auditMonth !== ''
            ? $this->auditMonth
            : now()->format('Y-m');
        $this->attemptContactedAt = now()->format('Y-m-d\TH:i');
        $this->buildAvailableTabs();
        $this->validateActiveTab();
    }

    protected function buildAvailableTabs(): void
    {
        $tabs = [
            'info' => ['label' => 'Przegląd', 'icon' => 'bi bi-info-circle'],
            'contacts' => ['label' => 'Osoby', 'icon' => 'bi bi-person-lines-fill'],
            'attempts' => ['label' => 'Aktywność', 'icon' => 'bi bi-telephone-outbound'],
        ];

        if ($this->client->canOwnProjects()) {
            $tabs['projects'] = ['label' => 'Projekty', 'icon' => 'bi bi-folder'];
            $tabs['hour_audit'] = ['label' => 'Audyt godzin', 'icon' => 'bi bi-exclamation-triangle'];
        }

        $tabs['comments'] = ['label' => 'Notatki zespołu', 'icon' => 'bi bi-chat-left-text'];

        $this->availableTabs = $tabs;
    }

    protected function validateActiveTab(): void
    {
        // Stare bookmarki / aliasy.
        if ($this->activeTab === 'activity') {
            $this->activeTab = 'attempts';
        }

        if (! isset($this->availableTabs[$this->activeTab])) {
            $this->activeTab = array_key_first($this->availableTabs) ?? 'info';
        }
    }

    public function setTab(string $tab): void
    {
        if (! isset($this->availableTabs[$tab])) {
            return;
        }
        $this->activeTab = $tab;
    }

    public function goToDueFollowUps(): void
    {
        $this->attemptsScopeFilter = 'due';
        $this->activeTab = 'attempts';
    }

    public function goToHourAudit(): void
    {
        $this->activeTab = 'hour_audit';
    }

    public function updatedAuditMonth(string $value): void
    {
        try {
            $this->auditMonth = Carbon::parse($value.'-01')->format('Y-m');
        } catch (\Throwable) {
            $this->auditMonth = now()->format('Y-m');
        }
    }

    public function updatedAttemptsProjectFilter(mixed $value): void
    {
        $this->attemptsProjectFilter = $value === '' || $value === null
            ? null
            : (int) $value;
    }

    public function updatedAttemptsScopeFilter(string $value): void
    {
        $allowed = ['all', 'due', 'no_answer'];
        $this->attemptsScopeFilter = in_array($value, $allowed, true) ? $value : 'all';
    }

    public function canManage(): bool
    {
        $user = Auth::user();

        return $user && ($user->isAdmin() || $user->hasPermission('clients.update'));
    }

    public function updateClientField(string $field, mixed $value = null): void
    {
        if (! $this->canManage()) {
            return;
        }

        $allowed = [
            'name', 'type', 'short_name', 'nip', 'tax_id', 'address', 'city',
            'postal_code', 'country', 'notes', 'is_active',
        ];
        if (! in_array($field, $allowed, true)) {
            return;
        }

        $raw = is_string($value) ? trim($value) : $value;
        if (in_array($field, ['short_name', 'nip', 'tax_id', 'address', 'city', 'postal_code', 'country', 'notes'], true)
            && ($raw === '' || $raw === null)) {
            $raw = null;
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(ClientType::class)],
            'short_name' => ['nullable', 'string', 'max:100'],
            'nip' => ['nullable', 'string', 'digits:10', Rule::unique('clients', 'nip')->ignore($this->client->id)],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'country' => ['nullable', 'string', Rule::in(array_column(EuropeanCountry::cases(), 'value'))],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];

        if ($field === 'is_active') {
            $raw = in_array($raw, [true, 1, '1', 'true', 'on'], true);
        }

        if ($field === 'type'
            && $raw === ClientType::ServiceProvider->value
            && $this->client->projects()->exists()) {
            $this->addError('type', 'Nie można zmienić na usługodawcę — kontrahent ma przypisane projekty.');

            return;
        }

        $validated = validator([$field => $raw], [$field => $rules[$field]])->validate();
        $this->client->update([$field => $validated[$field]]);

        if ($field === 'name') {
            $this->client->projects()->update(['client_name' => $this->client->name]);
        }

        $this->client->refresh();

        if ($field === 'type') {
            $this->buildAvailableTabs();
            $this->validateActiveTab();
        }
    }

    public function updateContactField(int $contactId, string $field, mixed $value = null): void
    {
        if (! $this->canManage()) {
            return;
        }

        $contact = $this->client->contacts()->whereKey($contactId)->first();
        if (! $contact) {
            return;
        }

        $allowed = ['name', 'role', 'email', 'phone', 'notes', 'is_primary', 'is_active'];
        if (! in_array($field, $allowed, true)) {
            return;
        }

        $raw = is_string($value) ? trim($value) : $value;
        if (in_array($field, ['role', 'email', 'phone', 'notes'], true) && ($raw === '' || $raw === null)) {
            $raw = null;
        }

        if ($field === 'is_primary' || $field === 'is_active') {
            $raw = filter_var($raw, FILTER_VALIDATE_BOOLEAN);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'is_primary' => ['boolean'],
            'is_active' => ['boolean'],
        ];

        $validated = validator([$field => $raw], [$field => $rules[$field]])->validate();

        if ($field === 'is_primary' && $validated['is_primary'] === true) {
            $this->client->contacts()->where('id', '!=', $contactId)->update(['is_primary' => false]);
        }

        $contact->update([$field => $validated[$field]]);
        $this->client->syncLegacyContactFields();
    }

    public function updateAttemptField(int $attemptId, string $field, mixed $value = null): void
    {
        if (! $this->canManage()) {
            return;
        }

        $attempt = $this->client->contactAttempts()->whereKey($attemptId)->first();
        if (! $attempt) {
            return;
        }

        $allowed = [
            'project_id', 'client_contact_id', 'channel', 'purpose', 'outcome',
            'contacted_at', 'next_action_at', 'summary',
        ];
        if (! in_array($field, $allowed, true)) {
            return;
        }

        $raw = is_string($value) ? trim($value) : $value;
        if (in_array($field, ['project_id', 'client_contact_id', 'next_action_at', 'summary'], true)
            && ($raw === '' || $raw === null)) {
            $raw = null;
        }

        $projectIds = $this->client->projects()->pluck('id')->all();
        $contactIds = $this->client->contacts()->pluck('id')->all();

        $rules = [
            'project_id' => ['nullable', 'integer', Rule::in($projectIds)],
            'client_contact_id' => ['nullable', 'integer', Rule::in($contactIds)],
            'channel' => ['required', Rule::enum(ClientContactChannel::class)],
            'purpose' => ['required', Rule::enum(ClientContactPurpose::class)],
            'outcome' => ['required', Rule::enum(ClientContactOutcome::class)],
            'contacted_at' => ['required', 'date'],
            'next_action_at' => ['nullable', 'date'],
            'summary' => ['nullable', 'string', 'max:5000'],
        ];

        $validated = validator([$field => $raw], [$field => $rules[$field]])->validate();
        $payload = [$field => $validated[$field]];

        if ($field === 'contacted_at' || $field === 'next_action_at') {
            $payload[$field] = $validated[$field]
                ? Carbon::parse($validated[$field])
                : null;
        }

        $attempt->update($payload);
    }

    public function openContactForm(?int $contactId = null): void
    {
        if (! $this->canManage()) {
            return;
        }

        // Edycja tylko inline — formularz służy wyłącznie do dodania osoby.
        $this->resetContactForm();
        $this->showContactForm = true;
        $this->editingContactId = null;
        $this->activeTab = 'contacts';
    }

    public function cancelContactForm(): void
    {
        $this->resetContactForm();
    }

    public function saveContact(): void
    {
        if (! $this->canManage()) {
            return;
        }

        $data = $this->validate([
            'contactName' => ['required', 'string', 'max:255'],
            'contactRole' => ['nullable', 'string', 'max:255'],
            'contactEmail' => ['nullable', 'email', 'max:255'],
            'contactPhone' => ['nullable', 'string', 'max:50'],
            'contactIsPrimary' => ['boolean'],
            'contactIsActive' => ['boolean'],
            'contactNotes' => ['nullable', 'string'],
        ], [], [
            'contactName' => 'imię i nazwisko',
            'contactEmail' => 'e-mail',
            'contactPhone' => 'telefon',
        ]);

        if ($this->contactIsPrimary) {
            $this->client->contacts()->update(['is_primary' => false]);
        }

        $payload = [
            'name' => $data['contactName'],
            'role' => $data['contactRole'] ?: null,
            'email' => $data['contactEmail'] ?: null,
            'phone' => $data['contactPhone'] ?: null,
            'is_primary' => $this->contactIsPrimary,
            'is_active' => $this->contactIsActive,
            'notes' => $data['contactNotes'] ?: null,
        ];

        if (! $this->client->contacts()->exists()) {
            $payload['is_primary'] = true;
        }
        $this->client->contacts()->create($payload);

        $this->client->syncLegacyContactFields();
        $this->resetContactForm();
        $this->activeTab = 'contacts';
        session()->flash('success', 'Dodano osobę kontaktową.');
    }

    public function deleteContact(int $contactId): void
    {
        if (! $this->canManage()) {
            return;
        }

        $contact = $this->client->contacts()->whereKey($contactId)->first();
        if (! $contact) {
            return;
        }

        $wasPrimary = $contact->is_primary;
        $contact->delete();

        if ($wasPrimary) {
            $next = $this->client->contacts()->where('is_active', true)->orderBy('id')->first();
            $next?->update(['is_primary' => true]);
        }

        $this->client->syncLegacyContactFields();
        session()->flash('success', 'Usunięto osobę kontaktową.');
    }

    public function setPrimaryContact(int $contactId): void
    {
        if (! $this->canManage()) {
            return;
        }

        $contact = $this->client->contacts()->whereKey($contactId)->first();
        if (! $contact) {
            return;
        }

        $this->client->contacts()->update(['is_primary' => false]);
        $contact->update(['is_primary' => true, 'is_active' => true]);
        $this->client->syncLegacyContactFields();
        session()->flash('success', 'Ustawiono kontakt główny.');
    }

    public function openAttemptForm(?int $projectId = null): void
    {
        if (! $this->canManage()) {
            return;
        }

        $this->resetAttemptForm();
        $this->showAttemptForm = true;
        $this->expandedAttemptId = null;
        $this->attemptProjectId = $projectId;
        $this->activeTab = 'attempts';
    }

    public function toggleAttemptExpanded(int $attemptId): void
    {
        if ($this->expandedAttemptId === $attemptId) {
            $this->expandedAttemptId = null;

            return;
        }

        $this->showAttemptForm = false;
        $this->expandedAttemptId = $attemptId;
        $this->activeTab = 'attempts';
    }

    public function cancelAttemptForm(): void
    {
        $this->resetAttemptForm();
    }

    public function saveAttempt(): void
    {
        if (! $this->canManage()) {
            return;
        }

        if ($this->attemptProjectId === 0 || $this->attemptProjectId === '') {
            $this->attemptProjectId = null;
        }
        if ($this->attemptContactId === 0 || $this->attemptContactId === '') {
            $this->attemptContactId = null;
        }

        $projectIds = $this->client->projects()->pluck('id')->all();
        $contactIds = $this->client->contacts()->pluck('id')->all();

        $data = $this->validate([
            'attemptProjectId' => ['nullable', 'integer', Rule::in($projectIds)],
            'attemptContactId' => ['nullable', 'integer', Rule::in($contactIds)],
            'attemptChannel' => ['required', Rule::enum(ClientContactChannel::class)],
            'attemptPurpose' => ['required', Rule::enum(ClientContactPurpose::class)],
            'attemptOutcome' => ['required', Rule::enum(ClientContactOutcome::class)],
            'attemptContactedAt' => ['required', 'date'],
            'attemptNextActionAt' => ['nullable', 'date', 'after_or_equal:attemptContactedAt'],
            'attemptSummary' => ['nullable', 'string', 'max:5000'],
        ], [], [
            'attemptProjectId' => 'projekt',
            'attemptContactId' => 'osoba kontaktowa',
            'attemptChannel' => 'kanał',
            'attemptPurpose' => 'cel',
            'attemptOutcome' => 'wynik',
            'attemptContactedAt' => 'data kontaktu',
            'attemptNextActionAt' => 'oddzwoń',
            'attemptSummary' => 'notatka',
        ]);

        $payload = [
            'project_id' => $data['attemptProjectId'] ?: null,
            'client_contact_id' => $data['attemptContactId'] ?: null,
            'channel' => $data['attemptChannel'],
            'purpose' => $data['attemptPurpose'],
            'outcome' => $data['attemptOutcome'],
            'contacted_at' => Carbon::parse($data['attemptContactedAt']),
            'next_action_at' => ! empty($data['attemptNextActionAt'])
                ? Carbon::parse($data['attemptNextActionAt'])
                : null,
            'summary' => $data['attemptSummary'] ?: null,
        ];

        // Edycja istniejących = rozwinięta karta; formularz draft tylko tworzy nowe.
        $payload['user_id'] = Auth::id();
        $created = $this->client->contactAttempts()->create($payload);
        session()->flash('success', 'Zarejestrowano kontakt.');

        $this->resetAttemptForm();
        $this->expandedAttemptId = $created->id;
        $this->activeTab = 'attempts';
    }

    public function deleteAttempt(int $attemptId): void
    {
        if (! $this->canManage()) {
            return;
        }

        $attempt = $this->client->contactAttempts()->whereKey($attemptId)->first();
        $attempt?->delete();
        session()->flash('success', 'Usunięto wpis kontaktu.');
    }

    public function completeFollowUp(int $attemptId): void
    {
        if (! $this->canManage()) {
            return;
        }

        $attempt = $this->client->contactAttempts()->whereKey($attemptId)->first();
        if (! $attempt) {
            return;
        }

        $attempt->update(['next_action_at' => null]);
        session()->flash('success', 'Oddzwoń zamknięte.');
    }

    public function snoozeFollowUp(int $attemptId, int $days = 1): void
    {
        if (! $this->canManage()) {
            return;
        }

        $days = max(1, min(30, $days));
        $attempt = $this->client->contactAttempts()->whereKey($attemptId)->first();
        if (! $attempt) {
            return;
        }

        $base = $attempt->next_action_at && $attempt->next_action_at->isFuture()
            ? $attempt->next_action_at->copy()
            : now()->copy();

        $attempt->update([
            'next_action_at' => $base->addDays($days)->setTime(9, 0),
        ]);

        session()->flash('success', "Oddzwoń przesunięte o {$days} dn.");
    }

    public function continueFollowUp(int $attemptId): void
    {
        if (! $this->canManage()) {
            return;
        }

        $attempt = $this->client->contactAttempts()->whereKey($attemptId)->first();
        if (! $attempt) {
            return;
        }

        $this->resetAttemptForm();
        $this->showAttemptForm = true;
        $this->attemptProjectId = $attempt->project_id;
        $this->attemptContactId = $attempt->client_contact_id;
        $this->attemptChannel = $attempt->channel->value;
        $this->attemptPurpose = $attempt->purpose->value;
        $this->attemptOutcome = ClientContactOutcome::Reached->value;
        $this->attemptContactedAt = now()->format('Y-m-d\TH:i');
        $this->attemptNextActionAt = '';
        $this->attemptSummary = '';
        $this->activeTab = 'attempts';
        $this->attemptsScopeFilter = 'all';
        $this->expandedAttemptId = null;

        // Zamknięcie starego „oddzwoń” — nowy wpis to kolejny kontakt.
        $attempt->update(['next_action_at' => null]);
    }

    protected function resetContactForm(): void
    {
        $this->showContactForm = false;
        $this->editingContactId = null;
        $this->contactName = '';
        $this->contactRole = '';
        $this->contactEmail = '';
        $this->contactPhone = '';
        $this->contactIsPrimary = false;
        $this->contactIsActive = true;
        $this->contactNotes = '';
        $this->resetValidation();
    }

    protected function resetAttemptForm(): void
    {
        $this->showAttemptForm = false;
        $this->editingAttemptId = null;
        $this->attemptProjectId = null;
        $this->attemptContactId = null;
        $this->attemptChannel = ClientContactChannel::Phone->value;
        $this->attemptPurpose = ClientContactPurpose::HoursSettlement->value;
        $this->attemptOutcome = ClientContactOutcome::Reached->value;
        $this->attemptContactedAt = now()->format('Y-m-d\TH:i');
        $this->attemptNextActionAt = '';
        $this->attemptSummary = '';
        $this->resetValidation();
    }

    public function render()
    {
        $this->client->loadCount(['projects', 'comments', 'contacts', 'contactAttempts']);

        $contacts = collect();
        $attempts = collect();
        $projects = collect();
        $hourDiscrepancies = [];
        $openDiscrepanciesCount = 0;

        $allProjects = $this->client->projects()
            ->with('location')
            ->withMax('clientContactAttempts as last_contact_at', 'contacted_at')
            ->orderBy('name')
            ->get();

        $activeProjectsCount = $allProjects
            ->filter(fn ($project) => $project->status === ProjectStatus::ACTIVE && $project->isCurrentlyActive())
            ->count();

        $projectIds = $allProjects->pluck('id')->all();

        if ($projectIds !== []) {
            $preview = app(TimeLogService::class)->getHourDiscrepancies($this->auditMonth, $projectIds);
            $openDiscrepanciesCount = collect($preview)
                ->filter(fn (array $row) => ($row['settled_hours'] ?? null) === null)
                ->count();
            if ($this->activeTab === 'hour_audit') {
                $hourDiscrepancies = $preview;
            }
        }

        // Osoby zawsze pod ręką (hero + inline na zakładkach).
        $contacts = $this->client->contacts()->get();

        $lastAttempt = $this->client->contactAttempts()
            ->with(['project', 'contact', 'user'])
            ->latest('contacted_at')
            ->first();

        $dueFollowUps = $this->client->contactAttempts()
            ->with(['project', 'contact', 'user'])
            ->whereNotNull('next_action_at')
            ->where('next_action_at', '<=', now()->endOfDay()->addDays(3))
            ->orderBy('next_action_at')
            ->limit(8)
            ->get();

        $overdueFollowUpsCount = $this->client->contactAttempts()
            ->whereNotNull('next_action_at')
            ->where('next_action_at', '<', now())
            ->count();

        if (in_array($this->activeTab, ['info', 'attempts'], true)) {
            $attemptsQuery = $this->client->contactAttempts()
                ->with(['project', 'contact', 'user'])
                ->latest('contacted_at');

            if ($this->attemptsProjectFilter) {
                $attemptsQuery->where('project_id', $this->attemptsProjectFilter);
            }

            if ($this->activeTab === 'attempts') {
                $attemptsQuery = match ($this->attemptsScopeFilter) {
                    'due' => $attemptsQuery
                        ->whereNotNull('next_action_at')
                        ->where('next_action_at', '<=', now()->endOfDay()->addDays(3))
                        ->reorder()
                        ->orderBy('next_action_at'),
                    'no_answer' => $attemptsQuery->where('outcome', ClientContactOutcome::NoAnswer->value),
                    default => $attemptsQuery,
                };
            }

            // Przegląd nie dubluje listy aktywności — tylko kolejka „oddzwoń”.
            if ($this->activeTab === 'attempts') {
                $attempts = $attemptsQuery->limit(50)->get();
            }
        }

        if ($this->activeTab === 'projects') {
            $projects = $allProjects;
        }

        if ($this->activeTab === 'comments') {
            $this->client->load(['comments.user']);
        }

        $primaryContact = $contacts->firstWhere('is_primary', true) ?? $contacts->first();

        $tabsForComponent = [];
        foreach ($this->availableTabs as $tabKey => $tab) {
            $count = match ($tabKey) {
                'contacts' => $this->client->contacts_count ?? 0,
                'attempts' => $overdueFollowUpsCount > 0
                    ? $overdueFollowUpsCount
                    : ($this->client->contact_attempts_count ?? 0),
                'projects' => $this->client->projects_count ?? 0,
                'hour_audit' => $openDiscrepanciesCount,
                'comments' => $this->client->comments_count ?? 0,
                default => null,
            };

            $tabsForComponent[$tabKey] = [
                'label' => $tab['label'],
                'icon' => $tab['icon'] ?? null,
                'count' => $count,
                'wireClick' => "setTab('{$tabKey}')",
                'warning' => ($tabKey === 'hour_audit' && $openDiscrepanciesCount > 0)
                    || ($tabKey === 'attempts' && $overdueFollowUpsCount > 0),
            ];
        }

        return view('livewire.client-tabs', [
            'tabsForComponent' => $tabsForComponent,
            'contacts' => $contacts,
            'attempts' => $attempts,
            'projects' => $projects,
            'allProjects' => $allProjects,
            'hourDiscrepancies' => $hourDiscrepancies,
            'openDiscrepanciesCount' => $openDiscrepanciesCount,
            'activeProjectsCount' => $activeProjectsCount,
            'lastAttempt' => $lastAttempt,
            'dueFollowUps' => $dueFollowUps,
            'overdueFollowUpsCount' => $overdueFollowUpsCount,
            'primaryContact' => $primaryContact,
            'canManage' => $this->canManage(),
            'channelOptions' => ClientContactChannel::options(),
            'purposeOptions' => ClientContactPurpose::options(),
            'outcomeOptions' => ClientContactOutcome::options(),
            'countryOptions' => EuropeanCountry::cases(),
            'typeOptions' => ClientType::options(),
        ]);
    }
}
