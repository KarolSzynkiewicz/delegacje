<?php

namespace App\Models;

use App\Enums\RecruitmentReferralSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RecruitmentLead extends Model
{
    protected $fillable = [
        'candidate_id',
        'referral_source',
        'referral_source_detail',
        'cover_letter',
    ];

    protected $casts = [
        'referral_source' => RecruitmentReferralSource::class,
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(RecruitmentCandidate::class, 'candidate_id');
    }

    public function process(): HasOne
    {
        return $this->hasOne(RecruitmentProcess::class, 'lead_id');
    }

    public function getReferralSourceLabelAttribute(): ?string
    {
        $label = $this->referral_source?->label();
        if ($label && $this->referral_source_detail) {
            $label .= ' — ' . $this->referral_source_detail;
        }

        return $label;
    }
}
