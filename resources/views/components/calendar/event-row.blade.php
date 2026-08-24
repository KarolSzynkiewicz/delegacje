@props([
    'event',
    'layer' => null,
])

@php
    $style = $layer ? '--rc-c: '.$layer->color().'; --rc-c-rgb: '.$layer->rgb().';' : '';

    $range = $event->isMultiDay()
        ? $event->start->format('d.m.Y').' – '.($event->openEnded ? 'bez końca' : $event->end->format('d.m.Y'))
        : $event->start->format('d.m.Y');

    $meta = collect([
        $layer?->label(),
        $event->subtitle,
        $range,
    ])->filter();
@endphp

<div class="rc-row" style="{{ $style }}">
    <span class="rc-row__dot" aria-hidden="true"></span>

    <div class="rc-row__main">
        <div class="rc-row__title">
            @if($event->timeLabel)
                <span class="rc-row__time font-mono">{{ $event->timeLabel }}</span>
            @endif

            @if($event->url)
                <a href="{{ $event->url }}" class="stretched-link">{{ $event->title }}</a>
            @else
                {{ $event->title }}
            @endif
        </div>

        <div class="rc-row__meta">{{ $meta->implode(' · ') }}</div>
    </div>

    @if($event->badge)
        <x-ui.badge variant="secondary" class="rc-row__badge">{{ $event->badge }}</x-ui.badge>
    @endif
</div>
