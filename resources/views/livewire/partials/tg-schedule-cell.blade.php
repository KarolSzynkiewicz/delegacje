@php
    $scheduleState = $item->scheduleState();
    $scheduleLabel = $item->scheduleChipLabel();
    $scheduleUrl = $item->planPinUrl();
    $assignFirst = ($assignFirst ?? false)
        && $item->assignee_id === null
        && $this->rowWritable($item, 'assigned_to');
    $scheduleTitle = match (true) {
        $assignFirst => 'Najpierw przypisz osobę — bez tego nie wejdzie do planu',
        default => $item->scheduleHoverTip(),
    };
    $chipClass = 'tg-time-chip tg-time-chip--cal tg-time-chip--'.$scheduleState.' tg-schedule tg-schedule--'.$scheduleState.' tg-dt-hit';
    $showSlotList = (bool) ($showSlotList ?? false);
    $slotPills = $showSlotList && $item->scheduleSlotCount() > 1 ? $item->schedulePills() : [];
@endphp
<div @class(['tg-schedule-stack' => $slotPills !== []])>
@if($assignFirst)
    <button type="button"
            class="{{ $chipClass }}"
            @if($assignClickStop ?? false) wire:click.stop="startEdit({{ $item->id }}, 'assigned_to')"
            @else wire:click="startEdit({{ $item->id }}, 'assigned_to')" @endif
            data-tip="{{ $scheduleTitle }}"
            aria-label="{{ $scheduleTitle }}">
        <span class="tg-time-chip__icon" aria-hidden="true">
            <i class="bi bi-calendar-event"></i>
        </span>
        <span class="tg-time-chip__label">{{ $scheduleLabel }}</span>
        <i class="bi bi-chevron-right tg-time-chip__go" aria-hidden="true"></i>
    </button>
@else
    <a href="{{ $scheduleUrl }}"
       class="{{ $chipClass }}"
       data-tip="{{ $scheduleTitle }}">
        <span class="tg-time-chip__icon" aria-hidden="true">
            <i class="bi bi-calendar-event"></i>
        </span>
        <span class="tg-time-chip__label">{{ $scheduleLabel }}</span>
        <i class="bi bi-chevron-right tg-time-chip__go" aria-hidden="true"></i>
    </a>
@endif
@if($slotPills !== [])
    <ul class="tg-schedule-slots">
        @foreach($slotPills as $pill)
            <li>{{ $pill }}</li>
        @endforeach
    </ul>
@endif
</div>
