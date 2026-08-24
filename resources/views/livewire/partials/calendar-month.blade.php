@php
    $weekdays = ['Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'Sob', 'Ndz'];
@endphp

<div class="rc-month">
    <div class="rc-weekdays">
        @foreach($weekdays as $weekday)
            <div class="rc-weekday">{{ $weekday }}</div>
        @endforeach
    </div>

    @foreach($weeks as $week)
        @include('livewire.partials.calendar-week-band', ['week' => $week, 'compact' => true])
    @endforeach
</div>
