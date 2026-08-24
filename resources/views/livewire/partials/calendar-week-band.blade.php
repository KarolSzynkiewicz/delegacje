@php
    /**
     * Jeden tydzień jako siatka 7 kolumn: komórki dni w tle (wiersz 1 do końca),
     * numery dni w wierszu 1, a paski zdarzeń w kolejnych wierszach (pasach).
     */
    $lanes = $week['lanes'];
    $laneRows = $lanes > 0 ? "repeat({$lanes}, var(--rc-bar-h)) " : '';
    $templateRows = 'auto '.$laneRows.'auto';
    $compact = $compact ?? true;
@endphp

<div class="rc-week @if(! $compact) rc-week--tall @endif" style="grid-template-rows: {{ $templateRows }};">

    @foreach($week['days'] as $index => $day)
        @php
            $cellClasses = collect([
                'rc-cell',
                $day['in_period'] ? null : 'is-out',
                $day['is_today'] ? 'is-today' : null,
                $day['is_weekend'] ? 'is-weekend' : null,
            ])->filter()->implode(' ');

            $dotLayers = collect($day['events'])->pluck('layer')->unique()->take(6);
        @endphp

        <div
            class="{{ $cellClasses }}"
            style="grid-column: {{ $index + 1 }}; grid-row: 1 / -1;"
            wire:key="rc-cell-{{ $day['date']->toDateString() }}"
        >
            <div class="rc-cell__dots">
                @foreach($dotLayers as $layerKey)
                    @if($layers->has($layerKey))
                        <span class="rc-dot" style="--rc-c: {{ $layers[$layerKey]->color() }};"></span>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach

    @foreach($week['days'] as $index => $day)
        @php
            $numClasses = 'rc-daynum'
                .($day['is_today'] ? ' is-today' : '')
                .($day['in_period'] ? '' : ' is-out');
        @endphp

        <button
            type="button"
            class="{{ $numClasses }}"
            style="grid-column: {{ $index + 1 }}; grid-row: 1;"
            wire:click="goToDay('{{ $day['date']->toDateString() }}')"
            wire:key="rc-num-{{ $day['date']->toDateString() }}"
            title="Pokaż dzień {{ $day['date']->format('d.m.Y') }}"
        >
            @if($compact)
                {{ $day['date']->day }}
            @else
                <span class="rc-daynum__name">{{ ucfirst($day['date']->locale('pl')->translatedFormat('D')) }}</span>
                <span class="font-mono">{{ $day['date']->format('d.m') }}</span>
            @endif
        </button>
    @endforeach

    @foreach($week['bars'] as $bar)
        @php
            $barClasses = 'rc-bar'
                .($bar['continues_before'] ? ' is-cont-before' : '')
                .($bar['continues_after'] ? ' is-cont-after' : '');
        @endphp

        <div
            class="{{ $barClasses }}"
            style="grid-column: {{ $bar['col'] }} / span {{ $bar['span'] }}; grid-row: {{ $bar['lane'] + 2 }};"
            wire:key="rc-bar-{{ $bar['event']->key() }}-{{ $week['days'][0]['date']->toDateString() }}"
        >
            <x-calendar.event-chip
                :event="$bar['event']"
                :layer="$layers[$bar['event']->layer] ?? null"
                :continues-before="$bar['continues_before']"
                :continues-after="$bar['continues_after']"
            />
        </div>
    @endforeach

    @foreach($week['days'] as $index => $day)
        @php $extra = $week['overflow'][$day['date']->toDateString()] ?? 0; @endphp

        @if($extra > 0)
            <button
                type="button"
                class="rc-more"
                style="grid-column: {{ $index + 1 }}; grid-row: {{ $lanes + 2 }};"
                wire:click="goToDay('{{ $day['date']->toDateString() }}')"
                wire:key="rc-more-{{ $day['date']->toDateString() }}"
            >+{{ $extra }} więcej</button>
        @endif
    @endforeach
</div>
