@props([
    'field',
    'eqLabel' => 'jest',
    'neqLabel' => 'nie jest',
])
@php
    $op = $filterOps[$field] ?? 'eq';
@endphp
<div class="rp-filter-ops">
    <button type="button" wire:click="setFilterOp('{{ $field }}', 'eq')"
            class="rp-filter-op {{ $op === 'eq' ? 'is-active' : '' }}" @click.stop>
        {{ $eqLabel }}
    </button>
    <button type="button" wire:click="setFilterOp('{{ $field }}', 'neq')"
            class="rp-filter-op {{ $op === 'neq' ? 'is-active' : '' }}" @click.stop>
        {{ $neqLabel }}
    </button>
</div>
