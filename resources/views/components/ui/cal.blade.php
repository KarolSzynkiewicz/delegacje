@props([
    'startDate' => null, // Carbon date - data rozpoczęcia kalendarza
    'days' => 30, // Liczba dni do wyświetlenia
    'availability' => [], // Array z dostępnością dla każdego dnia ['Y-m-d' => ['can_assign' => bool, 'reason' => string]]
    'selectedStartDate' => null, // Wybrana data początkowa
    'selectedEndDate' => null, // Wybrana data końcowa
    'onDateClick' => null, // Callback dla kliknięcia w datę (wire:click lub JS)
    'showMonthNavigation' => false, // Czy pokazać guziki poprzedni/następny miesiąc
    'onPreviousMonth' => null, // Callback dla poprzedniego miesiąca
    'onNextMonth' => null, // Callback dla następnego miesiąca
    'arrivalDate' => null, // Carbon date - data przyjazdu (dla wyświetlania etykiety "Przyjazd")
])

@php
    $start = $startDate ? \Carbon\Carbon::parse($startDate) : \Carbon\Carbon::now();
    $weekDays = ['Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'Sob', 'Nie'];
    
    // Jeśli days jest ustawione, użyj go (dla kompatybilności wstecznej)
    // W przeciwnym razie pokaż cały miesiąc
    if ($days && $days > 0) {
        // Stary sposób - pokaż określoną liczbę dni od daty początkowej
        $calendarStart = $start->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        $daysToShow = $days;
    } else {
        // Nowy sposób - pokaż cały miesiąc od pierwszego do ostatniego dnia
        $firstDayOfMonth = $start->copy()->startOfMonth();
        $lastDayOfMonth = $start->copy()->endOfMonth();
        
        // Zaczynaj od poniedziałku tygodnia, w którym jest pierwszy dzień miesiąca
        $calendarStart = $firstDayOfMonth->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        
        // Kończ w niedzielę tygodnia, w którym jest ostatni dzień miesiąca
        $calendarEnd = $lastDayOfMonth->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
        
        // Oblicz liczbę dni do pokazania
        $daysToShow = $calendarStart->diffInDays($calendarEnd) + 1;
    }
    
    // Generuj dni - zawsze zaczynając od poniedziałku
    $calendarDays = [];
    for ($i = 0; $i < $daysToShow; $i++) {
        $date = $calendarStart->copy()->addDays($i);
        $dateKey = $date->format('Y-m-d');
        $dayData = $availability[$dateKey] ?? null;
        
        // Sprawdź czy to dzień z bieżącego miesiąca
        $isCurrentMonth = $date->month === $start->month && $date->year === $start->year;
        
        $calendarDays[] = [
            'date' => $date,
            'dateKey' => $dateKey,
            'dayNumber' => $date->day,
            'dayOfWeek' => $weekDays[$date->dayOfWeek === 0 ? 6 : $date->dayOfWeek - 1],
            'isWeekend' => $date->isWeekend(),
            'isToday' => $date->isToday(),
            'isCurrentMonth' => $isCurrentMonth,
            'availability' => $dayData,
            'canAssign' => $dayData ? (bool)($dayData['can_assign'] ?? false) : false,
            'reason' => $dayData['reason'] ?? null,
            'reason_text' => $dayData['reason_text'] ?? null,
            'warning' => $dayData['warning'] ?? false,
        ];
    }
    
    // Sprawdź czy data jest w wybranym zakresie
    $selectedStart = $selectedStartDate ? \Carbon\Carbon::parse($selectedStartDate) : null;
    $selectedEnd = $selectedEndDate ? \Carbon\Carbon::parse($selectedEndDate) : null;
@endphp

<div {{ $attributes->merge(['class' => 'ui-cal']) }}>
    <!-- Header z miesiącami -->
    <div class="ui-cal-header">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                @php
                    $firstDayOfMonth = $start->copy()->startOfMonth();
                    $monthName = $firstDayOfMonth->locale('pl')->translatedFormat('F Y');
                @endphp
                <div class="fw-semibold" style="color: var(--text-main); font-size: 1.1rem;">{{ $monthName }}</div>
                <div class="small" style="color: var(--text-muted); margin-top: 2px;">{{ $firstDayOfMonth->format('d.m.Y') }} – {{ $start->copy()->endOfMonth()->format('d.m.Y') }}</div>
            </div>
            @if($showMonthNavigation)
                <div class="d-flex gap-2">
                    @if($onPreviousMonth)
                        <button 
                            type="button" 
                            class="btn btn-sm btn-outline-secondary"
                            @if(str_starts_with($onPreviousMonth, 'wire:'))
                                {{ $onPreviousMonth }}
                            @else
                                onclick="{{ $onPreviousMonth }}"
                            @endif
                        >
                            <i class="bi bi-chevron-left"></i> Poprzedni
                        </button>
                    @endif
                    @if($onNextMonth)
                        <button 
                            type="button" 
                            class="btn btn-sm btn-outline-secondary"
                            @if(str_starts_with($onNextMonth, 'wire:'))
                                {{ $onNextMonth }}
                            @else
                                onclick="{{ $onNextMonth }}"
                            @endif
                        >
                            Następny <i class="bi bi-chevron-right"></i>
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
    
    <!-- Kalendarz - siatka dni -->
    <div class="ui-cal-grid">
        <!-- Nagłówki dni tygodnia -->
        <div class="ui-cal-weekdays mb-2">
            @foreach($weekDays as $dayName)
                <div class="ui-cal-weekday text-muted small fw-semibold text-center">
                    {{ $dayName }}
                </div>
            @endforeach
        </div>
        
        <!-- Dni kalendarza -->
        <div class="ui-cal-days">
            @foreach($calendarDays as $day)
                @php
                    $isSelected = false;
                    $isStartDate = false;
                    $isEndDate = false;
                    
                    if ($selectedStart && $selectedEnd) {
                        $isStartDate = $day['date']->eq($selectedStart);
                        $isEndDate = $day['date']->eq($selectedEnd);
                        $isSelected = $day['date']->gte($selectedStart) && $day['date']->lte($selectedEnd);
                    } elseif ($selectedStart) {
                        $isStartDate = $day['date']->eq($selectedStart);
                        $isSelected = $isStartDate;
                    }
                @endphp
                
                @php
                    $warningClass = isset($day['warning']) && $day['warning'] ? 'ui-cal-day-warning' : '';
                    $tooltipText = '';
                    
                    // Check if has warning and no projects
                    $hasNoProjects = isset($day['warning']) && $day['warning'] && 
                                     isset($day['availability']['has_projects']) && 
                                     !$day['availability']['has_projects'];
                    
                    // Check if has warning but has projects (outside project range)
                    $isOutsideProjectRange = isset($day['warning']) && $day['warning'] && 
                                            isset($day['availability']['has_projects']) && 
                                            $day['availability']['has_projects'];
                    
                    if ($day['canAssign']) {
                        if ($hasNoProjects) {
                            // No projects at all - show message
                            $tooltipText = 'Tutaj już nie jest w projekcie';
                            if (isset($day['availability']['available_capacity'])) {
                                $tooltipText .= ' (Wolne miejsca: ' . $day['availability']['available_capacity'] . ')';
                            }
                        } elseif ($isOutsideProjectRange) {
                            // Has projects but outside range
                            $tooltipText = 'Tutaj już nie jest w projekcie';
                            if (isset($day['availability']['available_capacity'])) {
                                $tooltipText .= ' (Wolne miejsca: ' . $day['availability']['available_capacity'] . ')';
                            }
                        } else {
                            $tooltipText = 'Kliknij aby wybrać';
                            if (isset($day['availability']['available_capacity'])) {
                                $tooltipText .= ' (Wolne miejsca: ' . $day['availability']['available_capacity'] . ')';
                            }
                        }
                    } else {
                        $tooltipText = $day['reason_text'] ?? ($day['reason'] ?? 'Niedostępne');
                        if (isset($day['availability']['available_capacity'])) {
                            $tooltipText .= ' (Wolne miejsca: ' . $day['availability']['available_capacity'] . ')';
                        }
                    }
                @endphp
                
                <div 
                    class="ui-cal-day 
                           {{ $day['canAssign'] ? 'ui-cal-day-available' : 'ui-cal-day-unavailable' }}
                           {{ $day['isWeekend'] ? 'ui-cal-day-weekend' : '' }}
                           {{ $day['isToday'] ? 'ui-cal-day-today' : '' }}
                           {{ $isSelected ? 'ui-cal-day-selected' : '' }}
                           {{ $isStartDate ? 'ui-cal-day-start' : '' }}
                           {{ $isEndDate ? 'ui-cal-day-end' : '' }}
                           {{ $warningClass }}
                           {{ !$day['isCurrentMonth'] ? 'ui-cal-day-other-month' : '' }}"
                    @if($day['canAssign'] && $onDateClick)
                        wire:click="{{ $onDateClick }}('{{ $day['dateKey'] }}')"
                        style="cursor: pointer;"
                    @elseif($day['canAssign'])
                        onclick="{{ $onDateClick ?? '' }}('{{ $day['dateKey'] }}')"
                        style="cursor: pointer;"
                    @endif
                    title="{{ $tooltipText }}"
                >
                    <div class="ui-cal-day-number">{{ $day['dayNumber'] }}</div>
                    <div class="ui-cal-day-name small">{{ $day['dayOfWeek'] }}</div>
                    @php
                        $arrival = $arrivalDate ? \Carbon\Carbon::parse($arrivalDate) : null;
                    @endphp
                    @if($arrival && $day['date']->eq($arrival))
                        <div class="ui-cal-day-label small">Przyjazd</div>
                    @endif
                    @if(!$day['canAssign'] && $day['reason_text'])
                        <div class="ui-cal-day-reason" title="{{ $day['reason_text'] }}">
                            <i class="bi bi-x-circle"></i>
                        </div>
                    @endif
                    @if(isset($day['warning']) && $day['warning'])
                        @php
                            $hasProjects = isset($day['availability']['has_projects']) ? $day['availability']['has_projects'] : true;
                            $warningTitle = $hasProjects ? 'Przypisanie dłuższe niż do projektu' : 'Brak przypisania do projektu';
                        @endphp
                        <div class="ui-cal-day-warning-icon" title="{{ $warningTitle }}">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    
    <!-- Legenda -->
    <div class="ui-cal-legend mt-3 pt-3 border-top border-glass">
        <div class="small text-muted d-flex align-items-center gap-3 flex-wrap">
            <span class="fw-semibold">Legenda:</span>
            <div class="d-flex align-items-center gap-2">
                <div class="ui-cal-day ui-cal-day-available" style="width: 40px; height: 40px; pointer-events: none;"></div>
                <span>Dostępne</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="ui-cal-day ui-cal-day-unavailable" style="width: 40px; height: 40px; pointer-events: none;"></div>
                <span>Niedostępne</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="ui-cal-day ui-cal-day-available ui-cal-day-warning" style="width: 40px; height: 40px; pointer-events: none;"></div>
                <span>Bez projektu</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="ui-cal-day ui-cal-day-selected" style="width: 40px; height: 40px; pointer-events: none;"></div>
                <span>Wybrany zakres</span>
            </div>
        </div>
    </div>
</div>

<style>
.ui-cal {
    background: transparent;
    border: none;
    border-radius: 0;
    padding: 0;
}

.ui-cal-header {
    color: var(--text-main);
    margin-bottom: 1rem;
}

.ui-cal-grid {
    width: 100%;
}

.ui-cal-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 6px;
    margin-bottom: 6px;
}

