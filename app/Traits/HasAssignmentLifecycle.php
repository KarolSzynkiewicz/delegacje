<?php

namespace App\Traits;

use App\Enums\AssignmentStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait providing common lifecycle methods for Assignment models.
 * 
 * IMPORTANT: This trait contains ONLY state mechanics, NOT business logic.
 * 
 * DO NOT ADD:
 * - moveToBase() - business logic, belongs in services
 * - attachToEvent() - logistics logic, belongs in services
 * - getLocation() - location logic, belongs in LocationTrackingService
 * - Knowledge about transport, equipment, events
 * 
 * ALLOWED:
 * - Status checks (isActive, isInTransit, isAtBase)
 * - Status changes (complete, cancel)
 * - Date range queries (getDateRange)
 * - Employee access (getEmployee)
 */
trait HasAssignmentLifecycle
{
    /**
     * Check if this assignment is active.
     */
    public function isActive(): bool
    {
        return $this->getStatus() === AssignmentStatus::ACTIVE;
    }

    /**
     * Check if this assignment is in transit.
     */
    public function isInTransit(): bool
    {
        return $this->getStatus() === AssignmentStatus::IN_TRANSIT;
    }

    /**
     * Check if this assignment is at base.
     */
    public function isAtBase(): bool
    {
        return $this->getStatus() === AssignmentStatus::AT_BASE;
    }

    /**
     * Check if this assignment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->getStatus() === AssignmentStatus::COMPLETED;
    }

    /**
     * Check if this assignment is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->getStatus() === AssignmentStatus::CANCELLED;
    }

    /**
     * Complete this assignment on the given date.
     * Updates status to COMPLETED and sets actual_end_date if the column exists.
     * 
     * NOTE: This method is part of AssignmentContract interface.
     * It must be implemented in the model, but we provide default implementation here.
     */
    public function complete(Carbon $date): void
    {
        $updateData = [
            'status' => AssignmentStatus::COMPLETED,
        ];

        // Set actual_end_date if column exists
        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'actual_end_date')) {
            $updateData['actual_end_date'] = $date;
        }

        $this->update($updateData);
    }

    /**
     * Cancel this assignment.
     * Updates status to CANCELLED.
     * 
     * NOTE: This method is part of AssignmentContract interface.
     * It must be implemented in the model, but we provide default implementation here.
     */
    public function cancel(): void
    {
        $this->update([
            'status' => AssignmentStatus::CANCELLED,
        ]);
    }

    /**
     * Scope a query to only include active assignments.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', AssignmentStatus::ACTIVE);
    }

    /**
     * Scope a query to only include assignments in transit.
     */
    public function scopeInTransit(Builder $query): Builder
    {
        return $query->where('status', AssignmentStatus::IN_TRANSIT);
    }

    /**
     * Scope a query to only include assignments at base.
     */
    public function scopeAtBase(Builder $query): Builder
    {
        return $query->where('status', AssignmentStatus::AT_BASE);
    }

    /**
     * Scope a query to only include completed assignments.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', AssignmentStatus::COMPLETED);
    }

    /**
     * Scope a query to only include cancelled assignments.
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', AssignmentStatus::CANCELLED);
    }
}
