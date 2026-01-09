@props([
    'title',
    'subtitle' => null,
])

<div class="d-flex justify-content-between align-items-center mb-0">
    <div>
        <h2 class="h4 fw-semibold text-dark mb-0">{{ $title }}</h2>
        @if($subtitle)
            <p class="text-muted small mb-0 mt-1">{{ $subtitle }}</p>
        @endif
    </div>
    
    @isset($actions)
        <div class="d-flex gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
