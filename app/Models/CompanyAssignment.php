<?php

namespace App\Models;

use App\Enums\AssignmentStatus;
use App\Traits\HasDateRange;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyAssignment extends Model
{
    use HasDateRange, HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'start_date',
        'end_date',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getStatusAttribute($value): AssignmentStatus
    {
        if ($this->isCurrentlyActive()) {
            return AssignmentStatus::ACTIVE;
        }

        if ($this->isPast()) {
            return AssignmentStatus::COMPLETED;
        }

        if ($this->isScheduled()) {
            return AssignmentStatus::ACTIVE;
        }

        return AssignmentStatus::ACTIVE;
    }
}
