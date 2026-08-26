<x-ui.card class="dt-card">
    <livewire:task-show-quick-edit :task="$task" wire:key="task-show-qe-{{ $task->id }}" />
</x-ui.card>

<div class="mt-4">
    <livewire:task-subtasks :task="$task" />
</div>

<div class="mt-4">
    <x-comments :commentable="$task" />
</div>

<div class="mt-4">
    <livewire:task-activity :task="$task" :key="'task-activity-'.$task->id" />
</div>
