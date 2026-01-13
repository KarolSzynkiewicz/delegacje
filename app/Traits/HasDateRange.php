<?php

namespace App\Traits;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use App\Services\DateRangeService;

/**
 * Trait for models with date ranges (start_date, end_date).
 * 
 * Provides:
 * - Query scopes for date range filtering
 * - Methods for checking overlaps and validity
 * - Consistent Carbon-based date handling
 */
trait HasDateRange
{
    /**
     * Get the start date column name.
     * Override if your model uses different column names.
     */
    public function getStartDateColumn(): string
    {
        return 'start_date';
    }

    /**
     * Get the end date column name.
     * Override if your model uses different column names.
     */
    public function getEndDateColumn(): string
    {
        return 'end_date';
    }

    /**
     * Get the start date as Carbon.
     */
    public function getStartDate(): ?CarbonInterface
    {
        $column = $this->getStartDateColumn();
        $value = $this->getAttribute($column);
        
        if ($value === null) {
            return null;
        }

        return DateRangeService::toCarbon($value);
    }

    /**
     * Get the end date as Carbon.
     */
    public function getEndDate(): ?CarbonInterface
    {
        $column = $this->getEndDateColumn();
        $value = $this->getAttribute($column);
        
        if ($value === null) {
            return null;
        }

        return DateRangeService::toCarbon($value);
    }

    /**
     * Get the date range as CarbonPeriod.
     * 
     * @return CarbonPeriod|null Returns null if start_date is missing
     */
    public function getDateRange(): ?CarbonPeriod
    {
        $start = $this->getStartDate();
        
        if ($start === null) {
            return null;
        }

        $end = $this->getEndDate();
        
        // For open-ended ranges, use a far future date
        $periodEnd = $end ?? Carbon::create(9999, 12, 31);
        
        return CarbonPeriod::create($start, $periodEnd);
    }

    /**
     * Check if this date range is valid (start <= end).
     */
    public function isValidDateRange(): bool
    {
        $start = $this->getStartDate();
        $end = $this->getEndDate();

        if ($start === null) {
            return false; // Start date is required
        }

        return DateRangeService::isValidRange($start, $end);
    }

    /**
     * Check if this date range is currently active (based on dates only).
     * Active = start_date <= today <= end_date (or end_date is null)
     */
    public function isCurrentlyActive(): bool
    {
        $start = $this->getStartDate();
        if ($start === null) {
            return false;
        }
        
        $today = Carbon::today();
        $end = $this->getEndDate();
        
        return $start->lte($today) && ($end === null || $end->gte($today));
    }

    /**
     * Check if this date range is in the past.
     * Past = end_date < today (and end_date is not null)
     */
    public function isPast(): bool
    {
        $end = $this->getEndDate();
        if ($end === null) {
            return false; // Open-ended ranges are never past
        }
        
        return $end->lt(Carbon::today());
    }

    /**
     * Check if this date range is scheduled (in the future).
     * Scheduled = start_date > today
     */
    public function isScheduled(): bool
    {
        $start = $this->getStartDate();
        if ($start === null) {
            return false;
        }
        
        return $start->gt(Carbon::today());
    }

    /**
     * Check if this date range is active at a specific date.
     */
    public function isActiveAt(CarbonInterface|string $date): bool
    {
        $start = $this->getStartDate();
        if ($start === null) {
            return false;
        }
        
        $date = DateRangeService::normalizeDate($date);
        $end = $this->getEndDate();
        
        return $start->lte($date) && ($end === null || $end->gte($date));
    }

    /**
     * Check if this date range overlaps with another date range.
     * 
     * @param CarbonInterface|string $otherStart
     * @param CarbonInterface|string|null $otherEnd
     * @return bool
     */
    public function overlapsWith(
        CarbonInterface|string $otherStart,
        CarbonInterface|string|null $otherEnd = null
    ): bool {
        $start = $this->getStartDate();
        
        if ($start === null) {
            return false;
        }

        $end = $this->getEndDate();
        $otherStart = DateRangeService::toCarbon($otherStart);
        $otherEnd = DateRangeService::toCarbon($otherEnd);

        return DateRangeService::overlaps($start, $end, $otherStart, $otherEnd);
    }

    /**
     * Check if this date range overlaps with another model instance.
     * 
     * @param self $other
     * @return bool
     */
    public function overlapsWithModel(self $other): bool
    {
        $start = $this->getStartDate();
        $otherStart = $other->getStartDate();

        if ($start === null || $otherStart === null) {
            return false;
        }

        return DateRangeService::overlaps(
            $start,
            $this->getEndDate(),
            $otherStart,
            $other->getEndDate()
        );
    }

