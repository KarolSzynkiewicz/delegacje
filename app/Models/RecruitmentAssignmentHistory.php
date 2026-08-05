<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentAssignmentHistory extends Model
{
    protected $table = 'recruitment_assignment_history';

    protected $fillable = [
        'recruitment_process_id',
        'from_recruiter_id',
        'to_recruiter_id',
        'changed_by',
    ];

    public function process(): BelongsTo
    {
        return $this->belongsTo(RecruitmentProcess::class, 'recruitment_process_id');
    }

    public function fromRecruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_recruiter_id');
    }

    public function toRecruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_recruiter_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
