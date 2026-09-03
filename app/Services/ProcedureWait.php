<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class ProcedureWait
{
    /**
     * @param  array{duration?: int|string, unit?: string}  $wait
     */
    public static function resumeAt(array $wait, ?CarbonInterface $from = null): Carbon
    {
        $duration = max(0, (int) ($wait['duration'] ?? 0));
        $from = $from ? Carbon::parse($from) : now();

        return match ($wait['unit'] ?? 'min') {
            'sek' => $from->copy()->addSeconds($duration),
            'godz' => $from->copy()->addHours($duration),
            'dni' => $from->copy()->addDays($duration),
            default => $from->copy()->addMinutes($duration),
        };
    }
}
