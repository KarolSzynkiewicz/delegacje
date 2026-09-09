<div class="rp-doc-section rp-doc-section--candidate">
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
                       ? 'Zwolniony'.($linkedEmployee->terminated_at ? ' '.$linkedEmployee->terminated_at->format('d.m.Y') : '').($linkedEmployee->hired_at ? ' · zatrudniony '.$linkedEmployee->hired_at->format('d.m.Y') : '')
                       : 'Zatrudniony'.($linkedEmployee->hired_at ? ' od '.$linkedEmployee->hired_at->format('d.m.Y') : '') }}">
                    <i class="bi bi-person-{{ $isFormerEmployee ? 'x' : 'check' }} me-1"></i>{{ $isFormerEmployee ? 'Były pracownik' : 'Pracownik' }}
                    @if($linkedEmployee->hired_at && ! $isFormerEmployee)
                        <span class="ms-1" style="opacity:.8;font-weight:500;">{{ $linkedEmployee->hired_at->format('d.m.Y') }}</span>
                    @endif
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
                    @foreach($candidate->roles as $candidateRole)
                        <span class="rp-skill-chip rp-skill-chip--role">{{ $candidateRole->name }}</span>
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
</div>
