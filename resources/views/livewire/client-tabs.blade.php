<div>
    @include('livewire.partials.client-crm-styles')

    @if(session('success'))
        <x-ui.alert variant="success" dismissible class="mb-3">{{ session('success') }}</x-ui.alert>
    @endif

    @include('livewire.partials.client-company-inline')

    <div class="crm-hero crm-hero--four">
        <div class="crm-hero__panel">
            <div class="crm-hero__label">Kontrahent</div>
            <div class="crm-hero__value">{{ $client->displayName() }}</div>
            <div class="crm-hero__meta">
                {{ $client->type?->shortLabel() ?? 'Klient' }}
                ·
                @if($client->is_active)
                    <span class="text-success">aktywny</span>
                @else
                    <span class="text-warning">nieaktywny</span>
                @endif
                @if($primaryContact)
                    · {{ $primaryContact->name }}
                @endif
            </div>
        </div>

        @if($client->canOwnProjects())
            <button type="button" class="crm-hero__panel" wire:click="setTab('projects')">
                <div class="crm-hero__label">Projekty</div>
                <div class="crm-hero__value font-mono">
                    {{ $activeProjectsCount }}
                    <span class="crm-hero__value-muted">/ {{ $client->projects_count ?? 0 }}</span>
                </div>
                <div class="crm-hero__meta">aktywne dziś / wszystkie</div>
            </button>
        @else
            <div class="crm-hero__panel">
                <div class="crm-hero__label">Rola</div>
                <div class="crm-hero__value">{{ $client->type?->shortLabel() }}</div>
                <div class="crm-hero__meta">usługi / podwykonawstwo — bez projektów CRM</div>
            </div>
        @endif

        <button type="button" class="crm-hero__panel" wire:click="setTab('contacts')">
            <div class="crm-hero__label">Relacje</div>
            <div class="crm-hero__value font-mono">{{ $client->contacts_count ?? 0 }}</div>
            <div class="crm-hero__meta">
                osoby kontaktowe
                @if(($client->contact_attempts_count ?? 0) > 0)
                    · {{ $client->contact_attempts_count }} wpisów aktywności
                @endif
            </div>
        </button>

        <button
            type="button"
            @class([
                'crm-hero__panel',
                'crm-hero__panel--alert' => $dueFollowUps->isNotEmpty() && $overdueFollowUpsCount === 0,
                'crm-hero__panel--danger' => $overdueFollowUpsCount > 0,
            ])
            wire:click="setTab('attempts')"
        >
            <div class="crm-hero__label">Ostatni kontakt</div>
            @if($lastAttempt)
                <div class="crm-hero__value">
                    {{ $lastAttempt->contacted_at->format('d.m.Y H:i') }}
                </div>
                <div class="crm-hero__meta">
                    {{ $lastAttempt->purpose->label() }}
                    @if($lastAttempt->project) · {{ $lastAttempt->project->name }}@endif
                    @if($overdueFollowUpsCount > 0)
                        · <span class="text-danger">{{ $overdueFollowUpsCount }} zaległe</span>
                    @endif
                </div>
            @else
                <div class="crm-hero__value">Brak</div>
                <div class="crm-hero__meta">Kliknij, aby dodać wpis.</div>
            @endif
        </button>
    </div>

    <x-ui.tabs
        :tabs="$tabsForComponent"
        :activeTab="$activeTab"
        id="clientTabs"
        :compact-mobile="true"
        mobile-label="Sekcja kartoteki"
    />

    <div id="clientTabsContent">
        @if($activeTab === 'info')
            <x-ui.card label="Do oddzwonienia">
                <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                    <div class="small text-muted">Terminy z aktywności — zaległe i najbliższe 3 dni. Edycja pól jest inline na zakładce Aktywność.</div>
                    <div class="d-flex gap-2">
                        <x-ui.button variant="ghost" type="button" class="btn-sm" wire:click="goToDueFollowUps">
                            Cała aktywność
                        </x-ui.button>
                        @if($canManage)
                            <x-ui.button variant="primary" type="button" class="btn-sm" wire:click="openAttemptForm">
                                <i class="bi bi-plus-lg"></i> Nowy kontakt
                            </x-ui.button>
                        @endif
                    </div>
                </div>

                @if($dueFollowUps->isEmpty())
                    <x-ui.empty-state icon="check2-circle" message="Nic do oddzwonienia w najbliższych dniach." />
                @else
                    <div class="crm-followups">
                        @foreach($dueFollowUps as $followUp)
                            @php $isOverdue = $followUp->next_action_at->isPast(); @endphp
                            <div @class(['crm-followup', 'crm-followup--overdue' => $isOverdue]) wire:key="crm-fu-{{ $followUp->id }}">
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">
                                        {{ $followUp->purpose->label() }}
                                        @if($followUp->project)
                                            <span class="text-muted fw-normal">· {{ $followUp->project->name }}</span>
                                        @endif
                                    </div>
                                    <div class="small text-muted mt-1">
                                        @if($followUp->contact){{ $followUp->contact->name }} · @endif
                                        {{ $followUp->outcome->label() }}
                                        @if($followUp->summary)
                                            · {{ \Illuminate\Support\Str::limit($followUp->summary, 80) }}
                                        @endif
                                    </div>
                                    @if($canManage)
                                        <div class="mt-2">
                                            <label class="crm-inline-label {{ $isOverdue ? 'text-danger' : 'text-warning' }}">Oddzwoń</label>
                                            <input
                                                type="datetime-local"
                                                class="form-control form-control-sm crm-inline"
                                                style="max-width: 14rem;"
                                                value="{{ $followUp->next_action_at->format('Y-m-d\TH:i') }}"
                                                wire:change="updateAttemptField({{ $followUp->id }}, 'next_action_at', $event.target.value)"
                                            >
                                        </div>
                                    @else
                                        <div class="crm-followup__when mt-1 {{ $isOverdue ? 'text-danger' : 'text-warning' }}">
                                            Oddzwoń {{ $isOverdue ? '(zaległe)' : '' }}:
                                            {{ $followUp->next_action_at->format('d.m.Y H:i') }}
                                        </div>
                                    @endif
                                </div>
                                @if($canManage)
                                    <div class="crm-followup__actions">
                                        <button type="button" class="btn btn-sm btn-outline-success" wire:click="completeFollowUp({{ $followUp->id }})">
                                            <i class="bi bi-check2"></i> Gotowe
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="snoozeFollowUp({{ $followUp->id }}, 1)">+1d</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="snoozeFollowUp({{ $followUp->id }}, 3)">+3d</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="continueFollowUp({{ $followUp->id }})">
                                            <i class="bi bi-telephone-outbound"></i>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>

        @elseif($activeTab === 'contacts')
            <x-ui.card label="Osoby po stronie klienta">
                <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                    <div class="small text-muted">Kogo dzwonisz przy rozliczeniach i ustaleniach.</div>
                    @if($canManage)
                        <x-ui.button variant="primary" type="button" class="btn-sm" wire:click="openContactForm">
                            <i class="bi bi-person-plus"></i> Dodaj osobę
                        </x-ui.button>
                    @endif
                </div>

                @if($showContactForm && $canManage)
                    <div class="crm-form-panel">
                        <div class="fw-semibold mb-3">Nowa osoba kontaktowa</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small">Imię i nazwisko</label>
                                <input type="text" class="form-control @error('contactName') is-invalid @enderror" wire:model="contactName">
                                @error('contactName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Rola / stanowisko</label>
                                <input type="text" class="form-control" wire:model="contactRole" placeholder="np. Kierownik budowy">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">E-mail</label>
                                <input type="email" class="form-control @error('contactEmail') is-invalid @enderror" wire:model="contactEmail">
                                @error('contactEmail') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Telefon</label>
                                <input type="text" class="form-control" wire:model="contactPhone">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Notatka</label>
                                <textarea class="form-control" rows="2" wire:model="contactNotes"></textarea>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="contactIsPrimary" wire:model="contactIsPrimary">
                                    <label class="form-check-label" for="contactIsPrimary">Kontakt główny</label>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <x-ui.button variant="ghost" type="button" wire:click="cancelContactForm">Anuluj</x-ui.button>
                            <x-ui.button variant="primary" type="button" wire:click="saveContact">Dodaj</x-ui.button>
                        </div>
                    </div>
                @endif

                @if($contacts->isEmpty())
                    <x-ui.empty-state icon="person" message="Brak osób kontaktowych. Dodaj pierwszą osobę po stronie klienta." />
                @else
                    <div class="crm-contact-grid">
                        @foreach($contacts as $contact)
                            <div @class(['crm-contact', 'crm-contact--primary' => $contact->is_primary]) wire:key="crm-contact-{{ $contact->id }}">
                                <div class="d-flex justify-content-between gap-2 mb-2">
                                    <div class="flex-grow-1">
                                        @if($canManage)
                                            <input
                                                type="text"
                                                class="form-control crm-inline crm-inline--title"
                                                value="{{ $contact->name }}"
                                                wire:blur="updateContactField({{ $contact->id }}, 'name', $event.target.value)"
                                            >
                                            <input
                                                type="text"
                                                class="form-control crm-inline crm-inline--muted"
                                                value="{{ $contact->role }}"
                                                placeholder="Rola / stanowisko"
                                                wire:blur="updateContactField({{ $contact->id }}, 'role', $event.target.value)"
                                            >
                                        @else
                                            <div class="crm-contact__name">{{ $contact->name }}</div>
                                            <div class="crm-contact__role">{{ $contact->role ?? 'Bez roli' }}</div>
                                        @endif
                                    </div>
                                    @if($canManage)
                                        <div class="d-flex gap-1 align-items-start">
                                            @unless($contact->is_primary)
                                                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="setPrimaryContact({{ $contact->id }})" title="Ustaw jako główny">
                                                    <i class="bi bi-star"></i>
                                                </button>
                                            @else
                                                <span class="badge badge-info align-self-center">główny</span>
                                            @endunless
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                wire:click="deleteContact({{ $contact->id }})"
                                                wire:confirm="Usunąć osobę kontaktową?"
                                                title="Usuń"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                @if($canManage)
                                    <div class="crm-contact__row">
                                        <i class="bi bi-envelope"></i>
                                        <input type="email" class="form-control form-control-sm crm-inline" value="{{ $contact->email }}" placeholder="e-mail" wire:blur="updateContactField({{ $contact->id }}, 'email', $event.target.value)">
                                    </div>
                                    <div class="crm-contact__row">
                                        <i class="bi bi-telephone"></i>
                                        <input type="text" class="form-control form-control-sm crm-inline" value="{{ $contact->phone }}" placeholder="telefon" wire:blur="updateContactField({{ $contact->id }}, 'phone', $event.target.value)">
                                    </div>
                                    <div class="crm-contact__row">
                                        <i class="bi bi-sticky"></i>
                                        <input type="text" class="form-control form-control-sm crm-inline crm-inline--muted" value="{{ $contact->notes }}" placeholder="notatka" wire:blur="updateContactField({{ $contact->id }}, 'notes', $event.target.value)">
                                    </div>
                                    <div class="form-check mt-2">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            id="contact-active-{{ $contact->id }}"
                                            @checked($contact->is_active)
                                            wire:change="updateContactField({{ $contact->id }}, 'is_active', $event.target.checked)"
                                        >
                                        <label class="form-check-label small" for="contact-active-{{ $contact->id }}">Aktywna</label>
                                    </div>
                                @else
                                    @if($contact->email)
                                        <div class="crm-contact__row"><i class="bi bi-envelope"></i> {{ $contact->email }}</div>
                                    @endif
                                    @if($contact->phone)
                                        <div class="crm-contact__row"><i class="bi bi-telephone"></i> {{ $contact->phone }}</div>
                                    @endif
                                    @if($contact->notes)
                                        <div class="crm-contact__row"><i class="bi bi-sticky"></i> {{ $contact->notes }}</div>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>

        @elseif($activeTab === 'attempts')
            <x-ui.card label="Aktywność">
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-stretch align-items-xl-end gap-3 mb-3">
                    <div class="d-flex flex-column flex-md-row gap-3 align-items-stretch align-items-md-end">
                        <div>
                            <div class="small text-muted mb-1">Filtr</div>
                            <div class="crm-scope" role="group" aria-label="Filtr aktywności">
                                <button type="button" @class(['crm-scope__btn', 'is-active' => $attemptsScopeFilter === 'all']) wire:click="$set('attemptsScopeFilter', 'all')">Wszystkie</button>
                                <button type="button" @class(['crm-scope__btn', 'is-active' => $attemptsScopeFilter === 'due']) wire:click="$set('attemptsScopeFilter', 'due')">Do oddzwonienia</button>
                                <button type="button" @class(['crm-scope__btn', 'is-active' => $attemptsScopeFilter === 'no_answer']) wire:click="$set('attemptsScopeFilter', 'no_answer')">Bez odpowiedzi</button>
                            </div>
                        </div>
                        <div>
                            <div class="small text-muted mb-1">Projekt</div>
                            <select class="form-select" style="min-width: 14rem;" wire:model.live="attemptsProjectFilter">
                                <option value="">Wszystkie + ogólne</option>
                                @foreach($allProjects as $projectOption)
                                    <option value="{{ $projectOption->id }}">{{ $projectOption->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @if($canManage)
                        <x-ui.button variant="primary" type="button" wire:click="openAttemptForm">
                            <i class="bi bi-plus-lg"></i> Nowy kontakt
                        </x-ui.button>
                    @endif
                </div>

                <p class="small text-muted mb-3">
                    Jedna historia rozmów i ustaleń. „Oddzwoń” to data przy wpisie — nie osobny typ.
                </p>

                @if($showAttemptForm && $canManage)
                    @include('livewire.partials.client-attempt-form')
                @endif

                @include('livewire.partials.client-attempts-timeline', [
                    'attempts' => $attempts,
                    'compact' => false,
                    'canManage' => $canManage,
                    'expandedAttemptId' => $expandedAttemptId,
                    'allProjects' => $allProjects,
                    'contacts' => $contacts,
                    'channelOptions' => $channelOptions,
                    'purposeOptions' => $purposeOptions,
                    'outcomeOptions' => $outcomeOptions,
                ])
            </x-ui.card>

        @elseif($activeTab === 'projects')
            <x-ui.card label="Projekty klienta">
                <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                    <div class="small text-muted">Ostatni kontakt liczony per projekt z rejestru CRM.</div>
                    <div class="d-flex gap-2">
                        @if($canManage)
                            <x-ui.button variant="ghost" type="button" class="btn-sm" wire:click="openAttemptForm">
                                <i class="bi bi-telephone-outbound"></i> Kontakt
                            </x-ui.button>
                        @endif
                        <x-ui.button variant="primary" href="{{ route('projects.create', ['client_id' => $client->id]) }}" routeName="projects.create" class="btn-sm">
                            <i class="bi bi-plus-lg"></i> Nowy projekt
                        </x-ui.button>
                    </div>
                </div>

                @if($projects->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Nazwa</th>
                                    <th>Lokalizacja</th>
                                    <th>Status</th>
                                    <th>Ostatni kontakt</th>
                                    <th class="text-end">Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($projects as $project)
                                    <tr wire:key="client-project-{{ $project->id }}">
                                        <td class="fw-semibold">
                                            <a href="{{ route('projects.show', $project) }}" class="text-primary text-decoration-none">
                                                {{ $project->name }}
                                            </a>
                                            <div class="small text-muted">{{ $project->type?->label() ?? '—' }}</div>
                                        </td>
                                        <td>{{ $project->location?->name ?? '—' }}</td>
                                        <td>
                                            @php
                                                $statusValue = $project->status instanceof \App\Enums\ProjectStatus
                                                    ? $project->status->value
                                                    : $project->status;
                                            @endphp
                                            <x-ui.badge variant="info">{{ ucfirst((string) $statusValue) }}</x-ui.badge>
                                        </td>
                                        <td class="font-mono small">
                                            @if($project->last_contact_at)
                                                {{ \Carbon\Carbon::parse($project->last_contact_at)->format('d.m.Y H:i') }}
                                            @else
                                                <span class="text-muted">brak</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($canManage)
                                                <button type="button" class="btn btn-sm btn-outline-secondary me-1" wire:click="openAttemptForm({{ $project->id }})">
                                                    <i class="bi bi-telephone"></i>
                                                </button>
                                            @endif
                                            <x-ui.button variant="ghost" href="{{ route('projects.show', $project) }}" class="btn-sm">Szczegóły</x-ui.button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-ui.empty-state icon="folder" message="Ten klient nie ma jeszcze projektów." />
                @endif
            </x-ui.card>

        @elseif($activeTab === 'hour_audit')
            <x-ui.card label="Audyt niezgodności godzin">
                <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-end justify-content-between gap-3 mb-3">
                    <div>
                        <div class="small text-muted mb-1">Miesiąc rozliczenia</div>
                        <input
                            type="month"
                            class="form-control"
                            style="max-width: 14rem;"
                            wire:model.live="auditMonth"
                        >
                    </div>
                    <div class="small text-muted">
                        Lista dni, gdzie godziny klienta ≠ deklarowane. Konsensus zapisujesz bez nadpisywania warstw źródłowych.
                        @if($openDiscrepanciesCount > 0)
                            <span class="d-block mt-1 text-warning">Otwarte do ustalenia: {{ $openDiscrepanciesCount }}</span>
                        @endif
                    </div>
                </div>

                @php
                    $formatGridHours = static function ($hours): string {
                        if ($hours === '' || $hours === null) {
                            return '';
                        }
                        $hoursFloat = is_numeric($hours) ? (float) $hours : (float) str_replace(',', '.', (string) $hours);
                        $totalMinutes = (int) round($hoursFloat * 60);
                        $roundedMinutes = (int) (round($totalMinutes / 15) * 15);
                        if ($roundedMinutes <= 0) {
                            return '';
                        }
                        $hPart = intdiv($roundedMinutes, 60);
                        $mPart = $roundedMinutes % 60;

                        return $hPart.':'.str_pad((string) $mPart, 2, '0', STR_PAD_LEFT);
                    };
                @endphp

                <form method="POST" action="{{ route('time-logs.bulk-update') }}">
                    @csrf
                    <input type="hidden" name="layer" value="settled">
                    <input type="hidden" name="redirect_to" value="{{ route('clients.show', ['client' => $client->id, 'tab' => 'hour_audit', 'month' => $auditMonth]) }}">

                    <div class="table-responsive">
                        <table class="table align-middle mb-0 monthly-grid-discrepancies">
                            <thead>
                                <tr>
                                    <th>Projekt</th>
                                    <th>Osoba</th>
                                    <th class="text-nowrap">Dzień</th>
                                    <th class="text-center text-nowrap">Deklarowane</th>
                                    <th class="text-center text-nowrap">Wg klienta</th>
                                    <th class="text-center text-nowrap" style="min-width: 7rem;">Ustalone</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($hourDiscrepancies as $row)
                                    <tr @class(['monthly-grid-discrepancies__row--open' => $row['settled_hours'] === null])>
                                        <td class="fw-semibold">{{ $row['project_name'] }}</td>
                                        <td>{{ $row['employee_name'] }}</td>
                                        <td class="font-mono text-nowrap">{{ $row['day_label'] }}</td>
                                        <td class="text-center font-mono">{{ $formatGridHours($row['hours_worked']) }}</td>
                                        <td class="text-center font-mono">{{ $formatGridHours($row['client_hours']) }}</td>
                                        <td class="p-1">
                                            <input
                                                type="text"
                                                name="entries[{{ $row['assignment_id'] }}][{{ $row['date'] }}]"
                                                class="form-control form-control-sm text-center time-input time-input--settled"
                                                value="{{ $formatGridHours($row['settled_hours']) }}"
                                                inputmode="numeric"
                                                placeholder="H:MM"
                                            >
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            Brak rozbieżności w {{ \Carbon\Carbon::parse($auditMonth.'-01')->locale('pl')->translatedFormat('F Y') }}.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(count($hourDiscrepancies) > 0)
                        <div class="d-flex justify-content-end mt-3 gap-2 flex-wrap">
                            @if($canManage)
                                <x-ui.button variant="ghost" type="button" wire:click="openAttemptForm">
                                    <i class="bi bi-telephone-outbound"></i> Zarejestruj ustalenie
                                </x-ui.button>
                            @endif
                            <x-ui.button variant="primary" type="submit">
                                <i class="bi bi-check-lg"></i> Zapisz konsensus
                            </x-ui.button>
                        </div>
                    @endif
                </form>
            </x-ui.card>
        @elseif($activeTab === 'comments')
            <x-ui.card label="Notatki zespołu">
                <p class="small text-muted mb-3">
                    Wewnętrzne uwagi ChronoLogic — to nie jest rejestr rozmów z klientem (ten jest w „Aktywność”).
                </p>
                <x-comments :commentable="$client" />
            </x-ui.card>
        @endif
    </div>
</div>
