@php
    $dayEvents = $day['events'] ?? [];

    $grouped = collect($dayEvents)->groupBy(function ($event) use ($layers) {
        return $layers[$event->layer]?->group() ?? 'Inne';
    });
@endphp

<div class="rc-dayview">
    <div class="rc-dayview__head @if($day && $day['is_today']) is-today @endif">
        <div class="rc-dayview__date">
            {{ $day ? ucfirst($day['date']->locale('pl')->translatedFormat('l')) : '' }}
            <span class="font-mono">{{ $day ? $day['date']->format('d.m.Y') : '' }}</span>
        </div>
        <span class="rc-dayview__count font-mono">{{ count($dayEvents) }}</span>
    </div>

    @forelse($grouped as $groupName => $groupEvents)
        <div class="rc-dayview__group">
            <div class="rc-dayview__group-name">{{ $groupName }}</div>

            @foreach($groupEvents as $event)
                <x-calendar.event-row
                    :event="$event"
                    :layer="$layers[$event->layer] ?? null"
                />
            @endforeach
        </div>
    @empty
        <x-ui.empty-state
            icon="calendar3"
            message="Brak zdarzeń w wybranych warstwach tego dnia."
        />
    @endforelse
</div>
