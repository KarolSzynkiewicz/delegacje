<?php

namespace App\Models;

use App\Enums\RecruitmentConsentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentConsent extends Model
{
    protected $fillable = [
        'candidate_id',
        'recruitment_lead_id',
        'type',
        'given_at',
        'withdrawn_at',
    ];

    protected $casts = [
        'type' => RecruitmentConsentType::class,
        'given_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(RecruitmentCandidate::class, 'candidate_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(RecruitmentLead::class, 'recruitment_lead_id');
    }

    public function isActive(): bool
    {
        return $this->withdrawn_at === null;
    }

    public function withdraw(): void
    {
        if (! $this->withdrawn_at) {
            $this->update(['withdrawn_at' => now()]);
        }
    }
}