    /**
     * Check if this date range completely covers another date range.
     * 
     * @param CarbonInterface|string $coveredStart
     * @param CarbonInterface|string|null $coveredEnd
     * @return bool
     */
    public function covers(
        CarbonInterface|string $coveredStart,
        CarbonInterface|string|null $coveredEnd = null
    ): bool {
        $start = $this->getStartDate();
        
        if ($start === null) {
            return false;
        }

        $end = $this->getEndDate();
        $coveredStart = DateRangeService::toCarbon($coveredStart);
        $coveredEnd = DateRangeService::toCarbon($coveredEnd);

        return DateRangeService::covers($start, $end, $coveredStart, $coveredEnd);
    }

    /**
     * Scope: Filter records that overlap with the given date range.
     * 
     * Uses SQL-based overlap detection for efficiency.
     * Two ranges overlap if: start1 <= end2 AND start2 <= end1
     * 
     * @param Builder $query
     * @param CarbonInterface|string $startDate
     * @param CarbonInterface|string|null $endDate Null means open-ended
     * @return Builder
     */
    public function scopeOverlappingWith(
        Builder $query,
        CarbonInterface|string $startDate,
        CarbonInterface|string|null $endDate = null
    ): Builder {
        $startDate = DateRangeService::normalizeDate($startDate);
        $startColumn = $this->getStartDateColumn();
        $endColumn = $this->getEndDateColumn();

        return $query->where(function ($q) use ($startDate, $endDate, $startColumn, $endColumn) {
            // Two ranges overlap if: record_start <= query_end AND query_start <= record_end
            // For open-ended ranges (null end), we treat them as infinite
            
            // Condition 1: record_start <= query_end (always true if query is open-ended)
            if ($endDate === null) {
                // Query is open-ended, so condition 1 is always true
                // Condition 2: query_start <= record_end (or record is open-ended)
                $q->where(function ($q2) use ($startDate, $endColumn) {
                    $q2->whereNull($endColumn)
                       ->orWhere($endColumn, '>=', $startDate);
                });
            } else {
                $endDate = DateRangeService::normalizeDate($endDate);
                
                // Condition 1: record_start <= query_end
                // Condition 2: query_start <= record_end (or record is open-ended)
                $q->where($startColumn, '<=', $endDate)
                  ->where(function ($q2) use ($startDate, $endColumn) {
                      $q2->whereNull($endColumn)
                         ->orWhere($endColumn, '>=', $startDate);
                  });
            }
        });
    }

    /**
     * Scope: Filter records within a date range (overlaps with range).
     * 
     * Alias for scopeOverlappingWith for backward compatibility.
     * 
     * @param Builder $query
     * @param CarbonInterface|string $startDate
     * @param CarbonInterface|string|null $endDate
     * @return Builder
     */
    public function scopeInDateRange(
        Builder $query,
        CarbonInterface|string $startDate,
        CarbonInterface|string|null $endDate = null
    ): Builder {
        return $this->scopeOverlappingWith($query, $startDate, $endDate);
    }

    /**
     * Scope: Filter records that are active at a specific date.
     * 
     * A record is active if:
     * - start_date <= date
     * - end_date is null OR end_date >= date
     * 
     * @param Builder $query
     * @param CarbonInterface|string $date
     * @return Builder
     */
    public function scopeActiveAtDate(
        Builder $query,
        CarbonInterface|string $date
    ): Builder {
        $date = DateRangeService::normalizeDate($date);
        $startColumn = $this->getStartDateColumn();
        $endColumn = $this->getEndDateColumn();

        return $query->where($startColumn, '<=', $date)
            ->where(function ($q) use ($date, $endColumn) {
                $q->whereNull($endColumn)
                  ->orWhere($endColumn, '>=', $date);
            });
    }

    /**
     * Scope: Filter records that are currently active (active at today).
     * 
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $this->scopeActiveAtDate($query, Carbon::today());
    }

    /**
     * Scope: Filter records that start in the future.
     * 
     * @param Builder $query
     * @return Builder
     */
    public function scopeScheduled(Builder $query): Builder
    {
        $today = Carbon::today();
        $startColumn = $this->getStartDateColumn();

        return $query->where($startColumn, '>', $today);
    }

    /**
     * Scope: Filter records that have ended.
     * 
     * @param Builder $query
     * @return Builder
     */
    public function scopeCompleted(Builder $query): Builder
    {
        $today = Carbon::today();
        $endColumn = $this->getEndDateColumn();

        return $query->whereNotNull($endColumn)
            ->where($endColumn, '<', $today);
    }
}
