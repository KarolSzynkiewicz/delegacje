<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasDateRange;
use App\Traits\HasAssignmentLifecycle;
use Carbon\Carbon;

class EmployeeRate extends Model
{
    use HasFactory, 
        HasDateRange, 
        HasAssignmentLifecycle {
            HasAssignmentLifecycle::scopeActiveAtDate insteadof HasDateRange;
            HasAssignmentLifecycle::scopeCompleted insteadof HasDateRange;
            HasAssignmentLifecycle::scopeScheduled insteadof HasDateRange;
        }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'amount',
        'currency',
        'actual_start_date',
        'actual_end_date',
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
        'actual_start_date' => 'datetime',
        'actual_end_date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the employee for this rate.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Scope: Filter rates that are active (date-based).
     * Uses HasAssignmentLifecycle::scopeActive() which filters by dates only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $this->scopeActiveAtDate($query, Carbon::today());
    }
}
