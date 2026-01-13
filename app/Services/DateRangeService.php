<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Centralized service for date range operations.
 * 
 * Provides consistent date range validation, overlap checking, and transformations
 * using Carbon and CarbonPeriod instead of manual string comparisons.
 */
class DateRangeService
{
    /**
     * Check if two date ranges overlap.
     * 
     * Uses CarbonPeriod::overlaps() for reliable overlap detection.
     * 
     * @param CarbonInterface $start1
     * @param CarbonInterface|null $end1 Null means open-ended range
     * @param CarbonInterface $start2
     * @param CarbonInterface|null $end2 Null means open-ended range
     * @return bool
     */
    public static function overlaps(
        CarbonInterface $start1,
        ?CarbonInterface $end1,
        CarbonInterface $start2,
        ?CarbonInterface $end2
    ): bool {
        // Normalize dates to start of day for consistent comparison
        $start1 = static::normalizeDate($start1);
        $start2 = static::normalizeDate($start2);
        $end1 = $end1 ? static::normalizeDate($end1) : null;
        $end2 = $end2 ? static::normalizeDate($end2) : null;

        // If both ranges are open-ended, they overlap
        if ($end1 === null && $end2 === null) {
            return true;
        }

        // Create periods for overlap check
        // For open-ended ranges, use a far future date
        $period1End = $end1 ?? Carbon::create(9999, 12, 31);
        $period2End = $end2 ?? Carbon::create(9999, 12, 31);

        $period1 = CarbonPeriod::create($start1, $period1End);
        $period2 = CarbonPeriod::create($start2, $period2End);

        return $period1->overlaps($period2);
    }

    /**
     * Check if a date range is valid (start <= end).
     * 
     * @param CarbonInterface $start
     * @param CarbonInterface|null $end Null means open-ended (always valid)
     * @return bool
     */
    public static function isValidRange(
        CarbonInterface $start,
        ?CarbonInterface $end
    ): bool {
        if ($end === null) {
            return true; // Open-ended ranges are always valid
        }

        return static::normalizeDate($start)->lte(static::normalizeDate($end));
    }

    /**
     * Validate that start date is before or equal to end date.
     * 
     * @param CarbonInterface $start
     * @param CarbonInterface|null $end
     * @throws InvalidArgumentException
     */
    public static function validateRange(
        CarbonInterface $start,
        ?CarbonInterface $end
    ): void {
        if (!static::isValidRange($start, $end)) {
            throw new InvalidArgumentException(
                'Start date must be before or equal to end date.'
            );
        }
    }

    /**
     * Check if a date range completely covers another date range.
     * 
     * @param CarbonInterface $coverStart Start of covering range
     * @param CarbonInterface|null $coverEnd End of covering range (null = open-ended)
     * @param CarbonInterface $coveredStart Start of covered range
     * @param CarbonInterface|null $coveredEnd End of covered range (null = open-ended)
     * @return bool
     */
    public static function covers(
        CarbonInterface $coverStart,
        ?CarbonInterface $coverEnd,
        CarbonInterface $coveredStart,
        ?CarbonInterface $coveredEnd
    ): bool {
        $coverStart = static::normalizeDate($coverStart);
        $coveredStart = static::normalizeDate($coveredStart);

        // Covering range must start before or at the same time as covered range
        if ($coverStart->gt($coveredStart)) {
            return false;
        }

        // If covered range is open-ended, covering range must also be open-ended
        if ($coveredEnd === null) {
            return $coverEnd === null;
        }

        $coveredEnd = static::normalizeDate($coveredEnd);

        // If covering range is open-ended, it covers everything
        if ($coverEnd === null) {
            return true;
        }

        $coverEnd = static::normalizeDate($coverEnd);

        // Covering range must end after or at the same time as covered range
        return $coverEnd->gte($coveredEnd);
    }

    /**
     * Normalize a date to start of day for consistent comparison.
     * 
     * @param CarbonInterface|string $date
     * @return Carbon
     */
    public static function normalizeDate(CarbonInterface|string $date): Carbon
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return $date->copy()->startOfDay();
    }

    /**
     * Convert date to Carbon if it's a string.
     * 
     * @param CarbonInterface|string|null $date
     * @return CarbonInterface|null
     */
    public static function toCarbon(CarbonInterface|string|null $date): ?CarbonInterface
    {
        if ($date === null) {
            return null;
        }

        if (is_string($date)) {
            return Carbon::parse($date);
        }

        return $date;
    }

    /**
     * Get the overlap period between two date ranges.
     * 
     * @param CarbonInterface $start1
     * @param CarbonInterface|null $end1
     * @param CarbonInterface $start2
     * @param CarbonInterface|null $end2
     * @return CarbonPeriod|null Returns null if ranges don't overlap
     */
    public static function getOverlapPeriod(
        CarbonInterface $start1,
        ?CarbonInterface $end1,
        CarbonInterface $start2,
        ?CarbonInterface $end2
    ): ?CarbonPeriod {
        if (!static::overlaps($start1, $end1, $start2, $end2)) {
            return null;
        }

        $start1 = static::normalizeDate($start1);
        $start2 = static::normalizeDate($start2);
        $end1 = $end1 ? static::normalizeDate($end1) : Carbon::create(9999, 12, 31);
        $end2 = $end2 ? static::normalizeDate($end2) : Carbon::create(9999, 12, 31);

        $overlapStart = $start1->gt($start2) ? $start1 : $start2;
        $overlapEnd = $end1->lt($end2) ? $end1 : $end2;

        return CarbonPeriod::create($overlapStart, $overlapEnd);
    }
}
