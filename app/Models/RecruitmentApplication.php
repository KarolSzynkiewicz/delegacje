<?php

namespace App\Models;

use App\Enums\RecruitmentReferralSource;
use App\Traits\HasComments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentApplication extends Model
{
    use HasComments;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'desired_role',
        'referral_source',
        'cover_letter',
        'photo_path',
        'consent_rodo',
        'consent_recruitment_processing',
        'consent_marketing',
        'consent_given_at',
        'status',
        'admin_notes',
        'employee_id',
    ];

    protected $casts = [
        'status' => 'string',
        'referral_source' => RecruitmentReferralSource::class,
        'consent_rodo' => 'boolean',
        'consent_recruitment_processing' => 'boolean',
        'consent_marketing' => 'boolean',
        'consent_given_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return asset('storage/'.$this->photo_path);
    }

    public function getReferralSourceLabelAttribute(): ?string
    {
        return $this->referral_source?->label();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'    => 'Oczekuje',
            'reviewing'  => 'W trakcie weryfikacji',
            'accepted'   => 'Zaakceptowany',
            'rejected'   => 'Odrzucony',
            'converted'  => 'Zatrudniony',
            default      => $this->status,
        };
    }

    public function getStatusVariantAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'warning',
            'reviewing' => 'info',
            'accepted'  => 'success',
            'rejected'  => 'danger',
            'converted' => 'secondary',
            default     => 'secondary',
        };
    }
}
