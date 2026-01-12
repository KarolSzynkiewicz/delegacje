@props([
    'field',
    'sortField' => null,
    'sortDirection' => 'asc',
    'wireClick' => null, // e.g., "sortBy('name')"
])

@php
    $isActive = $sortField === $field;
    $wireClick = $wireClick ?? "sortBy('{$field}')";
@endphp

<th {{ $attributes->merge(['class' => 'text-start']) }}>
    <button wire:click="{{ $wireClick }}" 
            class="btn-link text-decoration-none p-0 fw-semibold d-flex align-items-center gap-1" 
            style="background: none; border: none; color: var(--text-main); cursor: pointer;">
        <span>{{ $slot }}</span>
        @if($isActive)
            @if($sortDirection === 'asc')
                <i class="bi bi-chevron-up"></i>
            @else
                <i class="bi bi-chevron-down"></i>
            @endif
        @else
            <i class="bi bi-chevron-expand text-muted"></i>
        @endif
    </button>
</th>
