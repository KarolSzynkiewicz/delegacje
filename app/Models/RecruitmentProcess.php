<?php

namespace App\Models;

use App\Enums\RecruitmentRejectionReason;
use App\Enums\RecruitmentShipyardExperience;
use App\Enums\RecruitmentStatus;
use App\Traits\HasComments;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecruitmentProcess extends Model
{
    use HasComments;

    protected $fillable = [
        'lead_id',
        'candidate_id',
        'status',
        'assigned_recruiter_id',
        'admin_notes',
        'rejection_reason',
        'rejection_reason_note',
        'employee_id',
    ];

    protected $casts = [
        'status' => RecruitmentStatus::class,
        'rejection_reason' => RecruitmentRejectionReason::class,
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(RecruitmentLead::class, 'lead_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(RecruitmentCandidate::class, 'candidate_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignedRecruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_recruiter_id');
    }

    public function contactAttempts(): HasMany
    {
        return $this->hasMany(RecruitmentContactAttempt::class)->latest();
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(RecruitmentStatusHistory::class)->latest();
    }

    public function assignmentHistory(): HasMany
    {
        return $this->hasMany(RecruitmentAssignmentHistory::class)->latest();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'recruitment_process_id');
    }

    protected static function booted(): void
    {
        static::created(function (self $process) {
            $process->statusHistory()->create([
                'from_status' => null,
                'to_status' => $process->status?->value ?? RecruitmentStatus::Nowy->value,
                'changed_by' => null,
            ]);
        });
    }

    /**
     * Change status and record the transition in recruitment_status_history.
     * The single entry point both the Livewire pipeline table and the plain admin
     * controller route go through, so history is recorded regardless of caller.
     */
    public function transitionTo(
        RecruitmentStatus $status,
        ?int $changedBy = null,
        ?RecruitmentRejectionReason $rejectionReason = null,
        ?string $rejectionReasonNote = null
    ): void {
        if ($this->status === $status) {
            return;
        }

        $fromStatus = $this->status;

        $attributes = ['status' => $status->value];
        if ($status === RecruitmentStatus::Odrzucony) {
            $attributes['rejection_reason'] = $rejectionReason?->value;
            $attributes['rejection_reason_note'] = $rejectionReasonNote;
        }

        $this->update($attributes);

        $this->statusHistory()->create([
            'from_status' => $fromStatus?->value,
            'to_status' => $status->value,
            'changed_by' => $changedBy,
        ]);
    }

    /**
     * Change assigned recruiter and record the transition in recruitment_assignment_history.
     */
    public function assignRecruiter(?int $recruiterId, ?int $changedBy = null): void
    {
        if ($this->assigned_recruiter_id === $recruiterId) {
            return;
        }

        $fromRecruiterId = $this->assigned_recruiter_id;

        $this->update(['assigned_recruiter_id' => $recruiterId]);

        $this->assignmentHistory()->create([
            'from_recruiter_id' => $fromRecruiterId,
            'to_recruiter_id' => $recruiterId,
            'changed_by' => $changedBy,
        ]);
    }

    public function lastContactAttempt(): ?RecruitmentContactAttempt
    {
        return $this->relationLoaded('contactAttempts')
            ? $this->contactAttempts->first()
            : $this->contactAttempts()->first();
    }

    // ── Proxy accessors ─────────────────────────────────────────────
    // The pipeline UI historically read candidate identity fields straight off the
    // recruitment row. Now that identity lives on RecruitmentCandidate and the submission
    // details live on RecruitmentLead, these accessors keep read access ergonomic without
    // having to touch every view reference.

    public function getFirstNameAttribute(): ?string
    {
        return $this->candidate?->first_name;
    }

    public function getLastNameAttribute(): ?string
    {
        return $this->candidate?->last_name;
    }

    public function getFullNameAttribute(): string
    {
        return $this->candidate?->full_name ?? '';
    }

    public function getEmailAttribute(): ?string
    {
        return $this->candidate?->email;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->candidate?->phone;
    }

    public function getCityAttribute(): ?string
    {
        return $this->candidate?->city;
    }

    public function getHasDrivingLicenseBAttribute(): bool
    {
        return (bool) $this->candidate?->has_driving_license_b;
    }

    public function getSpeaksEnglishAttribute(): bool
    {
        return (bool) $this->candidate?->speaks_english;
    }

    public function getSpeaksFrenchAttribute(): bool
    {
        return (bool) $this->candidate?->speaks_french;
    }

    public function getSpeaksGermanAttribute(): bool
    {
        return (bool) $this->candidate?->speaks_german;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->candidate?->photo_url;
    }

    public function getCoverLetterAttribute(): ?string
    {
        return $this->lead?->cover_letter;
    }

    public function getReferralSourceLabelAttribute(): ?string
    {
        return $this->lead?->referral_source_label;
    }

    public function getRolesLabelAttribute(): ?string
    {
        $roles = $this->candidate?->roles;
        if ($roles && $roles->isNotEmpty()) {
            return $roles->pluck('name')->implode(', ');
        }

        return null;
    }

    public function getExpectedRateEurAttribute(): ?string
    {
        return $this->candidate?->expected_rate_eur;
    }

    public function getShipyardExperienceAttribute(): ?RecruitmentShipyardExperience
    {
        return $this->candidate?->shipyard_experience;
    }

    public function getAvailableFromAttribute(): ?\Carbon\Carbon
    {
        return $this->candidate?->available_from;
    }

    /** Read-only proxy returning the candidate's role collection. */
    public function getRolesAttribute(): Collection
    {
        if ($this->relationLoaded('candidate') && $this->candidate?->relationLoaded('roles')) {
            return $this->candidate->roles;
        }

        return $this->candidate?->roles ?? collect();
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? (string) $this->status;
    }

    public function getStatusVariantAttribute(): string
    {
        return $this->status?->variant() ?? 'secondary';
    }
}
