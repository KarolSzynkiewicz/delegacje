@props([
    'href',
    'size' => 'sm',
    'title' => 'Edytuj',
])

@php
    $sizeClass = $size === 'sm' ? 'btn-sm' : ($size === 'lg' ? 'btn-lg' : '');
@endphp

<a href="{{ $href }}" 
   class="btn btn-outline-secondary {{ $sizeClass }}" 
   title="{{ $title }}">
    <i class="bi bi-pencil"></i>
    @if($slot->isNotEmpty())
        <span class="ms-1">{{ $slot }}</span>
    @endif
</a>
