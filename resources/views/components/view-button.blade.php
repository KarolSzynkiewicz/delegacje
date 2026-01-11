@props([
    'href',
    'size' => 'sm',
    'title' => 'Zobacz',
])

@php
    $sizeClass = $size === 'sm' ? 'btn-sm' : ($size === 'lg' ? 'btn-lg' : '');
@endphp

<a href="{{ $href }}" 
   class="btn btn-outline-primary {{ $sizeClass }}" 
   title="{{ $title }}">
    <i class="bi bi-eye"></i>
    @if($slot->isNotEmpty())
        <span class="ms-1">{{ $slot }}</span>
    @endif
</a>
