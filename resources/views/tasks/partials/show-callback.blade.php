@php
    $story = $task->callbackStory();
    $done = $task->status === \App\Enums\TaskStatus::COMPLETED;
    $process = $task->recruitmentProcess;
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <x-ui.card>
            @if($story)
                <p class="fs-5 mb-3" style="line-height:1.55">
                    <strong>{{ $story['author'] }}</strong>
                    @if($story['isForYou'])
                        zlecił Ci oddzwonienie.
                    @else
                        zlecił oddzwonienie użytkownikowi <strong>{{ $story['assignee'] }}</strong>.
                    @endif
                </p>

                <p class="mb-3" style="line-height:1.55">
                    Kandydat
                    @if($story['contextUrl'])
                        <a href="{{ $story['contextUrl'] }}" class="text-decoration-none">
                            <span class="badge bg-primary bg-opacity-25 text-primary align-middle">
                                <i class="bi bi-person-badge me-1"></i>{{ $story['candidate'] }}
                            </span>
                        </a>
                    @else
                        <strong>{{ $story['candidate'] }}</strong>
                    @endif
                    prosi o kontakt.
                </p>

                @if($story['due'])
                    <p class="small text-muted mb-3">
                        <i class="bi bi-calendar-event me-1"></i>Termin: {{ $story['due']->format('d.m.Y') }}
                    </p>
                @endif

                @if($story['note'] !== '')
                    <div class="mb-1 small text-muted">Notatka:</div>
                    <blockquote class="mb-0 px-3 py-2 rounded-3" style="background: rgba(59, 130, 246, 0.08); border-left: 3px solid var(--primary); white-space:pre-wrap">
                        {{ $story['note'] }}
                    </blockquote>
                @endif
            @else
                <p class="text-muted mb-0">{{ $task->name }}</p>
            @endif
        </x-ui.card>

        <x-ui.card label="Odhacz" class="mt-4">
            <form action="{{ route('tasks.toggle-done', $task) }}" method="POST">
                @csrf
                <label class="d-flex align-items-center gap-3 mb-0" style="cursor:pointer">
                    <input type="checkbox"
                           class="form-check-input m-0"
                           style="width:1.25rem;height:1.25rem"
                           @checked($done)
                           onchange="this.form.submit()">
                    <span class="fw-semibold">{{ $done ? 'Zrobione' : 'Oznacz jako zrobione' }}</span>
                </label>
            </form>
        </x-ui.card>

        @if($process)
            <x-ui.card label="Notatka na karcie kandydata" class="mt-4">
                <p class="small text-muted mb-3">
                    Wpis pojawi się w komentarzach procesu rekrutacji
                    @if($story['contextUrl'] ?? null)
                        (<a href="{{ $story['contextUrl'] }}">{{ $story['candidate'] }}</a>).
                    @endif
                </p>
                <form action="{{ route('comments.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="commentable_type" value="{{ \App\Enums\CommentableType::RECRUITMENT_PROCESS->value }}">
                    <input type="hidden" name="commentable_id" value="{{ $process->id }}">

                    <div class="mb-3">
                        <textarea
                            name="body"
                            rows="3"
                            class="form-control @error('body') is-invalid @enderror"
                            placeholder="Co ustaliliście / kiedy oddzwonić ponownie…"
                            required
                        >{{ old('body') }}</textarea>
                        @error('body')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <x-ui.button variant="primary" type="submit" action="save">
                        Dodaj na kartę
                    </x-ui.button>
                </form>
            </x-ui.card>
        @endif
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
                    @else
                        <span class="text-muted">Poza sprintem</span>
                    @endif
                </div>
            </div>
        </x-ui.card>
    </div>
</div>
