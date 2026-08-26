@props([
    'task',
    'size' => 'sm',
    'gap' => '1',
    'class' => '',
    'isMineView' => false,
    'showView' => true,
    'showEdit' => true,
    // compact: same ikonki co na mobile; tekst tylko od md w górę gdy false
    'compact' => false,
])

@php
    $sizeClass = $size === 'sm' ? 'btn-sm' : ($size === 'lg' ? 'btn-lg' : '');
    $wrapClass = trim('d-flex align-items-center gap-'.$gap.' '.$class);
    $labelClass = $compact ? 'd-none' : 'd-none d-md-inline';
@endphp

<div class="{{ $wrapClass }} task-actions" role="group">
    @if($showView)
        <x-ui.button
            variant="ghost"
            href="{{ route('tasks.show', $task) }}"
            routeName="tasks.show"
            action="view"
            class="{{ $sizeClass }}"
        />
    @endif
    @if($showEdit && ! $isMineView)
        <x-ui.button
            variant="ghost"
            href="{{ route('tasks.edit', $task) }}"
            routeName="tasks.edit"
            action="edit"
            class="{{ $sizeClass }}"
        />
    @endif

    @if($task->status === \App\Enums\TaskStatus::PENDING)
        <form action="{{ route('tasks.mark-in-progress', $task) }}" method="POST" class="d-inline">
            @csrf
            <x-ui.button variant="info" type="submit" class="{{ $sizeClass }} task-actions__btn" title="Rozpocznij">
                <i class="bi bi-play-circle"></i>
                <span class="{{ $labelClass }}">Rozpocznij</span>
            </x-ui.button>
        </form>
    @endif

    @if($task->status === \App\Enums\TaskStatus::IN_PROGRESS)
        <form action="{{ route('tasks.mark-completed', $task) }}" method="POST" class="d-inline">
            @csrf
            <x-ui.button variant="success" type="submit" class="{{ $sizeClass }} task-actions__btn" title="Zakończ">
                <i class="bi bi-check-circle"></i>
                <span class="{{ $labelClass }}">Zakończ</span>
            </x-ui.button>
        </form>
    @endif

    @if($task->status !== \App\Enums\TaskStatus::CANCELLED && $task->status !== \App\Enums\TaskStatus::COMPLETED)
        <form action="{{ route('tasks.cancel', $task) }}" method="POST" class="d-inline"
              onsubmit="return confirm('Anulować to zadanie?')">
            @csrf
            <x-ui.button variant="danger" type="submit" class="{{ $sizeClass }} task-actions__btn" title="Anuluj zadanie">
                <i class="bi bi-x-circle"></i>
                <span class="{{ $labelClass }}">Anuluj</span>
            </x-ui.button>
        </form>
    @endif
</div>
