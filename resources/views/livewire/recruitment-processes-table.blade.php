@php
    use App\Enums\RecruitmentCandidateFlag;
    use App\Enums\RecruitmentContactOutcome;
    use App\Enums\RecruitmentRejectionReason;
    use App\Enums\RecruitmentStatus;
@endphp
<div>
    @if(session('success'))
        <x-ui.alert variant="success" dismissible class="mb-3">{{ session('success') }}</x-ui.alert>
    @endif

    {{-- ════════ SEARCH + FILTERS + TOOLS ════════ --}}
    @if($flash)
        <div class="alert alert-success alert-dismissible py-2 mb-2 d-flex align-items-center gap-2 small" role="alert">
            <i class="bi bi-check-circle-fill text-success"></i>
            <span class="flex-grow-1">{{ $flash }}</span>
            <button type="button" wire:click="$set('flash', null)" class="btn-close" style="font-size:.8rem"></button>
        </div>
    @endif

    @unless($selectedId)
    <div class="rp-toolbar mb-2 d-flex align-items-center gap-2 flex-wrap">
        <div class="rp-search flex-grow-1" style="max-width:360px;">
            <i class="bi bi-search"></i>
            <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                   placeholder="Szukaj kandydata…">
        </div>

        {{-- Filtry (SharePoint-style dropdown) --}}
        <div x-data="{ open: false, top: 0, left: 0, openCandidate: false, openProcess: false, openLead: false, openOther: false }">
            <button type="button"
                    @click.stop="if(open){open=false;$wire.syncDraftFilters();return} $wire.syncDraftFilters(); const r=$el.getBoundingClientRect(); top=r.bottom+4; left=Math.min(r.left, window.innerWidth-620); open=true"
                    class="btn btn-sm {{ $activeFilterCount > 0 ? 'btn-primary' : 'btn-outline-secondary' }}">
                <i class="bi bi-sliders me-1"></i>Filtry
                @if($activeFilterCount > 0)
                    <span class="badge bg-light text-dark ms-1" style="font-size:.6rem;">{{ $activeFilterCount }}</span>
                @endif
                <i class="bi bi-chevron-down ms-1" style="font-size:.6rem;"></i>
            </button>
            <template x-teleport="body">
                <div x-show="open" x-cloak
                     @click.outside="open=false; $wire.syncDraftFilters()"
                     :style="`position:fixed;top:${top}px;left:${left}px;z-index:999990;`"
                     class="rp-filter-panel">
                    @include('livewire.partials.rp-filter-panel')
                </div>
            </template>
        </div>

        {{-- Badge’e zapisanych widoków (przełączanie) --}}
        @foreach($savedViews as $savedView)
            <button type="button" wire:click="loadView('{{ $savedView->slug }}')"
                    class="btn btn-sm rp-topbar-btn {{ $view === $savedView->slug ? 'btn-info' : 'btn-outline-secondary' }}">
                <i class="bi bi-bookmark{{ $view === $savedView->slug ? '-fill' : '' }} me-1"></i>{{ $savedView->name }}
                <span class="rp-view-count">{{ $viewCounts[$savedView->slug] ?? 0 }}</span>
            </button>
        @endforeach

        <div class="ms-auto d-flex align-items-center gap-2">
            <div x-data="{ open: false, showViews: false }">
                <button type="button" @click="open = true; showViews = false"
                        class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-tools me-1"></i>Narzędzia
                </button>
                @include('livewire.partials.rp-tools-modal')
            </div>
        </div>
    </div>

    @if(count($activeFilterLabels) > 0)
        <div class="rp-active-filters mb-3">
            <span class="rp-active-filters__label">Filtry:</span>
            @foreach($activeFilterLabels as $filterLabel)
                <span class="rp-active-filters__chip">{{ $filterLabel }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters" class="rp-active-filters__clear">Wyczyść</button>
        </div>
    @endif

    <livewire:recruitment-workload-distribution :hide-trigger="true" wire:key="workload-distribution" />
    <livewire:mbs-lead-import :hide-trigger="true" wire:key="mbs-import" />

    {{-- ════════ PROCESS TABLE: grouped by candidate ════════ --}}
    <x-ui.card class="p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle" style="border-collapse:collapse;">
                    <thead>
                        <tr style="font-size:.67rem;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);">
                            <th style="padding-left:1rem;border-bottom:0;" colspan="2">Kandydat</th>
                            <th style="border-bottom:0;">Źródło</th>
                            <th style="border-bottom:0;">Rekruter</th>
                            <th style="border-bottom:0;">Status</th>
                            <th style="border-bottom:0;"><button type="button" wire:click="sortBy('last_contact_at')" class="rp-sort-btn">Ost. kontakt @if($sortField==='last_contact_at')<i class="bi bi-arrow-{{ $sortDirection==='asc'?'up':'down' }}"></i>@endif</button></th>
                            <th style="border-bottom:0;">Próby</th>
                            <th style="border-bottom:0;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applications as $candidate)
                            @php $procs = $candidate->processes; @endphp
                            {{-- ── Candidate header row ──────────────────────── --}}
                            <tr wire:key="cand-{{ $candidate->id }}" style="background:rgba(255,255,255,.025);border-top:2px solid var(--glass-border);">
                                <td style="padding:.6rem 0 .6rem 1rem;width:36px;">
                                    <div class="position-relative flex-shrink-0" style="width:36px;">
                                        <x-ui.avatar :image-url="$candidate->photo_url" :initials="mb_strtoupper(mb_substr($candidate->first_name,0,1).mb_substr($candidate->last_name,0,1))" size="34px" shape="rounded" :border="false" />
                                        @if($candidate->rating === RecruitmentCandidateFlag::Wartosciowy)
                                            <i class="bi bi-star-fill position-absolute" style="font-size:.6rem;color:#f59e0b;bottom:-2px;right:-2px;"></i>
                                        @elseif($candidate->rating === RecruitmentCandidateFlag::CzarnaLista)
                                            <i class="bi bi-flag-fill position-absolute" style="font-size:.6rem;color:var(--danger);bottom:-2px;right:-2px;"></i>
                                        @endif
                                    </div>
                                </td>
                                <td colspan="7" style="padding:.6rem .75rem;">
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <div>
                                            <span class="fw-semibold" style="font-size:.92rem;">{{ $candidate->full_name }}</span>
                                            @if($candidate->rating)
                                                <span class="badge badge-{{ $candidate->rating->variant() }} ms-1" style="font-size:.58rem;">{{ $candidate->rating->label() }}</span>
                                            @endif
                                            <div style="font-size:.75rem;color:var(--text-muted);">
                                                @if($candidate->phone)<a href="tel:{{ $candidate->phone }}" class="text-decoration-none" style="color:var(--text-muted);">{{ $candidate->phone }}</a>@endif
                                                @if($candidate->phone && $candidate->email) &nbsp;·&nbsp; @endif
                                                @if($candidate->email)<span>{{ $candidate->email }}</span>@endif
                                                @if($candidate->city) &nbsp;·&nbsp; <i class="bi bi-geo-alt"></i> {{ $candidate->city }}@endif
                                            </div>
                                        </div>
                                        <div class="d-flex flex-wrap gap-1 ms-auto">
                                            @foreach($candidate->roles as $role)
                                                <span class="badge badge-info" style="font-size:.65rem;">{{ $role->name }}</span>
                                            @endforeach
                                            @if($candidate->shipyard_experience)
                                                <span class="badge badge-secondary" style="font-size:.65rem;"><i class="bi bi-tools me-1"></i>{{ $candidate->shipyard_experience->label() }}</span>
                                            @endif
                                            @if($candidate->expected_rate_eur !== null)
                                                <span class="badge badge-secondary" style="font-size:.65rem;">{{ number_format((float)$candidate->expected_rate_eur, 0) }} €/h</span>
                                            @endif
                                            @if($candidate->available_from)
                                                <span class="badge badge-success" style="font-size:.65rem;"><i class="bi bi-calendar-check me-1"></i>Od {{ $candidate->available_from->format('d.m.Y') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            {{-- ── Process sub-rows ──────────────────────────── --}}
                            @foreach($procs as $proc)
                                @php
                                    $lo = RecruitmentContactOutcome::tryFrom($proc->last_contact_outcome ?? '');
                                    $loBadge = match($lo?->variant()) { 'success'=>'badge-success','danger'=>'badge-danger','warning'=>'badge-warning',default=>'badge-info' };
                                    $matchesFilter = ! $status || $proc->status?->value === $status;
                                @endphp
                                <tr wire:key="proc-{{ $proc->id }}"
                                    wire:click="selectProcess({{ $proc->id }})"
                                    role="button"
                                    style="font-size:.82rem;cursor:pointer;{{ $matchesFilter ? '' : 'opacity:.45;' }}"
                                    class="{{ $selectedId===$proc->id ? 'table-active' : '' }}">
                                    <td style="width:36px;"></td>
                                    <td style="padding-left:.75rem;color:var(--text-muted);font-size:.75rem;white-space:nowrap;">
                                        <i class="bi bi-arrow-return-right me-1" style="font-size:.65rem;"></i>
                                        #{{ $proc->id }}
                                    </td>
                                    <td style="color:var(--text-muted);">{{ $proc->lead?->referral_source_label ?? '—' }}</td>
                                    <td onclick="event.stopPropagation()">
                                        <select class="form-select form-select-sm" style="min-width:130px;font-size:.78rem;"
                                                wire:change="updateAssignedRecruiter({{ $proc->id }}, $event.target.value)">
                                            <option value="">— Nieprzypisany —</option>
                                            @foreach($recruiters as $recruiter)
                                                <option value="{{ $recruiter->id }}" @selected($proc->assigned_recruiter_id === $recruiter->id)>{{ $recruiter->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td onclick="event.stopPropagation()">
                                        <select class="form-select form-select-sm" style="min-width:130px;font-size:.78rem;" wire:change="updateStatus({{ $proc->id }}, $event.target.value)">
                                            @foreach(RecruitmentStatus::cases() as $case)
                                                <option value="{{ $case->value }}" @selected($proc->status === $case)>{{ $case->label() }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        @if($proc->last_contact_at)
                                            <div style="font-weight:600;">{{ \Carbon\Carbon::parse($proc->last_contact_at)->format('d.m.Y') }}</div>
                                            @if($lo)<span class="badge {{ $loBadge }}" style="font-size:.62rem;padding:2px 5px;">{{ $lo->label() }}</span>@endif
                                        @else
                                            <span style="color:var(--text-muted);">Brak</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($proc->contact_attempts_count > 0)
                                            <span class="badge badge-info">{{ $proc->contact_attempts_count }}</span>
                                        @else
                                            <span style="color:var(--text-muted);">0</span>
                                        @endif
                                    </td>
                                    <td onclick="event.stopPropagation()">
                                        @if($candidate->phone)
                                            <a href="tel:{{ $candidate->phone }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-telephone"></i></a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="8"><x-ui.empty-state :in-table="false" icon="person-lines-fill" :message="$status || $search || $recruiter || $rejectionFilter || $referralSource || $flag || $mine || $formerEmployee ? 'Brak kandydatów spełniających kryteria.' : 'Nie przesłano jeszcze żadnych zgłoszeń.'" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($applications->hasPages())
                <div class="px-3 py-2" style="border-top:1px solid var(--glass-border);">{{ $applications->links() }}</div>
            @endif
            <div class="px-3 pb-2" style="color:var(--text-muted);font-size:.75rem;">Wyświetlono {{ $applications->count() }} z {{ $applications->total() }} kandydatów</div>
        </x-ui.card>
    @endunless

    @if($selectedId && $selected)
            <div class="rp-modal rp-modal--page"
                 x-data="{
                    pane: 'profile',
                    listOpen: window.__rpListOpen ?? (window.innerWidth >= 1280),
                 }"
                 x-init="$watch('listOpen', v => window.__rpListOpen = v)"
                 :data-pane="pane"
                 :class="{ 'rp-list-open': listOpen }">

                {{-- Top bar --}}
                <div class="rp-modal-topbar">
                    <div class="rp-topbar-main">
                        <div class="rp-topbar-row">
                            <button type="button" class="btn btn-sm btn-outline-secondary rp-list-toggle"
                                    :class="listOpen && 'is-active'"
                                    @click="listOpen = !listOpen; if (listOpen && window.innerWidth < 768) pane = 'list'">
                                <i class="bi" :class="listOpen ? 'bi-layout-sidebar-inset' : 'bi-people'"></i>
                                <span x-text="listOpen ? 'Ukryj listę' : 'Lista'"></span>
                            </button>
                            <div class="rp-search rp-search--sm rp-topbar-search">
                                <i class="bi bi-search"></i>
                                <input type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" placeholder="Szukaj…">
                            </div>
                            @if(count($activeFilterLabels) > 0)
                                <div class="rp-active-filters rp-active-filters--compact">
                                    @foreach($activeFilterLabels as $filterLabel)
                                        <span class="rp-active-filters__chip">{{ $filterLabel }}</span>
                                    @endforeach
                                </div>
                            @endif
                            <span class="rp-topbar-label ms-1">Sortuj:</span>
                            @foreach(['last_contact_at' => ['Ost. kontakt', 'bi-telephone'], 'created_at' => ['Dodano', 'bi-calendar-plus'], 'last_name' => ['Nazwisko', 'bi-person'], 'expected_rate_eur' => ['Stawka', 'bi-currency-euro']] as $field => [$label, $icon])
                                <button type="button" wire:click="sortBy('{{ $field }}')"
                                        class="btn btn-sm btn-outline-secondary rp-topbar-btn {{ $sortField===$field ? 'is-active' : '' }}">
                                    <i class="bi {{ $icon }} me-1"></i>{{ $label }}
                                    @if($sortField===$field)<i class="bi bi-arrow-{{ $sortDirection==='asc'?'up':'down' }} ms-1"></i>@endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <a href="{{ $this->listUrl() }}" class="rp-modal-close" title="Wróć do listy" aria-label="Wróć do listy">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>

                <div class="rp-pane-tabs">
                    <button type="button" class="rp-pane-tab" :class="pane === 'list' && 'is-active'" @click="pane = 'list'; listOpen = false">Lista</button>
                    <button type="button" class="rp-pane-tab" :class="pane === 'profile' && 'is-active'" @click="pane = 'profile'; listOpen = false">Kandydat</button>
                </div>

                {{-- Body --}}
                <div class="rp-modal-body">
                    <div class="rp-list-backdrop" x-show="listOpen" x-cloak @click="listOpen = false"></div>

                    {{-- ── LEFT: candidate list (synced with main table page) ── --}}
                    <div class="rp-modal-left">
                        {{-- Open lead sits outside the scroll area so it never scrolls away. --}}
                        @if($pinnedCandidate)
                            <div class="rp-modal-left__pinned">
                                @include('livewire.partials.rp-candidate-group', ['cand' => $pinnedCandidate, 'isPinned' => true])
                            </div>
                        @endif
                        <div class="rp-modal-left__list">
                            @forelse($listCandidates as $cand)
                                @include('livewire.partials.rp-candidate-group', ['cand' => $cand, 'isPinned' => false])
                            @empty
                                <div class="p-3 text-center" style="color:var(--text-muted);font-size:.85rem;">Brak wyników</div>
                            @endforelse
                        </div>
                        @if($applications->hasPages())
                            <div class="rp-modal-left__pager">
                                <button type="button" class="btn btn-sm btn-outline-secondary" style="padding:2px 8px;"
                                        wire:click="previousPage" @disabled($applications->onFirstPage()) title="Poprzednia strona">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                                <span style="font-size:.7rem;color:var(--text-muted);white-space:nowrap;">
                                    {{ $applications->currentPage() }} / {{ $applications->lastPage() }}
                                </span>
                                <button type="button" class="btn btn-sm btn-outline-secondary" style="padding:2px 8px;"
                                        wire:click="nextPage" @disabled(! $applications->hasMorePages()) title="Następna strona">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- ── CENTER: details + editing ───────────── --}}
                    <div class="rp-modal-center">
                        @php
                            $sideStatuses  = [RecruitmentStatus::BylyPracownik];
                            $candidate     = $selected->candidate;
                            $isStarred     = $candidate?->rating === RecruitmentCandidateFlag::Wartosciowy;
                            $isBlacklisted = $candidate?->rating === RecruitmentCandidateFlag::CzarnaLista;
                            $statusVal     = $selected->status?->value;
                            $onRejectPath  = $selected->status === RecruitmentStatus::Odrzucony;
                            $onAcceptPath  = in_array($selected->status, [
                                RecruitmentStatus::Zaakceptowany,
                                RecruitmentStatus::Onboarding,
                                RecruitmentStatus::Zatrudniony,
                            ], true);
                            $pastContact   = $onRejectPath || $onAcceptPath
                                || $selected->status === RecruitmentStatus::WTrakcieKontaktu
                                || $selected->status === RecruitmentStatus::BylyPracownik;
                        @endphp

                        {{-- ══════════════════════════════════════════
                             LEAD — zawiera kandydata + proces
                        ══════════════════════════════════════════ --}}
                        <div class="rp-doc">
                        <div class="rp-doc-section">
                            <div class="rp-card">
                                @php
                                    $linkedEmployee = $selected->employee ?? $candidate?->employee;
                                    $isFormerEmployee = $linkedEmployee?->isTerminated() ?? false;
                                    $langBits = collect([
                                        $candidate?->speaks_english ? '🇬🇧 EN' : null,
                                        $candidate?->speaks_french ? '🇫🇷 FR' : null,
                                        $candidate?->speaks_german ? '🇩🇪 DE' : null,
                                    ])->filter()->values();
                                @endphp
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <div class="rp-field-label mb-0">
                                        <i class="bi bi-person me-1"></i>Kandydat
                                    </div>
                                    @if($linkedEmployee)
                                        <a href="{{ route('employees.show', $linkedEmployee) }}"
                                           class="badge {{ $isFormerEmployee ? 'badge-warning' : 'badge-success' }} text-decoration-none"
                                           style="font-size:.68rem;padding:4px 9px;"
                                           title="{{ $isFormerEmployee
                                               ? 'Zwolniony'.($linkedEmployee->terminated_at ? ' '.$linkedEmployee->terminated_at->format('d.m.Y') : '')
                                               : 'Zatrudniony pracownik' }}">
                                            <i class="bi bi-person-{{ $isFormerEmployee ? 'x' : 'check' }} me-1"></i>{{ $isFormerEmployee ? 'Były pracownik' : 'Pracownik' }}
                                            <i class="bi bi-box-arrow-up-right ms-1" style="font-size:.55rem;opacity:.75;"></i>
                                        </a>
                                    @endif
                                </div>

                                <div class="rp-profile">
                                <div class="rp-profile__info">
                                @if($editingCandidateIdentity)
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <span style="font-size:.78rem;font-weight:600;color:var(--text-muted);"><i class="bi bi-pencil me-1"></i>Dane kontaktowe</span>
                                        <button type="button" wire:click="toggleCandidateIdentityEdit" class="btn btn-sm btn-outline-secondary" style="padding:2px 8px;font-size:.72rem;">Anuluj</button>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="rp-field-label">Imię</label>
                                            <input type="text" wire:model="editFirstName" class="form-control form-control-sm @error('editFirstName') is-invalid @enderror">
                                            @error('editFirstName') <div class="invalid-feedback" style="font-size:.72rem;">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-6">
                                            <label class="rp-field-label">Nazwisko</label>
                                            <input type="text" wire:model="editLastName" class="form-control form-control-sm @error('editLastName') is-invalid @enderror">
                                            @error('editLastName') <div class="invalid-feedback" style="font-size:.72rem;">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="rp-field-label">Telefon</label>
                                            <input type="tel" wire:model="editPhone" class="form-control form-control-sm @error('editPhone') is-invalid @enderror" placeholder="+48 600 000 000">
                                            @error('editPhone') <div class="invalid-feedback" style="font-size:.72rem;">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-6">
                                            <label class="rp-field-label">E-mail</label>
                                            <input type="email" wire:model="editEmail" class="form-control form-control-sm @error('editEmail') is-invalid @enderror" placeholder="jan@example.com">
                                            @error('editEmail') <div class="invalid-feedback" style="font-size:.72rem;">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="rp-field-label">Lokalizacja</label>
                                        <input type="text" wire:model="editCity" class="form-control form-control-sm @error('editCity') is-invalid @enderror" style="max-width:280px;" placeholder="Miasto zamieszkania…">
                                        @error('editCity') <div class="invalid-feedback" style="font-size:.72rem;">{{ $message }}</div> @enderror
                                    </div>

                                    {{-- Skillset — edytowany tu, na liście widoczny tylko jako małe ikonki --}}
                                    <div class="mt-3 pt-3" style="border-top:1px solid var(--glass-border);">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span style="font-size:.78rem;font-weight:600;color:var(--text-muted);"><i class="bi bi-briefcase me-1"></i>Skillset</span>
                                            @if($skillsetSaved)
                                                <span wire:key="skillset-saved-{{ now()->timestamp }}" x-data x-init="setTimeout(() => $wire.set('skillsetSaved', false), 2000)" class="badge badge-success" style="font-size:.65rem;">
                                                    <i class="bi bi-check2 me-1"></i>Zapisano
                                                </span>
                                            @endif
                                        </div>

                                        <div class="mb-3">
                                            @include('livewire.partials.rp-role-picker', [
                                                'roles' => $roles,
                                                'selected' => $editRoles,
                                                'wireModel' => 'editRoles',
                                                'live' => true,
                                                'keyPrefix' => 'er',
                                                'missing' => empty($editRoles),
                                            ])
                                        </div>

                                        <div class="mb-3">
                                            <div class="rp-field-label">Doświadczenie na stoczni</div>
                                            <div class="rp-exp-picker">
                                                @foreach(\App\Enums\RecruitmentShipyardExperience::cases() as $exp)
                                                    <button type="button"
                                                            wire:click="$set('editShipyardExperience', '{{ $editShipyardExperience === $exp->value ? '' : $exp->value }}')"
                                                            class="rp-exp-btn {{ $editShipyardExperience === $exp->value ? 'rp-exp-active' : '' }}">
                                                        {{ $exp->label() }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="d-flex flex-wrap align-items-start gap-3">
                                            <div>
                                                <div class="rp-field-label">Stawka oczekiwana</div>
                                                <div class="input-group input-group-sm" style="width:148px;">
                                                    <input type="number" step="0.01" min="0" wire:model.live.debounce.300ms="editRate" class="form-control" placeholder="0.00">
                                                    <span class="input-group-text" style="background:var(--bg-input);border-color:var(--glass-border);color:var(--text-muted);">€/h</span>
                                                </div>
                                                @error('editRate') <div class="small mt-1" style="color:var(--danger);">{{ $message }}</div> @enderror
                                            </div>

                                            <div>
                                                <div class="rp-field-label">Dostępny od</div>
                                                <div class="input-group input-group-sm" style="width:170px;">
                                                    <input type="date" wire:model.live.debounce.300ms="editAvailableFrom" class="form-control">
                                                </div>
                                                @error('editAvailableFrom') <div class="small mt-1" style="color:var(--danger);">{{ $message }}</div> @enderror
                                            </div>

                                            <div>
                                                <div class="rp-field-label">Inne</div>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <button type="button" wire:click="$toggle('editSpeaksEnglish')" class="btn btn-sm {{ $editSpeaksEnglish ? 'btn-primary' : 'btn-outline-secondary' }}" style="padding:4px 10px;height:31px;" title="Angielski">🇬🇧 EN</button>
                                                    <button type="button" wire:click="$toggle('editSpeaksFrench')" class="btn btn-sm {{ $editSpeaksFrench ? 'btn-primary' : 'btn-outline-secondary' }}" style="padding:4px 10px;height:31px;" title="Francuski">🇫🇷 FR</button>
                                                    <button type="button" wire:click="$toggle('editSpeaksGerman')" class="btn btn-sm {{ $editSpeaksGerman ? 'btn-primary' : 'btn-outline-secondary' }}" style="padding:4px 10px;height:31px;" title="Niemiecki">🇩🇪 DE</button>
                                                    <button type="button" wire:click="$toggle('editDrivingLicense')" class="btn btn-sm {{ $editDrivingLicense ? 'btn-primary' : 'btn-outline-secondary' }}" style="padding:4px 10px;height:31px;" title="Prawo jazdy kat. B"><i class="bi bi-car-front me-1"></i>Kat.&nbsp;B</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2 mt-3">
                                        <button type="button" wire:click="saveCandidateIdentity" class="btn btn-primary btn-sm">
                                            <i class="bi bi-check2 me-1"></i>Zapisz dane kandydata
                                        </button>
                                        <button type="button" wire:click="toggleCandidateIdentityEdit" class="btn btn-outline-secondary btn-sm">Anuluj</button>
                                    </div>
                                @else
                                    <div class="rp-profile__id">
                                        <x-ui.avatar :image-url="$selected->photo_url" :initials="mb_strtoupper(mb_substr($selected->first_name,0,1).mb_substr($selected->last_name,0,1))" size="56px" shape="rounded" :border="false" />
                                        <div class="min-width-0">
                                            <h5 class="rp-profile__name">{{ $selected->full_name }}</h5>
                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                @if($isStarred)
                                                    <span class="badge badge-warning" style="font-size:.68rem;"><i class="bi bi-star-fill me-1"></i>Wartościowy</span>
                                                @endif
                                                @if($isBlacklisted)
                                                    <span class="badge badge-danger" style="font-size:.68rem;"><i class="bi bi-flag-fill me-1"></i>Czarna lista</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @if($isBlacklisted && $candidate->rating_note)
                                        <div class="mt-2" style="font-size:.82rem;color:var(--danger);"><i class="bi bi-exclamation-triangle me-1"></i>{{ $candidate->rating_note }}</div>
                                    @endif
                                    <div class="rp-profile__contact">
                                        @if($selected->phone)
                                            <a href="tel:{{ $selected->phone }}" class="rp-profile__contact-row">
                                                <span class="rp-profile__contact-icon"><i class="bi bi-telephone"></i></span>
                                                {{ $selected->phone }}
                                            </a>
                                        @endif
                                        @if($selected->email)
                                            <span class="rp-profile__contact-row">
                                                <span class="rp-profile__contact-icon"><i class="bi bi-envelope"></i></span>
                                                {{ $selected->email }}
                                            </span>
                                        @endif
                                        @if($selected->city)
                                            <span class="rp-profile__contact-row">
                                                <span class="rp-profile__contact-icon"><i class="bi bi-geo-alt"></i></span>
                                                {{ $selected->city }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($candidate?->roles?->isNotEmpty())
                                        <div class="rp-skill-chips">
                                            @foreach($candidate->roles as $role)
                                                <span class="rp-skill-chip rp-skill-chip--role">{{ $role->name }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($candidate && ($candidate->shipyard_experience || $candidate->available_from || $candidate->has_driving_license_b || $candidate->expected_rate_eur !== null || $langBits->isNotEmpty()))
                                        <div class="rp-attr-grid">
                                            @if($candidate->shipyard_experience)
                                                <div class="rp-attr">
                                                    <span class="rp-attr__icon"><i class="bi bi-tools"></i></span>
                                                    <div>
                                                        <div class="rp-attr__label">Doświadczenie</div>
                                                        <div class="rp-attr__value">{{ $candidate->shipyard_experience->label() }}</div>
                                                    </div>
                                                </div>
                                            @endif
                                            @if($candidate->available_from)
                                                <div class="rp-attr">
                                                    <span class="rp-attr__icon"><i class="bi bi-calendar-check"></i></span>
                                                    <div>
                                                        <div class="rp-attr__label">Dostępność</div>
                                                        <div class="rp-attr__value rp-attr__value--ok">Od {{ $candidate->available_from->format('d.m.Y') }}</div>
                                                    </div>
                                                </div>
                                            @endif
                                            @if($candidate->has_driving_license_b)
                                                <div class="rp-attr">
                                                    <span class="rp-attr__icon"><i class="bi bi-car-front"></i></span>
                                                    <div>
                                                        <div class="rp-attr__label">Kategoria</div>
                                                        <div class="rp-attr__value">Kat. B</div>
                                                    </div>
                                                </div>
                                            @endif
                                            @if($candidate->expected_rate_eur !== null)
                                                <div class="rp-attr">
                                                    <span class="rp-attr__icon"><i class="bi bi-cash-coin"></i></span>
                                                    <div>
                                                        <div class="rp-attr__label">Stawka</div>
                                                        <div class="rp-attr__value font-mono">{{ number_format((float) $candidate->expected_rate_eur, 2) }} €/h</div>
                                                    </div>
                                                </div>
                                            @endif
                                            @if($langBits->isNotEmpty())
                                                <div class="rp-attr">
                                                    <span class="rp-attr__icon"><i class="bi bi-translate"></i></span>
                                                    <div>
                                                        <div class="rp-attr__label">Języki</div>
                                                        <div class="rp-attr__value">{{ $langBits->implode('  ') }}</div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @elseif(! $candidate?->roles?->isNotEmpty())
                                        <button type="button" wire:click="toggleCandidateIdentityEdit" class="rp-skill-chip rp-skill-chip--empty mt-2">
                                            <i class="bi bi-plus-lg"></i>Uzupełnij role, staż, stawkę…
                                        </button>
                                    @endif
                                @endif
                                </div>{{-- /info --}}

                                <div class="rp-profile__aside">
                                    <div class="rp-profile__actions">
                                        <button type="button" wire:click="toggleCandidateIdentityEdit" class="rp-action {{ $editingCandidateIdentity ? 'is-active' : '' }}">
                                            <i class="bi bi-pencil"></i>
                                            <span>{{ $editingCandidateIdentity ? 'Anuluj' : 'Edytuj' }}</span>
                                        </button>
                                        <button type="button" wire:click="setCandidateFlag({{ $selected->id }}, 'wartosciowy')" class="rp-action rp-icon-btn {{ $isStarred ? 'is-starred' : '' }}">
                                            <i class="bi bi-star{{ $isStarred ? '-fill' : '' }}"></i>
                                            <span>Ulubiony</span>
                                        </button>
                                        <button type="button" wire:click="setCandidateFlag({{ $selected->id }}, 'czarna_lista')" class="rp-action rp-icon-btn {{ $isBlacklisted ? 'is-flagged' : '' }}">
                                            <i class="bi bi-flag{{ $isBlacklisted ? '-fill' : '' }}"></i>
                                            <span>Oznacz</span>
                                        </button>
                                        <button type="button" wire:click="openContactModal" class="rp-action rp-action--call">
                                            <i class="bi bi-telephone{{ $selected->phone ? '-fill' : '' }}"></i>
                                            <span>Zadzwoń</span>
                                        </button>
                                    </div>

                                    @if($showBlacklistPrompt)
                                        <div class="mt-3">
                                            <div class="rp-section-title" style="color:var(--danger);">Powód wpisania na czarną listę</div>
                                            <textarea wire:model="blacklistNote" class="form-control mb-2" rows="2" style="font-size:.82rem;" placeholder="np. sfałszowane referencje…"></textarea>
                                            @error('blacklistNote') <div class="small mb-2" style="color:var(--danger);">{{ $message }}</div> @enderror
                                            <div class="d-flex gap-2">
                                                <button type="button" wire:click="confirmBlacklist" class="btn btn-danger btn-sm"><i class="bi bi-flag-fill me-1"></i>Potwierdź</button>
                                                <button type="button" wire:click="cancelBlacklist" class="btn btn-outline-secondary btn-sm">Anuluj</button>
                                            </div>
                                        </div>
                                    @endif

                                    @if($candidate)
                                        <div class="rp-profile__comments">
                                            <x-comments
                                                embedded
                                                :commentable="$candidate"
                                                label="Komentarze"
                                                input-label="Dodaj komentarz"
                                                button-text="Dodaj komentarz"
                                            />
                                        </div>
                                    @endif

                                    <div class="rp-note__foot mt-auto">
                                        <i class="bi bi-calendar3"></i>
                                        Profil utworzony: {{ ($candidate->created_at ?? $selected->created_at)?->format('d.m.Y') }}
                                    </div>
                                </div>{{-- /aside --}}
                                </div>{{-- /rp-profile --}}

                                <div class="rp-lead-line mt-3 pt-3" style="border-top:1px solid var(--glass-border);">
                                    <i class="bi bi-send"></i>Lead — zgłoszenie
                                    @if($selected->lead)
                                        z {{ $selected->lead->created_at->format('d.m.Y') }}
                                        <span class="rp-plain-tag" style="cursor:default;">#{{ $selected->lead->id }}</span>
                                    @endif
                                    @if($selected->referral_source_label)
                                        · {{ $selected->referral_source_label }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        @php
                            $allAttempts = $candidate
                                ? $candidate->allContactAttempts->sortByDesc('created_at')
                                : collect();
                        @endphp
                        <div class="rp-doc-section">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                <div class="rp-field-label mb-0">
                                    <i class="bi bi-telephone me-1"></i>Historia kontaktu
                                    <span style="font-size:.74rem;font-weight:400;text-transform:none;letter-spacing:0;color:var(--text-muted);"> (cały kandydat)</span>
                                    <span class="rp-plain-tag" style="cursor:default;">{{ $allAttempts->count() }}</span>
                                </div>
                                <button type="button" wire:click="openContactModal" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-plus me-1"></i>Zarejestruj
                                </button>
                            </div>
                            @if($allAttempts->isNotEmpty())
                                <div class="rp-timeline">
                                @foreach($allAttempts as $attempt)
                                    @php
                                        $variant = $attempt->outcome->variant();
                                        $canManageAttempt = $attempt->user_id === auth()->id();
                                    @endphp
                                    <div class="rp-timeline-item" wire:key="att-{{ $attempt->id }}">
                                        <span class="rp-status-dot rp-outcome is-{{ $variant }}"></span>
                                        <div class="flex-grow-1 min-width-0">
                                            <div class="d-flex justify-content-between align-items-start gap-2">
                                                <span style="font-size:.9rem;font-weight:650;">{{ $attempt->user?->name ?? '—' }}</span>
                                                <div class="d-flex align-items-start gap-1 flex-shrink-0">
                                                    <small style="color:var(--text-muted);font-size:.75rem;white-space:nowrap;text-align:right;line-height:1.3;"
                                                           title="{{ $attempt->created_at->format('d.m.Y H:i') }}">
                                                        <div>{{ $attempt->created_at->diffForHumans() }}</div>
                                                        <div style="font-size:.68rem;opacity:.8;">{{ $attempt->created_at->format('d.m.Y H:i') }}</div>
                                                    </small>
                                                    @if($canManageAttempt)
                                                        <div class="rp-comment__actions">
                                                            <button type="button" class="comments-icon-btn" title="Edytuj komentarz" wire:click="startEditAttempt({{ $attempt->id }})"><i class="bi bi-pencil"></i></button>
                                                            <button type="button" class="comments-icon-btn is-danger" title="Usuń próbę kontaktu" wire:click="deleteAttempt({{ $attempt->id }})" onclick="return confirm('Usunąć tę próbę kontaktu?')"><i class="bi bi-trash"></i></button>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 flex-wrap mt-1">
                                                <span class="rp-outcome is-{{ $variant }}">{{ $attempt->outcome->label() }}</span>
                                                @if($attempt->recruitment_process_id === $selected->id)
                                                    <span class="rp-plain-tag" style="cursor:default;">bieżący proces</span>
                                                @elseif($attempt->recruitmentProcess)
                                                    <button type="button"
                                                            wire:click="selectProcess({{ $attempt->recruitment_process_id }})"
                                                            class="rp-plain-tag"
                                                            title="Przejdź do proc. #{{ $attempt->recruitment_process_id }}">
                                                        proc.&nbsp;#{{ $attempt->recruitment_process_id }}
                                                        @if($attempt->recruitmentProcess->status) · {{ $attempt->recruitmentProcess->status->label() }} @endif
                                                    </button>
                                                @endif
                                            </div>
                                            @if($editingAttemptId === $attempt->id)
                                                <div class="mt-2">
                                                    <textarea wire:model="editAttemptComment" class="form-control form-control-sm mb-2" rows="2" placeholder="Komentarz…"></textarea>
                                                    <div class="d-flex gap-2">
                                                        <button type="button" wire:click="saveEditAttempt" class="btn btn-sm btn-primary">Zapisz</button>
                                                        <button type="button" wire:click="cancelEditAttempt" class="btn btn-sm btn-outline-secondary">Anuluj</button>
                                                    </div>
                                                </div>
                                            @elseif($attempt->comment)
                                                <div style="font-size:.84rem;color:var(--text-muted);margin-top:.35rem;line-height:1.45;">{{ $attempt->comment }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                                </div>
                            @else
                                <p style="color:var(--text-muted);font-size:.85rem;margin:0;">Brak prób kontaktu.</p>
                            @endif
                        </div>

                        <div class="rp-doc-section">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                <div class="rp-field-label mb-0">
                                    <i class="bi bi-check2-square me-1"></i>Zadania
                                    <span class="rp-plain-tag" style="cursor:default;">{{ $selected->tasks->count() }}</span>
                                </div>
                                <button type="button" wire:click="openTaskModalManual" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-plus me-1"></i>Dodaj
                                </button>
                            </div>
                            @forelse($selected->tasks as $task)
                                <div class="d-flex justify-content-between align-items-start gap-2" wire:key="task-{{ $task->id }}" style="font-size:.88rem;padding:.65rem 0;border-top:1px solid rgba(255,255,255,.05);">
                                    <div class="min-width-0">
                                        <div style="{{ $task->status === \App\Enums\TaskStatus::COMPLETED ? 'text-decoration:line-through;color:var(--text-muted);' : '' }}">{{ $task->name }}</div>
                                        <div style="color:var(--text-muted);font-size:.78rem;margin-top:.15rem;">
                                            @if($task->due_date)<i class="bi bi-calendar-event me-1"></i>{{ $task->due_date->format('d.m.Y') }}@endif
                                            @if($task->assignedTo) · {{ $task->assignedTo->name }} @endif
                                        </div>
                                    </div>
                                    <button type="button" wire:click="toggleTaskDone({{ $task->id }})" class="btn btn-sm btn-outline-secondary flex-shrink-0" style="padding:2px 9px;font-size:.75rem;">
                                        <i class="bi bi-check2{{ $task->status === \App\Enums\TaskStatus::COMPLETED ? '-square-fill' : '-square' }}"></i>
                                    </button>
                                </div>
                            @empty
                                <p style="color:var(--text-muted);font-size:.85rem;margin:0;">Brak zaplanowanych zadań.</p>
                            @endforelse
                        </div>

                        {{-- ══════════════════════════════════════════
                             PROCES — sekcja statusu i historii
                        ══════════════════════════════════════════ --}}
                            <div class="rp-doc-section">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                    <div class="rp-field-label mb-0">
                                        <i class="bi bi-kanban me-1"></i>Proces rekrutacyjny
                                        <span class="rp-plain-tag" style="cursor:default;">#{{ $selected->id }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span style="font-size:.78rem;color:var(--text-muted);white-space:nowrap;"><i class="bi bi-person-workspace me-1"></i>Prowadzi</span>
                                        <select wire:model.live="editAssignedRecruiterId" class="form-select form-select-sm" style="min-width:160px;max-width:220px;">
                                            <option value="">— Nieprzypisany —</option>
                                            @foreach($recruiters as $recruiter)
                                                <option value="{{ $recruiter->id }}">{{ $recruiter->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="rp-field-label">Status</div>

                                    @php
                                        $acceptDoneOnly = in_array($selected->status, [
                                            RecruitmentStatus::Onboarding,
                                            RecruitmentStatus::Zatrudniony,
                                        ], true);
                                        $boxCls = fn ($isDone, $isActive, $danger = false) => $isDone
                                            ? 'rp-done'
                                            : ($isActive ? ($danger ? 'rp-active rp-active--danger' : 'rp-active') : '');
                                    @endphp
                                    <div class="rp-flow2" wire:key="rp-flow2-{{ $selected->id }}-{{ $statusVal }}">
                                        <button type="button" wire:click="updateStatus({{ $selected->id }}, 'nowy')"
                                                class="rp-flow2-box {{ $boxCls($pastContact, $selected->status === RecruitmentStatus::Nowy) }}">Nowy</button>

                                        <div class="rp-flow2-connector {{ $pastContact ? 'rp-done' : '' }}"></div>

                                        <button type="button" wire:click="updateStatus({{ $selected->id }}, 'w_trakcie_kontaktu')"
                                                class="rp-flow2-box {{ $boxCls($onAcceptPath || $onRejectPath, $selected->status === RecruitmentStatus::WTrakcieKontaktu) }}">W trakcie kontaktu</button>

                                        <div class="rp-flow2-connector {{ $onAcceptPath ? 'rp-done' : ($onRejectPath ? 'rp-done--danger' : '') }}"></div>

                                        <div class="rp-flow2-fork">
                                            <button type="button" wire:click="updateStatus({{ $selected->id }}, 'zaakceptowany')"
                                                    class="rp-flow2-box rp-flow2-box--sm {{ $boxCls($acceptDoneOnly, $selected->status === RecruitmentStatus::Zaakceptowany) }} {{ $onRejectPath ? 'rp-flow2-box--dim' : '' }}">{{ RecruitmentStatus::Zaakceptowany->label() }}</button>
                                            <button type="button" wire:click="updateStatus({{ $selected->id }}, 'odrzucony')"
                                                    class="rp-flow2-box rp-flow2-box--sm {{ $boxCls(false, $onRejectPath, true) }} {{ $onAcceptPath ? 'rp-flow2-box--dim' : '' }}">Odrzucony</button>
                                        </div>

                                        <div class="rp-flow2-connector {{ $onAcceptPath ? 'rp-done' : '' }} {{ $onRejectPath ? 'rp-flow2-box--dim' : '' }}"></div>

                                        <button type="button" wire:click="updateStatus({{ $selected->id }}, 'onboarding')"
                                                class="rp-flow2-box {{ $boxCls($selected->status === RecruitmentStatus::Zatrudniony, $selected->status === RecruitmentStatus::Onboarding) }} {{ $onRejectPath ? 'rp-flow2-box--dim' : '' }}">Onboarding</button>

                                        <div class="rp-flow2-connector {{ $selected->status === RecruitmentStatus::Zatrudniony ? 'rp-done' : '' }} {{ $onRejectPath ? 'rp-flow2-box--dim' : '' }}"></div>

                                        <button type="button" wire:click="updateStatus({{ $selected->id }}, 'zatrudniony')"
                                                class="rp-flow2-box {{ $boxCls(false, $selected->status === RecruitmentStatus::Zatrudniony) }} {{ $onRejectPath ? 'rp-flow2-box--dim' : '' }}">Zatrudniony</button>
                                    </div>

                                    <div class="rp-pipeline-side">
                                        @foreach($sideStatuses as $side)
                                            <button type="button" wire:click="updateStatus({{ $selected->id }}, '{{ $side->value }}')"
                                                    class="btn btn-sm {{ $selected->status === $side ? 'btn-'.$side->variant() : 'btn-outline-secondary' }}">
                                                {{ $side->label() }}
                                            </button>
                                        @endforeach
                                    </div>

                                    @if($selected->status === RecruitmentStatus::Odrzucony && $selected->rejection_reason)
                                        <div class="rp-rejection-callout mt-3">
                                            <i class="bi bi-x-octagon-fill"></i>
                                            <div>
                                                <div class="rp-rejection-callout__label">Powód odrzucenia</div>
                                                <div class="rp-rejection-callout__reason">{{ $selected->rejection_reason->label() }}</div>
                                                @if($selected->rejection_reason_note)
                                                    <div class="rp-rejection-callout__note">{{ $selected->rejection_reason_note }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    @php
                                        // Jeden slot procedury per status — karta widoczna tylko w tym jednym widoku,
                                        // dostosowana do tego, co aktualnie dzieje się z kandydatem na danym etapie.
                                        $statusSlots = [
                                            RecruitmentStatus::Zaakceptowany->value => ['key' => 'recruitment_process.zaakceptowany', 'label' => 'Procedura: Weryfikacja'],
                                            RecruitmentStatus::Onboarding->value    => ['key' => 'recruitment_process.onboarding', 'label' => 'Procedura: Onboarding'],
                                            RecruitmentStatus::Zatrudniony->value   => ['key' => 'recruitment_process.zatrudniony', 'label' => 'Procedura: Zatrudniony'],
                                        ];
                                        $activeSlot = $statusSlots[$selected->status->value] ?? null;
                                    @endphp
                                    @if($activeSlot)
                                        <div class="mt-3 pt-3" style="border-top:1px solid var(--glass-border);">
                                            <div class="rp-section-title">{{ $activeSlot['label'] }}</div>
                                            <livewire:procedure-slot
                                                :slot-key="$activeSlot['key']"
                                                :subject="$selected"
                                                :variables="['candidate_name' => $selected->candidate?->full_name, 'recruitment_process_id' => $selected->id]"
                                                :subject-label="($selected->candidate?->full_name ?? 'Kandydat').' #'.$selected->id"
                                                wire:key="proc-slot-{{ $activeSlot['key'] }}-{{ $selected->id }}"
                                            />
                                        </div>
                                    @endif

                                    @if($showRejectionPrompt)
                                        <div class="mt-3 pt-3" style="border-top:1px solid var(--glass-border);">
                                            <div class="rp-section-title" style="color:var(--danger);">Powód odrzucenia</div>
                                            <select wire:model="rejectionReason" class="form-select form-select-sm mb-2">
                                                <option value="">— Wybierz powód —</option>
                                                @foreach(RecruitmentRejectionReason::options() as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('rejectionReason') <div class="small mb-2" style="color:var(--danger);">{{ $message }}</div> @enderror
                                            <textarea wire:model="rejectionNote" class="form-control mb-2" rows="2" style="font-size:.82rem;" placeholder="Komentarz (opcjonalnie)…"></textarea>
                                            <div class="d-flex gap-2">
                                                <button type="button" wire:click="confirmRejection" class="btn btn-danger btn-sm"><i class="bi bi-x-circle me-1"></i>Potwierdź odrzucenie</button>
                                                <button type="button" wire:click="cancelRejection" class="btn btn-outline-secondary btn-sm">Anuluj</button>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                {{-- Historia procesu (statusy + przypisania) --}}
                                <div class="mt-4 pt-4" style="border-top:1px solid var(--glass-border);">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="rp-field-label mb-0">Historia procesu</div>
                                        <span style="font-size:.76rem;color:var(--text-muted);">proces #{{ $selected->id }}</span>
                                    </div>
                                    <div class="rp-timeline" style="max-height:170px;overflow-y:auto;">
                                        @forelse($processTimeline as $timelineItem)
                                            @php
                                                $entry = $timelineItem['entry'];
                                                $isStatus = $timelineItem['type'] === 'status';
                                            @endphp
                                            <div class="rp-timeline-item" wire:key="timeline-{{ $timelineItem['type'] }}-{{ $entry->id }}" style="font-size:.85rem;">
                                                <span class="rp-status-dot" style="color:{{ $isStatus ? 'var(--primary)' : '#a78bfa' }};box-shadow:0 0 0 3px {{ $isStatus ? 'rgba(59,130,246,.15)' : 'rgba(167,139,250,.15)' }};"></span>
                                                <div class="flex-grow-1 d-flex justify-content-between align-items-start gap-2 min-width-0">
                                                    <div class="min-width-0">
                                                        @if($isStatus)
                                                            @if($entry->from_status)
                                                                <span style="color:var(--text-muted);">{{ $entry->from_status->label() }}</span>
                                                                <i class="bi bi-arrow-right mx-1" style="color:var(--text-muted);font-size:.72rem;"></i>
                                                            @else
                                                                <span style="color:var(--text-muted);">Utworzono</span>
                                                                <i class="bi bi-arrow-right mx-1" style="color:var(--text-muted);font-size:.72rem;"></i>
                                                            @endif
                                                            <strong>{{ $entry->to_status->label() }}</strong>
                                                        @else
                                                            <i class="bi bi-person-badge me-1" style="color:#a78bfa;font-size:.78rem;"></i>
                                                            <span style="color:var(--text-muted);">{{ $entry->fromRecruiter?->name ?? 'Nieprzypisany' }}</span>
                                                            <i class="bi bi-arrow-right mx-1" style="color:var(--text-muted);font-size:.72rem;"></i>
                                                            <strong>{{ $entry->toRecruiter?->name ?? 'Nieprzypisany' }}</strong>
                                                        @endif
                                                        <div style="color:var(--text-muted);font-size:.72rem;">{{ $entry->changedBy?->name ?? 'System' }}</div>
                                                    </div>
                                                    <small style="color:var(--text-muted);white-space:nowrap;text-align:right;line-height:1.25;flex-shrink:0;"
                                                           title="{{ $entry->created_at->format('d.m.Y H:i') }}">
                                                        <div>{{ $entry->created_at->diffForHumans() }}</div>
                                                        <div style="font-size:.65rem;opacity:.8;">{{ $entry->created_at->format('d.m.Y H:i') }}</div>
                                                    </small>
                                                </div>
                                            </div>
                                        @empty
                                            <p style="color:var(--text-muted);font-size:.82rem;margin:0;">Brak historii — wpisy pojawią się po zmianie statusu lub przypisania.</p>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <x-comments
                                        :commentable="$selected"
                                        label="Komentarze procesu"
                                    />
                                </div>

                                @if(! $selected->employee_id && $selected->status === RecruitmentStatus::Onboarding)
                                    <div class="mt-3 p-3" style="border:1px solid rgba(34,197,94,.3);border-radius:12px;background:rgba(34,197,94,.05);">
                                        <div class="rp-field-label mb-1" style="color:var(--success);"><i class="bi bi-person-plus me-1"></i>Zatrudnij kandydata</div>
                                        <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:.75rem;">
                                            Tworzy profil pracownika i ustawia status na „Zatrudniony".
                                        </p>
                                        <div class="mb-3">
                                            @include('livewire.partials.rp-role-picker', [
                                                'roles' => $roles,
                                                'selected' => $hireRoles,
                                                'wireModel' => 'hireRoles',
                                                'live' => false,
                                                'keyPrefix' => 'hr',
                                                'missing' => empty($hireRoles),
                                            ])
                                        </div>
                                        @error('hireRoles') <div class="small mb-2" style="color:var(--danger);">{{ $message }}</div> @enderror
                                        <button type="button" class="btn btn-success btn-sm"
                                                wire:click="convertToEmployee"
                                                onclick="return confirm('Zatrudnić kandydata?')">
                                            <i class="bi bi-person-plus me-1"></i>Zatrudnij
                                        </button>
                                    </div>
                                @endif
                            </div>{{-- /sekcja procesu --}}

                        {{-- Inne procesy tego kandydata (bez bieżącego) --}}
                        @php
                            $siblingProcesses = $candidate
                                ? $candidate->processes->where('id', '!=', $selected->id)->sortByDesc('created_at')
                                : collect();
                        @endphp
                        @if($siblingProcesses->isNotEmpty())
                            <div class="rp-doc-section">
                                <div class="rp-kicker">
                                    <i class="bi bi-diagram-2 me-1"></i>Inne procesy tego kandydata
                                    <span class="rp-plain-tag" style="cursor:default;">{{ $siblingProcesses->count() }}</span>
                                </div>
                                <div class="d-flex flex-column gap-2">
                                @foreach($siblingProcesses as $proc)
                                    @php $procStatus = $proc->status; @endphp
                                    <div class="d-flex align-items-center gap-2 p-3"
                                         style="border-radius:10px;background:rgba(255,255,255,.025);"
                                         wire:key="sibling-{{ $proc->id }}">
                                        <div class="flex-grow-1 min-width-0">
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                @if($procStatus)
                                                    <span class="badge badge-{{ $procStatus->variant() }}" style="font-size:.68rem;">{{ $procStatus->label() }}</span>
                                                @endif
                                                <span style="font-size:.8rem;color:var(--text-muted);"
                                                      title="{{ ($proc->lead?->created_at ?? $proc->created_at)->format('d.m.Y H:i') }}">
                                                    {{ ($proc->lead?->created_at ?? $proc->created_at)->diffForHumans() }}
                                                    · {{ ($proc->lead?->created_at ?? $proc->created_at)->format('d.m.Y') }}
                                                </span>
                                                @if($proc->assignedRecruiter)
                                                    <span style="font-size:.78rem;color:var(--text-muted);">· {{ $proc->assignedRecruiter->name }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1 flex-shrink-0">
                                            <button type="button"
                                                    wire:click="selectProcess({{ $proc->id }})"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    title="Przejdź do tego procesu">
                                                <i class="bi bi-arrow-right"></i>
                                            </button>
                                            @if($procStatus !== \App\Enums\RecruitmentStatus::Odrzucony && $procStatus !== \App\Enums\RecruitmentStatus::Zatrudniony)
                                                <button type="button"
                                                        wire:click="updateStatus({{ $proc->id }}, 'odrzucony')"
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Odrzuć ten proces">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                                </div>
                            </div>
                        @endif
                        </div>{{-- /rp-doc --}}

                    </div>{{-- /center --}}

                </div>{{-- /rp-modal-body --}}
            </div>{{-- /rp-modal --}}
    @endif

    @if($showContactModal)
        <div class="rp-modal-backdrop" style="z-index:1060;" wire:click="closeContactModal"></div>
        <div class="rp-modal-wrap" style="z-index:1061;align-items:center;justify-content:center;" role="dialog">
            <div class="rp-modal" style="max-width:440px;width:100%;height:auto;">
                <div class="rp-modal-topbar">
                    <strong style="font-size:.9rem;"><i class="bi bi-telephone me-1"></i>Zarejestruj próbę kontaktu</strong>
                    <button type="button" wire:click="closeContactModal" class="btn btn-sm btn-outline-secondary ms-auto"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="p-3">
                    @if($selected?->phone)
                        <a href="tel:{{ $selected->phone }}" class="btn btn-success btn-sm w-100 mb-3">
                            <i class="bi bi-telephone-fill me-1"></i>{{ $selected->phone }}
                        </a>
                    @endif
                    <div class="rp-field-label">Efekt rozmowy</div>
                    <div class="rp-contact-grid mb-3">
                        @foreach(RecruitmentContactOutcome::cases() as $outcome)
                            @php
                                $oCls = match($outcome->variant()) { 'success'=>'btn-outline-success','danger'=>'btn-outline-danger','warning'=>'btn-outline-warning',default=>'btn-outline-secondary' };
                            @endphp
                            <button type="button" wire:click="logContactAttempt('{{ $outcome->value }}')" class="btn btn-sm {{ $oCls }}">
                                {{ $outcome->label() }}
                            </button>
                        @endforeach
                    </div>
                    <label class="rp-field-label" for="rp-contact-note">Notatka</label>
                    <textarea id="rp-contact-note" wire:model="newComment" class="form-control form-control-sm mb-2" rows="3" placeholder="Opcjonalnie, przed wyborem efektu…"></textarea>
                    @error('newOutcome') <div class="small" style="color:var(--danger);">{{ $message }}</div> @enderror
                    @error('newComment') <div class="small" style="color:var(--danger);">{{ $message }}</div> @enderror
                    <p class="mb-0 mt-2" style="font-size:.75rem;color:var(--text-muted);">Wybierz efekt — zapisze próbę i zamknie okno.</p>
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════
         FOLLOW-UP TASK MODAL (opened after "Prosi o oddzwonienie")
    ════════════════════════════════════════════════════════ --}}
    @if($showTaskModal)
        <div class="rp-modal-backdrop" style="z-index:1060;" wire:click="closeTaskModal"></div>
        <div class="rp-modal-wrap" style="z-index:1061;align-items:center;justify-content:center;" role="dialog">
            <div class="rp-modal" style="max-width:420px;width:100%;height:auto;">
                <div class="rp-modal-topbar">
                    <strong style="font-size:.9rem;"><i class="bi bi-journal-plus me-1"></i>Dodaj zadanie</strong>
                    <button type="button" wire:click="closeTaskModal" class="btn btn-sm btn-outline-secondary ms-auto"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="p-3">
                    <div class="mb-2">
                        <label class="form-label" style="font-size:.75rem;">Tytuł zadania <span class="text-danger">*</span></label>
                        <input type="text" wire:model="taskTitle" class="form-control form-control-sm">
                        @error('taskTitle') <div class="small mt-1" style="color:var(--danger);">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label" style="font-size:.75rem;">Opis (opcjonalnie)</label>
                        <textarea wire:model="taskDescription" class="form-control form-control-sm" rows="2" style="resize:vertical;" placeholder="Dodatkowe informacje…"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" style="font-size:.75rem;">Termin <span class="text-danger">*</span></label>
                        <input type="date" wire:model="taskDueDate" class="form-control form-control-sm">
                        @error('taskDueDate') <div class="small mt-1" style="color:var(--danger);">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.75rem;">Przypisz do</label>
                        <select wire:model="taskAssignedTo" class="form-select form-select-sm">
                            @foreach($recruiters as $recruiter)
                                <option value="{{ $recruiter->id }}">{{ $recruiter->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" wire:click="saveFollowUpTask" class="btn btn-primary btn-sm"><i class="bi bi-check2 me-1"></i>Dodaj zadanie</button>
                        <button type="button" wire:click="closeTaskModal" class="btn btn-outline-secondary btn-sm">Anuluj</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
