@props([
    'header' => null,
    'footer' => null,
    'shadow' => true, // true, false, sm, lg
    'border' => true, // true, false, 0
])

@php
    $classes = 'card';
    
    if ($shadow === true) {
        $classes .= ' shadow-sm';
    } elseif ($shadow === 'sm') {
        $classes .= ' shadow-sm';
    } elseif ($shadow === 'lg') {
        $classes .= ' shadow-lg';
    }
    
    if ($border === false || $border === '0') {
        $classes .= ' border-0';
    }
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    @if($header)
        <div class="card-header bg-white border-bottom">
            {{ $header }}
        </div>
    @endif
    
    <div class="card-body">
        {{ $slot }}
    </div>
    
    @if($footer)
        <div class="card-footer bg-light">
            {{ $footer }}
        </div>
    @endif
</div>
