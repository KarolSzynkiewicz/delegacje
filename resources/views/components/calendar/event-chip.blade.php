@props([
    'event',
    'layer' => null,
    'continuesBefore' => false,
    'continuesAfter' => false,
])

@php
    $style = $layer ? '--rc-c: '.$layer->color().'; --rc-c-rgb: '.$layer->rgb().';' : '';

    $range = $event->isMultiDay()
        ? $event->start->format('d.m').' – '.($event->openEnded ? 'bez końca' : $event->end->format('d.m.Y'))
        : $event->start->format('d.m.Y');

    $tooltip = collect([
        $layer?->label(),
        $event->title,
        $event->subtitle,
        $range,
        $event->badge,
    ])->filter()->implode(' · ');

    $classes = 'rc-chip'
        .($continuesBefore ? ' is-cont-before' : '')
        .($continuesAfter ? ' is-cont-after' : '');

    $tag = $event->url ? 'a' : 'span';
@endphp

<{{ $tag }}
    @if($event->url) href="{{ $event->url }}" @endif
    class="{{ $classes }}"
    style="{{ $style }}"
    title="{{ $tooltip }}"
>
    @if($continuesBefore)
        <i class="bi bi-caret-left-fill rc-chip__cont" aria-hidden="true"></i>
    @endif

    <span class="rc-chip__text">
        @if($event->timeLabel && ! $event->isMultiDay())
            <span class="rc-chip__time font-mono">{{ $event->timeLabel }}</span>
        @endif
        {{ $event->title }}
        @if($event->subtitle)
            <span class="rc-chip__sub">{{ $event->subtitle }}</span>
        @endif
    </span>

    @if($continuesAfter)
        <i class="bi bi-caret-right-fill rc-chip__cont" aria-hidden="true"></i>
    @endif
</{{ $tag }}>