.ui-cal-weekday {
    padding: 6px 4px;
    color: var(--text-muted);
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 600;
}

.ui-cal-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 6px;
}

.ui-cal-day {
    aspect-ratio: 1;
    min-height: 50px;
    background: rgba(15, 23, 42, 0.4);
    border: 1px solid var(--glass-border);
    border-radius: 8px;
    padding: 6px 4px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    position: relative;
    color: var(--text-muted);
}

.ui-cal-day:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* Dostępne dni */
.ui-cal-day-available {
    background: rgba(59, 130, 246, 0.15);
    border-color: rgba(59, 130, 246, 0.4);
    color: var(--text-main);
}

.ui-cal-day-available:hover {
    background: rgba(59, 130, 246, 0.25);
    border-color: rgba(59, 130, 246, 0.6);
    box-shadow: 0 2px 12px rgba(59, 130, 246, 0.3);
}

/* Niedostępne dni */
.ui-cal-day-unavailable {
    background: rgba(30, 41, 59, 0.3);
    border-color: rgba(255, 255, 255, 0.05);
    color: var(--text-muted);
    opacity: 0.4;
    cursor: not-allowed;
}

.ui-cal-day-unavailable:hover {
    transform: none;
    box-shadow: none;
    background: rgba(30, 41, 59, 0.3);
}

