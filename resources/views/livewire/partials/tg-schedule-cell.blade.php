@php
    $scheduleState = $item->scheduleState();
    $scheduleLabel = $item->scheduleChipLabel();
    $scheduleUrl = $item->planPinUrl();
    $assignFirst = ($assignFirst ?? false)
        && $item->assignee_id === null
        && $this->rowWritable($item, 'assigned_to');
    $scheduleFilter = method_exists($this, 'pinClick')
        ? $this->pinClick('filterSchedule', $scheduleState)
        : null;
    $scheduleExclude = $scheduleFilter
        ? $this->pinClick('filterSchedule', $scheduleState, 'neq')
        : null;
    $scheduleHref = $scheduleFilter
        ? null
        : \App\Support\TasksGridUrlParams::gridUrl(['schedule' => $scheduleState]);
    $scheduleMainTip = match ($scheduleState) {
        'stale' => 'Pokaż zaległe',
        'scheduled' => 'Pokaż zaplanowane',
        default => 'Pokaż bez slotu',
    };
    $scheduleExcludeTip = match ($scheduleState) {
        'stale' => 'Ukryj zaległe',
        'scheduled' => 'Ukryj zaplanowane',
        default => 'Pokaż ze slotem',
    };
    $scheduleTitle = match (true) {
        $assignFirst => 'Najpierw przypisz osobę — bez tego nie wejdzie do planu',
        default => $item->scheduleHoverTip(),
    };
    $chipClass = 'tg-time-chip tg-time-chip--cal tg-time-chip--'.$scheduleState.' tg-schedule tg-schedule--'.$scheduleState.' tg-dt-hit';
    $showSlotList = (bool) ($showSlotList ?? false);
    $slotPills = $showSlotList && $item->scheduleSlotCount() > 1 ? $item->schedulePills() : [];
    $side = $assignFirst ? 'edit' : 'go';
    $sideClick = $assignFirst ? 'startEdit('.$item->id.', \'assigned_to\')' : null;
    $sideHref = $assignFirst ? null : $scheduleUrl;
@endphp
<div @class(['tg-schedule-stack' => $slotPills !== []])>
    <x-tasks.chip
        :class="$chipClass"
        :href="$scheduleHref"
        :main-click="$scheduleFilter"
        :main-tip="$scheduleMainTip"
        :exclude-click="$scheduleExclude"
        :exclude-tip="$scheduleExcludeTip"
        :side="$side"
        :side-click="$sideClick"
        :side-href="$sideHref"
        :side-tip="$scheduleTitle"
    >
        <span class="tg-time-chip__icon" aria-hidden="true">
            <i class="bi bi-calendar-event"></i>
        </span>
        <span class="tg-col-chip__label tg-time-chip__label">{{ $scheduleLabel }}</span>
    </x-tasks.chip>
@if($slotPills !== [])
    <ul class="tg-schedule-slots">
        @foreach($slotPills as $pill)
            <li>{{ $pill }}</li>
        @endforeach
    </ul>
@endif
</div>
