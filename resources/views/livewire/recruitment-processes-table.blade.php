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

    {{-- ════════════════════════════════════════════════════════
         PIPELINE MODAL
    ════════════════════════════════════════════════════════ --}}
    @if($selectedId && $selected)
        <div class="rp-modal-backdrop" wire:click="closeDrawer"></div>
        <div class="rp-modal-wrap" role="dialog">
            <div class="rp-modal">

                {{-- Top bar --}}
                <div class="rp-modal-topbar">
                    <div class="rp-topbar-main">
                        <div class="rp-topbar-row">
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
                                        class="btn btn-sm rp-topbar-btn {{ $sortField===$field ? 'btn-primary' : 'btn-outline-secondary' }}">
                                    <i class="bi {{ $icon }} me-1"></i>{{ $label }}
                                    @if($sortField===$field)<i class="bi bi-arrow-{{ $sortDirection==='asc'?'up':'down' }} ms-1"></i>@endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <button type="button" wire:click="closeDrawer" class="rp-modal-close" title="Zamknij" aria-label="Zamknij">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                {{-- Body --}}
                <div class="rp-modal-body">

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
                        <div style="margin-bottom:1.5rem;">
                            <div style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--text-muted);margin-bottom:.35rem;padding-left:.25rem;">
                                <i class="bi bi-send me-1"></i>Lead — zgłoszenie
                                @if($selected->lead)
                                    <span style="font-weight:400;"> z {{ $selected->lead->created_at->format('d.m.Y') }}</span>
                                    <span class="badge badge-secondary ms-1" style="font-size:.6rem;font-weight:600;">#{{ $selected->lead->id }}</span>
                                @endif
                                @if($selected->referral_source_label)
                                    <span style="font-weight:400;"> · {{ $selected->referral_source_label }}</span>
                                @endif
                            </div>
                            <div style="font-size:.72rem;color:var(--text-muted);margin-bottom:.6rem;padding-left:.25rem;">
                                Jedno zgłoszenie = lead + proces. Dane osoby edytujesz w sekcji Kandydat (wspólne dla wszystkich procesów).
                            </div>

                            <div style="border:1px solid var(--glass-border);border-radius:12px;padding:1rem;background:rgba(255,255,255,.02);">

                                {{-- Kandydat --}}
                                @php
                                    $linkedEmployee = $selected->employee ?? $candidate?->employee;
                                    $isFormerEmployee = $linkedEmployee?->isTerminated() ?? false;
                                @endphp
                                <div class="d-flex align-items-center justify-content-between gap-2" style="margin-bottom:.25rem;">
                                    <div style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">
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
                                <div style="font-size:.72rem;color:var(--text-muted);margin-bottom:.6rem;">
                                    Profil osoby — kontakt i skillset są wspólne dla wszystkich procesów tego kandydata.
                                </div>

                                @if($editingCandidateIdentity)
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <span style="font-size:.78rem;font-weight:600;color:var(--text-muted);"><i class="bi bi-pencil me-1"></i>Dane kontaktowe</span>
                                        <button type="button" wire:click="toggleCandidateIdentityEdit" class="btn btn-sm btn-outline-secondary" style="padding:2px 8px;font-size:.72rem;">Anuluj</button>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">Imię</label>
                                            <input type="text" wire:model="editFirstName" class="form-control form-control-sm @error('editFirstName') is-invalid @enderror">
                                            @error('editFirstName') <div class="invalid-feedback" style="font-size:.72rem;">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-6">
                                            <label style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">Nazwisko</label>
                                            <input type="text" wire:model="editLastName" class="form-control form-control-sm @error('editLastName') is-invalid @enderror">
                                            @error('editLastName') <div class="invalid-feedback" style="font-size:.72rem;">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">Telefon</label>
                                            <input type="tel" wire:model="editPhone" class="form-control form-control-sm @error('editPhone') is-invalid @enderror" placeholder="+48 600 000 000">
                                            @error('editPhone') <div class="invalid-feedback" style="font-size:.72rem;">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-6">
                                            <label style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">E-mail</label>
                                            <input type="email" wire:model="editEmail" class="form-control form-control-sm @error('editEmail') is-invalid @enderror" placeholder="jan@example.com">
                                            @error('editEmail') <div class="invalid-feedback" style="font-size:.72rem;">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">Lokalizacja</label>
                                        <input type="text" wire:model="editCity" class="form-control form-control-sm @error('editCity') is-invalid @enderror" style="max-width:280px;" placeholder="Miasto zamieszkania…">
                                        @error('editCity') <div class="invalid-feedback" style="font-size:.72rem;">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" wire:click="saveCandidateIdentity" class="btn btn-primary btn-sm">
                                            <i class="bi bi-check2 me-1"></i>Zapisz dane kontaktowe
                                        </button>
                                        <button type="button" wire:click="toggleCandidateIdentityEdit" class="btn btn-outline-secondary btn-sm">Anuluj</button>
                                    </div>
                                @else
                                    <div class="d-flex align-items-start gap-3">
                                        <x-ui.avatar :image-url="$selected->photo_url" :initials="mb_strtoupper(mb_substr($selected->first_name,0,1).mb_substr($selected->last_name,0,1))" size="48px" shape="rounded" :border="false" />
                                        <div class="flex-grow-1 min-width-0">
                                            <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                                                <h5 class="mb-0" style="font-size:1rem;">{{ $selected->full_name }}</h5>
                                                @if($isStarred)
                                                    <span class="badge badge-warning ms-1" style="font-size:.65rem;"><i class="bi bi-star-fill me-1"></i>Wartościowy</span>
                                                @endif
                                                @if($isBlacklisted)
                                                    <span class="badge badge-danger ms-1" style="font-size:.65rem;"><i class="bi bi-flag-fill me-1"></i>Czarna lista</span>
                                                @endif
                                            </div>
                                            <div class="d-flex flex-wrap gap-2" style="font-size:.8rem;color:var(--text-muted);">
                                                @if($selected->phone)<a href="tel:{{ $selected->phone }}" class="text-decoration-none" style="color:var(--text-muted);"><i class="bi bi-telephone me-1"></i>{{ $selected->phone }}</a>@endif
                                                @if($selected->email)<span><i class="bi bi-envelope me-1"></i>{{ $selected->email }}</span>@endif
                                                @if($selected->city)<span><i class="bi bi-geo-alt me-1"></i>{{ $selected->city }}</span>@endif
                                            </div>
                                            @if($isBlacklisted && $candidate->rating_note)
                                                <div class="mt-2" style="font-size:.78rem;color:var(--danger);"><i class="bi bi-exclamation-triangle me-1"></i>{{ $candidate->rating_note }}</div>
                                            @endif
                                        </div>
                                        <div class="d-flex gap-1 flex-shrink-0">
                                            <button type="button" wire:click="toggleCandidateIdentityEdit" class="btn btn-sm btn-outline-secondary" title="Edytuj dane kontaktowe">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" wire:click="setCandidateFlag({{ $selected->id }}, 'wartosciowy')" class="btn btn-sm {{ $isStarred ? 'btn-warning' : 'btn-outline-secondary' }}" title="{{ $isStarred ? 'Usuń oznaczenie wartościowy' : 'Oznacz jako wartościowy kandydat' }}">
                                                <i class="bi bi-star{{ $isStarred ? '-fill' : '' }}"></i>
                                            </button>
                                            <button type="button" wire:click="setCandidateFlag({{ $selected->id }}, 'czarna_lista')" class="btn btn-sm {{ $isBlacklisted ? 'btn-danger' : 'btn-outline-secondary' }}" title="{{ $isBlacklisted ? 'Usuń z czarnej listy' : 'Wpisz na czarną listę' }}">
                                                <i class="bi bi-flag{{ $isBlacklisted ? '-fill' : '' }}"></i>
                                            </button>
                                            @if($selected->phone)
                                                <a href="tel:{{ $selected->phone }}" class="btn btn-success btn-sm"><i class="bi bi-telephone-fill"></i></a>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                @if($showBlacklistPrompt)
                                    <div class="mt-3 pt-3" style="border-top:1px solid var(--glass-border);">
                                        <div class="rp-section-title" style="color:var(--danger);">Powód wpisania na czarną listę</div>
                                        <textarea wire:model="blacklistNote" class="form-control mb-2" rows="2" style="font-size:.82rem;" placeholder="np. sfałszowane referencje…"></textarea>
                                        @error('blacklistNote') <div class="small mb-2" style="color:var(--danger);">{{ $message }}</div> @enderror
                                        <div class="d-flex gap-2">
                                            <button type="button" wire:click="confirmBlacklist" class="btn btn-danger btn-sm"><i class="bi bi-flag-fill me-1"></i>Potwierdź</button>
                                            <button type="button" wire:click="cancelBlacklist" class="btn btn-outline-secondary btn-sm">Anuluj</button>
                                        </div>
                                    </div>
                                @endif

                                {{-- Skillset --}}
                                @php
                                    $missingRoles     = empty($editRoles);
                                    $missingShipyard  = $editShipyardExperience === '';
                                    $missingRate      = $editRate === null || $editRate === '';
                                    $missingAvailable = $editAvailableFrom === '';
                                    $missingInne      = ! $editSpeaksEnglish && ! $editSpeaksFrench && ! $editSpeaksGerman && ! $editDrivingLicense;

                                    // helper: zwraca style dla etykiety sekcji gdy brak danych
                                    $missingLabelStyle = fn(bool $missing) => $missing
                                        ? 'font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:rgba(239,68,68,.65);'
                                        : 'font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);';

                                    // helper: zwraca style dla kontenera sekcji gdy brak danych
                                    $missingBoxStyle = fn(bool $missing) => $missing
                                        ? 'border-radius:6px;padding:6px 8px;margin:-6px -8px;background:rgba(239,68,68,.04);border:1px solid rgba(239,68,68,.18);'
                                        : '';
                                @endphp
                                <div class="mt-3 pt-3" style="border-top:1px solid var(--glass-border);">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">Skillset</div>
                                        @if($skillsetSaved)
                                            <span wire:key="skillset-saved-{{ now()->timestamp }}" x-data x-init="setTimeout(() => $wire.set('skillsetSaved', false), 2000)" class="badge badge-success" style="font-size:.65rem;">
                                                <i class="bi bi-check2 me-1"></i>Zapisano
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Role / stanowiska --}}
                                    <div class="mb-3" style="{{ $missingBoxStyle($missingRoles) }}">
                                        <div style="{{ $missingLabelStyle($missingRoles) }}margin-bottom:.4rem;">
                                            Role / stanowiska
                                            @if($missingRoles)
                                                <span style="font-size:.6rem;opacity:.8;margin-left:4px;font-style:italic;text-transform:none;letter-spacing:0;">· nie wybrano</span>
                                            @endif
                                        </div>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($roles as $role)
                                                <label class="form-check-compact" wire:key="er-{{ $role->id }}">
                                                    <input type="checkbox" wire:model.live.debounce.300ms="editRoles" value="{{ $role->id }}">
                                                    <span>{{ $role->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Doświadczenie na stoczni --}}
                                    <div class="mb-3" style="{{ $missingBoxStyle($missingShipyard) }}">
                                        <div style="{{ $missingLabelStyle($missingShipyard) }}margin-bottom:.4rem;">
                                            Doświadczenie na stoczni
                                            @if($missingShipyard)
                                                <span style="font-size:.6rem;opacity:.8;margin-left:4px;font-style:italic;text-transform:none;letter-spacing:0;">· nie uzupełniono</span>
                                            @endif
                                        </div>
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
                                        {{-- Stawka oczekiwana --}}
                                        <div style="{{ $missingBoxStyle($missingRate) }}">
                                            <div style="{{ $missingLabelStyle($missingRate) }}margin-bottom:.3rem;">
                                                Stawka oczekiwana
                                                @if($missingRate)
                                                    <span style="font-size:.6rem;opacity:.8;margin-left:4px;font-style:italic;text-transform:none;letter-spacing:0;">· nie uzupełniono</span>
                                                @endif
                                            </div>
                                            <div class="input-group input-group-sm" style="width:140px;">
                                                <input type="number" step="0.01" min="0" wire:model.live.debounce.300ms="editRate" class="form-control" placeholder="0.00"
                                                       style="{{ $missingRate ? 'border-color:rgba(239,68,68,.4);' : '' }}">
                                                <span class="input-group-text" style="background:var(--bg-input);border-color:{{ $missingRate ? 'rgba(239,68,68,.4)' : 'var(--glass-border)' }};color:var(--text-muted);">€/h</span>
                                            </div>
                                            @error('editRate') <div class="small mt-1" style="color:var(--danger);">{{ $message }}</div> @enderror
                                        </div>

                                        {{-- Dostępny od --}}
                                        <div style="{{ $missingBoxStyle($missingAvailable) }}">
                                            <div style="{{ $missingLabelStyle($missingAvailable) }}margin-bottom:.3rem;">
                                                Dostępny od
                                                @if($missingAvailable)
                                                    <span style="font-size:.6rem;opacity:.8;margin-left:4px;font-style:italic;text-transform:none;letter-spacing:0;">· nie uzupełniono</span>
                                                @endif
                                            </div>
                                            <div class="input-group input-group-sm" style="width:170px;">
                                                <input type="date" wire:model.live.debounce.300ms="editAvailableFrom" class="form-control"
                                                       style="{{ $missingAvailable ? 'border-color:rgba(239,68,68,.4);' : '' }}">
                                            </div>
                                            @error('editAvailableFrom') <div class="small mt-1" style="color:var(--danger);">{{ $message }}</div> @enderror
                                        </div>

                                        {{-- Inne --}}
                                        <div style="{{ $missingBoxStyle($missingInne) }}">
                                            <div style="{{ $missingLabelStyle($missingInne) }}margin-bottom:.3rem;">
                                                Inne
                                                @if($missingInne)
                                                    <span style="font-size:.6rem;opacity:.8;margin-left:4px;font-style:italic;text-transform:none;letter-spacing:0;">· nie uzupełniono</span>
                                                @endif
                                            </div>
                                            <div class="d-flex flex-wrap gap-1">
                                                <button type="button" wire:click="$toggle('editSpeaksEnglish')" class="btn btn-sm {{ $editSpeaksEnglish ? 'btn-primary' : 'btn-outline-secondary' }}" style="padding:4px 10px;height:31px;" title="Angielski">🇬🇧 EN</button>
                                                <button type="button" wire:click="$toggle('editSpeaksFrench')" class="btn btn-sm {{ $editSpeaksFrench ? 'btn-primary' : 'btn-outline-secondary' }}" style="padding:4px 10px;height:31px;" title="Francuski">🇫🇷 FR</button>
                                                <button type="button" wire:click="$toggle('editSpeaksGerman')" class="btn btn-sm {{ $editSpeaksGerman ? 'btn-primary' : 'btn-outline-secondary' }}" style="padding:4px 10px;height:31px;" title="Niemiecki">🇩🇪 DE</button>
                                                <button type="button" wire:click="$toggle('editDrivingLicense')" class="btn btn-sm {{ $editDrivingLicense ? 'btn-success' : 'btn-outline-secondary' }}" style="padding:4px 10px;height:31px;" title="Prawo jazdy kat. B"><i class="bi bi-car-front me-1"></i>Kat.&nbsp;B</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Komentarze o kandydacie --}}
                                @if($candidate)
                                @php $candidateComments = $candidate->comments->sortByDesc('created_at'); $missingComments = $candidateComments->count() === 0; @endphp
                                <div class="mt-3 pt-3" style="border-top:1px solid var(--glass-border);">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span style="font-size:.68rem;{{ $missingComments ? 'color:rgba(239,68,68,.65);' : 'color:var(--text-muted);' }}text-transform:uppercase;letter-spacing:.05em;">
                                            <i class="bi bi-chat-dots me-1"></i>Komentarze o kandydacie
                                            @if($candidateComments->count())
                                                <span class="badge badge-secondary ms-1" style="font-size:.6rem;">{{ $candidateComments->count() }}</span>
                                            @else
                                                <span style="font-size:.6rem;opacity:.8;margin-left:4px;font-style:italic;text-transform:none;letter-spacing:0;">· nie uzupełniono</span>
                                            @endif
                                        </span>
                                        <button type="button" wire:click="openCommentModal('candidate')" class="btn btn-sm btn-outline-secondary" style="padding:1px 7px;font-size:.7rem;line-height:1.6;">
                                            <i class="bi bi-plus me-1"></i>Dodaj
                                        </button>
                                    </div>
                                    @if($candidateComments->count())
                                        <div style="max-height:120px;overflow-y:auto;">
                                            @foreach($candidateComments->take(20) as $cm)
                                                <div class="d-flex align-items-start gap-2 py-1" style="border-bottom:1px solid rgba(255,255,255,.05);">
                                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-25 text-primary flex-shrink-0" style="width:1.4rem;height:1.4rem;font-size:.55rem;font-weight:700;">{{ mb_strtoupper(mb_substr($cm->user->name ?? '?',0,2)) }}</span>
                                                    <div class="flex-grow-1 min-width-0" style="font-size:.73rem;">
                                                        <span class="fw-semibold" style="color:var(--text-body);">{{ $cm->user->name ?? '—' }}</span>
                                                        <span style="color:var(--text-muted);"> · {{ $cm->created_at->diffForHumans() }}</span>
                                                        <div style="color:var(--text-muted);white-space:pre-line;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $cm->body }}</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p style="color:var(--text-muted);font-size:.72rem;margin:.2rem 0 0;">Brak komentarzy.</p>
                                    @endif
                                </div>
                                @endif
                            </div>{{-- /karta kandydata --}}
                        </div>

                        {{-- ══════════════════════════════════════════
                             PROCES — osobna karta
                        ══════════════════════════════════════════ --}}
                        <div style="margin-bottom:1.5rem;">
                            <div style="border:1px solid var(--glass-border);border-radius:12px;padding:1rem;background:rgba(255,255,255,.02);">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                    <div style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">
                                        <i class="bi bi-kanban me-1"></i>Proces rekrutacyjny
                                        <span class="badge badge-secondary ms-1" style="font-size:.65rem;font-weight:600;">#{{ $selected->id }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span style="font-size:.72rem;color:var(--text-muted);white-space:nowrap;">Prowadzi</span>
                                        <select wire:model.live="editAssignedRecruiterId" class="form-select form-select-sm" style="min-width:160px;max-width:220px;">
                                            <option value="">— Nieprzypisany —</option>
                                            @foreach($recruiters as $recruiter)
                                                <option value="{{ $recruiter->id }}">{{ $recruiter->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem;">Status</div>

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
                                        <div class="mt-2" style="font-size:.8rem;color:var(--text-muted);">
                                            <i class="bi bi-info-circle me-1"></i>Powód: <strong style="color:var(--text-0,inherit);">{{ $selected->rejection_reason->label() }}</strong>
                                            @if($selected->rejection_reason_note) — {{ $selected->rejection_reason_note }} @endif
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
                                <div class="mb-3" style="border-radius:10px;background:rgba(255,255,255,.03);border:1px solid var(--glass-border);padding:.75rem .85rem;">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">
                                            Historia procesu
                                        </div>
                                        <span style="font-size:.65rem;color:var(--text-muted);">proces #{{ $selected->id }}</span>
                                    </div>
                                    <div style="max-height:160px;overflow-y:auto;">
                                        @forelse($processTimeline as $timelineItem)
                                            @php
                                                $entry = $timelineItem['entry'];
                                                $isStatus = $timelineItem['type'] === 'status';
                                            @endphp
                                            <div class="d-flex gap-2 mb-2" wire:key="timeline-{{ $timelineItem['type'] }}-{{ $entry->id }}" style="font-size:.78rem;">
                                                <div style="padding-top:5px;flex-shrink:0;">
                                                    <div style="width:8px;height:8px;border-radius:50%;background:{{ $isStatus ? 'var(--primary)' : '#a78bfa' }};box-shadow:0 0 0 3px {{ $isStatus ? 'rgba(var(--primary-rgb,59,130,246),.15)' : 'rgba(167,139,250,.15)' }};"></div>
                                                </div>
                                                <div class="flex-grow-1 d-flex justify-content-between align-items-start gap-2">
                                                    <div>
                                                        @if($isStatus)
                                                            @if($entry->from_status)
                                                                <span style="color:var(--text-muted);">{{ $entry->from_status->label() }}</span>
                                                                <i class="bi bi-arrow-right mx-1" style="color:var(--text-muted);font-size:.7rem;"></i>
                                                            @else
                                                                <span style="color:var(--text-muted);">Utworzono</span>
                                                                <i class="bi bi-arrow-right mx-1" style="color:var(--text-muted);font-size:.7rem;"></i>
                                                            @endif
                                                            <strong>{{ $entry->to_status->label() }}</strong>
                                                        @else
                                                            <i class="bi bi-person-badge me-1" style="color:#a78bfa;font-size:.75rem;"></i>
                                                            <span style="color:var(--text-muted);">{{ $entry->fromRecruiter?->name ?? 'Nieprzypisany' }}</span>
                                                            <i class="bi bi-arrow-right mx-1" style="color:var(--text-muted);font-size:.7rem;"></i>
                                                            <strong>{{ $entry->toRecruiter?->name ?? 'Nieprzypisany' }}</strong>
                                                        @endif
                                                        <div style="color:var(--text-muted);font-size:.68rem;">{{ $entry->changedBy?->name ?? 'System' }}</div>
                                                    </div>
                                                    <small style="color:var(--text-muted);white-space:nowrap;text-align:right;line-height:1.25;"
                                                           title="{{ $entry->created_at->format('d.m.Y H:i') }}">
                                                        <div>{{ $entry->created_at->diffForHumans() }}</div>
                                                        <div style="font-size:.62rem;opacity:.8;">{{ $entry->created_at->format('d.m.Y H:i') }}</div>
                                                    </small>
                                                </div>
                                            </div>
                                        @empty
                                            <p style="color:var(--text-muted);font-size:.78rem;margin:0;">Brak historii — wpisy pojawią się po zmianie statusu lub przypisania.</p>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="mb-3">
                                    @php $processComments = $selected->comments->sortByDesc('created_at'); @endphp
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">
                                            <i class="bi bi-chat-dots me-1"></i>Komentarze procesu
                                            @if($processComments->count()) <span class="badge badge-secondary ms-1" style="font-size:.6rem;">{{ $processComments->count() }}</span> @endif
                                        </span>
                                        <button type="button" wire:click="openCommentModal('process')" class="btn btn-sm btn-outline-secondary" style="padding:1px 7px;font-size:.7rem;line-height:1.6;">
                                            <i class="bi bi-plus me-1"></i>Dodaj
                                        </button>
                                    </div>
                                    @if($processComments->count())
                                        <div style="max-height:120px;overflow-y:auto;">
                                            @foreach($processComments->take(20) as $cm)
                                                <div class="d-flex align-items-start gap-2 py-1" style="border-bottom:1px solid rgba(255,255,255,.05);">
                                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-25 text-primary flex-shrink-0" style="width:1.4rem;height:1.4rem;font-size:.55rem;font-weight:700;">{{ mb_strtoupper(mb_substr($cm->user->name ?? '?',0,2)) }}</span>
                                                    <div class="flex-grow-1 min-width-0" style="font-size:.73rem;">
                                                        <span class="fw-semibold" style="color:var(--text-body);">{{ $cm->user->name ?? '—' }}</span>
                                                        <span style="color:var(--text-muted);"> · {{ $cm->created_at->diffForHumans() }}</span>
                                                        <div style="color:var(--text-muted);white-space:pre-line;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $cm->body }}</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p style="color:var(--text-muted);font-size:.72rem;margin:.2rem 0 0;">Brak komentarzy.</p>
                                    @endif
                                </div>

                                @if(! $selected->employee_id && $selected->status === RecruitmentStatus::Onboarding)
                                    <div class="mt-3" style="border:1px solid rgba(34,197,94,.25);border-radius:10px;padding:.85rem;background:rgba(34,197,94,.04);">
                                        <div class="rp-section-title" style="color:var(--success);"><i class="bi bi-person-plus me-1"></i>Zatrudnij kandydata</div>
                                        <p style="color:var(--text-muted);font-size:.8rem;margin-bottom:.6rem;">
                                            Tworzy profil pracownika i ustawia status na „Zatrudniony". Wybierz role:
                                        </p>
                                        <div class="d-flex flex-wrap gap-1 mb-3">
                                            @foreach($roles as $role)
                                                <label class="form-check-compact" wire:key="hr-{{ $role->id }}">
                                                    <input type="checkbox" wire:model="hireRoles" value="{{ $role->id }}">
                                                    <span>{{ $role->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        @error('hireRoles') <div class="small mb-2" style="color:var(--danger);">{{ $message }}</div> @enderror
                                        <button type="button" class="btn btn-success btn-sm"
                                                wire:click="convertToEmployee"
                                                onclick="return confirm('Zatrudnić kandydata?')">
                                            <i class="bi bi-person-plus me-1"></i>Zatrudnij
                                        </button>
                                    </div>
                                @endif
                            </div>{{-- /karta procesu --}}
                        </div>

                        {{-- Inne procesy tego kandydata (bez bieżącego) --}}
                        @php
                            $siblingProcesses = $candidate
                                ? $candidate->processes->where('id', '!=', $selected->id)->sortByDesc('created_at')
                                : collect();
                        @endphp
                        @if($siblingProcesses->isNotEmpty())
                            <div style="margin-bottom:1rem;">
                                <div style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--text-muted);margin-bottom:.6rem;padding-left:.25rem;">
                                    <i class="bi bi-diagram-2 me-1"></i>Inne procesy tego kandydata
                                    <span class="badge badge-secondary ms-1" style="font-size:.55rem;">{{ $siblingProcesses->count() }}</span>
                                </div>
                                @foreach($siblingProcesses as $proc)
                                    @php $procStatus = $proc->status; @endphp
                                    <div class="d-flex align-items-center gap-2 mb-2 p-2"
                                         style="border-radius:8px;background:rgba(255,255,255,.02);border:1px solid var(--glass-border);"
                                         wire:key="sibling-{{ $proc->id }}">
                                        <div class="flex-grow-1 min-width-0">
                                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                                @if($procStatus)
                                                    <span class="badge badge-{{ $procStatus->variant() }}" style="font-size:.62rem;">{{ $procStatus->label() }}</span>
                                                @endif
                                                <span style="font-size:.72rem;color:var(--text-muted);"
                                                      title="{{ ($proc->lead?->created_at ?? $proc->created_at)->format('d.m.Y H:i') }}">
                                                    {{ ($proc->lead?->created_at ?? $proc->created_at)->diffForHumans() }}
                                                    · {{ ($proc->lead?->created_at ?? $proc->created_at)->format('d.m.Y') }}
                                                </span>
                                                @if($proc->assignedRecruiter)
                                                    <span style="font-size:.7rem;color:var(--text-muted);">· {{ $proc->assignedRecruiter->name }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1 flex-shrink-0">
                                            <button type="button"
                                                    wire:click="selectProcess({{ $proc->id }})"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    style="padding:2px 8px;font-size:.68rem;"
                                                    title="Przejdź do tego procesu">
                                                <i class="bi bi-arrow-right"></i>
                                            </button>
                                            @if($procStatus !== \App\Enums\RecruitmentStatus::Odrzucony && $procStatus !== \App\Enums\RecruitmentStatus::Zatrudniony)
                                                <button type="button"
                                                        wire:click="updateStatus({{ $proc->id }}, 'odrzucony')"
                                                        class="btn btn-sm btn-outline-danger"
                                                        style="padding:2px 8px;font-size:.68rem;"
                                                        title="Odrzuć ten proces">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                    </div>{{-- /center --}}

                    {{-- ── RIGHT: contact log + history ─────────── --}}
                    <div class="rp-modal-right">

                        <div class="pb-3 mb-0" style="border-bottom:1px solid var(--glass-border);">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="rp-section-title mb-0">Zarejestruj próbę kontaktu</div>
                                @if($contactSaved)
                                    <span wire:key="contact-saved-{{ now()->timestamp }}"
                                          x-data
                                          x-init="setTimeout(() => $wire.set('contactSaved', false), 2000)"
                                          class="badge badge-success"
                                          style="font-size:.65rem;">
                                        <i class="bi bi-check2 me-1"></i>Zapisano
                                    </span>
                                @endif
                            </div>
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                @foreach(RecruitmentContactOutcome::cases() as $outcome)
                                    @php
                                        $oCls = match($outcome->variant()) { 'success'=>'btn-outline-success','danger'=>'btn-outline-danger','warning'=>'btn-outline-warning',default=>'btn-outline-secondary' };
                                    @endphp
                                    <button type="button" wire:click="logContactAttempt('{{ $outcome->value }}')"
                                            class="btn btn-sm {{ $oCls }}" style="flex:1 1 44%;">
                                        {{ $outcome->label() }}
                                    </button>
                                @endforeach
                            </div>
                            <textarea wire:model.live.debounce.300ms="newComment" class="form-control" rows="2" style="font-size:.82rem;" placeholder="Komentarz (opcjonalnie, przed wyborem efektu)…"></textarea>
                        </div>

                        <div class="rp-section">
                            @php
                                $allAttempts = $candidate
                                    ? $candidate->allContactAttempts->sortByDesc('created_at')
                                    : collect();
                            @endphp
                            <div class="rp-section-title">
                                Historia kontaktu <span style="font-size:.65rem;color:var(--text-muted);font-weight:400;">(cały kandydat)</span>
                                <span class="badge badge-info ms-1">{{ $allAttempts->count() }}</span>
                            </div>
                            @forelse($allAttempts as $attempt)
                                @php
                                    $dotC = match($attempt->outcome->variant()) { 'success'=>'var(--success)','danger'=>'var(--danger)','warning'=>'var(--warning)',default=>'var(--primary)' };
                                    $aBadge = match($attempt->outcome->variant()) { 'success'=>'badge-success','danger'=>'badge-danger','warning'=>'badge-warning',default=>'badge-info' };
                                    $isCurrentProc = $attempt->recruitment_process_id === $selected->id;
                                @endphp
                                <div class="d-flex gap-2 mb-3" wire:key="att-{{ $attempt->id }}">
                                    <div style="padding-top:5px;flex-shrink:0;"><div style="width:8px;height:8px;border-radius:50%;background:{{ $dotC }};"></div></div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <span style="font-size:.8rem;font-weight:600;">{{ $attempt->user?->name ?? '—' }}</span>
                                            <small style="color:var(--text-muted);font-size:.72rem;white-space:nowrap;margin-left:.5rem;text-align:right;line-height:1.25;"
                                                   title="{{ $attempt->created_at->format('d.m.Y H:i') }}">
                                                <div>{{ $attempt->created_at->diffForHumans() }}</div>
                                                <div style="font-size:.62rem;opacity:.8;">{{ $attempt->created_at->format('d.m.Y H:i') }}</div>
                                            </small>
                                        </div>
                                        <div class="d-flex align-items-center gap-1 flex-wrap mt-1">
                                            <span class="badge {{ $aBadge }}" style="font-size:.65rem;padding:2px 6px;">{{ $attempt->outcome->label() }}</span>
                                            @if($isCurrentProc)
                                                <span class="badge badge-secondary" style="font-size:.58rem;padding:1px 5px;">bieżący proc.</span>
                                            @elseif($attempt->recruitmentProcess)
                                                <button type="button"
                                                        wire:click="selectProcess({{ $attempt->recruitment_process_id }})"
                                                        class="badge badge-secondary text-decoration-none border-0"
                                                        style="font-size:.58rem;padding:1px 5px;cursor:pointer;background:none;color:var(--text-muted);"
                                                        title="Przejdź do proc. #{{ $attempt->recruitment_process_id }}">
                                                    proc.&nbsp;#{{ $attempt->recruitment_process_id }}
                                                    @if($attempt->recruitmentProcess->status) · {{ $attempt->recruitmentProcess->status->label() }} @endif
                                                </button>
                                            @endif
                                        </div>
                                        @if($attempt->comment)<div style="font-size:.78rem;color:var(--text-muted);margin-top:3px;">{{ $attempt->comment }}</div>@endif
                                    </div>
                                </div>
                            @empty
                                <p style="color:var(--text-muted);font-size:.82rem;margin:0;">Brak prób kontaktu.</p>
                            @endforelse
                        </div>

                        <div class="rp-section">
                            <div class="rp-section-title">
                                Zadania
                                <span class="badge badge-info ms-1">{{ $selected->tasks->count() }}</span>
                                <span style="font-size:.65rem;color:var(--text-muted);font-weight:400;margin-left:.35rem;">proces #{{ $selected->id }}</span>
                                <button type="button" wire:click="openTaskModalManual" class="btn btn-sm btn-outline-secondary ms-auto" style="padding:1px 7px;font-size:.7rem;line-height:1.6;">
                                    <i class="bi bi-plus me-1"></i>Dodaj
                                </button>
                            </div>
                            @forelse($selected->tasks as $task)
                                <div class="d-flex justify-content-between align-items-start mb-2" wire:key="task-{{ $task->id }}" style="font-size:.8rem;">
                                    <div>
                                        <div style="{{ $task->status === \App\Enums\TaskStatus::COMPLETED ? 'text-decoration:line-through;color:var(--text-muted);' : '' }}">{{ $task->name }}</div>
                                        <div style="color:var(--text-muted);font-size:.72rem;">
                                            @if($task->due_date)<i class="bi bi-calendar-event me-1"></i>{{ $task->due_date->format('d.m.Y') }}@endif
                                            @if($task->assignedTo) · {{ $task->assignedTo->name }} @endif
                                        </div>
                                    </div>
                                    <button type="button" wire:click="toggleTaskDone({{ $task->id }})" class="btn btn-sm btn-outline-secondary flex-shrink-0" style="padding:1px 8px;font-size:.68rem;">
                                        <i class="bi bi-check2{{ $task->status === \App\Enums\TaskStatus::COMPLETED ? '-square-fill' : '-square' }}"></i>
                                    </button>
                                </div>
                            @empty
                                <p style="color:var(--text-muted);font-size:.82rem;margin:0;">Brak zaplanowanych zadań.</p>
                            @endforelse
                        </div>

                    </div>{{-- /right --}}

                </div>{{-- /rp-modal-body --}}
            </div>{{-- /rp-modal --}}
        </div>{{-- /rp-modal-wrap --}}
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

    @if($showCommentModal)
        <div class="rp-modal-backdrop" style="z-index:1070;" wire:click="closeCommentModal"></div>
        <div class="rp-modal-wrap" style="z-index:1071;align-items:center;justify-content:center;" role="dialog">
            <div class="rp-modal" style="max-width:520px;width:100%;height:auto;">
                <div class="rp-modal-topbar">
                    <strong style="font-size:.9rem;">
                        <i class="bi bi-chat-dots me-1"></i>
                        @if($commentModalTarget === 'candidate')
                            Komentarz o kandydacie
                        @else
                            Komentarz do procesu #{{ $selected?->id }}
                        @endif
                    </strong>
                    <button type="button" wire:click="closeCommentModal" class="btn btn-sm btn-outline-secondary ms-auto"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="p-3">
                    {{-- Previous comments (compact scroll) --}}
                    @php
                        $modalComments = $commentModalTarget === 'candidate'
                            ? ($selected?->candidate?->comments?->sortByDesc('created_at') ?? collect())
                            : ($selected?->comments?->sortByDesc('created_at') ?? collect());
                    @endphp
                    @if($modalComments->count())
                        <div class="mb-3" style="max-height:180px;overflow-y:auto;border:1px solid var(--glass-border);border-radius:8px;padding:.5rem .75rem;">
                            @foreach($modalComments as $cm)
                                <div class="d-flex align-items-start gap-2 py-1" style="{{ !$loop->last ? 'border-bottom:1px solid rgba(255,255,255,.05);' : '' }}">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-25 text-primary flex-shrink-0" style="width:1.5rem;height:1.5rem;font-size:.58rem;font-weight:700;">{{ mb_strtoupper(mb_substr($cm->user->name ?? '?',0,2)) }}</span>
                                    <div class="flex-grow-1 min-width-0" style="font-size:.74rem;">
                                        <span class="fw-semibold" style="color:var(--text-body);">{{ $cm->user->name ?? '—' }}</span>
                                        <span style="color:var(--text-muted);"> · {{ $cm->created_at->diffForHumans() }}</span>
                                        <div style="color:var(--text-muted);white-space:pre-line;">{{ $cm->body }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Full comment form (standard POST → CommentController → redirect back) --}}
                    @php
                        $cmTarget   = $commentModalTarget === 'candidate' ? $selected?->candidate : $selected;
                        $cmType     = $commentModalTarget === 'candidate' ? 'recruitment_candidate' : 'recruitment_process';
                        $cmId       = $cmTarget?->id;
                        $cmUsers    = \App\Models\User::orderBy('name')->get()
                                        ->map(fn($u) => ['name' => $u->name, 'initials' => $u->initials])
                                        ->values()->all();
                        $cmPayload  = ['users' => $cmUsers, 'subtasks' => []];
                    @endphp
                    <form action="{{ route('comments.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="commentable_type" value="{{ $cmType }}">
                        <input type="hidden" name="commentable_id" value="{{ $cmId }}">

                        <div class="mb-2 position-relative" x-data="commentBodyAutocomplete(@js($cmPayload))">
                            <textarea
                                name="body"
                                rows="3"
                                class="form-control form-control-sm"
                                placeholder="Możesz użyć @NazwaUzytkownika (treść lub załącznik — wymagane jest przynajmniej jedno)"
                                x-ref="textarea"
                                @input="onInput()"
                                @keydown.escape="close()"
                                @keydown.arrow-down="if (show && results.length) { $event.preventDefault(); moveActive(1); }"
                                @keydown.arrow-up="if (show && results.length) { $event.preventDefault(); moveActive(-1); }"
                                @keydown.enter="if (show && results.length) { $event.preventDefault(); pickActive(); }"
                            ></textarea>
                            <ul
                                x-show="show && results.length > 0"
                                x-cloak
                                class="dropdown-menu show list-unstyled position-absolute mb-0 py-1"
                                style="z-index:1090;min-width:16rem;max-height:14rem;overflow-y:auto;top:100%;left:0;"
                            >
                                <template x-for="(item, idx) in results" :key="'u-' + item.name">
                                    <li>
                                        <button type="button"
                                            class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 text-start w-100"
                                            :class="idx === activeIdx ? 'active' : ''"
                                            @click="selectItem(item)"
                                            @mouseenter="activeIdx = idx">
                                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                                                :class="item.isEveryone ? 'bg-warning bg-opacity-25 text-warning' : 'bg-primary bg-opacity-25 text-primary'"
                                                style="width:1.75rem;height:1.75rem;font-size:.65rem;"
                                                x-text="item.initials"></span>
                                            <span class="small fw-medium text-truncate" x-text="item.isEveryone ? '@wszyscy — powiadomienie do wszystkich' : item.name"></span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <div class="mb-3">
                            <input type="file" name="attachments[]" class="form-control form-control-sm" multiple
                                   accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.txt,.zip,application/pdf,image/*">
                            <small class="text-muted d-block mt-1" style="font-size:.7rem;">Do 15 plików, każdy max. 15 MB.</small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check2 me-1"></i>Dodaj komentarz</button>
                            <button type="button" wire:click="closeCommentModal" class="btn btn-outline-secondary btn-sm">Anuluj</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

</div>

@once
@push('scripts')
<script>
    if (typeof commentBodyAutocomplete === 'undefined') {
        function commentBodyAutocomplete(payload) {
            const allUsers = payload.users || [];
            const subtasks = payload.subtasks || [];
            return {
                show: false, results: [], activeIdx: 0, triggerStart: -1,
                onInput() {
                    const ta = this.$refs.textarea;
                    const pos = ta.selectionStart;
                    const text = ta.value.substring(0, pos);
                    const atMatch = text.match(/(^|(?<=\s))@(\S*)$/u);
                    if (atMatch) {
                        const fragment = atMatch[2];
                        if (fragment.length > 0) {
                            this.triggerStart = pos - fragment.length - 1;
                            const q = fragment.toLowerCase();
                            const userResults = allUsers.filter(u => u.name.toLowerCase().includes(q)).slice(0, 7).map(u => ({ kind: 'user', name: u.name, initials: u.initials }));
                            const wszyscyResults = 'wszyscy'.startsWith(q) ? [{ kind: 'user', name: 'wszyscy', initials: '★', isEveryone: true }] : [];
                            this.results = [...wszyscyResults, ...userResults];
                            this.activeIdx = 0; this.show = this.results.length > 0; return;
                        }
                    }
                    this.close();
                },
                moveActive(delta) { if (!this.show || !this.results.length) return; const n = this.results.length; this.activeIdx = (this.activeIdx + delta + n) % n; },
                pickActive() { if (!this.show || !this.results.length) return; this.selectItem(this.results[this.activeIdx]); },
                selectItem(item) {
                    const ta = this.$refs.textarea;
                    const before = ta.value.substring(0, this.triggerStart);
                    const after = ta.value.substring(ta.selectionStart);
                    ta.value = before + '@' + item.name + ' ' + after;
                    const newPos = before.length + item.name.length + 2;
                    ta.setSelectionRange(newPos, newPos); ta.focus(); this.close();
                },
                close() { this.show = false; this.results = []; this.triggerStart = -1; },
            };
        }
    }
</script>
@endpush
@endonce
