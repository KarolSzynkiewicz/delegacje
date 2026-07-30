<?php

namespace App\Models;

use App\Enums\RecruitmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentStatusHistory extends Model
{
    protected $table = 'recruitment_status_history';

    protected $fillable = [
        'recruitment_process_id',
        'from_status',
        'to_status',
        'changed_by',
    ];

    protected $casts = [
        'from_status' => RecruitmentStatus::class,
        'to_status' => RecruitmentStatus::class,
    ];

    public function process(): BelongsTo
    {
        return $this->belongsTo(RecruitmentProcess::class, 'recruitment_process_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
