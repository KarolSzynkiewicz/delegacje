@props([
    'href',
    'size' => 'sm',
    'title' => 'Zobacz',
])

@php
    $sizeClass = $size === 'sm' ? 'btn-sm' : ($size === 'lg' ? 'btn-lg' : '');
@endphp

<x-ui.button variant="ghost" href="{{ $href }}" title="{{ $title }}" class="{{ $sizeClass }}">
    <i class="bi bi-eye"></i>
    @if($slot->isNotEmpty())
        <span class="ms-1">{{ $slot }}</span>
    @endif
</x-ui.button>
