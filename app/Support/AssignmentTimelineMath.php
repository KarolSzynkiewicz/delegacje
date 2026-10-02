<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Day-range geometry for the assignment timeline.
 * Dates are Y-m-d strings so the picker can run in memory after one bulk load.
 */
final class AssignmentTimelineMath
{
    public static function addDays(string $iso, int $days): string
    {
        return Carbon::parse($iso)->startOfDay()->addDays($days)->toDateString();
    }

    public static function dayIndex(string $origin, string $date): int
    {
        return (int) Carbon::parse($origin)->startOfDay()->diffInDays(Carbon::parse($date)->startOfDay(), false);
    }

    /**
     * @param  list<array{start:string,end:?string}>  $ranges
     * @return array{peak:int, full_from:?string}
     */
    public static function peak(array $ranges, string $from, string $to, int $capacity): array
    {
        if ($from > $to || $ranges === []) {
            return ['peak' => 0, 'full_from' => null];
        }

        $events = [];
        foreach ($ranges as $range) {
            $start = $range['start'] > $from ? $range['start'] : $from;
            $end = $range['end'] ?? $to;
            if ($end > $to) {
                $end = $to;
            }
            if ($start > $end) {
                continue;
            }
            $events[] = [$start, 1];
            $events[] = [self::addDays($end, 1), -1];
        }

        usort($events, function (array $a, array $b): int {
            if ($a[0] === $b[0]) {
                return $a[1] <=> $b[1];
            }

            return $a[0] <=> $b[0];
        });

        $current = 0;
        $peak = 0;
        $fullFrom = null;
        foreach ($events as [$day, $delta]) {
            if ($day > $to && $delta > 0) {
                continue;
            }
            $current += $delta;
            if ($current > $peak) {
                $peak = $current;
            }
            if ($delta > 0 && $capacity > 0 && $current >= $capacity && $fullFrom === null && $day <= $to) {
                $fullFrom = $day;
            }
        }

        return ['peak' => $peak, 'full_from' => $fullFrom];
    }

    /**
     * Free slots on the worst day. A day with no covering demand counts as zero.
     *
     * @param  list<array{start:string,end:?string,required:int}>  $demands
     * @param  list<array{start:string,end:?string}>  $assigned
     */
    public static function minFree(array $demands, array $assigned, string $from, string $to): int
    {
        if ($from > $to) {
            return 0;
        }

        $min = null;
        for ($day = $from; $day <= $to; $day = self::addDays($day, 1)) {
            $required = null;
            foreach ($demands as $demand) {
                if (self::covers($demand['start'], $demand['end'], $day)) {
                    $required = (int) $demand['required'];
                    break;
                }
            }
            $free = 0;
            if ($required !== null) {
                $count = 0;
                foreach ($assigned as $range) {
                    if (self::covers($range['start'], $range['end'], $day)) {
                        $count++;
                    }
                }
                $free = $required - $count;
            }
            $min = $min === null ? $free : min($min, $free);
        }

        return (int) $min;
    }

    /**
     * @param  list<array{start:string,end:string}>  $bounds
     * @param  list<array{start:string,end:string}>  $blocks
     * @return list<array{start:string,end:string}>
     */
    public static function gaps(array $bounds, array $blocks): array
    {
        $gaps = [];
        foreach ($bounds as $bound) {
            $cursor = $bound['start'];
            $end = $bound['end'];
            if ($cursor > $end) {
                continue;
            }

            $overlapping = array_values(array_filter($blocks, function (array $block) use ($bound): bool {
                return $block['start'] <= $bound['end'] && $block['end'] >= $bound['start'];
            }));
            usort($overlapping, fn (array $a, array $b): int => $a['start'] <=> $b['start']);

            foreach ($overlapping as $block) {
                $blockStart = $block['start'] > $bound['start'] ? $block['start'] : $bound['start'];
                $blockEnd = $block['end'] < $end ? $block['end'] : $end;
                if ($cursor < $blockStart) {
                    $gapEnd = self::addDays($blockStart, -1);
                    if ($cursor <= $gapEnd) {
                        $gaps[] = ['start' => $cursor, 'end' => $gapEnd];
                    }
                }
                $next = self::addDays($blockEnd, 1);
                if ($next > $cursor) {
                    $cursor = $next;
                }
            }

            if ($cursor <= $end) {
                $gaps[] = ['start' => $cursor, 'end' => $end];
            }
        }

        return $gaps;
    }

    /**
     * @param  list<array{start:string,end:?string}>  $siblings
     * @return array{min:string,max:string}
     */
    public static function resizeLimits(string $barStart, ?string $barEnd, array $siblings, string $boundStart, string $boundEnd): array
    {
        $min = $boundStart;
        $max = $boundEnd;
        $barFinish = $barEnd ?? '9999-12-31';

        foreach ($siblings as $sibling) {
            $siblingEnd = $sibling['end'] ?? '9999-12-31';
            if ($siblingEnd < $barStart) {
                $next = self::addDays($siblingEnd, 1);
                if ($next > $min) {
                    $min = $next;
                }
            }
            if ($sibling['start'] > $barFinish || ($barEnd === null && $sibling['start'] > $barStart)) {
                $prev = self::addDays($sibling['start'], -1);
                if ($prev < $max) {
                    $max = $prev;
                }
            }
        }

        if ($max < $min) {
            $max = $min;
        }

        return ['min' => $min, 'max' => $max];
    }

    public static function covers(string $start, ?string $end, string $day): bool
    {
        return $start <= $day && ($end === null || $end >= $day);
    }

    /**
     * Tint from the arrival day (not the day travel started) through the return,
     * or through the window end when the return is still ahead.
     *
     * @param  list<array{date: string, kind: string}>  $markers
     * @return list<array{start: string, end: string}>
     */
    public static function onSiteBands(array $markers, string $windowStart, string $windowEnd): array
    {
        $open = null;
        $bands = [];
        foreach ($markers as $marker) {
            if (($marker['kind'] ?? '') === 'transfer') {
                continue;
            }
            if ($marker['kind'] === 'arrival') {
                if ($marker['date'] <= $windowEnd) {
                    $open ??= $marker['date'];
                }

                continue;
            }
            if ($marker['date'] < $windowStart) {
                continue;
            }
            $start = $open ?? $windowStart;
            $end = $marker['date'] > $windowEnd ? $windowEnd : $marker['date'];
            if ($start <= $end) {
                $bands[] = ['start' => $start < $windowStart ? $windowStart : $start, 'end' => $end];
            }
            $open = null;
        }
        if ($open !== null && $open <= $windowEnd) {
            $bands[] = ['start' => $open < $windowStart ? $windowStart : $open, 'end' => $windowEnd];
        }

        return $bands;
    }

    public static function rangeInsideGap(string $start, string $end, array $gaps): bool
    {
        foreach ($gaps as $gap) {
            if ($start >= $gap['start'] && $end <= $gap['end']) {
                return true;
            }
        }

        return false;
    }
}