/* Weekend */
.ui-cal-day-weekend {
    background: rgba(30, 41, 59, 0.25);
}

.ui-cal-day-weekend.ui-cal-day-available {
    background: rgba(59, 130, 246, 0.12);
}

/* Dzisiaj */
.ui-cal-day-today {
    border-color: var(--primary);
    border-width: 2px;
}

.ui-cal-day-today.ui-cal-day-available {
    border-color: var(--primary);
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

/* Wybrany zakres */
.ui-cal-day-selected {
    background: rgba(59, 130, 246, 0.3);
    border-color: rgba(59, 130, 246, 0.7);
    color: var(--text-main);
}

.ui-cal-day-start {
    background: rgba(59, 130, 246, 0.4);
    border-color: var(--primary);
    border-width: 2px;
    font-weight: 600;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.ui-cal-day-end {
    background: rgba(59, 130, 246, 0.4);
    border-color: var(--primary);
    border-width: 2px;
    font-weight: 600;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.ui-cal-day-number {
    font-size: 0.9rem;
    font-weight: 600;
    color: inherit;
    line-height: 1.2;
}

.ui-cal-day-name {
    font-size: 0.65rem;
    color: var(--text-muted);
    margin-top: 2px;
    opacity: 0.8;
}

.ui-cal-day-label {
    position: absolute;
    bottom: 2px;
    font-size: 0.6rem;
    color: var(--primary);
    font-weight: 600;
    opacity: 0.9;
}

.ui-cal-day-reason {
    position: absolute;
    top: 2px;
    right: 2px;
    color: var(--danger);
    font-size: 0.65rem;
    opacity: 0.8;
}

/* Warning - yellow border for days outside project range */
.ui-cal-day-warning {
    border-color: var(--warning) !important;
    border-width: 2px !important;
}

/* Dni z innych miesięcy */
.ui-cal-day-other-month {
    opacity: 0.4;
}

.ui-cal-day-other-month.ui-cal-day-available {
    opacity: 0.3;
}

.ui-cal-day-warning-icon {
    position: absolute;
    top: 2px;
    left: 2px;
    color: var(--warning);
    font-size: 0.7rem;
    opacity: 0.9;
}

.ui-cal-legend {
    border-color: var(--glass-border) !important;
    padding-top: 1rem;
    margin-top: 1rem;
}

.text-main {
    color: var(--text-main) !important;
}

.border-glass {
    border-color: var(--glass-border) !important;
}
</style>
