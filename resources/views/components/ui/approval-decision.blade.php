@props([
    'decision' => null,
    'size' => 'sm',
    'withLabel' => false,
])

@php
    $decision = $decision instanceof \App\Enums\ApprovalDecision ? $decision : null;
    $font = match ($size) {
        'lg' => '1.85rem',
        'md' => '1.15rem',
        default => '0.72rem',
    };
    if ($decision === \App\Enums\ApprovalDecision::Approved) {
        $icon = 'bi-check-circle-fill';
        $color = '#34d399';
        $label = 'Zatwierdzone';
    } elseif ($decision === \App\Enums\ApprovalDecision::Rejected) {
        $icon = 'bi-slash-circle';
        $color = '#f87171';
        $label = 'Odrzucone';
    } else {
        $icon = 'bi-hourglass-split';
        $color = '#f59e0b';
        $label = 'Oczekuje';
    }
@endphp

<span {{ $attributes->class('tg-approval-mark d-inline-flex align-items-center gap-2 flex-shrink-0') }}
      style="color:{{ $color }}; line-height:1"
      title="{{ $label }}"
      aria-label="{{ $label }}">
    <i class="bi {{ $icon }}" style="font-size:{{ $font }}"></i>
    @if($withLabel)
        <span class="fw-semibold" style="font-size:{{ $size === 'lg' ? '1.05rem' : '0.85rem' }}; color:{{ $color }}">{{ $label }}</span>
    @endif
</span>
