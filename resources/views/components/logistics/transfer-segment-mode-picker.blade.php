{{-- Wybór: transport publiczny vs własny — slot na przyciski (wire:click w rodzicu). --}}
@props([
    'intro' => '',
])
<div class="d-grid gap-2 transfer-segment-mode-picker">
    @if($intro !== '')
        <div class="small text-muted mb-1">{{ $intro }}</div>
    @endif
    {{ $slot }}
</div>
