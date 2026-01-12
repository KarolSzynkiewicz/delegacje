@props([
    'gap' => '1',
    'class' => '',
])

<div class="d-flex gap-{{ $gap }} {{ $class }}">
    {{ $slot }}
</div>
