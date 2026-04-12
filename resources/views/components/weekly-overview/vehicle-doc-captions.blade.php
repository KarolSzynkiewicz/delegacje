@props(['vehicle'])

@php
    $todayStart = \Carbon\Carbon::today();
    $rows = [];
    foreach ([
        'insurance_valid_to' => 'Dni do końca OC',
        'inspection_valid_to' => 'Dni do końca przeglądu',
    ] as $field => $label) {
        $date = $vehicle->{$field} ?? null;
        if (! $date) {
            continue;
        }
        $d = (int) $todayStart->diffInDays($date->copy()->startOfDay(), false);
        if ($d < 0) {
            $rows[] = ['text' => $label.': po terminie', 'class' => 'text-danger'];
        } elseif ($d === 0) {
            $rows[] = ['text' => $label.': ostatni dzień', 'class' => 'text-warning'];
        } else {
            $dayWord = $d === 1 ? 'dzień' : 'dni';
            $rows[] = ['text' => $label.': '.$d.' '.$dayWord, 'class' => 'text-muted'];
        }
    }
@endphp

@if(count($rows) > 0)
    <div class="small mt-1">
        @foreach($rows as $row)
            <div class="{{ $row['class'] }}">{{ $row['text'] }}</div>
        @endforeach
    </div>
@endif
