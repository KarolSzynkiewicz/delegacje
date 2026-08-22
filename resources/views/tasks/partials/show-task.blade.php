@php
    $sourceCard = $task->sourceCard();
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <x-ui.card>
            @if($task->description)
                <div class="text-break" style="white-space:pre-wrap">{{ $task->plainDescription() }}</div>
            @else
                <p class="text-muted mb-0">Brak opisu.</p>
            @endif

            @if($sourceCard)
                <div class="mt-3">
                    <a href="{{ $sourceCard['url'] }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi {{ $sourceCard['icon'] }} me-1"></i>{{ $sourceCard['label'] }}
                    </a>
                </div>
            @endif

            @if($task->attachments->count() > 0)
                <div class="mt-3">
                    <small class="text-muted d-block mb-1">
                        <i class="bi bi-paperclip me-1"></i>Załączniki
                    </small>
                    <x-attachment-list :attachments="$task->attachments" />
                </div>
            @endif
        </x-ui.card>

        <div class="mt-4">
            <livewire:task-subtasks :task="$task" />
        </div>

        <div class="mt-4">
            <x-comments :commentable="$task" />
        </div>
    </div>

    <div class="col-lg-4">
        <x-ui.card label="Szczegóły">
            <livewire:task-show-quick-edit :task="$task" wire:key="task-show-qe-{{ $task->id }}" />

            <hr class="my-3" style="border-color: var(--glass-border);">

            <div class="d-flex flex-column gap-3">
                <div>
                    <small class="text-muted d-block mb-1">
                        <i class="bi bi-calendar3 me-1"></i>Sprint
                    </small>
                    @if($task->sprint)
                        <a href="{{ route('sprints.show', $task->sprint) }}">{{ $task->sprint->label() }}</a>
                        @if($task->sprint->goal)
                            <div class="small text-muted mt-1">Cel: {{ $task->sprint->goal }}</div>
                        @endif
                    @else
                        <span class="text-muted">Poza sprintem</span>
                    @endif
                </div>

                @if($task->completed_at)
                    <div>
                        <small class="text-muted d-block mb-1">Data zakończenia</small>
                        <span class="fw-semibold">{{ $task->completed_at->format('d.m.Y H:i') }}</span>
                        <span class="text-muted ms-1">({{ $task->completed_at->diffForHumans() }})</span>
                    </div>
                @endif

                <div>
                    <small class="text-muted d-block mb-1">Utworzone przez</small>
                    {{ $task->createdBy->name }}
                    <span class="text-muted">· {{ $task->created_at->format('d.m.Y H:i') }}</span>
                </div>
            </div>
        </x-ui.card>

        @if($task->status !== \App\Enums\TaskStatus::COMPLETED && $task->status !== \App\Enums\TaskStatus::CANCELLED)
            <x-ui.card label="Akcje" class="mt-4">
                <x-tasks-actions :task="$task" size="sm" gap="2" class="flex-wrap" :show-view="false" :show-edit="false" />
            </x-ui.card>
        @endif
    </div>
</div>
