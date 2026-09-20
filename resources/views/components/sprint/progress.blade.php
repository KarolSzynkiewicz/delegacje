@props([
    'sprint',
    'size' => 'sm',
    'details' => null,
    'layout' => null,
])

@php
    $snapshot = $sprint->completionSnapshot();
    $size = $size === 'lg' ? 'lg' : 'sm';
    $details = $details ?? ($size === 'lg' ? 'always' : 'hover');
    $layout = $layout === 'side' ? 'side' : 'stacked';
@endphp

<div
    {{ $attributes->class([
        'sp-progress',
        'sp-progress--'.$size,
        'sp-progress--'.$details,
        'sp-progress--'.$layout => $details === 'always',
    ]) }}
    @if($details === 'hover')
        tabindex="0"
    @endif
>
    <div class="sp-progress__dial">
        <div class="sp-progress__wheel" style="background: {{ $snapshot['gradient'] }}" aria-hidden="true"></div>
        <div class="sp-progress__center">
            <strong>{{ $snapshot['percent'] }}%</strong>
            @if($size === 'lg')
                <span>ukończone</span>
            @endif
        </div>
    </div>

    <div class="sp-progress__details" @if($details === 'hover') role="tooltip" @endif>
        <div class="sp-progress__details-title">
            <i class="bi bi-pie-chart"></i>
            Szczegóły
        </div>
        @foreach($snapshot['metrics'] as $metric)
            <div class="sp-progress__row">
                <i class="bi bi-{{ $metric['icon'] }}" style="color: {{ $metric['color'] }}"></i>
                <span class="sp-progress__label">{{ $metric['label'] }}</span>
                <span class="sp-progress__track"><span style="width: {{ $metric['pct'] }}%; background: {{ $metric['color'] }}"></span></span>
                <span class="sp-progress__count font-mono">{{ $metric['total'] ? $metric['done'].'/'.$metric['total'] : '—' }}</span>
                <span class="sp-progress__pct font-mono">{{ $metric['pct'] }}%</span>
            </div>
        @endforeach
    </div>
</div>
