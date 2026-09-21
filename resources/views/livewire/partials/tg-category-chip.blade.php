@php
    $chipClass = $chipClass ?? '';
    $ediCategory = $this->ediCell($task, 'category');
    $categoryClick = $task->category
        ? 'filterByCategory('.\Illuminate\Support\Js::from($task->category).')'
        : 'startEdit('.$task->id.', \'category\')';
    $categoryEdit = 'startEdit('.$task->id.', \'category\')';
@endphp
@if($ediCategory)
    @include('livewire.partials.tasks-grid-edi-value', ['diff' => $ediCategory, 'rowId' => $task->id, 'field' => 'category'])
@elseif($isEditing && $editingField === 'category')
    <input type="text" wire:model="editingValue" class="form-control form-control-sm"
           wire:keydown.enter="saveEdit" wire:keydown.escape="cancelEdit" wire:blur="saveEdit"
           x-data x-init="$el.focus(); $el.select()">
@elseif($this->rowWritable($task, 'category'))
    <x-tasks.col-chip
        variant="category"
        :class="$chipClass"
        :main-click="$categoryClick"
        :main-tip="$task->category ? 'Pokaż tę kategorię' : 'Wpisz kategorię'"
        side="edit"
        :side-click="$categoryEdit"
        :side-tip="$task->category ? 'Edytuj kategorię' : 'Wpisz kategorię'"
    >{{ $task->category ?: '—' }}</x-tasks.col-chip>
@elseif($task->category)
    <x-tasks.col-chip
        variant="category"
        :class="$chipClass"
        :main-click="$categoryClick"
        main-tip="Pokaż tę kategorię"
    >{{ $task->category }}</x-tasks.col-chip>
@else
    <span class="text-muted" style="font-size:0.82rem">—</span>
@endif
