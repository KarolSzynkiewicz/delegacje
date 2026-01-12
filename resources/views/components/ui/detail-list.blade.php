@props([
    'class' => 'mb-0',
])

<dl class="row {{ $class }}">
    {{ $slot }}
</dl>
