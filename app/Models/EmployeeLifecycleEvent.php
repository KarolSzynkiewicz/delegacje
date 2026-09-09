<?php

namespace App\Models;

use App\Enums\EmployeeLifecycleEventType;
use App\Enums\EmployeeTerminationReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLifecycleEvent extends Model
{
    protected $fillable = [
        'employee_id',
        'type',
        'occurred_at',
        'reason',
        'note',
        'recruitment_process_id',
        'created_by',
    ];

    protected $casts = [
        'type' => EmployeeLifecycleEventType::class,
        'occurred_at' => 'datetime',
        'reason' => EmployeeTerminationReason::class,
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function recruitmentProcess(): BelongsTo
    {
        return $this->belongsTo(RecruitmentProcess::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
