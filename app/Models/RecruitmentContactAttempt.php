<?php

namespace App\Models;

use App\Enums\RecruitmentContactOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentContactAttempt extends Model
{
    protected $fillable = [
        'recruitment_process_id',
        'user_id',
        'outcome',
        'comment',
    ];

    protected $casts = [
        'outcome' => RecruitmentContactOutcome::class,
    ];

    public function recruitmentProcess(): BelongsTo
    {
        return $this->belongsTo(RecruitmentProcess::class, 'recruitment_process_id');
    }

    public function process(): BelongsTo
    {
        return $this->recruitmentProcess();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
