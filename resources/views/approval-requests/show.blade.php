@php
    $canDecide = auth()->user()->can('decide', $approval);
    $sourceCard = $approval->comment?->commentableCard();
    $decision = $approval->decision;
    $procedureRun = $approval->procedureRun;
    $subjectCard = $procedureRun?->sourceCard();
    $procedureTask = $procedureRun?->task;
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$approval->name">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('tasks.home') }}" action="back">
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.badge variant="accent">Zatwierdzenie</x-ui.badge>
                <x-ui.approval-decision :decision="$decision" size="lg" with-label />
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-ui.alert variant="success" dismissible class="mb-4">
            {{ session('success') }}
        </x-ui.alert>
    @endif
    @if(session('error'))
        <x-ui.alert variant="danger" dismissible class="mb-4">
            {{ session('error') }}
        </x-ui.alert>
    @endif

    <div class="row g-4 justify-content-center">
        <div class="col-lg-7">
            <x-ui.card class="mb-4">
                <div class="mb-4">
                    <h3 class="fs-5 fw-semibold mb-2">{{ $approval->name }}</h3>
                    @if($subjectCard)
                        <a href="{{ $subjectCard['url'] }}" class="d-inline-flex align-items-center gap-1 small text-decoration-none mb-1">
                            <i class="bi {{ $subjectCard['icon'] }}"></i>
                            <span>Dotyczy: {{ $subjectCard['label'] }}</span>
                        </a>
                    @endif
                    @if($procedureTask)
                        <div class="small text-muted">
                            Procedura:
                            <a href="{{ route('tasks.show', $procedureTask) }}">{{ $procedureRun?->template?->name ?? 'przebieg' }}</a>
                        </div>
                    @endif
                    @if(filled($approval->description))
                        <p class="mb-0 mt-3 text-break" style="white-space:pre-wrap">{{ $approval->description }}</p>
                    @endif
                </div>

                <div class="d-flex align-items-center gap-3 mb-4 pb-3" style="border-bottom:1px solid var(--glass-border, rgba(255,255,255,0.1)); border-top:1px solid var(--glass-border, rgba(255,255,255,0.1)); padding-top:1rem;">
                    <x-ui.approval-decision :decision="$decision" size="lg" />
                    <div>
                        <div class="fw-semibold">{{ $decision?->label() ?? 'Oczekuje' }}</div>
                        @if($approval->isDecided() && $approval->decidedBy)
                            <div class="small text-muted">
                                {{ $approval->decidedBy->name }}
                                · {{ $approval->decided_at?->format('d.m.Y H:i') }}
                            </div>
                        @else
                            <div class="small text-muted">Czeka na decyzję {{ $approval->approver?->name ? 'osoby '.$approval->approver->name : 'zatwierdzającego' }}</div>
                        @endif
                    </div>
                </div>
                <dl class="row mb-0 small">
                    <dt class="col-sm-4 text-muted">Zatwierdzający</dt>
                    <dd class="col-sm-8">{{ $approval->approver?->name ?? '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Wnioskodawca</dt>
                    <dd class="col-sm-8">{{ $approval->createdBy?->name ?? '—' }}</dd>
                    @if($approval->sprint)
                        <dt class="col-sm-4 text-muted">Sprint</dt>
                        <dd class="col-sm-8">
                            <a href="{{ route('sprints.show', $approval->sprint) }}">{{ $approval->sprint->label() }}</a>
                        </dd>
                    @endif
                    @if($approval->category)
                        <dt class="col-sm-4 text-muted">Kategoria</dt>
                        <dd class="col-sm-8">{{ $approval->category }}</dd>
                    @endif
                    @if($approval->priority)
                        <dt class="col-sm-4 text-muted">Priorytet</dt>
                        <dd class="col-sm-8 font-mono">{{ $approval->priority }}</dd>
                    @endif
                    @if($approval->due_at)
                        <dt class="col-sm-4 text-muted">Termin</dt>
                        <dd class="col-sm-8 font-mono">{{ $approval->due_at->format('d.m.Y') }}</dd>
                    @endif
                    @if($sourceCard)
                        <dt class="col-sm-4 text-muted">Źródło</dt>
                        <dd class="col-sm-8">
                            <a href="{{ $sourceCard['url'] }}">
                                <i class="bi {{ $sourceCard['icon'] }} me-1"></i>{{ $sourceCard['label'] }}
                            </a>
                            @if($approval->comment)
                                <a href="{{ $approval->comment->urlWithCommentAnchor() }}" class="ms-2">komentarz</a>
                            @endif
                        </dd>
                    @endif
                </dl>
            </x-ui.card>

            @if($approval->attachments->isNotEmpty())
                <x-ui.card class="mb-4">
                    <h3 class="fs-6 fw-semibold text-uppercase text-muted mb-3" style="letter-spacing:.05em;">Załącznik</h3>
                    <x-attachment-list :attachments="$approval->attachments" />
                </x-ui.card>
            @endif

            @if($canDecide && ! $approval->isDecided())
                <x-ui.card class="mb-4">
                    <h3 class="fs-6 fw-semibold text-uppercase text-muted mb-3" style="letter-spacing:.05em;">Decyzja</h3>
                    <form action="{{ route('approval-requests.decide', $approval) }}" method="POST">
                        @csrf
                        <label class="form-label small text-muted" for="approval-comment">Uzasadnienie (opcjonalnie)</label>
                        <textarea id="approval-comment"
                                  name="comment"
                                  class="form-control mb-3"
                                  rows="3"
                                  placeholder="Dlaczego zatwierdzasz albo odrzucasz?">{{ old('comment') }}</textarea>
                        @error('comment')
                            <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                        @enderror
                        <div class="d-flex flex-wrap gap-2">
                            <x-ui.button variant="success" type="submit" name="decision" value="approved">
                                <i class="bi bi-check-lg me-1"></i>Zatwierdź
                            </x-ui.button>
                            <x-ui.button variant="danger" type="submit" name="decision" value="rejected">
                                <i class="bi bi-x-lg me-1"></i>Odrzuć
                            </x-ui.button>
                        </div>
                    </form>
                </x-ui.card>
            @endif

            <x-comments :commentable="$approval" />
        </div>
    </div>
</x-app-layout>
