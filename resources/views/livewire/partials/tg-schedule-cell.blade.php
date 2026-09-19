@php
    $scheduleState = $item->scheduleState();
    $scheduleLabel = $item->scheduleLabel();
    $scheduleUrl = $item->planPinUrl();
    $scheduleTitle = match ($scheduleState) {
        'stale' => 'Slot przed dniem dzisiejszym — otwórz plan',
        'scheduled' => 'Otwórz plan przy tym slocie',
        default => 'Zaplanuj w kalendarzu',
    };
@endphp
<a href="{{ $scheduleUrl }}"
   class="tg-schedule tg-schedule--{{ $scheduleState }} tg-dt-hit"
   title="{{ $scheduleTitle }}">
    {{ $scheduleLabel }}
</a>
