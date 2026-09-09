<div class="rp-doc-section rp-doc-section--process">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div class="rp-field-label mb-0">
            <i class="bi bi-kanban me-1"></i>Proces rekrutacyjny
            <span class="rp-plain-tag" style="cursor:default;">#{{ $selected->id }}</span>
        </div>
        <div class="rp-recruiter {{ empty($editAssignedRecruiterId) ? 'is-empty' : '' }}">
            <span class="rp-field-label mb-0 {{ empty($editAssignedRecruiterId) ? 'is-empty' : '' }}"><i class="bi bi-person-workspace me-1"></i>Prowadzi</span>
            <select wire:model.live="editAssignedRecruiterId" class="form-select form-select-sm" style="min-width:160px;max-width:220px;">
                <option value="">— Nieprzypisany —</option>
                @foreach($recruiters as $recruiter)
                    <option value="{{ $recruiter->id }}">{{ $recruiter->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="rp-stageflow" wire:key="rp-stageflow-{{ $selected->id }}-{{ $statusVal }}-{{ $reviewStage }}">
        <div class="rp-stageflow__rail" role="tablist" aria-label="Etapy rekrutacji">
            @foreach(\App\Enums\RecruitmentStatus::pipelineSteps() as $index => $stage)
                @if(! $loop->first)
                    <span @class([
                        'rp-stageflow__link',
                        'is-done' => $currentIndex !== null && $index <= $currentIndex,
                        'is-off' => $onRejectPath,
                    ])></span>
                @endif
                <button type="button" role="tab"
                        wire:click="previewStage('{{ $stage->value }}')"
                        @class([
                            'rp-stageflow__stage',
                            'is-done' => $currentIndex !== null && $index < $currentIndex,
                            'is-current' => $stage === $currentStatus,
                            'is-reviewed' => $stage === $reviewStatus,
                            'is-off' => $onRejectPath,
                        ])
                        aria-selected="{{ $stage === $reviewStatus ? 'true' : 'false' }}"
                        title="{{ $stage === $currentStatus && $isReviewing
                            ? 'Wróć do aktualnego etapu „'.$stage->label().'”'
                            : 'Podejrzyj etap „'.$stage->label().'” — status pozostaje bez zmian' }}">
                    <span class="rp-stageflow__dot">
                        @if($isReviewing && $stage === $reviewStatus)
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        @elseif($currentIndex !== null && $index < $currentIndex)
                            <i class="bi bi-check-lg"></i>
                        @else
                            {{ $index + 1 }}
                        @endif
                    </span>
                    <span class="rp-stageflow__label">{{ $stage->label() }}</span>
                </button>
            @endforeach

            @foreach($exitStatuses as $exit)
                @if($exit === $currentStatus || $exit === $reviewStatus)
                    <span class="rp-stageflow__link is-exit"></span>
                    <button type="button" role="tab"
                            wire:click="previewStage('{{ $exit->value }}')"
                            @class([
                                'rp-stageflow__stage',
                                'is-exit',
                                'is-danger' => $exit === \App\Enums\RecruitmentStatus::Odrzucony,
                                'is-current' => $exit === $currentStatus,
                                'is-reviewed' => $exit === $reviewStatus,
                            ])
                            aria-selected="{{ $exit === $reviewStatus ? 'true' : 'false' }}"
                            title="Podejrzyj etap „{{ $exit->label() }}” — status pozostaje bez zmian">
                        <span class="rp-stageflow__dot">
                            @if($isReviewing && $exit === $reviewStatus)
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            @else
                                <i class="bi bi-{{ $exit === \App\Enums\RecruitmentStatus::Odrzucony ? 'x-lg' : 'box-arrow-left' }}"></i>
                            @endif
                        </span>
                        <span class="rp-stageflow__label">{{ $exit->label() }}</span>
                    </button>
                @endif
            @endforeach
        </div>

        <div class="rp-stageflow__actions">
            @if($nextStatus)
                @php
                    $nextLabel = ($currentStatus?->isPipelineExit() ? 'Przywróć do ' : 'Dalej: ').$nextStatus->label();
                @endphp
                <button type="button" wire:click="advanceStatus"
                        wire:confirm="Zmienić status na „{{ $nextStatus->label() }}”?"
                        class="btn btn-sm btn-primary rp-stageflow__next"
                        aria-label="{{ $nextLabel }}"
                        title="{{ $nextLabel }}">
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </button>
            @endif
            @if($isReviewing)
                <button type="button" wire:click="resetStageReview"
                        class="btn btn-sm btn-outline-secondary rp-stageflow__abort"
                        aria-label="Wróć do aktualnego etapu {{ $currentStatus?->label() }}"
                        title="Wróć do aktualnego etapu „{{ $currentStatus?->label() }}”">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            @elseif($currentStatus !== \App\Enums\RecruitmentStatus::Odrzucony
                && $currentStatus !== \App\Enums\RecruitmentStatus::BylyPracownik)
                <button type="button" wire:click="updateStatus({{ $selected->id }}, '{{ \App\Enums\RecruitmentStatus::Odrzucony->value }}')"
                        class="btn btn-sm btn-outline-secondary rp-stageflow__abort is-reject"
                        aria-label="Odrzuć proces"
                        title="Odrzuć proces">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            @endif
        </div>
    </div>

    @if($showRejectionPrompt)
        <div class="mt-3 pt-3" style="border-top:1px solid var(--glass-border);">
            <div class="rp-section-title" style="color:var(--danger);">Powód odrzucenia</div>
            <select wire:model="rejectionReason" class="form-select form-select-sm mb-2">
                <option value="">— Wybierz powód —</option>
                @foreach(\App\Enums\RecruitmentRejectionReason::options() as $value => $label)
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
