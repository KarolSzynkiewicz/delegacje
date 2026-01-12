@props([
    'title' => null,
    'subtitle' => null,
    'class' => 'mb-3',
])

<div class="d-flex justify-content-between align-items-center {{ $class }}">
    <div>
        @if($title)
            <h5 class="mb-0">{{ $title }}</h5>
        @endif
        @if($subtitle)
            <p class="small text-muted mb-0 mt-1">{{ $subtitle }}</p>
        @endif
        {{ $titleSlot ?? '' }}
    </div>
    @if(isset($actions))
        <div class="d-flex gap-2">
            {{ $actions }}
        </div>
    @elseif($slot->isNotEmpty())
        <div class="d-flex gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
