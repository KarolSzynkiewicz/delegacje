@php
    $run = $task->procedureRun;
    $sourceCard = $task->sourceCard();
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <livewire:procedure-run-stepper
            :run="$run"
            :compact="true"
            wire:key="stepper-{{ $run->id }}"
        />

        <div class="mt-4">
            <x-comments :commentable="$task" />
        </div>
    </div>

    <div class="col-lg-4">
        <x-ui.card label="Przebieg">
            <div class="d-flex flex-column gap-3">
                <div>
                    <small class="text-muted d-block mb-1">Szablon</small>
                    <div class="fw-semibold">{{ $run->template->name ?? '—' }}</div>
                </div>

                @if($sourceCard)
                    <div>
                        <small class="text-muted d-block mb-1">Dotyczy</small>
                        <a href="{{ $sourceCard['url'] }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi {{ $sourceCard['icon'] }} me-1"></i>{{ $sourceCard['label'] }}
                        </a>
                    </div>
                @endif

                <div>
                    <small class="text-muted d-block mb-1">Uruchomiono</small>
                    <div>
                        {{ $run->startedBy?->name ?? '—' }}
                        <span class="text-muted">· {{ $run->started_at->format('d.m.Y H:i') }}</span>
                    </div>
                </div>

                @if($run->finished_at)
                    <div>
                        <small class="text-muted d-block mb-1">Zakończono</small>
                        <div>{{ $run->finished_at->format('d.m.Y H:i') }}</div>
                    </div>
                @endif
            </div>
        </x-ui.card>

        <x-ui.card label="Szczegóły" class="mt-4">
            <livewire:task-show-quick-edit :task="$task" wire:key="task-show-qe-{{ $task->id }}" />

            <hr class="my-3" style="border-color: var(--glass-border);">

            <div class="d-flex flex-column gap-3">
                <div>
                    <small class="text-muted d-block mb-1">
                        <i class="bi bi-calendar3 me-1"></i>Sprint
                    </small>
                    @if($task->sprint)
                        <a href="{{ route('sprints.show', $task->sprint) }}">{{ $task->sprint->label() }}</a>
                    @else
                        <span class="text-muted">Poza sprintem</span>
                    @endif
                </div>
            </div>
        </x-ui.card>

        @if($task->description || $task->attachments->count() > 0)
            <x-ui.card label="Notatki" class="mt-4">
                @if($task->description)
                    <div class="text-break" style="white-space:pre-wrap">{{ $task->plainDescription() }}</div>
                @endif
                @if($task->attachments->count() > 0)
                    <div class="{{ $task->description ? 'mt-3' : '' }}">
                        <x-attachment-list :attachments="$task->attachments" />
                    </div>
                @endif
            </x-ui.card>
        @endif
    </div>
</div>
