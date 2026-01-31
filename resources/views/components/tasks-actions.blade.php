@props([
    'task',
    'project',
    'size' => 'sm',
    'gap' => '1',
    'class' => '',
    'isMineView' => false,
])

@php
    $sizeClass = $size === 'sm' ? 'btn-sm' : ($size === 'lg' ? 'btn-lg' : '');
@endphp

<div class="d-flex gap-{{ $gap }} {{ $class }}" role="group">
    <x-ui.button 
        variant="ghost" 
        href="{{ route('projects.tasks.show', [$project, $task]) }}"
        routeName="projects.tasks.show"
        action="view"
        class="{{ $sizeClass }}"
    />
    @if(!$isMineView)
    <x-ui.button 
        variant="ghost" 
        href="{{ route('projects.tasks.edit', [$project, $task]) }}"
        routeName="projects.tasks.edit"
        action="edit"
        class="{{ $sizeClass }}"
    />
    @endif
    
    @if($task->status === \App\Enums\TaskStatus::PENDING)
        <form action="{{ route('projects.tasks.mark-in-progress', [$project, $task]) }}" method="POST" class="d-inline">
            @csrf
            <x-ui.button variant="info" type="submit" class="{{ $sizeClass }}" title="Rozpocznij">
                <i class="bi bi-play-circle"></i> Start progress
            </x-ui.button>
        </form>
    @endif
    
    @if($task->status === \App\Enums\TaskStatus::IN_PROGRESS)
        <form action="{{ route('projects.tasks.mark-completed', [$project, $task]) }}" method="POST" class="d-inline">
            @csrf
            <x-ui.button variant="success" type="submit" class="{{ $sizeClass }}" title="Zakończ">
                <i class="bi bi-check-circle"></i> Complete
            </x-ui.button>
        </form>
    @endif
    
    @if($task->status !== \App\Enums\TaskStatus::CANCELLED && $task->status !== \App\Enums\TaskStatus::COMPLETED)
        <form action="{{ route('projects.tasks.cancel', [$project, $task]) }}" method="POST" class="d-inline">
            @csrf
            <x-ui.button variant="danger" type="submit" class="{{ $sizeClass }}" title="Anuluj zadanie">
                <i class="bi bi-x-circle"></i> Cancel
            </x-ui.button>
        </form>
    @endif
</div>
