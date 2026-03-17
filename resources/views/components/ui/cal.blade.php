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
{{-- Styles are defined in resources/css/app.css to ensure they're always loaded,
     even when this component is first rendered inside a Livewire AJAX modal update --}}
