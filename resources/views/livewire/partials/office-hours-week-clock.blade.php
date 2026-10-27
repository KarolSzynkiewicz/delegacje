@php
    /** @var string $starts */
    /** @var string $ends */
    /** @var float $hours */
    /** @var \App\Enums\OfficeWorkLocation $locEnum */
    /** @var bool $isLeave */
    /** @var float $expectedHours */
    $expectedHours = isset($expectedHours) ? (float) $expectedHours : 8.0;
    if ($expectedHours <= 0) {
        $expectedHours = 8.0;
    }

    $hasRange = ! $isLeave && $starts !== '' && $ends !== '' && $hours > 0;

    $toAngle = static function (string $time): ?float {
        if (! preg_match('/^(\d{1,2}):(\d{2})$/', $time, $m)) {
            return null;
        }

        return (((int) $m[1] % 12) + ((int) $m[2] / 60)) * 30.0;
    };

    $aStart = $hasRange ? $toAngle($starts) : null;
    $radius = 34.0;
    $circumference = round(2 * M_PI * $radius, 2);
    $arcLength = 0.0;
    $arcRotation = -90.0;

    if ($isLeave) {
        $arcLength = $circumference;
    } elseif ($hasRange && $aStart !== null) {
        $arcLength = round(min(1.0, max(0.0, $hours / 12.0)) * $circumference, 2);
        $arcRotation = $aStart - 90.0;
    }

    $vsNorm = ($hasRange || $isLeave) ? round($hours - $expectedHours, 1) : null;
    $isOverNorm = $vsNorm !== null && $vsNorm > 0.05;
    $isUnderNorm = $vsNorm !== null && $vsNorm < -0.05;

    $tone = match ($locEnum) {
        \App\Enums\OfficeWorkLocation::Remote => 'remote',
        \App\Enums\OfficeWorkLocation::Leave => 'leave',
        default => 'office',
    };
@endphp

<div @class([
    'oh-clock',
    'oh-clock--'.$tone,
    'oh-clock--overnorm' => $isOverNorm,
    'oh-clock--empty' => ! ($hasRange || $isLeave),
])>
    <svg class="oh-clock__svg" viewBox="0 0 100 100" aria-hidden="true">
        <circle class="oh-clock__face" cx="50" cy="50" r="46" />
        <circle class="oh-clock__track" cx="50" cy="50" r="{{ $radius }}" fill="none" stroke-width="8" />

        @for($i = 0; $i < 12; $i++)
            @php
                $tick = $i * 30;
                $rad = deg2rad($tick - 90);
                $tx1 = round(50 + 40 * cos($rad), 2);
                $ty1 = round(50 + 40 * sin($rad), 2);
                $inner = $i % 3 === 0 ? 34 : 37;
                $tx2 = round(50 + $inner * cos($rad), 2);
                $ty2 = round(50 + $inner * sin($rad), 2);
            @endphp
            <line
                class="oh-clock__tick {{ $i % 3 === 0 ? 'oh-clock__tick--major' : '' }}"
                x1="{{ $tx1 }}" y1="{{ $ty1 }}" x2="{{ $tx2 }}" y2="{{ $ty2 }}"
            />
        @endfor

        <text class="oh-clock__num" x="50" y="16" text-anchor="middle">12</text>
        <text class="oh-clock__num" x="86" y="54" text-anchor="middle">3</text>
        <text class="oh-clock__num" x="50" y="92" text-anchor="middle">6</text>
        <text class="oh-clock__num" x="14" y="54" text-anchor="middle">9</text>

        @if($arcLength > 0.5)
            <circle
                class="oh-clock__arc"
                cx="50" cy="50" r="{{ $radius }}"
                fill="none" stroke-width="8" stroke-linecap="round"
                stroke-dasharray="{{ $arcLength }} {{ $circumference }}"
                transform="rotate({{ round($arcRotation, 2) }} 50 50)"
            />
        @endif
    </svg>
</div>

@if($vsNorm !== null && abs($vsNorm) >= 0.05)
    <div
        @class([
            'oh-week-delta',
            'oh-week-delta--pos' => $isOverNorm,
            'oh-week-delta--neg' => $isUnderNorm,
        ])
        title="vs norma dnia {{ number_format($expectedHours, 1, ',', '') }} h"
    >
        {{ $vsNorm > 0 ? '+' : '' }}{{ number_format($vsNorm, 1, ',', '') }} h
    </div>
@endif
