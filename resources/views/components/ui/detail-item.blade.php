@props([
    'label' => null,
    'fullWidth' => false,
    'class' => 'mb-3',
])

<div class="{{ $fullWidth ? 'col-12' : 'col-md-6' }} {{ $class }}">
    @if($label || isset($labelSlot))
        <dt class="fw-semibold mb-1">
            @if(isset($labelSlot))
                {{ $labelSlot }}
            @else
                {{ $label }}
            @endif
        </dt>
    @endif
    <dd>{{ $slot }}</dd>
</div>
