@php
    $showSubtasks = $showSubtasks ?? true;
    $showComments = $showComments ?? true;
    $showActivity = $showActivity ?? false;
    $wireKey = $wireKey ?? 'task-'.$task->id;
@endphp

<x-ui.card class="dt-card">
    <livewire:task-show-quick-edit :task="$task" wire:key="{{ $wireKey }}-qe" />
</x-ui.card>

@if($showSubtasks)
    <div class="mt-4">
        <livewire:task-subtasks :task="$task" wire:key="{{ $wireKey }}-st" />
    </div>
@endif

@if($showComments)
    <div class="mt-4">
        <x-comments :commentable="$task" />
    </div>
@endif

@if($showActivity)
    <div class="mt-4">
        <livewire:task-activity :task="$task" wire:key="{{ $wireKey }}-activity" />
    </div>
@endif
