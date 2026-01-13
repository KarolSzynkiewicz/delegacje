<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasDateRangeScope;
use App\Traits\HasAssignmentLifecycle;
use App\Enums\AssignmentStatus;
use Carbon\Carbon;

class EmployeeRate extends Model
{
    use HasFactory, HasDateRangeScope, HasAssignmentLifecycle;

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
        'status',
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
        'start_date' => 'date',
        'end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
        'amount' => 'decimal:2',
        'status' => AssignmentStatus::class,
    ];

    /**
     * Get the employee for this rate.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the status of this rate.
     */
    public function getStatus(): AssignmentStatus
    {
        return $this->status ?? AssignmentStatus::ACTIVE;
    }

    /**
     * Get the start date of this rate.
     */
    public function getStartDate(): Carbon
    {
        return $this->start_date;
    }

    /**
     * Get the end date of this rate.
     */
    public function getEndDate(): ?Carbon
    {
        return $this->end_date;
    }
}
