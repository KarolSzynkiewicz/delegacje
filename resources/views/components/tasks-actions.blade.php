@props([
    'task',
    'size' => 'sm',
    'gap' => '1',
    'class' => '',
    'isMineView' => false,
    'showView' => true,
    'showEdit' => true,
])

@php
    $sizeClass = $size === 'sm' ? 'btn-sm' : ($size === 'lg' ? 'btn-lg' : '');
@endphp

<div class="d-flex gap-{{ $gap }} {{ $class }}" role="group">
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
            <x-ui.button variant="info" type="submit" class="{{ $sizeClass }}" title="Rozpocznij">
                <i class="bi bi-play-circle"></i> Rozpocznij
            </x-ui.button>
        </form>
    @endif
    
    @if($task->status === \App\Enums\TaskStatus::IN_PROGRESS)
        <form action="{{ route('tasks.mark-completed', $task) }}" method="POST" class="d-inline">
            @csrf
            <x-ui.button variant="success" type="submit" class="{{ $sizeClass }}" title="Zakończ">
                <i class="bi bi-check-circle"></i> Zakończ
            </x-ui.button>
        </form>
    @endif
    
    @if($task->status !== \App\Enums\TaskStatus::CANCELLED && $task->status !== \App\Enums\TaskStatus::COMPLETED)
        <form action="{{ route('tasks.cancel', $task) }}" method="POST" class="d-inline">
            @csrf
            <x-ui.button variant="danger" type="submit" class="{{ $sizeClass }}" title="Anuluj zadanie">
                <i class="bi bi-x-circle"></i> Anuluj
            </x-ui.button>
        </form>
    @endif
</div>
