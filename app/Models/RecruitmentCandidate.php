<?php

namespace App\Models;

use App\Enums\RecruitmentCandidateFlag;
use App\Enums\RecruitmentShipyardExperience;
use App\Support\PhoneNormalizer;
use App\Traits\HasComments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RecruitmentCandidate extends Model
{
    use HasComments;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'city',
        'has_driving_license_b',
        'speaks_english',
        'speaks_french',
        'speaks_german',
        'photo_path',
        'rating',
        'rating_note',
        'expected_rate_eur',
        'shipyard_experience',
        'available_from',
        'employee_id',
    ];

    protected $casts = [
        'has_driving_license_b' => 'boolean',
        'speaks_english' => 'boolean',
        'speaks_french' => 'boolean',
        'speaks_german' => 'boolean',
        'rating' => RecruitmentCandidateFlag::class,
        'expected_rate_eur' => 'decimal:2',
        'shipyard_experience' => RecruitmentShipyardExperience::class,
        'available_from' => 'date',
    ];

    public function leads(): HasMany
    {
        return $this->hasMany(RecruitmentLead::class, 'candidate_id');
    }

    public function processes(): HasMany
    {
        return $this->hasMany(RecruitmentProcess::class, 'candidate_id');
    }

    public function latestProcess(): HasOne
    {
        return $this->hasOne(RecruitmentProcess::class, 'candidate_id')->latestOfMany();
    }

    /**
     * The employee record this candidate identity is linked to, if any.
     * Source of truth for employment status — see isHired()/isFormerEmployee().
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** Currently employed: linked to an employee who has not been terminated. */
    public function isHired(): bool
    {
        return $this->employee_id !== null && ! $this->employee?->isTerminated();
    }

    /** Former employee: linked to an employee whose employment has ended. */
    public function isFormerEmployee(): bool
    {
        return $this->employee_id !== null && (bool) $this->employee?->isTerminated();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'recruitment_candidate_role', 'recruitment_candidate_id', 'role_id')->withTimestamps();
    }

    public function consents(): HasMany
    {
        return $this->hasMany(RecruitmentConsent::class, 'candidate_id');
    }

    public function allContactAttempts(): HasManyThrough
    {
        return $this->hasManyThrough(
            RecruitmentContactAttempt::class,
            RecruitmentProcess::class,
            'candidate_id',
            'recruitment_process_id',
        );
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return asset('storage/'.$this->photo_path);
    }

    public function setPhoneAttribute(?string $phone): void
    {
        $this->attributes['phone'] = PhoneNormalizer::normalize($phone);
    }

    public function isStarred(): bool
    {
        return $this->rating === RecruitmentCandidateFlag::Wartosciowy;
    }

    public function isBlacklisted(): bool
    {
        return $this->rating === RecruitmentCandidateFlag::CzarnaLista;
    }

    /**
     * Toggle a candidate flag (star / blacklist). Setting the flag that is already active
     * clears it back to neutral. `$note` is stored regardless (mainly meaningful for blacklist).
     */
    public function setFlag(?RecruitmentCandidateFlag $flag, ?string $note = null): void
    {
        $this->update([
            'rating' => $this->rating === $flag ? null : $flag?->value,
            'rating_note' => $this->rating === $flag ? null : $note,
        ]);
    }
}
