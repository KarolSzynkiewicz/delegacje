@props([
    'label' => null,
    'fullWidth' => false,
    'class' => 'mb-3',
])

<div class="{{ $fullWidth ? 'col-12' : 'col-md-6' }} {{ $class }}">
    @if($label)
        <dt class="fw-semibold mb-1">{{ $label }}</dt>
    @endif
    <dd>{{ $slot }}</dd>
</div>
