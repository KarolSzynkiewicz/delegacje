<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccommodationLease extends Model
{
    protected $fillable = [
        'accommodation_id',
        'type',
        'start_date',
        'end_date',
        'monthly_rent',
        'currency',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_rent' => 'decimal:2',
    ];

    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }

    public function isActive(): bool
    {
        return is_null($this->end_date) || $this->end_date->gte(now()->startOfDay());
    }

    public function getPeriodLabelAttribute(): string
    {
        $from = $this->start_date?->format('d.m.Y') ?? '—';
        $to = $this->end_date?->format('d.m.Y') ?? 'bezterminowo';

        return "{$from} – {$to}";
    }
}
