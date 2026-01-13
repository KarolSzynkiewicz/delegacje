<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Services\DateRangeService;

/**
 * Trait providing common lifecycle methods for Assignment models.
 * 
 * All status is calculated from dates - no status field needed.
 * If assignment is not needed, simply delete it from database.
 */
trait HasAssignmentLifecycle
{
    // Note: isCurrentlyActive(), isPast(), isScheduled() are provided by HasDateRange trait

    /**
     * Complete this assignment on the given date.
     * Sets actual_end_date if the column exists.
     */
    public function complete(Carbon $date): void
    {
        $updateData = [];
        
        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'actual_end_date')) {
            $updateData['actual_end_date'] = $date;
        }
        
        $this->update($updateData);
    }

    /**
     * Scope: Filter assignments that are currently active (date-based).
     * Active = start_date <= today <= end_date (or end_date is null)
     */
    public function scopeActive(Builder $query): Builder
    {
        $today = Carbon::today();
        $startColumn = $this->getStartDateColumn();
        $endColumn = $this->getEndDateColumn();
        
        return $query->where($startColumn, '<=', $today)
            ->where(function ($q) use ($today, $endColumn) {
                $q->whereNull($endColumn)
                  ->orWhere($endColumn, '>=', $today);
            });
    }

    /**
     * Scope: Filter assignments that are scheduled (future).
     */
    public function scopeScheduled(Builder $query): Builder
    {
        $today = Carbon::today();
        $startColumn = $this->getStartDateColumn();
        
        return $query->where($startColumn, '>', $today);
    }

    /**
     * Scope: Filter assignments that are completed (past).
     */
    public function scopeCompleted(Builder $query): Builder
    {
        $today = Carbon::today();
        $endColumn = $this->getEndDateColumn();
        
        return $query->whereNotNull($endColumn)
            ->where($endColumn, '<', $today);
    }

    /**
     * Scope: Filter assignments active at a specific date.
     */
    public function scopeActiveAtDate(Builder $query, Carbon $date): Builder
    {
        $date = DateRangeService::normalizeDate($date);
        $startColumn = $this->getStartDateColumn();
        $endColumn = $this->getEndDateColumn();
        
        return $query->where($startColumn, '<=', $date)
            ->where(function ($q) use ($date, $endColumn) {
                $q->whereNull($endColumn)
                  ->orWhere($endColumn, '>=', $date);
            });
    }
}
