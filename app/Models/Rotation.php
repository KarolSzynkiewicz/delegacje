<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasDateRange;

class Rotation extends Model
{
    use HasFactory, HasDateRange;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /**
     * Get the employee that owns this rotation.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the computed status based on dates.
     * Status: 'scheduled' (zaplanowana), 'active' (aktywna), 'completed' (zakończona)
     */
    public function getStatusAttribute($value): string
    {
        // Jeśli brak dat, zwróć 'scheduled'
        if (!$this->start_date || !$this->end_date) {
            return 'scheduled';
        }

        $today = now()->startOfDay();
        $startDate = $this->start_date->startOfDay();
        $endDate = $this->end_date->startOfDay();

        if ($startDate->isFuture()) {
            return 'scheduled'; // Zaplanowana - jeszcze się nie rozpoczęła
        } elseif ($endDate->isPast()) {
            return 'completed'; // Zakończona - już minęła
        } else {
            return 'active'; // Aktywna - trwa obecnie
        }
    }

    /**
     * Scope a query to only include active rotations (based on dates).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $this->scopeActiveAtDate($query, \Carbon\Carbon::today());
    }

    /**
     * Scope a query to only include scheduled rotations.
     */
    public function scopeScheduled(Builder $query): Builder
    {
        $today = \Carbon\Carbon::today();
        $startColumn = $this->getStartDateColumn();

        return $query->where($startColumn, '>', $today);
    }

    /**
     * Scope a query to only include completed rotations.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        $today = \Carbon\Carbon::today();
        $endColumn = $this->getEndDateColumn();

        return $query->whereNotNull($endColumn)
            ->where($endColumn, '<', $today);
    }

    /**
     * Check if a rotation overlaps with existing rotations for an employee.
     * Optionally excludes a specific rotation (for updates).
     */
    public static function hasOverlappingRotations(
        int $employeeId,
        string $startDate,
        string $endDate,
        ?int $excludeRotationId = null
    ): bool {
        $query = static::where('employee_id', $employeeId)
            ->overlappingWith($startDate, $endDate);

        if ($excludeRotationId) {
            $query->where('id', '!=', $excludeRotationId);
        }

        return $query->exists();
    }
}
