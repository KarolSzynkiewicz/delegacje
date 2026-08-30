@props([
    'field',
    'selected',
    'toggle',
    'clear',
    'hint',
])
@php
    /** @var list<string> $selected */
    $selected = is_array($selected) ? $selected : [];
    $authId = auth()->id();
@endphp
<span class="rp-filter-hint">{{ $hint }}</span>
@include('livewire.partials.tg-filter-op', ['field' => $field])
<div class="rp-filter-chips mb-1">
    <button type="button" wire:click="{{ $clear }}"
            class="rp-filter-chip {{ $selected === [] ? 'is-active' : '' }}">Wszyscy</button>
</div>
<div class="rp-filter-scroll">
    <button type="button" wire:click="{{ $toggle }}('me')"
            class="rp-filter-option {{ in_array('me', $selected, true) ? 'is-active' : '' }}">
        <span class="rp-filter-check {{ in_array('me', $selected, true) ? 'is-checked' : '' }}"><i class="bi bi-check"></i></span>
        <span class="rp-filter-option__label">Ja ({{ auth()->user()->name }})</span>
    </button>
    @foreach($allUsers as $u)
        @if($u->id !== $authId)
            <button type="button" wire:click="{{ $toggle }}('{{ $u->id }}')"
                    class="rp-filter-option {{ in_array((string) $u->id, $selected, true) ? 'is-active' : '' }}">
                <span class="rp-filter-check {{ in_array((string) $u->id, $selected, true) ? 'is-checked' : '' }}"><i class="bi bi-check"></i></span>
                <span class="rp-filter-option__label">{{ $u->name }}</span>
            </button>
        @endif
    @endforeach
</div>
