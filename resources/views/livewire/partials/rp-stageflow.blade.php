<div class="rp-stageflow" wire:key="rp-stageflow-{{ $selected->id }}-{{ $statusVal }}-{{ $reviewStage }}">
    <div class="rp-stageflow__rail" role="tablist" aria-label="Etapy rekrutacji">
        @foreach(RecruitmentStatus::pipelineSteps() as $index => $stage)
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
                    title="Podejrzyj etap „{{ $stage->label() }}” — status pozostaje bez zmian">
                <span class="rp-stageflow__dot">
                    @if($currentIndex !== null && $index < $currentIndex)
                        <i class="bi bi-check-lg"></i>
                    @else
                        {{ $index + 1 }}
                    @endif
                </span>
                <span class="rp-stageflow__label">{{ $stage->label() }}</span>
            </button>
        @endforeach

        {{-- Wyjścia ze ścieżki siedzą na szynie tylko wtedy, gdy naprawdę dotyczą tego procesu --}}
        @foreach($exitStatuses as $exit)
            @if($exit === $currentStatus || $exit === $reviewStatus)
                <span class="rp-stageflow__link is-exit"></span>
                <button type="button" role="tab"
                        wire:click="previewStage('{{ $exit->value }}')"
                        @class([
                            'rp-stageflow__stage',
                            'is-exit',
                            'is-danger' => $exit === RecruitmentStatus::Odrzucony,
                            'is-current' => $exit === $currentStatus,
                            'is-reviewed' => $exit === $reviewStatus,
                        ])
                        aria-selected="{{ $exit === $reviewStatus ? 'true' : 'false' }}"
                        title="Podejrzyj etap „{{ $exit->label() }}” — status pozostaje bez zmian">
                    <span class="rp-stageflow__dot">
                        <i class="bi bi-{{ $exit === RecruitmentStatus::Odrzucony ? 'x-lg' : 'box-arrow-left' }}"></i>
                    </span>
                    <span class="rp-stageflow__label">{{ $exit->label() }}</span>
                </button>
            @endif
        @endforeach
    </div>

    @if($isReviewing)
        <button type="button" wire:click="resetStageReview" class="rp-stageflow__peek"
                title="Oglądasz etap „{{ $reviewStatus?->label() }}”. Status pozostaje „{{ $currentStatus?->label() }}” — kliknij, aby wrócić.">
            <i class="bi bi-eye"></i>
            <span>Podgląd</span>
            <i class="bi bi-x-lg rp-stageflow__peek-x"></i>
        </button>
    @endif

    <div class="rp-stageflow__actions">
        @if($previousStatus)
            <button type="button" wire:click="regressStatus"
                    wire:confirm="Cofnąć status do „{{ $previousStatus->label() }}”?"
                    class="btn btn-sm btn-outline-secondary"
                    aria-label="Cofnij status do {{ $previousStatus->label() }}"
                    title="Cofnij status do „{{ $previousStatus->label() }}”">
                <i class="bi bi-arrow-left"></i>
            </button>
        @endif
        @if($nextStatus)
            <button type="button" wire:click="advanceStatus"
                    wire:confirm="Zmienić status na „{{ $nextStatus->label() }}”?"
                    class="btn btn-sm btn-primary rp-stageflow__next">
                {{ $currentStatus?->isPipelineExit() ? 'Przywróć' : 'Dalej' }}: {{ $nextStatus->label() }}
                <i class="bi bi-arrow-right ms-1"></i>
            </button>
        @endif
        <div class="dropdown">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown"
                    aria-expanded="false" aria-label="Więcej akcji statusu" title="Więcej akcji">
                <i class="bi bi-three-dots"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach($exitStatuses as $exit)
                    @if($currentStatus !== $exit)
                        <li>
                            <button type="button" wire:click="updateStatus({{ $selected->id }}, '{{ $exit->value }}')"
                                    @class(['dropdown-item', 'text-danger' => $exit === RecruitmentStatus::Odrzucony])>
                                <i class="bi bi-{{ $exit === RecruitmentStatus::Odrzucony ? 'x-circle' : 'box-arrow-left' }} me-2"></i>
                                {{ $exit === RecruitmentStatus::Odrzucony ? 'Odrzuć proces' : 'Oznacz jako byłego pracownika' }}
                            </button>
                        </li>
                    @endif
                @endforeach
                @php
                    $peekableExits = collect($exitStatuses)
                        ->reject(fn ($exit) => $exit === $currentStatus || $exit === $reviewStatus);
                @endphp
                @if($peekableExits->isNotEmpty())
                    <li><hr class="dropdown-divider"></li>
                    @foreach($peekableExits as $exit)
                        <li>
                            <button type="button" wire:click="previewStage('{{ $exit->value }}')" class="dropdown-item">
                                <i class="bi bi-eye me-2"></i>Podejrzyj: {{ $exit->label() }}
                            </button>
                        </li>
                    @endforeach
                @endif
            </ul>
        </div>
    </div>
</div>
