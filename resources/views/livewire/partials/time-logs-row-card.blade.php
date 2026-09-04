@props(['timeLog', 'hideEmployee' => false])

<x-ui.card class="dt-card" wire:key="time-log-card-{{ $timeLog->id }}">
    <div class="dt-card__title mb-2">
        {{ $timeLog->projectAssignment?->project?->name ?? 'Wpis godzin' }}
        <a href="{{ route('time-logs.show', $timeLog) }}" class="stretched-link visually-hidden">Szczegóły wpisu</a>
    </div>

    <div class="dt-card__row">
        <span class="dt-card__label">Data</span>
        <span class="dt-card__value">{{ $timeLog->start_time->format('d.m.Y H:i') }}</span>
    </div>

    @unless($hideEmployee)
        <div class="dt-card__row">
            <span class="dt-card__label">Pracownik</span>
            <span class="dt-card__value">
                @if($timeLog->projectAssignment?->employee)
                    {{ $timeLog->projectAssignment->employee->full_name }}
                @else
                    —
                @endif
            </span>
        </div>
    @endunless

    <div class="dt-card__row">
        <span class="dt-card__label">Godziny</span>
        <span class="dt-card__value font-mono">{{ number_format($timeLog->hours_worked, 2, ',', ' ') }}h</span>
    </div>

    @if($timeLog->notes)
        <div class="dt-card__row">
            <span class="dt-card__label">Notatki</span>
            <span class="dt-card__value text-start">{{ Str::limit($timeLog->notes, 80) }}</span>
        </div>
    @endif
</x-ui.card>
