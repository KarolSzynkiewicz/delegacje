@php
    use App\Enums\RecruitmentCandidateFlag;
    use App\Enums\RecruitmentShipyardExperience;
    use App\Enums\RecruitmentStatus;
@endphp
<div class="rp-filter-panel__inner">
    <div class="rp-filter-panel__header">
        <span class="rp-filter-panel__title">Zawężanie listy</span>
        <button type="button" wire:click="clearFilters" @click="open=false" class="rp-filter-panel__clear">
            Wyczyść
        </button>
    </div>

    {{-- 1. Kandydat --}}
    <div class="rp-filter-section">
        <button type="button" @click="openCandidate = !openCandidate" class="rp-filter-section__head">
            <span><i class="bi bi-person me-1 opacity-75"></i>Kandydat</span>
            <i class="bi" :class="openCandidate ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </button>
        <div x-show="openCandidate" class="rp-filter-section__body">
            <div class="rp-filter-grid">
                <div>
                    <div class="rp-filter-label">Oznaczenia</div>
                    <button type="button"
                            wire:click="$set('draftFlag', '{{ $draftFlag === RecruitmentCandidateFlag::Wartosciowy->value ? '' : RecruitmentCandidateFlag::Wartosciowy->value }}')"
                            class="rp-filter-option {{ $draftFlag === RecruitmentCandidateFlag::Wartosciowy->value ? 'is-active' : '' }}">
                        <span class="rp-filter-check {{ $draftFlag === RecruitmentCandidateFlag::Wartosciowy->value ? 'is-checked' : '' }}"><i class="bi bi-check"></i></span>
                        <span class="rp-filter-option__label">Wartościowy</span>
                        <span class="rp-filter-option__count">{{ $flagCounts[RecruitmentCandidateFlag::Wartosciowy->value] ?? 0 }}</span>
                    </button>
                    <button type="button"
                            wire:click="$set('draftFlag', '{{ $draftFlag === RecruitmentCandidateFlag::CzarnaLista->value ? '' : RecruitmentCandidateFlag::CzarnaLista->value }}')"
                            class="rp-filter-option {{ $draftFlag === RecruitmentCandidateFlag::CzarnaLista->value ? 'is-active' : '' }}">
                        <span class="rp-filter-check {{ $draftFlag === RecruitmentCandidateFlag::CzarnaLista->value ? 'is-checked' : '' }}"><i class="bi bi-check"></i></span>
                        <span class="rp-filter-option__label">Czarna lista</span>
                        <span class="rp-filter-option__count">{{ $flagCounts[RecruitmentCandidateFlag::CzarnaLista->value] ?? 0 }}</span>
                    </button>

                    <div class="rp-filter-label mt-2">Zatrudnienie</div>
                    <button type="button"
                            wire:click="$set('draftEmployment', '{{ $draftEmployment === 'hired' ? '' : 'hired' }}')"
                            class="rp-filter-option {{ $draftEmployment === 'hired' ? 'is-active' : '' }}">
                        <span class="rp-filter-check {{ $draftEmployment === 'hired' ? 'is-checked' : '' }}"><i class="bi bi-check"></i></span>
                        <span class="rp-filter-option__label">Zatrudniony</span>
                    </button>
                    <button type="button"
                            wire:click="$set('draftEmployment', '{{ $draftEmployment === 'former' ? '' : 'former' }}')"
                            class="rp-filter-option {{ $draftEmployment === 'former' ? 'is-active' : '' }}">
                        <span class="rp-filter-check {{ $draftEmployment === 'former' ? 'is-checked' : '' }}"><i class="bi bi-check"></i></span>
                        <span class="rp-filter-option__label">Były pracownik</span>
                    </button>

                    <div class="rp-filter-label mt-2">Doświadczenie (stocznia)</div>
                    <div class="rp-filter-chips">
                        @foreach(RecruitmentShipyardExperience::cases() as $exp)
                            <button type="button"
                                    wire:click="$set('draftShipyardExperience', '{{ $draftShipyardExperience === $exp->value ? '' : $exp->value }}')"
                                    class="rp-filter-chip {{ $draftShipyardExperience === $exp->value ? 'is-active' : '' }}">
                                {{ $exp->label() }}
                            </button>
                        @endforeach
                    </div>

                    <div class="rp-filter-label mt-2">Umiejętności</div>
                    <div class="rp-filter-chips">
                        <button type="button" wire:click="$toggle('draftSkillEnglish')"
                                class="rp-filter-chip {{ $draftSkillEnglish ? 'is-active' : '' }}">EN</button>
                        <button type="button" wire:click="$toggle('draftSkillFrench')"
                                class="rp-filter-chip {{ $draftSkillFrench ? 'is-active' : '' }}">FR</button>
                        <button type="button" wire:click="$toggle('draftSkillGerman')"
                                class="rp-filter-chip {{ $draftSkillGerman ? 'is-active' : '' }}">DE</button>
                        <button type="button" wire:click="$toggle('draftSkillDriving')"
                                class="rp-filter-chip {{ $draftSkillDriving ? 'is-active' : '' }}"><i class="bi bi-car-front me-1"></i>Kat. B</button>
                    </div>
                </div>

                <div>
                    <div class="rp-filter-label">Stawka (€/h)</div>
                    <div class="rp-filter-range">
                        <div>
                            <span class="rp-filter-hint">więcej niż</span>
                            <input type="number" min="0" step="0.5" inputmode="decimal"
                                   wire:model="draftRateMin" class="form-control form-control-sm rp-filter-input"
                                   placeholder="np. 18" @click.stop>
                        </div>
                        <div>
                            <span class="rp-filter-hint">mniej niż</span>
                            <input type="number" min="0" step="0.5" inputmode="decimal"
                                   wire:model="draftRateMax" class="form-control form-control-sm rp-filter-input"
                                   placeholder="np. 30" @click.stop>
                        </div>
                    </div>

                    <div class="rp-filter-label mt-3">Dostępny od</div>
                    <div class="rp-filter-range">
                        <div>
                            <span class="rp-filter-hint">później niż</span>
                            <input type="date" wire:model="draftAvailableAfter"
                                   class="form-control form-control-sm rp-filter-input" @click.stop>
                        </div>
                        <div>
                            <span class="rp-filter-hint">wcześniej niż</span>
                            <input type="date" wire:model="draftAvailableBefore"
                                   class="form-control form-control-sm rp-filter-input" @click.stop>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. Proces --}}
    <div class="rp-filter-section">
        <button type="button" @click="openProcess = !openProcess" class="rp-filter-section__head">
            <span><i class="bi bi-kanban me-1 opacity-75"></i>Proces rekrutacyjny</span>
            <i class="bi" :class="openProcess ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </button>
        <div x-show="openProcess" class="rp-filter-section__body">
            <div class="rp-filter-label">Etap</div>
            <div class="rp-filter-chips mb-2">
                <button type="button" wire:click="$set('draftStatus', '')"
                        class="rp-filter-chip {{ $draftStatus === '' ? 'is-active' : '' }}">Wszystkie</button>
                @foreach(RecruitmentStatus::tabOrder() as $case)
                    <button type="button" wire:click="$set('draftStatus', '{{ $case->value }}')"
                            class="rp-filter-chip {{ $draftStatus === $case->value ? 'is-active' : '' }}">
                        {{ $case->label() }}
                        @if($counts->has($case->value))
                            <span class="rp-filter-chip__count">{{ $counts[$case->value] }}</span>
                        @endif
                    </button>
                @endforeach
            </div>

            <div class="rp-filter-label">Przypisany rekruter</div>
            <div class="rp-filter-scroll">
                <button type="button"
                        wire:click="$set('draftRecruiter', '{{ $draftRecruiter === 'unassigned' ? '' : 'unassigned' }}')"
                        class="rp-filter-option {{ $draftRecruiter === 'unassigned' ? 'is-active' : '' }}">
                    <span class="rp-filter-check {{ $draftRecruiter === 'unassigned' ? 'is-checked' : '' }}"><i class="bi bi-check"></i></span>
                    <span class="rp-filter-option__label">Nieprzypisany</span>
                    <span class="rp-filter-option__count">{{ $recruiterCounts['unassigned'] ?? 0 }}</span>
                </button>
                @foreach($recruiters as $recruiterOption)
                    @php $key = (string) $recruiterOption->id; @endphp
                    <button type="button"
                            wire:click="$set('draftRecruiter', '{{ $draftRecruiter === $key ? '' : $key }}')"
                            class="rp-filter-option {{ $draftRecruiter === $key ? 'is-active' : '' }}">
                        <span class="rp-filter-check {{ $draftRecruiter === $key ? 'is-checked' : '' }}"><i class="bi bi-check"></i></span>
                        <span class="rp-filter-option__label">{{ $recruiterOption->name }}</span>
                        <span class="rp-filter-option__count">{{ $recruiterCounts[$key] ?? 0 }}</span>
                    </button>
                @endforeach
            </div>

            <div class="rp-filter-label mt-2">Ilość procesów rekrutacyjnych</div>
            <div class="rp-filter-chips">
                @foreach(['2' => '2+', '3' => '3+', '4' => '4+'] as $value => $label)
                    <button type="button"
                            wire:click="$set('draftMinProcesses', '{{ $draftMinProcesses === $value ? '' : $value }}')"
                            class="rp-filter-chip {{ $draftMinProcesses === $value ? 'is-active' : '' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- 3. Lead --}}
    <div class="rp-filter-section">
        <button type="button" @click="openLead = !openLead" class="rp-filter-section__head">
            <span><i class="bi bi-send me-1 opacity-75"></i>Lead — źródło</span>
            <i class="bi" :class="openLead ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </button>
        <div x-show="openLead" class="rp-filter-section__body">
            <div class="rp-filter-scroll">
                @foreach($referralSourceOptions as $option)
                    @php
                        $key = $option['key'];
                        $count = $referralSourceCounts[$key] ?? 0;
                    @endphp
                    @if($count > 0)
                        <button type="button"
                                wire:click="$set('draftReferralSource', '{{ $draftReferralSource === $key ? '' : $key }}')"
                                class="rp-filter-option {{ $draftReferralSource === $key ? 'is-active' : '' }}">
                            <span class="rp-filter-check {{ $draftReferralSource === $key ? 'is-checked' : '' }}"><i class="bi bi-check"></i></span>
                            <span class="rp-filter-option__label">{{ $option['label'] }}</span>
                            <span class="rp-filter-option__count">{{ $count }}</span>
                        </button>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    {{-- 4. Inne --}}
    <div class="rp-filter-section">
        <button type="button" @click="openOther = !openOther" class="rp-filter-section__head">
            <span><i class="bi bi-sliders me-1 opacity-75"></i>Inne</span>
            <i class="bi" :class="openOther ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </button>
        <div x-show="openOther" class="rp-filter-section__body">
            <button type="button" wire:click="$toggle('draftHasTask')"
                    class="rp-filter-option {{ $draftHasTask ? 'is-active' : '' }}">
                <span class="rp-filter-check {{ $draftHasTask ? 'is-checked' : '' }}"><i class="bi bi-check"></i></span>
                <span class="rp-filter-option__label">Ma zadanie</span>
            </button>

            <div class="rp-filter-label mt-2">Ostatni komentarz starszy niż</div>
            <div class="rp-filter-chips">
                @foreach(['1' => '1 dzień', '2' => '2 dni', '3' => '3 dni', '4' => '4 dni', '5' => '5+ dni'] as $value => $label)
                    <button type="button"
                            wire:click="$set('draftCommentOlderThanDays', '{{ $draftCommentOlderThanDays === $value ? '' : $value }}')"
                            class="rp-filter-chip {{ $draftCommentOlderThanDays === $value ? 'is-active' : '' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <button type="button"
            wire:click="applyDraftFilters"
            @click="open=false"
            class="rp-filter-apply">
        Zastosuj
    </button>
</div>
