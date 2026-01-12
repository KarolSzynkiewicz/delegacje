@props([
    'imageUrl' => null,
    'alt' => '',
    'initials' => '',
    'size' => '50px',
    'shape' => 'circle', // circle, square, rounded
    'border' => true,
])

@php
    $sizeValue = is_numeric($size) ? $size . 'px' : $size;
    $shapeClass = match($shape) {
        'circle' => 'rounded-circle',
        'square' => 'rounded-0',
        'rounded' => 'rounded',
        default => 'rounded-circle'
    };
    $borderClass = $border ? 'border border-2' : '';
@endphp

@if($imageUrl)
    <img src="{{ $imageUrl }}" 
         alt="{{ $alt }}" 
         class="{{ $shapeClass }} {{ $borderClass }}"
         style="width: {{ $sizeValue }}; height: {{ $sizeValue }}; object-fit: cover;">
@else
    <div class="avatar-ui {{ $shapeClass }} {{ $borderClass }}" 
         style="width: {{ $sizeValue }}; height: {{ $sizeValue }};">
        <span>{{ $initials }}</span>
    </div>
@endif
