<?php

namespace App\Models;

use App\Enums\OfficeContractType;
use App\Enums\OfficePayMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficeProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'contract_type',
        'pay_mode',
        'fte',
        'hourly_rate',
        'currency',
        'started_on',
        'ended_on',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'contract_type' => OfficeContractType::class,
        'pay_mode' => OfficePayMode::class,
        'fte' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'started_on' => 'date',
        'ended_on' => 'date',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workDays(): HasMany
    {
        return $this->hasMany(OfficeWorkDay::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('ended_on')
                    ->orWhereDate('ended_on', '>=', now()->toDateString());
            });
    }

    public function displayName(): string
    {
        return $this->user?->name ?? ('Profil #'.$this->id);
    }
}
